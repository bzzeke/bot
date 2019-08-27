<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class Notify extends Controller
{
    public function grafana()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

        $data = $this->request->getContent();
        $result = '';

        if (!empty($data) && $json = json_decode($data, true)) {

            $text = $this->getGrafanaMessage($json);

            $chat_ids = $this->app['storage']->get('Chats');

            foreach ($chat_ids as $chat_id => $_data) {
                $response = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $text
                ]);

                if ($response->isOk()) {
                    $result = 'ok';
                }
            }
        }

        return json_encode($result);
    }

    public function general()
    {
        $telegram = $this->app['telegram']; // instatiate class to correctly initialize static methods in Request class

        $data = $this->request->getContent();
        $result = '';

        if (!empty($data) && $json = json_decode($data, true)) {
            /*
            data structure:
            {
                text: 'message',
                parse_mode: 'Markdown',
                attachments: [
                    file1_base64,
                    file2_base64
                ]
            }
            */
            $chat_ids = $this->app['storage']->get('Chats');

            foreach ($chat_ids as $chat_id => $_data) {
                $response = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $json['text'],
                    'parse_mode' => $json['parse_mode'] ?? ''
                ]);

                if ($response->isOk()) {
                    if (!empty($json['attachments'])) {
                        foreach ($json['attachments'] as $attachment) {
                            $file = tempnam('/tmp/', 'att');
                            file_put_contents($file, base64_decode($attachment));
                            $response = Request::sendPhoto([
                                'chat_id' => $chat_id,
                                'photo' => Request::encodeFile($file)
                            ]);
                        }
                    }
                    $result = 'ok';
                }
            }
        }

        return json_encode($result);
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