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
use Longman\TelegramBot\Request;
use Bot\Synology\SurveillanceStation\Api;
use Bot\ChatStorage;

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
    protected $version = '0.1.0';


    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $keyboard = new Keyboard($this->config['keyboards']);
        $keyboard->setResizeKeyboard(true);

        foreach ($this->getSnapshots() as $cam_id => $file) {
            Request::sendPhoto([
                'chat_id' => ChatStorage::set($this->getMessage()->getChat()->getId()),
                'caption' => 'Cam #' . $cam_id,
                'reply_markup' => $keyboard,
                'photo' => Request::encodeFile($file)
            ]);
        }
    }

    protected function getSnapshots()
    {
        $cam_ids = [1,2];
        $files = [];
        $synology = new Api(getenv('SYNOLOGY_HOST'), 5000, 'http', 1);
        $synology->connect(getenv('SYNOLOGY_USER'), getenv('SYNOLOGY_PASSWORD'));

        foreach ($cam_ids as $cam_id) {
            $files[$cam_id] = tempnam('/tmp', 'cam_' . $cam_id);
            file_put_contents($files[$cam_id], $synology->getSnapshot($cam_id));
        }

        return $files;
    }
}