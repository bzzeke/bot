<?php

namespace Bot\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Bot\Telegram;

class TelegramServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['telegram'] = function ($app) {

            $telegram = new Telegram(getenv('BOT_TOKEN'), getenv('BOT_NAME'));
            $telegram->addCommandsPath(APP_DIR . '/app/Telegram/Commands', true);

            foreach ($app['telegram.commands'] as $command) {
                $telegram->setCommandConfig($command, $app['telegram.config']);
            }

            $admins_list = array_map(function($value) {
                return (int) $value;
            }, explode(',', getenv('BOT_ADMINS_LIST')));

            if (!empty($admins_list)) {
                $telegram->enableAdmins($admins_list);
            }

            return $telegram;
        };

        $app['telegram.commands'] = function ($app) {
            return [
                'temp',
                'cams',
                'set',
                'cry'
            ];
        };

        $app['telegram.config'] = function ($app) {
            $config = [
              'keyboards' => [],
              'mqtt' => $app['mqtt'],
              'sighthound' => $app['sighthound'],
              'storage' => $app['storage']
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