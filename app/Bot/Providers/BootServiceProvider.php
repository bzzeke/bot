<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Api\BootableProviderInterface;

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\Debug;

use Dotenv\Dotenv;

class BootServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['dotenv'] = function($app) {
            return new Dotenv(APP_DIR);
        };
    }

    public function boot(Application $app)
    {
        $app['dotenv']->load();

        if (getenv('DEBUG')) {
            $this->enableDebug($app);
        }
    }

    protected function enableDebug(Application $app)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');

        $app['debug'] = true;
        $app->error(function(\Exception $e) use ($app) {
            print_r($e); // Do something with $e
        });

        ErrorHandler::register();
        Debug::enable();
    }
}