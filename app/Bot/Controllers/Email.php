<?php

namespace Bot\Controllers;

use bashkarev\email\Parser;
use Bot\ChatStorage;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;

class Email extends Controller
{
    public function process()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

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
                        $this->saveFailedEmail($response, $message, $email);
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
                    $this->saveFailedEmail($response, $message, $email);
                }
            }
        }

        return 'ok';
    }

    protected function saveFailedEmail($response, $message, $contents)
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

}