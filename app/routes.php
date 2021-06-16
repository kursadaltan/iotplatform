<?php
declare(strict_types=1);


use Slim\App;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use DI\Container;
use IoT\Api\DeviceController;
use IoT\Api\ModemController;
use IoT\Api\ProductController;
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
        $group->get('/users[/{user:[0-9]+}]',   UserController::class.':getUsers');        
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

        $group->get('/modems[/{page:[0-9]+}]', ModemController::class.':getModems');
        $group->post('/modems/new', ModemController::class.':newModem');
        $group->put('/modem/{modem}', ModemController::class.':updateModem');
        $group->delete('/modem/{modem}', ModemController::class.':deleteModem');

        $group->get('/devices[/{page:[0-9]+}]', DeviceController::class.':getDevices');     
        $group->get('/device/table/{device}', DeviceController::class.':getDeviceTable');
        $group->post('/device/new', DeviceController::class.':newDevice');
        $group->put('/device/{device}', DeviceController::class.':updateDevice');
        $group->delete('/device/{device}', DeviceController::class.':deleteDevice');
        $group->post('/device/table/new', DeviceController::class.':newDeviceTable');
        $group->put('/device/table/{table}', DeviceController::class.':updateDeviceTable');
        $group->delete('/device/table/{table}', DeviceController::class.':deleteDeviceTable');

        $group->get('/products[/{page:[0-9]+}]', ProductController::class.':getProducts');    
        $group->get('/product/table/{product}', ProductController::class.':getProductTable');
        $group->post('/product/new', ProductController::class.':newProduct');
        $group->put('/product/{product}', ProductController::class.':updateProduct');
        $group->delete('/product/{product}', ProductController::class.':deleteProduct');
        $group->post('/product/table/new', ProductController::class.':newProductTable');
        $group->put('/product/table/{table}', ProductController::class.':updateProductTable');
        $group->delete('/product/table/{table}', ProductController::class.':deleteProductTable');

        $group->get('/products/groups', ProductController::class.':getProductGroups');
        $group->post('/products/groups/new', ProductController::class.':newProductGroup');
        $group->put('/products/groups/{group}', ProductController::class.':updateProductGroup');
        $group->delete('/products/groups/{group}', ProductController::class.':deleteProductGroup');

        $group->get('/products/labels', ProductController::class.':getLabels');
        $group->post('/products/labels/new', ProductController::class.':newLabel');
        $group->put('/products/labels/{label}', ProductController::class.':updateLabel');
        $group->delete('/products/labels/{label}', ProductController::class.':deleteLabel');
        

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
