<?php

define('APP_DIR', __DIR__);
$c = require __DIR__ . '/vendor/autoload.php';
$c->add('Bot', __DIR__ . '/app');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$commands_path = __DIR__ . '/app/Telegram/Commands';
$app = new Silex\Application();

$telegram = new Longman\TelegramBot\Telegram(getenv('API_KEY'), getenv('BOT_NAME'));
$telegram->addCommandsPath($commands_path);

$config = [
    'keyboards' => [
        ['text' => '/temp'],
        ['text' => '/cams'],
    ]
];

$telegram->setCommandConfig('temp', $config);
$telegram->setCommandConfig('cams', $config);
