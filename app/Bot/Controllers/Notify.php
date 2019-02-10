<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class Notify extends Controller
{
    public function process()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

        $data = file_get_contents('php://input');

        if (!empty($data) && $json = json_decode($data, true)) {

            $text = $this->getGrafanaMessage($json);

            $chat_ids = $this->app['storage']->get('Chats');

            foreach ($chat_ids as $chat_id => $_data) {
                $response = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $text
                ]);

                if ($response->isOk()) {
                    return 'ok';
                }
            }
        }

        return '';
    }

    protected function getGrafanaMessage($json)
    {
        $text = '';
        if ($json['state'] == 'alerting') {
            $text = $json['message'] . "\n" . join(', ', array_column($json['evalMatches'], 'metric'));
        } else {
            $text = '[' . $json['state'] .'] ' . $json['message'];
        }

        return $text;
    }
}