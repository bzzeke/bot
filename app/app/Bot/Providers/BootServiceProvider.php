<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
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
        if (file_exists(APP_DIR . '/.env')) {
            $app['dotenv']->load();
        }

        error_reporting(E_ALL);

        if (getenv('DEBUG')) {
            Debug::enable();
        }
    }
}