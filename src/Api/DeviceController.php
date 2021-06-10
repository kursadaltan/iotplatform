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

Class DeviceController 
{
  
    private $container;
    private $superAdmin = false;
    private $database;
    private $session;

    private $pageLimit = 25;
    private $activePage = 1;

    private $order_by = "deviceID";
    private $sort_by = "desc";
    private $modemID;
    private $deviceID;
    private $productID;
    private $search = null;
    private $pagination = true;
    private $code = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database = $this->container->get("connection");        
        $this->session = new SessionController($container);
    }

    public function getDevices(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $check = $this->permissionCheck("devices.index", $response);                
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('devices')           
            ->orderBy("$this->order_by $this->sort_by")       
            ->cache(60);  
            if(!empty($this->deviceID)){
                $db->where('deviceID', $this->deviceID);
            }  
            if(!empty($this->modemID)){
                $db->where('device_modemID', $this->modemID);
            }
            if(!empty($this->code)){
                $db->where('deviceCode', $this->code);
            }
            if(!empty($this->productID)){
                $db->where('device_productID', $this->productID);
            }
            if(!empty($this->search)){  
                $db->grouped(function($q){
                    $q->like('deviceCode', "%$this->search%");
                    $q->orLike('deviceName', "%$this->search%");
                });
            }
            if(!$this->superAdmin){
                $modemList = $this->userModems($this->session->authUser);
                $db->where('deviceStatus', 1)                    
                ->in("device_modemID", $modemList);         
            }
           
            $devices = $db->getAll();                 
            $totalRecord = $db->numRows();    
            $responseDevices = array();

            if($this->pagination){          
                $totalPage = ceil($totalRecord / $this->pageLimit);
                if($this->activePage > $totalPage) $this->activePage = $totalPage;
                
                $responseDevices['totalPage'] = $totalPage;
                $responseDevices['activePage'] = $this->activePage;
                $responseDevices['totalRecord'] = $totalRecord;

                if($this->activePage>1) $responseDevices['previousPage'] = $this->previousPage($request);
                if($this->activePage < $totalPage) $responseDevices['nextPage'] = $this->nextPage($request);          

                $responseDevices['data'] = array();
                $start = ($this->activePage-1) * $this->pageLimit;
                for($i=($start); $i<($start+ $this->pageLimit); $i++){
                    if(isset($devices[$i])){
                        array_push($responseDevices['data'], $devices[$i]);
                    }
                }     
            }  
            else
            {
                $responseDevices = $devices;
            }       
            $return =  $this->session->prepareResponse($responseDevices);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Device can\'t found';   
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

    public function getDeviceTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $check = $this->permissionCheck("devices.index", $response);
        $id = $args['device'];
        $id = (int)$id;        
        $yetki = false;
        
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('device_table')           
            ->orderBy("tableID desc")       
            ->cache(20)
            ->where('table_deviceID', $id);
            
            
            if(!$this->superAdmin){
                $modemList = $this->userModems($this->session->authUser);                          
                $db->in("table_modemID", $modemList);         
            }
           
            $devices = $db->getAll(); 
            $responseDevices = array();
            $responseDevices = $devices;
                 
            $return =  $this->session->prepareResponse($responseDevices);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Device can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);         
            }       
        }
        else
        {
            return $check;
        }       
    }

    public function newDevice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterDevice($input);     
            if(!$data){
                $return['message'] = 'Device can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                    try
                    {
                        $add = $db->table('devices')->insert($data);
                        $data['deviceID'] = $add;
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
    public function updateDevice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['device'];
            $id = (int)$id;        
            $yetki = false;

            $deviceDetail = $db->select('deviceID, deviceCode, device_modemID')
            ->table('devices')
            ->where('deviceID', $id)
            ->getAll();

            if(!$deviceDetail){
                $return['message'] = 'Device can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $deviceDetail[0]->device_modemID)
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
                $data = $this->filterDevice($input);     
                if(!$data){
                    $return['message'] = 'Device can\'t updated, please send correct information';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);                    
                }
                else
                {
                    try
                    {
                        $data['deviceUpdated_at'] = date('Y-m-d H:i:s');  
                        $data['deviceID'] = $id;        
                        if($data['deviceStatus'] == 1) $data['deviceDeleted_at'] = NULL;                                      
                        $db->table('devices')->where('deviceID', $id)->update($data);                       
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
    public function deleteDevice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.delete", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['device'];
            $id = (int)$id;        
            $yetki = false;

            $deviceDetail = $db->select('deviceID, deviceCode, device_modemID')
            ->table('devices')
            ->where('deviceID', $id)
            ->getAll();

            if(!$deviceDetail){
                $return['message'] = 'Device can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $deviceDetail[0]->device_modemID)
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
                    $data['deviceUpdated_at'] = date('Y-m-d H:i:s');  
                    $data['deviceDeleted_at'] = date('Y-m-d H:i:s');
                    $data['deviceStatus'] = 0;
                    $data['deviceID'] = $id;                      
                    $update = $db->table('devices')->where('deviceID', $id)->update($data);                       
                    $return['message'] = 'OK';                                    
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

    public function newDeviceTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterDeviceTable($input);     
            
            if(!$data){
                $return['message'] = 'Device Table can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                    try
                    {
                        $add = $db->table('device_table')->insert($data);
                        $data['tableID'] = $add;
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

    public function updateDeviceTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['table'];
            $id = (int)$id;        
            $yetki = false;

            $tableDetail = $db->select('tableID, tableCode, table_deviceID, table_modemID')
            ->table('device_table')
            ->where('tableID', $id)
            ->getAll();

            if(!$tableDetail){
                $return['message'] = 'Table can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $deviceDetail = $db->select('deviceID, deviceCode, device_modemID')
            ->table('devices')
            ->where('deviceID', $tableDetail[0]->table_deviceID)
            ->getAll();

            if(!$deviceDetail){
                $return['message'] = 'Device can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $deviceDetail[0]->device_modemID)
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
                $data = $this->filterDeviceTable($input);     
                if(!$data){
                    $return['message'] = 'Table can\'t updated, please send correct information';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);                    
                }
                else
                {
                    try
                    {
                        $data['tableID'] = $id;       
                        $db->table('device_table')->where('tableID', $id)->update($data);                       
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

    public function deleteDeviceTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("devices.delete", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['table'];
            $id = (int)$id;        
            $yetki = false;

            $tableDetail = $db->select('tableID, tableCode, table_deviceID, table_modemID')
            ->table('device_table')
            ->where('tableID', $id)
            ->getAll();

            if(!$tableDetail){
                $return['message'] = 'Table can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $deviceDetail = $db->select('deviceID, deviceCode, device_modemID')
            ->table('devices')
            ->where('deviceID', $tableDetail[0]->table_deviceID)
            ->getAll();

            if(!$deviceDetail){
                $return['message'] = 'Device can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $modemDetail = $db->select('modemID, modemCode, modem_groupID')
            ->table('modems')
            ->where('modemID', $deviceDetail[0]->device_modemID)
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
                    $db->table('device_table')->where('tableID', $id)->delete();                       
                    $return['message'] = 'OK';                                          
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
        $params = $request->getUri()->getQuery();
        if(strlen($params) > 0){
            $params = "?".$params;
        }
        else
        {
            $params = "";
        }
        return "$scheme://$host$newPath".$params;
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
        $params = $request->getUri()->getQuery();
        if(strlen($params) > 0){
            $params = "?".$params;
        }
        else
        {
            $params = "";
        }
        return "$scheme://$host$newPath".$params;
    }

    private function filterDevice($input){
        $resp = array();
        if(isset($input['deviceCode'])) 
            $resp['deviceCode'] = strip_tags(trim($input['deviceCode']));        
        
        if(isset($input['device_modemID'])) 
            $resp['device_modemID'] = (int)$input['device_modemID'];

        if(isset($input['deviceName'])) 
            $resp['deviceName'] = strip_tags(trim($input['deviceName'])); 
        
        if(isset($input['deviceDescription'])) 
            $resp['deviceDescription'] = strip_tags(trim($input['deviceDescription']));

        if(isset($input['deviceBrand'])) 
            $resp['deviceBrand'] = strip_tags(trim($input['deviceBrand']));        

        if(isset($input['device_groupID'])) 
            $resp['device_groupID'] = (int)$input['device_groupID'];

        if(isset($input['deviceStatus'])) 
            $resp['deviceStatus'] = (int)$input['deviceStatus'];

        if(isset($input['deviceTags'])) 
            $resp['deviceTags'] = strip_tags(trim($input['deviceTags'])); 

        if(isset($input['device_productID'])) 
            $resp['device_productID'] = (int)$input['device_productID'];   

        if(count($resp)){
            return $resp;
        }
        return false;       
    }

    private function filterDeviceTable($input){
        $resp = array();
        if(isset($input['tableName'])) 
            $resp['tableName'] = strip_tags(trim($input['tableName']));        
        
        if(isset($input['tableCode'])) 
            $resp['tableCode'] = strip_tags(trim($input['tableCode']));   

        if(isset($input['table_label'])) 
            $resp['table_label'] = strip_tags(trim($input['table_label'])); 
        
        if(isset($input['table_labelID'])) 
            $resp['table_labelID'] = (int)($input['table_labelID']);

        if(isset($input['tableType'])) 
            $resp['tableType'] = (int)$input['tableType'];

        if(isset($input['tableProtocol'])) 
            $resp['tableProtocol'] = strip_tags(trim($input['tableProtocol'])); 

        if(isset($input['tableSubProtocol'])) 
            $resp['tableSubProtocol'] = strip_tags(trim($input['tableSubProtocol']));

        if(isset($input['tableAddress'])) 
            $resp['tableAddress'] = strip_tags(trim($input['tableAddress']));
        
        if(isset($input['tableSubAddress'])) 
            $resp['tableSubAddress'] = strip_tags(trim($input['tableSubAddress']));

        if(isset($input['tableDataType'])) 
            $resp['tableDataType'] = strip_tags(trim($input['tableDataType']));

        if(isset($input['table_isFunction'])) 
            $resp['table_isFunction'] =  (int)$input['table_isFunction'];

        if(isset($input['tableFactor'])) 
            $resp['tableFactor'] =  (int)$input['tableFactor'];

        if(isset($input['tableFactorSymbol'])) 
            $resp['tableFactorSymbol'] = strip_tags(trim($input['tableFactorSymbol']));

        if(isset($input['tableFunction'])) 
            $resp['tableFunction'] = strip_tags(trim($input['tableFunction']));

        if(isset($input['tableFunctionText'])) 
            $resp['tableFunctionText'] = strip_tags(trim($input['tableFunctionText']));

        if(isset($input['table_isIndex'])) 
            $resp['table_isIndex'] =  (int)$input['table_isIndex'];

        if(isset($input['tableUnit'])) 
            $resp['tableUnit'] = strip_tags(trim($input['tableUnit']));

        if(isset($input['tableMinValue'])) 
            $resp['tableMinValue'] = (int)$input['tableMinValue'];
        
        if(isset($input['tableMaxValue'])) 
            $resp['tableMaxValue'] = (int)$input['tableMaxValue'];

        if(isset($input['table_deviceID'])) 
            $resp['table_deviceID'] = (int)$input['table_deviceID'];

        if(isset($input['tablePeriod'])) 
            $resp['tablePeriod'] = (int)$input['tablePeriod'];

        if(isset($input['tableDeleteMonth'])) 
            $resp['tableDeleteMonth'] = (int)$input['tableDeleteMonth'];

        if(isset($input['table_modemID'])) 
            $resp['table_modemID'] = (int)$input['table_modemID'];

        if(isset($input['tableNotes'])) 
            $resp['tableNotes'] = strip_tags(trim($input['tableNotes']));

        if(count($resp)){
            return $resp;
        }
        return false;       
    }

    private function checkParams($params, $args){
        $this->activePage = $this->pagination($args);
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
            }            
        }

        if(isset($params['id'])){
            $this->deviceID = strip_tags(trim($params['id']));
        }
        if(isset($params['modem'])){
            $this->modemID = strip_tags(trim($params['modem']));
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
    
    public function userModems($auth){
        $groups = json_decode($this->authUser->groups, true); 
        $groupArray = array();
        foreach($groups as $group){  
            $id = (int)$group;      
            if(is_array($group)){
                foreach($group as $g){              
                    $id = (int)$g;
                    array_push($groupArray, $id);
                 } 
            }
            else
            {
                array_push($groupArray, $id);
            }
                     
        }
        $groupArray = array_unique($groupArray, SORT_NUMERIC);
       
        $modems = $this->database->select('modemID')
        ->table('modems')
        ->IN('modem_groupID', $groupArray)
        ->getAll();

        $responseModem = array();
        foreach($modems as $modem){
            array_push($responseModem, $modem->modemID);
        }

        return $responseModem;
    }

    


}