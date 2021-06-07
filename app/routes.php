<?php
declare(strict_types=1);


use Slim\App;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use DI\Container;
use IoT\Api\DeviceController;
use IoT\Api\ModemController;
use IoT\Api\SessionController;
use IoT\Api\UserController;
use IoT\System\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;


return function(App $app){
    
    $app->get('/', function (RequestInterface $request, ResponseInterface $response, $args) {
        $response->getBody()->write("Main Response!");
        return $response;
      });
    
    $app->post("/api/v1/login", SessionController::class.':login');
    $app->group('/api/v1', function (RouteCollectorProxy $group) {
        $group->get('/users',   UserController::class.':getUsers');
        $group->get('/users/{user}',   UserController::class.':getUser');
        $group->delete('/users/{user}',   UserController::class.':deleteUser');
        $group->put('/users/{user}',   UserController::class.':updateUser');
        $group->post('/users/new',   UserController::class.':newUser');
        $group->get('/users/{user}/groups', UserController::class.':getUserGroups');
        $group->get('/groups', UserController::class.':listGroups');
        $group->post('/groups/new', UserController::class.':addGroups');
        $group->get('/groups/{group}',   UserController::class.':getGroup');
        $group->put('/groups/{group}',   UserController::class.':updateGroup');
        $group->delete('/groups/{group}',   UserController::class.':deleteGroup');
        $group->get('/groups/{group}/users', UserController::class.':getGroupUsers');

        $group->get('/modems', ModemController::class.':getModems');
        $group->get('/modems/{page}', ModemController::class.':getModems');
        $group->post('/modems/new', ModemController::class.':newModem');
        $group->put('/modem/{modem}', ModemController::class.':updateModem');
        $group->delete('/modem/{modem}', ModemController::class.':deleteModem');

        $group->get('/devices', DeviceController::class.':getDevices');
        $group->get('/devices/{page}', DeviceController::class.':getDevices');
        $group->get('/device/table/{device}', DeviceController::class.':getDeviceTable');

    })->add(new AuthMiddleware());

    
    
    $app->get('/home', function (RequestInterface $request, ResponseInterface $response, $args) {
      
      $db = $this->get('connection');
      
      $user = $db->query("SELECT * FROM kr_users", PDO::FETCH_ASSOC);
      $array = array();
      foreach($user as $us){
        $array['name'] = $us['userName'];
      }
      $text = json_encode($array);

     // $text = json_encode($user);

      $response->getBody()->write("Main Response!". $text);
        return $response;
      });
    
}; 
