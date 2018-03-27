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

    $message = Parser::email(file_get_contents('php://input'));
    $chat_ids = ChatStorage::get();

    $i = 0;
    $text = $message->textPlain();
    $attachments = $message->getAttachments();

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $file = tempnam('/tmp/', 'att_' . $i);
            $attachment->save($file);

            foreach ($chat_ids as $chat_id => $_data) {
                Request::sendPhoto([
                    'chat_id' => $chat_id,
                    'caption' => $text,
                    'photo' => Request::encodeFile($file)
                ]);
            }
        }
    } else {
        foreach ($chat_ids as $chat_id => $_data) {
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => $text
            ]);
        }
    }

    return 'ok';
});


$app->run();