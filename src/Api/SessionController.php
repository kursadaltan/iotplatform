<?php

declare(strict_types=1);

namespace IoT\Api;

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Firebase\JWT\JWT;
use \IoT\System\Helpers\JWTDecode;

Class SessionController 
{

    private $container;
    public $authUser;
    private $database;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database = $this->container->get("connection");  
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $db = $this->container->get("connection");        
        $req = $request->getBody()->__toString();        
        $args = json_decode($req, true);
        $return = array();
        $continue = true;
        if(empty($args['username'])){
            $return['message'] = 'Please send User Name'; 
            $return['status'] = 'ERROR';
            $continue = false;
        }
        if(empty($args['userpassword'])){
            $return['message'] = 'Please send user detail';
            $return['status'] = 'ERROR';
            $continue = false;
        }
        if($continue){
            $username = strip_tags($args['username']);
            $password = strip_tags($args['userpassword']);      
          
            $checkUser = $db->table('users')->where('userEmail', $username)           
            ->where('userStatus', 1)
            ->getAll();

            if(sizeof($checkUser)){   
                $checkUser = $checkUser[0];             
                if(password_verify($password, $checkUser->userPassword)){
                    $permissionArray = array();
                    $permission = $this->getPermission($checkUser->userID);
                    $role = $this->getRole($checkUser->user_roleID);
                    $groupArray = array();
                    $groups = $this->getGroups($checkUser->userID);
                    foreach($permission as $row){
                        $rt = array();
                        $rt['ID'] = $row->permission_id;
                        $rt['Name'] =$row->permissionName;
                        array_push($permissionArray, $rt);
                    }
                    foreach($groups as $row){
                        $rt = array($row->group_id);                        
                        array_push($groupArray, $rt);
                    }

                    $key = "IoT İle Dünya Sürdürülebilir Bir Şekil Alacak! İnanıyoruz. ";
                    $begin = time();
                    $expTime = $begin + 21600 ;
                    $payload = array(
                        "iss" => $this->container->get("settings")['host'],
                        "aud" => $this->container->get("settings")['host'],
                        "iat" => $begin,
                        "exp" => $expTime,
                        "user" => array(
                            "id" => $checkUser->userID,
                            "firstname" => $checkUser->userName,
                            "lastname"  => $checkUser->userSurname,
                            "email"     => $checkUser->userEmail,
                            "permission" => json_encode($permissionArray),
                            "groups" => json_encode($groupArray),
                            "role" => $role->roleName
                        )
                    );                             
                    $jwt = JWT::encode($payload, $key);
                    $return = array();
                    $return['status'] = 'OK';
                    $return['token'] = $jwt;
                    $return['beginning'] = $begin;
                    $return['expires_end'] = $expTime;
                    $return['userID'] = $checkUser->userID;                    
                }               
                $continue = false;
            }
            else
            {
                $continue = false;
                $return['status'] = 'ERROR';
                $return['message'] = 'Incorrect Username or Userpassword';
            }
        }
       
        echo $this->prepareResponse($return);          
        return $response;
    }

   

    public function groupsUsers($authUser){        
        $groups = json_decode($this->authUser->groups, true); 
        $userArray = array();
        foreach($groups as $group){
            $id = (int)$group;
            $users = $this->database->table("group_has_user")->where('group_id', $id)->getAll();
            if(is_array($users)){
                foreach($users as $user){
                    array_push($userArray, $user->user_id);
                }
            }
        }
        $userArray = array_unique($userArray, SORT_NUMERIC);
        return $userArray;        
    }

    public function userGroups($authUser){        
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
       
        return $groupArray;        
    }

    public function jwtUser(ServerRequestInterface $request){
        $jwt = new JWTDecode($request);        
        $getUser = $jwt->getUserDetail();
        return $getUser;
    }


    public function prepareResponse($object){
        $return = json_encode($object);
        return $return;
        /*
        if(is_object($object)){
            if(sizeof($object) > 0){
                $return = json_encode($object);
                return $return;
            }
            return false;            
        }
        else if(is_array($object)){
            if(sizeof($object) > 0){
                $return = json_encode($object);
                return $return;
            }
            return false;  
        }
        else
        {
            return false;
        }
        */
    }

    public function getPermission($userID){      
        $db = $this->database;
        $permission = $db->table('permission as per')                   
        ->innerJoin("role_has_permission as rhp", "per.permissionID", "rhp.permission_id")
        ->where('rhp.role_id', $userID)
        ->getAll();
        return $permission;
    }

    public function getGroups($userID){      
        $db = $this->database;
        $groups = $db->table('groups as gr')                   
        ->innerJoin("group_has_user as ghu", "gr.groupID", "ghu.group_id")
        ->where('ghu.user_id', $userID)
        ->getAll();
        return $groups;
    }

    public function getRole($roleID){
        $db = $this->database;
        $role = $db->table('user_roles')->where('roleID', $roleID)->getAll();
        if(is_array($role)){
            $role = $role[0];
        }
        return $role;        
    }  

}