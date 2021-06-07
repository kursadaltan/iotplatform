<?php

namespace IoT\System\Middleware ;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Exception;
use DI\Container;


class AuthMiddleware
{
    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $error = false;
        $contentType = $request->getHeaderLine('Content-Type');
        if (!$request->hasHeader('Authorization')) {
           $error = true;
        }

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
                $contents = $request->getBody();               
                //$request = $request->getParsedBody($contents);
            }
            catch (Exception $e){                      
                $error = true;
                $return['token'] = $e->getMessage();
            }
        }
        else
        {
            $error = true;
        }
       
        if($error)
        {
            $response = new Response();
            $return['status'] = 'ERROR';
            $return['message'] = 'Token is not validate';
            $returnText = json_encode($return);
            $response->getBody()->write($returnText);
            return $response->withStatus(401);
        }

        return $handler->handle($request);
    }

}