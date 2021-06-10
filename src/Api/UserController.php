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

use \IoT\System\Helpers\JWTDecode;
use IoT\Api\SessionController;

Class UserController 
{
  
    private $container;
    private $superAdmin = false;
    private $database;
    private $session ;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database = $this->container->get("connection");  
        $this->session = new SessionController($container);
    }

    public function getUsers(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.index", $response);        
       if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->groupsUsers($this->session->authUser);
                $responseUsers = $db->select('userID, userName, userSurname, userEmail, userPhone, userStatus, user_roleID, user_parentID')
                ->table('users')->where('userStatus', 1)->in("userID", $userList)->getAll(); 
            }
            else
            {
                $responseUsers = $db->select('userID, userName, userSurname, userEmail, userPhone, userStatus, user_roleID, user_parentID')
                ->table('users')->getAll();   
            }  
                                      
            $return =  $this->session->prepareResponse($responseUsers);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'User cant found';   
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

    public function getUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.show", $response);
        $id = $args['user'];
        $id = (int)$id;
        
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->groupsUsers($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($id, $userList)){
                    $responseUsers = $db->table('users')->where('userStatus', 1)->where("userID", $id)->getAll(); 
                }      
            }
            else
            {
                $responseUsers = $db->table('users')->where('userStatus', 1)->where("userID", $id)->getAll();   
            }           
              
            $return =  $this->session->prepareResponse($responseUsers);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'User cant found';   
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

    public function deleteUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.delete", $response);
        $id = $args['user'];
        $id = (int)$id;        
        $yetki = false;
        
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->groupsUsers($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($id, $userList)){
                    $yetki = true;
                }      
            }
            else
            {
                $yetki = true;
            }     
            
            if($id == $this->session->authUser->id){
                $yetki = false;
            }
           
              
            if($yetki){
                $data = [
                    'user_delete_at' => date('Y-m-d H:i:s'),
                    'userStatus' => 0,
                    'userNotes' => "User deleted from ".$this->session->authUser->firstname."(".$this->session->authUser->id.")"                    
                ];                
                $deleted = $db->table('users')->where('userID', $id)->update($data); 
                     
                if($deleted != 0){
                    $return['message'] = 'OK';
                    echo $this->session->prepareResponse($return);
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
                }
                else
                {
                    $return['message'] = 'User cant found';
                    $response->getBody()->write($this->session->prepareResponse($return));                  
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(422);
                    die();
                }               
            }
        }        
        if(!$yetki || is_object($check)){
                $return['message'] = 'You are not authorized to update this content. ';
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }              
    }

    public function updateUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->superAdmin = false;
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.update", $response);
        $id = $args['user'];
        $id = (int)$id;        
        $yetki = false;
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->groupsUsers($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($id, $userList)){
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
                               
                $data = $this->filterUser($input);               
                if(!$data){
                    $return['message'] = 'User cant update, please send correct information';
                    echo $this->session->prepareResponse($return);
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);                    
                }
                else
                {
                    $updated = $db->table('users')->where('userID', $id)->update($data);
                    if($updated){
                        $return['message'] = 'OK';
                        echo $this->session->prepareResponse($return);
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(201);
                    }
                    else
                    {
                        $return['message'] = 'User cant update';
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(422);
                    }
                }
            }
        }        
        if(!$yetki || is_object($check)){
                $return['message'] = 'You are not authorized to update this content. ';
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }              
    }

    public function newUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterUser($input);               
            if(!$data){
                $return['message'] = 'User cant added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
               $userEmailCheck = $db->select("userEmail")->table("users")->where('userEmail', $data['userEmail'])->getAll();
               if($userEmailCheck){
                    $return['message'] = 'This Email Address is already registered.';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(422); 
                    die();
               }

               if(isset($data['userStatus'])) unset($data['userStatus']);
               if(isset($data['password_confirm'])) unset($data['password_confirm']);

               $add = $db->table('users')->insert($data);
               if($add){
                    $return['message'] = 'OK';      
                    unset($data['userPassword']); 
                    $return['data'] = $data;             
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(201); 
                    die();
               }
               else
               {
                    $return['message'] = 'User Can\'t added.';                    
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(409); 
                    die();
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

    public function listGroups(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->superAdmin = false;
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.index", $response);     
        if(!is_object($check)){
            $db = $this->database;          
            $groups = array();
            if(!$this->superAdmin){                 
                $groupList = $this->session->userGroups($this->session->authUser);              
                $groups = $db->table('groups')->IN("groupID", $groupList)->getAll();  
            }
            else
            {      
                $groups = $db->table('groups')->getAll();  
            }
            
            $return =  $this->session->prepareResponse($groups);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'There are no groups to list.';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);         
            }       
        }
        else
        {
            return $check;
        }       
    }

    public function addGroups(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterGroup($input);               
            if(!$data){
                $return['message'] = 'Group cant add, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
               $userEmailCheck = $db->select("groupName, groupID")->table("groups")->where('groupName', $data['groupName'])->getAll();
               if($userEmailCheck){
                    $return['message'] = 'This Group Name is already registered.';
                    $return['group'] = $userEmailCheck;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(422); 
                    die();
               }

               if(!isset($data['groupOwnerID'])) $data['groupOwnerID'] = 0;
               $data['groupCreated_at'] = date('Y-m-d H:i:s');
               $data['groupStatus'] = 1;               

               $add = $db->table('groups')->insert($data);
               if($add){
                    $return['message'] = 'OK'; 
                    $return['data'] = $data;             
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(201); 
                    die();
               }
               else
               {
                    $return['message'] = 'Group Can\'t added.';                    
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(409); 
                    die();
               }
               
            }            
        }        
        if(is_object($check)){
                $return['message'] = 'You are not authorized to update this content. ';
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }              
    }

    public function getGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.show", $response);
        $id = @$args['group'];
        $id = (int)$id;
        
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->userGroups($this->session->authUser);                
                $responseGroups = array(); 
                if(in_array($id, $userList)){
                    $responseGroups = $db->table('groups')->where('groupStatus', 1)->where("groupID", $id)->getAll(); 
                }      
            }
            else
            {
                $responseGroups = $db->table('groups')->where("groupID", $id)->getAll();   
            }           
              
            $return =  $this->session->prepareResponse($responseGroups);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Group cant found';   
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

    public function updateGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->superAdmin = false;
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.update", $response);
        $id = @$args['group'];
        $id = (int)$id;        
        $yetki = false;
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->userGroups($this->session->authUser);                
                $responseGroups = array(); 
                if(in_array($id, $userList)){
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
                $data = $this->filterGroup($input);                            
                if(!$data){
                    $return['message'] = 'Group cant update, please send correct information';
                    echo $this->session->prepareResponse($return);
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);                    
                }
                else
                {                  
                    $data['groupUpdated_at'] = date('Y-m-d H:i:s'); 
                    $updated = $db->table('groups')->where('groupID', $id)->update($data);                   
                    if($updated){
                        $return['message'] = 'OK';
                        echo $this->session->prepareResponse($return);
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
                    }
                    else
                    {
                        $return['message'] = 'Group cant update';
                        $return['data'] = $data;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(422);
                    }
                }
            }
        }        
        if(!$yetki || is_object($check)){
                $return['message'] = 'You are not authorized to update this content. ';
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }              
    }

    public function deleteGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->superAdmin = false;
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.update", $response);
        $id = @$args['group'];
        $id = (int)$id;        
        $yetki = false;
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->userGroups($this->session->authUser);                
                $responseUsers = array(); 
                if(in_array($id, $userList)){
                    $yetki = true;
                }      
            }
            else
            {
                $yetki = true;
            }                   
            if($yetki){ 
                $data = array(
                    'groupDeleted_at' => date('Y-m-d H:i:s'),
                    'groupStatus' => 0
                );               
              
                $deleted = $db->table('groups')->where('groupID', $id)->update($data);
                if($deleted){
                    $return['message'] = 'OK';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
                }
                else
                {
                    $return['message'] = 'User cant deleted';
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(422);
                }                
            }
        }        
        if(!$yetki || is_object($check)){
                $return['message'] = 'You are not authorized to update this content. ';
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }              
    }

    public function getGroupUsers(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("users.index", $response);
        $id = @$args['group'];
        $id = (int)$id;
        $yetki = false;
        $responseData = array();
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->userGroups($this->session->authUser);                
                $responseGroups = array(); 
                if(in_array($id, $userList)){ 
                    $yetki = true;
                 }      
            }
            else
            {
                $yetki = true; 
            }           
              
            if($yetki){
                $groupUsers = $db->table('group_has_user')->where('group_id', $id)->getAll();
                $userArray = array();
                foreach($groupUsers as $users){                   
                    array_push($userArray, $users->user_id);
                }
                if(count($userArray) > 0){
                    $responseData = $db->select('userID, userEmail, userName, userSurname, userPhone')
                    ->table('users')->IN("userID", $userArray)->where('userStatus', 1)->getAll(); 
                }
            }

            $return =  $this->session->prepareResponse($responseData);
            if($return){
                $response->getBody()->write($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Group don\'t have any users.';   
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

    public function getUserGroups(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("groups.index", $response);
        $id = @$args['user'];
        $id = (int)$id;
        $yetki = false;
        $responseData = array();
        if(!is_object($check)){
            $db = $this->database;
            if(!$this->superAdmin){
                $userList = $this->session->groupsUsers($this->session->authUser);                
                $responseGroups = array(); 
                if(in_array($id, $userList)){ 
                    $yetki = true;
                 }      
            }
            else
            {
                $yetki = true; 
            }           
              
            if($yetki){
                $userArray = $db->table('group_has_user')->where('user_id', $id)->getAll();
                $groupArray = array();
                
                foreach($userArray as $group){                   
                    array_push($groupArray, $group->group_id);
                }              
                if(count($groupArray) > 0){                
                    $responseData = $db->select('groupID, groupName, groupDescription')
                    ->table('groups')->IN("groupID", $groupArray)->where('groupStatus', 1)->getAll(); 
                }
            }
            $return =  $this->session->prepareResponse($responseData);
            if($return){
                $response->getBody()->write($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'User don\'t have any groups.';   
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

    private function filterUser($input){
        $resp = array();
        if(isset($input['userName'])) 
            $resp['userName'] = strip_tags(trim($input['userName']));
        
        if(isset($input['userSurname'])) 
            $resp['userSurname'] = strip_tags(trim($input['userSurname']));
            
        if(isset($input['userEmail'])) 
            $resp['userEmail'] = strip_tags(trim($input['userEmail']));

        if(isset($input['userPassword'])) 
            $resp['userPassword'] = password_hash($input['userPassword'], PASSWORD_DEFAULT);

        if(isset($input['userPhone'])) 
            $resp['userPhone'] = strip_tags(trim($input['userPhone']));
        
        if(isset($input['userStatus'])) 
            $resp['userStatus'] = (int)$input['userStatus'];

        if(isset($input['password_confirmation'])){
            $resp['password_confirm'] = password_hash($input['userPassword'], PASSWORD_DEFAULT);
        }

        

        if(count($resp)){
            return $resp;
        }
        return false;
       
    }

    private function filterGroup($input){
        $resp = array();
        if(isset($input['groupName'])) 
            $resp['groupName'] = strip_tags(trim($input['groupName']));
        
        if(isset($input['groupDescription'])) 
            $resp['groupDescription'] = strip_tags(trim($input['groupDescription']));
            
        if(isset($input['groupOwnerID'])) 
            $resp['groupOwnerID'] = strip_tags(trim($input['groupOwnerID']));
        
            if(isset($input['groupNote'])) 
            $resp['groupNote'] = strip_tags(trim($input['groupNote']));    
        if(count($resp)){
            return $resp;
        }
        return false;       
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