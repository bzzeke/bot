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
use Bot\Mqtt;
use Bot\ChatStorage;

/**
 * User "/forcereply" command
 */
class TempCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'temp';
    protected $description = 'Get temperature';
    protected $usage = '/temp';
    protected $version = '0.1.0';
    protected $mqtt;
    protected $text = '';

    protected $topics = array(
        'Floor 1 Temperature' => null,
        'Floor 2 Temperature' => null,
        'Basement Temperature' => null,
        'Garret Temperature' => null,
        'Outside Temperature' => null,
        'Boiler Out Temperature' => null
    );

    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->mqtt = new Mqtt(getenv('MQTT_HOST'), 1883, "WB Delyanka");

        if(!$this->mqtt->connect()){
            echo('Failed to connecto to MQTT');
            return false;
        }

        $topics = array(
            '/devices/thermostat/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            )
        );

        $this->mqtt->subscribe($topics, 0);
        while ($this->mqtt->proc()) {
        }

        echo('done');

        $keyboard = new Keyboard($this->config['keyboards']);
        $keyboard->setResizeKeyboard(true);

        return Request::sendMessage([
            'chat_id' => ChatStorage::set($this->getMessage()->getChat()->getId()),
            'text' => $this->text,
            'reply_markup' => $keyboard
        ]);
    }

    public function processmsg($topic, $msg)
    {
        $topic_array = explode('/', $topic);
        $topic = array_pop($topic_array);

        if (array_key_exists($topic, $this->topics)) {
            $this->topics[$topic] = $msg;
        }

        $text = array();
        foreach ($this->topics as $t => $val) {
            if (is_null($val)) {
                return;
            }

            $text[] = $t . ': ' . $val;
        }

        $this->text = implode("\n", $text);
        $this->mqtt->close();
    }
}