<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Longman\TelegramBot\Telegram;

class TelegramServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['telegram'] = function ($app) {

            $telegram = new Telegram(getenv('API_KEY'), getenv('BOT_NAME'));
            $telegram->addCommandsPath(APP_DIR . '/app/Telegram/Commands', true);

            foreach ($app['telegram.commands'] as $command) {
                $telegram->setCommandConfig($command, $app['telegram.config']);
            }

            return $telegram;
        };

        $app['telegram.commands'] = function ($app) {
            return [
                'temp',
                'cams',
                'set',
                'video'
            ];
        };

        $app['telegram.config'] = function ($app) {
            $config = [
              'keyboards' => [],
              'synology' => $app['synology']
            ];

            foreach ($app['telegram.commands'] as $command) {
                $config['keyboards'][] = [
                    'text' => '/' . $command
                ];
            }

            return $config;
        };
    }
}