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
use Bot\Conversation;

/**
 * User "/forcereply" command
 */
class CryCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'cry';
    protected $description = 'Set cry alarm';
    protected $usage = '/cry';
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
        $state = $this->conversation->getState() ?  $this->conversation->getState() : 'ask_topic_value';
        $result = Request::emptyResponse();

        switch ($state) {
            case 'ask_topic_value':

                $topic = $text;

                $this->conversation->setState('publish_topic');
                $this->conversation->update();

                $data['text'] = 'Enable?';
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'Yes', 'callback_data' => $this->generateCallback(1)],
                        ['text' => 'No', 'callback_data' => $this->generateCallback(0)],
                    ]
                );

                $result = Request::sendMessage($data);

                break;

            case 'publish_topic':
                $payload = $text;

                $this->publishTopic($payload);

                $data['text'] = sprintf('Ok, alarm is *%s*', $payload ? 'enabled' : 'disabled');
                $data['parse_mode'] = 'Markdown';

                $keyboard = new Keyboard($this->config['keyboards']);
                $keyboard->setResizeKeyboard(true);

                $data['reply_markup'] = $keyboard;
                $this->conversation->stop();

                $result = Request::sendMessage($data);
        }

        return $result;
    }

    protected function publishTopic($payload)
    {
        if(!$this->config['mqtt']->connect()){
            error_log('Failed to connecto to MQTT (set)');
            return false;
        }

        $this->config['mqtt']->publish('/devices/util/controls/Cry Alarm/on', $payload);
    }

    protected function generateCallback($payload)
    {
        return $this->telegram->serialize(
            $this->name,
            $this->conversation->getState(),
            $payload
        );
    }
}
