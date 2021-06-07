<?php
declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\Authentication\SimpleTokenAuthentication;

require __DIR__ . '/../vendor/autoload.php';


date_default_timezone_set("Europe/Istanbul");

$container = new Container();

$settings = require __DIR__ . '/../app/settings.php';
$settings($container);

$connection = require __DIR__ . "/../app/connection.php";
$connection($container);

$logger = require __DIR__ . '/../app/logger.php';
$logger($container);


AppFactory::setContainer($container);


$app = AppFactory::create();
$app->setBasePath("/iot-api");

$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

$routes = require __DIR__ .'/../app/routes.php';
$routes($app);



$app->run();