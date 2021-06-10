<?php

declare(strict_types=1);

use DI\Container;

return function (Container $container) {
    $container->set('connection', function() use ($container) {
        $connection = $container->get('settings')['connection'];
      
        $config = [
            'host'		=> $connection['host'],
            'driver'	=> 'mysql',
            'database'	=> $connection['dbname'],
            'username'	=> $connection['dbuser'],
            'password'	=> $connection['dbpass'],
            'charset'	=> 'utf8',
            'collation'	=> 'utf8_general_ci',
            'prefix'	 => $connection['dbprefix']
        ];

        try {
            $connection = $db = new \Buki\Pdox($config);  
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        return $connection;
    });
};