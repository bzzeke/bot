<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Bot\Sighthound\Api;

class SighthoundServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['sighthound'] = function ($app) {
            return new Api(getenv('SIGHTHOUND_HOST'), [getenv('SIGHTHOUND_USER'), getenv('SIGHTHOUND_PASSWORD')]);
        };
    }
}