<?php

declare(strict_types=1);

namespace IoT\Api;



use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Exception;
use DI\Container;
use GuzzleHttp\Psr7\ServerRequest;
use IoT\Api\SessionController;

use \IoT\System\Helpers\JWTDecode;
use PDOException;

Class ModemController 
{
  
    private $container;
    private $superAdmin = false;
    private $database;
    private $session;

    private $pageLimit = 25;
    private $activePage = 1;

    private $order_by = "modemID";
    private $sort_by = "desc";
    private $modemID;
    private $search = null;
    private $pagination = true;
    private $code = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database = $this->container->get("connection");        
        $this->session = new SessionController($container);
    }

    public function getModems(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $check = $this->permissionCheck("modems.index", $response);                
        if(!is_object($check)){           
            $db = $this->database; 
            if(!$this->superAdmin){
                $groupList = $this->session->userGroups($this->session->authUser);
                $db->table('modems')
                    ->where('modemStatus', 1)                    
                    ->in("modem_groupID", $groupList)
                    ->orderBy("$this->order_by $this->sort_by")       
                    ->cache(60);    
                    if(!empty($this->modemID)){
                        $db->where('modemID', $this->modemID);
                    }
                    if(!empty($this->code)){
                        $db->where('modemCode', $this->code);
                    }
                    if(!empty($this->search)){  
                        $db->grouped(function($q){
                            $q->like('modemCode', "%$this->search%");
                            $q->orLike('modemName', "%$this->search%");
                        });
                    }
                    $modems = $db->getAll(); 

                $totalRecord = $db->numRows();              
            }
            else
            {      
                $db->table('modems')
                ->orderBy("$this->order_by $this->sort_by") 
                ->cache(60);    
                if(!empty($this->modemID)){
                    $db->where('modemID', $this->modemID);
                }
                if(!empty($this->code)){
                    $db->where('modemCode', $this->code);
                }
                if(!empty($this->search)){  
                    $db->grouped(function($q){
                        $q->like('modemCode', "%$this->search%");
                        $q->orLike('modemName', "%$this->search%");
                    });
                }            
                $modems = $db->getAll();   
                $totalRecord = $db->numRows();
            }   

            $responseModems = array();
            if($this->pagination){          
                $totalPage = ceil($totalRecord / $this->pageLimit);
                if($this->activePage > $totalPage) $this->activePage = $totalPage;
                
                $responseModems['totalPage'] = $totalPage;
                $responseModems['activePage'] = $this->activePage;
                $responseModems['totalRecord'] = $totalRecord;

                if($this->activePage>1) $responseModems['previousPage'] = $this->previousPage($request);
                if($this->activePage < $totalPage) $responseModems['nextPage'] = $this->nextPage($request);          

                $responseModems['data'] = array();
                $start = ($this->activePage-1) * $this->pageLimit;
                for($i=($start); $i<($start+ $this->pageLimit); $i++){
                    if(isset($modems[$i])){
                        array_push($responseModems['data'], $modems[$i]);
                    }
                }     
            }  
            else
            {
                $responseModems = $modems;
            }           
            
            $return =  $this->session->prepareResponse($responseModems);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Modem can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);         
            }       
        }
        else
        {
            return $check;
        }
       
    }

    public function newModem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {  
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("modems.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterModem($input);     
            if(!$data){
                $return['message'] = 'Modem can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {

                    try
                    {
                        $add = $db->table('modems')->insert($data);
                        $data['modemID'] = $add;
                        $return['message'] = 'OK';
                        $return['data'] = $data;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(201);    
                    }
                    catch(PDOException $ex)
                    {
                        $return['message'] = 'ERROR';
                        $return['data'] = $ex->errorInfo;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(403);   
                    }

            }        
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function updateModem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("modems.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['modem'];
            $id = (int)$id;        
            $yetki = false;

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $id)
            ->getAll();

            if(!$modemDetail){
                $return['message'] = 'Modem can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            if(!$this->superAdmin){
                $modem = $modemDetail[0];
                $groupList = $this->session->userGroups($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($modem->modem_groupID, $groupList)){
                    $yetki = true;
                }      
            }
            else
            {
                $yetki = true;
            }

            if($yetki){
                $body = $request->getBody()->__toString();
                $input = json_decode($body, true);                            
                $data = $this->filterModem($input);     
                if(!$data){
                    $return['message'] = 'Modem can\'t added, please send correct information';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);                    
                }
                else
                {
                    try
                    {

                        $data['modemUpdated_at'] = date('Y-m-d H:i:s');
                        $data['modemCode'] = $data['modemCode'];
                        $update = $db->table('modems')->where('modemID', $id)->update($data);                       
                        $return['message'] = 'OK';
                        $return['data'] = $data;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);    
                    }
                    catch(PDOException $ex)
                    {
                        $return['message'] = 'ERROR';
                        $return['data'] = $ex->errorInfo;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(403);   
                    }
                } 
            }                 
        }
        if(is_object($check) || !$yetki){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function deleteModem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("modems.delete", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['modem'];
            $id = (int)$id;        
            $yetki = false;

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $id)
            ->getAll();

            if(!$modemDetail){
                $return['message'] = 'Modem can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }
            if(!$this->superAdmin){
                $modem = $modemDetail[0];
                $groupList = $this->session->userGroups($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($modem->modem_groupID, $groupList)){
                    $yetki = true;
                }      
            }
            else
            {
                $yetki = true;
            }

            if($yetki){ 
                try
                {
                    $data['modemDeleted_at'] = date('Y-m-d H:i:s');
                    $data['modemStatus'] = 0;
                    $oldCode = $modemDetail[0]->modemCode;
                    $oldCode = str_replace("DEL-", "", $oldCode);
                    $data['modemCode']       = "DEL-".$oldCode;
                    $update = $db->table('modems')->where('modemID', $id)->update($data);                       
                    $return['message'] = 'OK';
                    $return['data'] = "DELETED";
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
                
            }                 
        }
        if(is_object($check) || !$yetki){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    private function pagination($args){      
       $activePage = 1;
        if(isset($args['page'])){
            $activePage = (int)$args['page'];
        }          
        if($activePage <= 1) $activePage = 1;     
        $this->activePage = $activePage;   
        return $activePage;
    }
    
    private function previousPage($request){
        $active = $this->activePage;
        $previous = $active - 1;
        $uri = $request->getUri();        
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $path = $uri->getPath();
        $explode = explode('/', $path);
        $last = count($explode)-1;
        $explode[$last] = str_replace("$active", "$previous", $explode[$last]);
        $newPath = implode("/", $explode);
        return "$scheme://$host$newPath";
    }
    private function nextPage($request){
        $active = $this->activePage;
        $previous = $active + 1;
        $uri = $request->getUri();        
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $path = $uri->getPath();
        $explode = explode('/', $path);
        $last = count($explode)-1;
        $explode[$last] = str_replace("$active", "$previous", $explode[$last]);
        $explode[$last] = str_replace("0", "$previous", $explode[$last]);
        $newPath = implode("/", $explode);
        return "$scheme://$host$newPath";
    }

    private function filterModem($input){
        $resp = array();
        if(isset($input['modemCode'])) 
            $resp['modemCode'] = strip_tags(trim($input['modemCode']));
        
        if(isset($input['modemName'])) 
            $resp['modemName'] = strip_tags(trim($input['modemName']));
        
        if(isset($input['modemStatus'])) 
            $resp['modemStatus'] = (int)$input['modemStatus'];

        if(isset($input['modem_groupID'])) 
            $resp['modem_groupID'] = (int)$input['modem_groupID'];
        
        if(isset($input['modemLat'])) 
            $resp['modemLat'] = strip_tags(trim($input['modemLat']));

        if(isset($input['modemLong'])) 
            $resp['modemLong'] = strip_tags(trim($input['modemLong']));        

        if(count($resp)){
            return $resp;
        }
        return false;
       
    }

    private function checkParams($params, $args){
        if(isset($params['order_by'])){
            $this->order_by = strip_tags(trim($params['order_by']));
        }

        if(isset($params['sort_by'])){
            if($params['sort_by'] == 'asc'){
                $this->sort_by = 'asc';
            }
            else
            {
                $this->sort_by = 'desc';
            }            
        }

        if(isset($params['pagination'])){
            if($params['pagination'] == "false"){
                $this->pagination = false;
            }
            else
            {
                $this->pagination = true;
                $this->activePage = $this->pagination($args);
            }            
        }

        if(isset($params['id'])){
            $this->modemID = strip_tags(trim($params['id']));
        }
        if(isset($params['search'])){
            $this->search = strip_tags(trim($params['search']));
        }
        if(isset($params['code'])){
            $this->code = strip_tags(trim($params['code']));
        }

        return true;
    }

    public function permissionCheck($needle = "all.permissions", $response){
        $permissions = json_decode($this->session->authUser->permission,true);        
        
        foreach($permissions as $permission){
           if($permission['Name'] == "all.permissions") {
               $this->superAdmin = true;
               return true;
           }
           if($permission['Name']  == $needle){
               return true;
           }
        }

        $return['response'] = "You are not authorized to view this content.";
        $response->getBody()->write($this->session->prepareResponse($return));
        return $response->withHeader('Content-Type', 'application/json')
        ->withStatus(401);        
    }
    

    


}