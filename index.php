#!/usr/bin/env php
<?php
$c = require __DIR__ . '/vendor/autoload.php';
$c->addClassMap(array(
  'phpMQTT' => __DIR__ . '/app/phpMQTT.php'
));

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$commands_path = __DIR__ . '/app/Commands/';
$app = new Silex\Application();


try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram(getenv('API_KEY'), getenv('BOT_NAME'));
    $telegram->addCommandsPath($commands_path);

    $app->match('/hook/register', function () use ($app, $telegram) {
        $result = $telegram->setWebhook(getenv('HOOK_URL'));
        if ($result->isOk()) {
            echo $result->getDescription();
        }
    });

    $app->match('/hook/process', function () use ($app, $telegram) {
        $telegram->handle();
    });

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    echo $e;
}