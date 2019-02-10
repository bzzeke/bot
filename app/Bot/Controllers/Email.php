<?php

namespace Bot\Controllers;

use bashkarev\email\Parser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class Email extends Controller
{
    public function process()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

        $email = file_get_contents('php://input');
        if (stripos($email, 'content-type') === false) {
            $email = "Content-Type: text/plain\n" . $email;
        }

        $message = Parser::email($email);

        if ($this->skipNotification($message)) {
            return 'skipped';
        }

        $chat_ids = $this->app['storage']->get('Chats');

        $text = $message->textPlain();
        if (empty($text)) {
            $text = strip_tags($this->convertMarkup($message->textHtml()));
        }

        $attachments = $message->getAttachments();

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $file = tempnam('/tmp/', 'att');
                $attachment->save($file);
                $reply_markup = null;

                if ($this->isSurveillanceNotification($message)) {
                    $reply_markup = new InlineKeyboard([[
                        'text' => 'Get video',
                        'callback_data' => $telegram->serialize('cams', 'get_video', $attachment->getFileName())
                    ]]);
                }

                foreach ($chat_ids as $chat_id => $_data) {
                    $response = Request::sendPhoto([
                        'chat_id' => $chat_id,
                        'caption' => $text,
                        'photo' => Request::encodeFile($file),
                        'reply_markup' => $reply_markup
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

        $chat_ids = $this->app['storage']->get('Chats');
        $message = sprintf("Failed to deliver email.\n%s\nemail stored at %s", $response->printError(true), $filename);

        error_log($message);
        foreach ($chat_ids as $chat_id => $_data) {
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => $message
            ]);
        }
    }

    protected function convertMarkup($text)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $text);
    }

    protected function skipNotification($message)
    {
        if ($this->isSurveillanceNotification($message) && $this->isHomeMode()) {
            return true;
        }

        return false;
    }

    protected function isSurveillanceNotification($message)
    {
        $to = $message->getTo();
        if ($to[0]->email == getenv('SURV_EMAIL')) {
            return true;
        }

        return false;
    }

    protected function isHomeMode()
    {
        $is_home = false;
        $mqtt = $this->app['mqtt'];
        if (!$mqtt->connect()){
            error_log('Failed to connecto to MQTT (email)');
            return false;
        }
        $mqtt->subscribe([
            '/devices/util/controls/Occupancy' => [
                'qos' => 0,
                'function' => function($topic, $message) use (&$is_home, $mqtt) {
                    $is_home = $message;
                    $mqtt->close();
                }
            ]
        ], 0);

        $time_start = time();
        $timeout = 10;
        while ($mqtt->proc()) {
            if (time() - $time_start > $timeout) {
                $mqtt->close();
                break;
            }
        }

        return $is_home;
    }
}