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
class CamsCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'cams';
    protected $description = 'Get camera snapshots';
    protected $usage = '/cams';
    protected $version = '0.2.0';

    const CAM_STREET = 1;
    const CAM_STREET_PRESET_HOME = 512;
    const CAM_STREET_PRESET_2 = 513;
    const CAM_STREET_PRESET_3 = 514;

    const CAM_BACKYARD = 2;
    const CAM_BACKYARD_PRESET_HOME = 767;
    const CAM_BACKYARD_PRESET_2 = 768;
    const CAM_BACKYARD_PRESET_3 = 769;

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
                $this->conversation->setState('get_snapshot');
                $this->conversation->update();

                $data['text'] = 'Select camera:';
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'All', 'callback_data' => $this->generateCallback('0')],
                    ], [
                        ['text' => 'Street: home', 'callback_data' => $this->generateCallback(static::CAM_STREET .':' . static::CAM_STREET_PRESET_HOME)],
                        ['text' => 'Street: 2', 'callback_data' => $this->generateCallback(static::CAM_STREET .':' . static::CAM_STREET_PRESET_2)],
                        ['text' => 'Street: 3', 'callback_data' => $this->generateCallback(static::CAM_STREET .':' . static::CAM_STREET_PRESET_3)],
                    ], [
                        ['text' => 'Backyard: home', 'callback_data' => $this->generateCallback(static::CAM_BACKYARD .':' . static::CAM_BACKYARD_PRESET_HOME)],
                        ['text' => 'Backyard: 2', 'callback_data' => $this->generateCallback(static::CAM_BACKYARD .':' . static::CAM_BACKYARD_PRESET_2)],
                        ['text' => 'Backyard: 3', 'callback_data' => $this->generateCallback(static::CAM_BACKYARD .':' . static::CAM_BACKYARD_PRESET_3)],
                    ]
                );

                $result = Request::sendMessage($data);
                break;


            case 'get_snapshot':

                $keyboard = new Keyboard($this->config['keyboards']);
                $keyboard->setResizeKeyboard(true);

                $data['reply_markup'] = $keyboard;
                $this->conversation->stop();

                $payload = $text;
                if ($payload === '0') {
                    $files = $this->getSnapshots([static::CAM_STREET, static::CAM_BACKYARD]);
                } else {
                    list($cam_id, $preset_id) = explode(':', $payload);
                    $files = $this->getSnapshots([$cam_id], $preset_id);
                }

                foreach ($files as $file) {
                    $data['photo'] = Request::encodeFile($file);
                    $result = Request::sendPhoto($data);
                }
        }

        return $result;
    }

    protected function getSnapshots($cam_ids, $preset_id = 0)
    {
        $files = [];
        $synology = $this->config['synology'];

        foreach ($cam_ids as $cam_id) {
            if (!empty($preset_id)) {
                $synology->setPosition($cam_id, $preset_id);
                sleep(10);
            }
            $files[$cam_id] = tempnam('/tmp', 'cam_' . $cam_id);
            file_put_contents($files[$cam_id], $synology->getSnapshot($cam_id));
        }

        return $files;
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