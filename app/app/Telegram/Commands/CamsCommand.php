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
use Bot\Conversation;

/**
 * User "/forcereply" command
 */
class CamsCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'cams';
    protected $description = 'Get camera snapshots';
    protected $usage = '/cams';
    protected $version = '0.2.0';

    protected $cameras = [];
    const ALL_CAMERAS = 'all';

    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->getCams();

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
                $this->conversation->setState('get_snapshot');
                $this->conversation->update();

                $data['text'] = 'Select camera:';

                $keyboard = new InlineKeyboard(
                    [
                        ['text' => 'All', 'callback_data' => $this->generateCallback('all')],
                    ]
                );
                foreach ($this->cameras as $cam => $_data) {
                    $keyboard->addRow(
                        ['text' => $cam, 'callback_data' => $this->generateCallback($cam)]
                    );
                }

                $data['reply_markup'] = $keyboard;
                $result = Request::sendMessage($data);
                break;


            case 'get_snapshot':

                $keyboard = new Keyboard($this->config['keyboards']);
                $keyboard->setResizeKeyboard(true);

                $data['reply_markup'] = $keyboard;
                $this->conversation->stop();

                $payload = $text;

                if (strpos($payload, ':') === false) {
                    $files = $this->getSnapshots($payload);
                } else {
                    list($cam_id, $preset_id) = explode(':', $payload);
                    $files = $this->getSnapshots($cam_id, $preset_id);
                }

                foreach ($files as $file) {
                    $data['photo'] = Request::encodeFile($file);
                    $result = Request::sendPhoto($data);
                }
                break;

            case 'get_video':

                $file = $this->getVideo($text);
                if (!empty($file)) {
                    $data['video'] = Request::encodeFile($file);
                    $result = Request::sendVideo($data);
                }
        }

        return $result;
    }

    protected function getCams()
    {
        $response = file_get_contents(sprintf("http://%s/camera_list", $_ENV['CAMERA_SERVER']));
        if (!empty($response) && $data = json_decode($response, true)) {
            if (!empty($data['results'])) {
                foreach ($data['results'] as $camera) {
                    $this->cameras[$camera['name']] = [];
                }
            }
        }
    }

    protected function getSnapshot($cam_id)
    {
        return file_get_contents(sprintf("http://%s/snapshot/%s", $_ENV['CAMERA_SERVER'], $cam_id));
    }

    protected function getSnapshots($cam_id, $preset_id = 0)
    {
        $files = [];

        if ($cam_id == static::ALL_CAMERAS) {
            $cam_ids = array_keys($this->cameras);
        } else {
            $cam_ids = [$cam_id];
        }

        foreach ($cam_ids as $cam_id) {
            $files[$cam_id] = tempnam('/tmp', 'cam_' . $cam_id);
            file_put_contents($files[$cam_id], $this->getSnapshot($cam_id));
        }

        return $files;
    }

    protected function getVideo($filename)
    {
        list($camera, $timestamp) = explode('_', basename($filename, ".jpeg"));
        $video_file = tempnam('/tmp', 'cam_' . $camera . $timestamp);
        file_put_contents($video_file, file_get_contents(sprintf("http://%s/video/%s/%s", $_ENV['CAMERA_SERVER'], $camera, $timestamp)));

        return $video_file;
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