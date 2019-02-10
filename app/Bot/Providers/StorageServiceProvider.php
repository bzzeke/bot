<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Bot\Storage;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['storage'] = function ($app) {
            return new Storage();
        };
    }
}