<?php
error_reporting(E_ALL);
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


include('./app/Telegram/Commands/CamsCommand.php');
$o = new Longman\TelegramBot\Commands\UserCommands\CamsCommand($telegram);
$o->execute();

die;
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
