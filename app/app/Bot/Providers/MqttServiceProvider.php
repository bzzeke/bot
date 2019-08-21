<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Bot\Mqtt;

class MqttServiceProvider implements ServiceProviderInterface
{
    const PORT = 1883;
    const NAME = 'Generic';

    public function register(Container $app)
    {
        $app['mqtt'] = function ($app) {
            if (strpos(getenv('MQTT_HOST'), ':') !== false) {
                list($host, $port) = explode(':', getenv('MQTT_HOST'));
            } else {
                $host = getenv('MQTT_HOST');
                $port = static::PORT;
            }

            $mqtt = new Mqtt($host, $port, static::NAME);


            return $mqtt;
        };
    }
}