<?php

namespace Bot\Controllers;

use Bot\Subscriber\Temp;
use Bot\Renderer\Text;

class Station extends Controller
{
    protected $processed_topics = [];

    public function process()
    {
        $data = file_get_contents('php://input');

        if (empty($data) || ($json = json_decode($data, true)) === false) {
            return '';
        }

        $response = [
            'response' => [
                'end_session' => false
            ],
            'session' => $json['session'],
            'version' => $json['version']
        ];

        if (!$this->isAuthorized($json['session']['user_id'])) {
            if (strtolower($json['request']['original_utterance']) == getenv('STATION_PASSWORD')) {
                $this->app['storage']->set('Station', $json['session']['user_id'], true);
            } elseif ($json['session']['new']) {
                $response['response']['text'] = 'Привет, это закрытый навык. Нужна авторизация, бро. Назови пароль.';
                $response['response']['tts'] = 'Привет, это закрытый навык. Нужна авторизация, бро. Назови пароль.';
                return json_encode($response);
            } else {
                $response['response']['text'] = 'Не, не угадал пароль';
                $response['response']['tts'] = 'Не, не угадал пароль';
                $response['response']['end_session'] = true;
                return json_encode($response);
            }
        }

        $subscriber = new Temp($this->app['mqtt']);
        $result = $subscriber->run();

        if ($result == false) {
            $text = 'Таймаут при конекте к mqtt';
        } else {
            $renderer = new Text($result);
            $text = $renderer->render();
        }

        $response['response']['text'] = $text;
        $response['response']['tts'] = $text;
        $response['response']['end_session'] = true;
        return json_encode($response);
    }

    protected function isAuthorized($user_id)
    {
        return $this->app['storage']->get('Station', $user_id);
    }
}