<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;

class Test extends Controller
{
    public function run()
    {
        $cmd = $this->request->get('cmd');
        $text = $this->request->get('text') || 'test';
        $user_id = $this->request->get('user_id') || 1;
        $chat_id = $this->request->get('chat_id') || 1;

        $firstname = 'John';
        $username = 'jdoe';

        $update = new Update([
            'update_id' => 0,
            'message' => [
                'message_id' => 0,
                'from' => [
                    'id' => $user_id,
                    'first_name' => $firstname,
                    'username' => $username,
                ],
                'date' => time(),
                'chat' => [
                    'id' => $chat_id,
                    'type' => 'private',
                ],
                'text' => $text,
                'command' => $cmd
            ],
        ]);

        $this->app['telegram']->processUpdate($update);

        return 'ok';
    }
}