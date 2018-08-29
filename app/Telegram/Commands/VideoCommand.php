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
use Longman\TelegramBot\Request;
use Bot\ChatStorage;
use Bot\Conversation;

/**
 * User "/forcereply" command
 */
class VideoCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'video';
    protected $description = 'Get video';
    protected $usage = '/video';
    protected $version = '0.1.0';

    const CAM_STREET = 1;
    const CAM_BACKYARD = 2;
    const LIST_LIMIT = 6;

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
        $state = $this->conversation->getState() ?  $this->conversation->getState() : 'select_cam';
        $result = Request::emptyResponse();

        switch ($state) {
            case 'select_cam':
                $this->conversation->setState('get_list');
                $this->conversation->update();

                $data['text'] = 'Select camera:';
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'Street', 'callback_data' => $this->generateCallback(static::CAM_STREET)],
                        ['text' => 'Backyard', 'callback_data' => $this->generateCallback(static::CAM_BACKYARD)]
                    ]
                );

                $result = Request::sendMessage($data);
                break;
            case 'get_list':
                $this->conversation->setState('get_video');
                $this->conversation->update();

                $list = $this->getList($text);

                if (!empty($list)) {
                    $recordings = [];
                    foreach ($list as $element) {
                        $recordings[] = [
                            'text' => $element['date'],
                            'callback_data' => $this->generateCallback($element['id'])
                        ];
                    }
                    $keyboard = array_chunk($recordings, 3);
                    $data['text'] = 'Select video:';
                    $data['reply_markup'] = new InlineKeyboard(...$keyboard);
                } else {
                    $data['text'] = 'No recordings found';
                }

                $result = Request::sendMessage($data);

                break;

            case 'get_video':
                $this->conversation->stop();

                $keyboard = new Keyboard($this->config['keyboards']);
                $keyboard->setResizeKeyboard(true);
                $data['reply_markup'] = $keyboard;
                $data['video'] = Request::encodeFile($this->getVideo($text));
                $result = Request::sendVideo($data);
        }

        return $result;
    }

    protected function getList($cam_id)
    {
        $synology = $this->config['synology'];
        $list = $synology->getRecordings($cam_id, static::LIST_LIMIT);

        $recordings = [];
        if (!empty($list['data'])) {
            $recordings = $list['data']['recordings'];

            foreach ($recordings as &$recording) {
                $name_parts = explode('-', basename($recording['filePath'], '.mp4'));
                $timestamp = array_pop($name_parts);
                $recording['date'] = strftime('%e %b, %H:%M:%S', $timestamp);
            }
        }

        return $recordings;
    }

    protected function getVideo($id)
    {
        $synology = $this->config['synology'];

        $file = tempnam('/tmp', 'cam_' . $id);
        file_put_contents($file, $synology->getRecording($id));

        return $file;
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