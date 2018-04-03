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

            if (strpos(getenv('SYNOLOGY_HOST'), ':') !== false) {
                list($host, $port) = explode(':', getenv('SYNOLOGY_HOST'));
            } else {
                $host = getenv('SYNOLOGY_HOST');
                $port = static::API_PORT;
            }

            $synology = new Api($host, $port, 'http', static::API_VERSION);
            $synology->setAuth(getenv('SYNOLOGY_USER'), getenv('SYNOLOGY_PASSWORD'));

            return $synology;
        };
    }
}