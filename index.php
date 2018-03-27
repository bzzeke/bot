<?php

include(__DIR__ . '/init.php');

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

$app->match('/email_received', function () use ($app, $telegram) {

    $message = \bashkarev\email\Parser::email(file_get_contents('php://input'));
    $chat_ids = Bot\ChatStorage::get();

    $i = 0;
    $text = $message->textPlain();
    $attachments = $message->getAttachments();

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $file = tempnam('/tmp/', 'att_' . $i);
            $attachment->save($file);

            foreach ($chat_ids as $chat_id => $_data) {
                Longman\TelegramBot\Request::sendPhoto([
                    'chat_id' => $chat_id,
                    'caption' => $text
                ], $file);
            }
        }
    } else {
        foreach ($chat_ids as $chat_id => $_data) {
            Longman\TelegramBot\Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => $text
            ]);
        }
    }

    return 'ok';
});


$app->run();