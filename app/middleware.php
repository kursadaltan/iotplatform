<?php
declare(strict_types=1);

use IoT\System\Middleware\ExampleAfterMiddleware;
use IoT\System\Middleware\ExampleBeforeMiddleware;
use Slim\App;

return function (App $app){
    //Add Global Error Handler

    $settings = $app->getContainer()->get('settings');

    $app->addErrorMiddleware(
        $settings['displayErrorDetails'], 
        $settings['logErrorDetails'], 
        $settings['logErrors']
    );
    
    //$app->add(ExampleBeforeMiddleware::class);
    //$app->add(ExampleAfterMiddleware::class);

};