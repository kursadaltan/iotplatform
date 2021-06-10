<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->setBasePath("/iot-api");

$app->get('/', function (Request $request, Response $response, $args) {
  $response->getBody()->write("Main Response!");
  return $response;
});


$app->group('/v1', function () use ($app) {
  $app->get('/login', function (Request $request, Response $response, $args) {
      $response->getBody()->write("Hello worlds!");
      return $response;
  });
});



try {
    $app->run();     
} catch (Exception $e) {    
  // We display a error message
  die( json_encode(array("status" => "failed", "message" => "This action is not allowed"))); 
}