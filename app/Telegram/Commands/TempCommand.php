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
    protected $text = '';

    protected $topics = array(
        'Floor_1' => null,
        'Floor_2' => null,
        'Basement' => null,
        'Floor 1 Temperature' => null,
        'Floor 2 Temperature' => null,
        'Basement Temperature' => null,
        'Garret Temperature' => null,
        'Outside Temperature' => null,
        'Boiler Out Temperature' => null,
        'Bath House Temperature' => null,
        'Simple' => null,
        'K8' => null,
        'K1' => null,
        'K2' => null,
        'K3' => null,
        'Water heater' => null,
        'Work time' => null,
        'Pressure' => null,
        'Enabled' => null
    );

    protected $formatted_msg = <<<EOT
Floor 1: *[K2]*, [Floor 1 Temperature]˚C → [Floor_1]˚C
Floor 2: *[K1]*, [Floor 2 Temperature]˚C → [Floor_2]˚C
Basement: *[K3]*, [Basement Temperature]˚C → [Basement]˚C
Boiler: *[K8]*, [Boiler Out Temperature]˚C
Simple mode: *[Simple]*
Enabled: *[Enabled]*
Pressure: [Pressure] bar
Boiler work time: [Work time]

Garret: [Garret Temperature]˚C
Outside: [Outside Temperature]˚C
Bath house: [Bath House Temperature]˚C

Water heater: *[Water heater]*

EOT;

    protected $placeholder_types = [
        'K1' => 'bool',
        'K2' => 'bool',
        'K3' => 'bool',
        'K8' => 'bool',
        'Simple' => 'bool',
        'Enabled' => 'bool',
        'Water heater' => 'bool',
        'Work time' => 'hours'
    ];

    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if(!$this->config['mqtt']->connect()){
            error_log('Failed to connecto to MQTT (temp)');
            return false;
        }

        $topics = array(
            '/devices/thermostat/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
            '/devices/wb-mr14_32/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
        );

        $this->config['mqtt']->subscribe($topics, 0);

        $time_start = time();
        $timeout = 10;
        while ($this->config['mqtt']->proc()) {
            if (time() - $time_start > $timeout) {
                $this->text = 'Mqtt subscription reset by timeout';
                $this->config['mqtt']->close();
                break;
            }
        }

        $keyboard = new Keyboard($this->config['keyboards']);
        $keyboard->setResizeKeyboard(true);

        return Request::sendMessage([
            'chat_id' => ChatStorage::set($this->getMessage()->getChat()->getId()),
            'text' => $this->text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard
        ]);
    }

    public function processmsg($topic, $msg)
    {
        $topic_array = explode('/', $topic);
        $topic = array_pop($topic_array);

        if (array_key_exists($topic, $this->topics)) {
            $this->topics[$topic] = $msg;

            if (isset($this->placeholder_types[$topic])) {
                switch ($this->placeholder_types[$topic]) {
                    case 'bool':
                        $this->topics[$topic] = (int)$msg == 0 ? 'off' : 'on';
                    break;

                    case 'hours':
                        $this->topics[$topic] = (int)$msg > 0 ? sprintf('%.1f hr', $msg / 60 / 60) : 'n/a';
                    break;
                }
            }
        }

        $text = array();
        foreach ($this->topics as $t => $val) {
            if (is_null($val)) {
                return;
            }
        }

        $this->text = strtr($this->formatted_msg, array_combine(array_map(function($v) {
            return '[' . $v . ']';
        }, array_keys($this->topics)), $this->topics));

        $this->config['mqtt']->close();
    }
}
