<?php

include(__DIR__ . '/init.php');

list(, $method) = $_SERVER['argv'];

$request = Symfony\Component\HttpFoundation\Request::create('/' . $method, 'GET');

$app->match('/mail', function () use ($app, $telegram) {

    $server = new Ddeboer\Imap\Server('imap.gmail.com');
    $connection = $server->authenticate(getenv('EMAIL_USER'), getenv('EMAIL_PASSWORD'));
    $mailbox = $connection->getMailbox('INBOX');
    $search = new Ddeboer\Imap\SearchExpression();
    $search->addCondition(new Ddeboer\Imap\Search\Email\To(getenv('EMAIL_USER')))
        ->addCondition(new Ddeboer\Imap\Search\Text\Subject('Synology'))
        ->addCondition(new Ddeboer\Imap\Search\Flag\Unseen());

    $messages = $mailbox->getMessages($search);
    $chat_ids = Bot\ChatStorage::get();

    $i = 0;
    if (!empty($messages)) {
        foreach ($messages as $message) {
            $text = $message->getBodyText();
            foreach ($message->getAttachments() as $attachment) {
                $file = tempnam('/tmp/', 'att_' . $i);
                file_put_contents($file, $attachment->getDecodedContent());

                foreach ($chat_ids as $chat_id => $_data) {
                    Longman\TelegramBot\Request::sendPhoto([
                        'chat_id' => $chat_id,
                        'caption' => $text
                    ], $file);
                }
            }
        }
    }

    return 'done';
});

$app->run($request);
