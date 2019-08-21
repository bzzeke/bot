<?php

require_once __DIR__ . '/vendor/autoload.php';

use TheFox\Smtp\Server;
use TheFox\Smtp\Event;
use Zend\Mail\Message;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$app = include(__DIR__ . '/init.php');
$app->boot();

$logger = new Logger('smtp_example');
$logger->pushHandler(new StreamHandler('php://stdout', getenv('MAILER_DEBUG') ? Logger::DEBUG : Logger::NOTICE));


$options = [
    'ip' => getenv('SMTP_HOST'),
    'port' => getenv('SMTP_PORT'),
    'logger' => $logger
];

$server = new Server($options);

if (!$server->listen([])) {
    print("Failed to bind to address\n");
    exit(1);
}

$sendEvent = new Event(Event::TRIGGER_NEW_MAIL, null, function (Event $event, string $from, array $rcpts, Message $mail) use ($app) {
    if (empty($rcpts[0]) || !checkEmail($rcpts[0])) {
        echo("Incorrect email\n");
        return;
    }

    $app->run(Request::create(
        '/email/process',
        'POST',
        array(),
        array(),
        array(),
        array(),
        $mail->toString()
    ));
});

$server->addEvent($sendEvent);
$server->loop();

function checkEmail($email)
{
    return preg_match(sprintf("/%s/", getenv("SMTP_VALID_EMAIL")), $email);
}