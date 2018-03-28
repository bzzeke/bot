<?php
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;
use \bashkarev\email\Parser;
use Bot\ChatStorage;

include(__DIR__ . '/init.php');

$app->match('/hook/register', function () use ($app, $telegram) {
    try {
            $result = $telegram->setWebhook(getenv('HOOK_URL'));
            if ($result->isOk()) {
                return $result->getDescription();
            }
    } catch (TelegramException $e) {
        return $e;
    }
});

$app->match('/hook/unregister', function () use ($app, $telegram) {
    try {
        $result = $telegram->deleteWebhook();

        if ($result->isOk()) {
            return $result->getDescription();
        }
    }  catch (TelegramException $e) {
        return $e;
    }
});

$app->match('/hook/process', function () use ($app, $telegram) {
    try {
           $telegram->handle();
    } catch (TelegramException $e) {
        return $e;
    }
    return 'ok';
});

$app->match('/email_received', function () use ($app, $telegram) {

    $email = file_get_contents('php://input');
    $message = Parser::email($email);
    $chat_ids = ChatStorage::get();

    $text = $message->textPlain();
    if (empty($text)) {
        $text = strip_tags($message->textHtml());
    }

    $attachments = $message->getAttachments();

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $file = tempnam('/tmp/', 'att');
            $attachment->save($file);

            foreach ($chat_ids as $chat_id => $_data) {
                $response = Request::sendPhoto([
                    'chat_id' => $chat_id,
                    'caption' => $text,
                    'photo' => Request::encodeFile($file)
                ]);

                if (!$response->isOk()) {
                    save_failed_email($response, $message, $email);
                }
            }
        }
    } else {
        foreach ($chat_ids as $chat_id => $_data) {
            $response = Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => $text
            ]);

            if (!$response->isOk()) {
                save_failed_email($response, $message, $email);
            }
        }
    }

    return 'ok';
});


$app->run();

function save_failed_email($response, $message, $contents)
{
    $date = $message->getDate();
    $filename = './var/failed_email_' . $date->getTimestamp();
    file_put_contents($filename, $contents);

    $chat_ids = ChatStorage::get();
    foreach ($chat_ids as $chat_id => $_data) {
        Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => sprintf("Failed to deliver email.\n%s\nemail stored at %s", $response->printError(true), $filename)
        ]);
    }
}
