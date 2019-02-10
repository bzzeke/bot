<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class Message extends Controller
{
    public function process()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

        $data = file_get_contents('php://input');

        if (!empty($data) && $json = json_decode($data, true)) {

            $chat_ids = $this->app['storage']->get('Chats');

            foreach ($chat_ids as $chat_id => $_data) {
                $response = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => sprintf("%s\n%s", $json['from'], $json['text'])
                ]);

                if ($response->isOk()) {
                    return 'ok';
                }
            }
        }

        return '';
    }
}