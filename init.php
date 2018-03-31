<?php

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\Debug;

error_reporting(E_ALL);
ini_set('display_errors','On');


define('APP_DIR', __DIR__);
$c = require __DIR__ . '/vendor/autoload.php';
$c->add('Bot', __DIR__ . '/app');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$commands_path = __DIR__ . '/app/Telegram/Commands';
$app = new Silex\Application();

// Enable debug mode
$app['debug'] = true;

// Handle fatal errors
ErrorHandler::register();

Debug::enable();

$telegram = new Longman\TelegramBot\Telegram(getenv('API_KEY'), getenv('BOT_NAME'));
$telegram->addCommandsPath($commands_path, true);

$config = [
    'keyboards' => [
        ['text' => '/temp'],
        ['text' => '/cams'],
        ['text' => '/set'],
        ['text' => '/video'],
    ]
];

$telegram->setCommandConfig('temp', $config);
$telegram->setCommandConfig('cams', $config);
$telegram->setCommandConfig('set', $config);
$telegram->setCommandConfig('video', $config);
