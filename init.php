<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Bot\Providers\BootServiceProvider;
use Bot\Providers\SynologyServiceProvider;
use Bot\Providers\TelegramServiceProvider;

define('APP_DIR', __DIR__);

$composer = require(APP_DIR . '/vendor/autoload.php');
$composer->add('Bot', APP_DIR . '/app');

$app = new Application();

$app->register(new BootServiceProvider);
$app->register(new SynologyServiceProvider);
$app->register(new TelegramServiceProvider);

$app->match('/{controller}/{action}', function (Application $app, Request $request, string $controller, string $action) {
    $class = 'Bot\\Controllers\\' . str_replace('_', '', ucwords($controller, '_'));

    if (class_exists($class)) {
        $handler = new $class($app, $request);
        return $handler->handle($action);
    }

    return new Response('Controller not found', 404);
})
    ->assert('controller', '\w+')
    ->assert('action', '\w+');

$app['debug'] = true;

return $app;