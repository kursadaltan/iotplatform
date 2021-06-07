<?php

namespace Iot\System\Helpers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Exception;


class JWTDecode {
    private $token ;
    private $decode;

    public function __construct(Request $request){
        $authorization = $request->getHeaderLine("Authorization");
        $token = explode(" " , $authorization,2);        
        $bearer = @trim($token[0]);
        $jwt    = @trim($token[1]);       
        if($bearer == "Bearer" && strlen($jwt)> 0){
            try {
                $key = "IoT İle Dünya Sürdürülebilir Bir Şekil Alacak! İnanıyoruz. ";
                JWT::$leeway = 60; // $leeway in seconds
                $decoded = JWT::decode($jwt, $key, array('HS256')); 
                // Access is granted. Add code of the operation here 
                $ar = (array) $decoded;                
                $this->token = $jwt;
                $this->decode = $ar;
            }
            catch (Exception $e){                      
                return false;
            }
        }
        return false;      
    }

    public function getToken(){
        return $this->token;
    }

    public function getDecode(){
        return $this->decode;
    }

    public function getUserDetail(){
        return $this->decode['user'];
    }
}