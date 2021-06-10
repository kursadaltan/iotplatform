<?php


declare(strict_types=1);

use DI\Container;
use Monolog\Logger;

return function (Container $container){
    $container->set('settings', function(){
        return [
            'name' => 'IoT Platform API V1',
            'host' => 'http://localhost/iot-app/',
            'displayErrorDetails' => true,
            'logErrorDetails' => true,
            'logErrors' => false,
            'logger' => [
                'name' => 'IoT-Api',
                'path' => __DIR__ . '/../logs/app.log',
                'level' => Logger::DEBUG
            ],
            'connection' => [
                'host' => 'localhost',
                'dbname' => 'iotplatform',
                'dbuser' => 'root',
                'dbpass' => '',
                'dbprefix' => 'kr_'
            ]
        ];
    });
};
