<?php
$c = require __DIR__ . '/vendor/autoload.php';
$c->addClassMap(array(
  'phpMQTT' => __DIR__ . '/app/phpMQTT.php',
  'Synology_SurveillanceStation_Api' => __DIR__ . '/app/Synology/SurveillanceStation/Api.php'
));

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$commands_path = __DIR__ . '/app/Commands/';
$app = new Silex\Application();

$telegram = new Longman\TelegramBot\Telegram(getenv('API_KEY'), getenv('BOT_NAME'));
$telegram->addCommandsPath($commands_path);

$app->match('/hook/register', function () use ($app, $telegram) {
    try {
            $result = $telegram->setWebhook(getenv('HOOK_URL'));
            if ($result->isOk()) {
                return $result->getDescription();
            }
    } catch (Longman\TelegramBot\Exception\TelegramException $e) {
        return $e;
    }
});

$app->match('/hook/unregister', function () use ($app, $telegram) {
    try {
        $result = $telegram->deleteWebhook();

        if ($result->isOk()) {
            return $result->getDescription();
        }
    }  catch (Longman\TelegramBot\Exception\TelegramException $e) {
        return $e;
    }
});

$app->match('/hook/process', function () use ($app, $telegram) {
    try {
           $telegram->handle();
    } catch (Longman\TelegramBot\Exception\TelegramException $e) {
        return $e;
    }
    return 'ok';
});

$app->run();
