<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use Bot\Mqtt;
use Bot\Conversation;

/**
 * User "/forcereply" command
 */
class SetCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'set';
    protected $description = 'Set temperature';
    protected $usage = '/set';
    protected $version = '0.1.0';

    protected $topics = [
        'Floor_1' => [
            'text' => '1st floor t˚C',
            'type' => 'temperature'
        ],
        'Floor_2' => [
            'text' => '2nd floor t˚C',
            'type' => 'temperature'
        ],
        'Basement' => [
            'text' => 'Basement t˚C',
            'type' => 'temperature'
        ],
        'Enabled' => [
            'text' => 'Boiler state',
            'type' => 'switch',
            'vars' => ['Off', 'On']
        ],
        'Simple' => [
            'text' => 'Heating mode',
            'type' => 'switch',
            'vars' => ['Extended', 'Simple']
        ],
    ];

    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data = [
            'chat_id' => $chat_id,
        ];

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName(), strlen($text) === 0);
        $state = $this->conversation->getState() ?  $this->conversation->getState() : 'ask_topic';
        $result = Request::emptyResponse();

        switch ($state) {
            case 'ask_topic':
                $this->conversation->setState('ask_topic_value');
                $this->conversation->update();

                $data['text'] = 'Select option:';
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => $this->topics['Floor_1']['text'], 'callback_data' => $this->generateCallback('Floor_1')],
                        ['text' => $this->topics['Floor_2']['text'], 'callback_data' => $this->generateCallback('Floor_2')],
                        ['text' => $this->topics['Basement']['text'], 'callback_data' => $this->generateCallback('Basement')],
                    ],[
                        ['text' => $this->topics['Enabled']['text'], 'callback_data' => $this->generateCallback('Enabled')],
                        ['text' => $this->topics['Simple']['text'], 'callback_data' => $this->generateCallback('Simple')],
                    ]
                );

                $result = Request::sendMessage($data);
                break;
            case 'ask_topic_value':

                $topic = $text;
                if (isset($this->topics[$topic])) {
                    $this->conversation->setData('topic', $topic);
                    $this->conversation->setState('publish_topic');
                    $this->conversation->update();

                    if ($this->topics[$topic]['type'] == 'temperature') {
                        $data['text'] = 'Set temperature for ' . $this->topics[$topic]['text'];
                    } elseif ($this->topics[$topic]['type'] == 'switch') {
                        $data['text'] = 'Set ' . $this->topics[$topic]['text'];
                        $data['reply_markup'] = new InlineKeyboard(
                            [
                                ['text' => $this->topics[$topic]['vars'][1], 'callback_data' => $this->generateCallback(1)],
                                ['text' => $this->topics[$topic]['vars'][0], 'callback_data' => $this->generateCallback(0)],
                            ]
                        );
                    }

                    $result = Request::sendMessage($data);
                }
                break;

            case 'publish_topic':
                $payload = $text;
                $topic = $this->conversation->getData('topic');

                $this->publishTopic($this->conversation->getData('topic'), $payload);

                if ($this->topics[$topic]['type'] == 'temperature') {
                    $data['text'] = sprintf('Ok, %s temperature set to *%s*', $this->topics[$topic]['text'], $payload);
                } elseif ($this->topics[$topic]['type'] == 'switch') {
                    $data['text'] = sprintf('Ok, %s set to *%s*', $this->topics[$topic]['text'], $this->topics[$topic]['vars'][$payload]);
                }

                $data['parse_mode'] = 'Markdown';

                $keyboard = new Keyboard($this->config['keyboards']);
                $keyboard->setResizeKeyboard(true);

                $data['reply_markup'] = $keyboard;
                $this->conversation->stop();

                $result = Request::sendMessage($data);
        }

        return $result;
    }

    protected function publishTopic($topic, $payload)
    {
        $this->mqtt = new Mqtt(getenv('MQTT_HOST'), 1883, "WB Delyanka");

        if(!$this->mqtt->connect()){
            error_log('Failed to connecto to MQTT');
            return false;
        }

        $this->mqtt->publish('/devices/thermostat/controls/' . $topic . '/on', $payload);
    }

    protected function generateCallback($payload)
    {
        return json_encode([
            'command' => $this->name,
            'state' => $this->conversation->getState(),
            'payload' => $payload,
        ]);
    }
}
