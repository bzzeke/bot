<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Bot\Synology\SurveillanceStation\Api;

class SynologyServiceProvider implements ServiceProviderInterface
{
    const API_VERSION = 6;
    const API_PORT = 5000;

    public function register(Container $app)
    {
        $app['synology'] = function ($app) {
            $synology = new Api(getenv('SYNOLOGY_HOST'), static::API_PORT, 'http', static::API_VERSION);
            $synology->connect(getenv('SYNOLOGY_USER'), getenv('SYNOLOGY_PASSWORD'));

            return $synology;
        };
    }
}