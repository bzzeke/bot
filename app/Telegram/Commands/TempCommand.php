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
 * User "/temp" command
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
        '/devices/thermostat/controls/Floor_1' => 'floor1',
        '/devices/thermostat/controls/Floor_2' => 'floor2',
        '/devices/thermostat/controls/Basement' => 'basement',
        '/devices/thermostat/controls/Floor 1 Temperature' => 'floor1_temp',
        '/devices/thermostat/controls/Floor 2 Temperature' => 'floor2_temp',
        '/devices/thermostat/controls/Basement Temperature' => 'basement_temp',
        '/devices/thermostat/controls/Garret Temperature' => 'garret_temp',
        '/devices/thermostat/controls/Outside Temperature' => 'outside_temp',
        '/devices/thermostat/controls/Boiler Out Temperature' => 'boiler_temp',
        '/devices/thermostat/controls/Bath House Temperature' => 'bathhouse_temp',
        '/devices/thermostat/controls/Simple' => 'simple',
        '/devices/thermostat/controls/Work time' => 'work_time',
        '/devices/thermostat/controls/Pressure' => 'pressure',
        '/devices/thermostat/controls/Enabled' => 'enabled',
        '/devices/wb-mr14_32/controls/K8' => 'boiler_relay',
        '/devices/wb-mr14_32/controls/K1' => 'floor2_pump',
        '/devices/wb-mr14_32/controls/K2' => 'floor1_pump',
        '/devices/wb-mr14_32/controls/K3' => 'basement_pump',
        '/devices/water_supply/controls/Heater' => 'water_heater',
        '/devices/water_supply/controls/Pressure' => 'water_pressure',
    );
    protected $processed_topics = [];

    protected $formatted_msg = <<<EOT
Floor 1: *[floor1_pump]*, [floor1_temp]˚C → [floor1]˚C
Floor 2: *[floor2_pump]*, [floor2_temp]˚C → [floor2]˚C
Basement: *[basement_pump]*, [basement_temp]˚C → [basement]˚C
Boiler: *[boiler_relay]*, [boiler_temp]˚C
Simple mode: *[simple]*
Enabled: *[enabled]*
Pressure: [pressure] bar
Boiler work time: [work_time]

Garret: [garret_temp]˚C
Outside: [outside_temp]˚C
Bath house: [bathhouse_temp]˚C

Water heater: *[water_heater]*
Water pressure: [water_pressure] bar

EOT;

    protected $placeholder_types = [
        'floor2_pump' => 'bool',
        'floor1_pump' => 'bool',
        'basement_pump' => 'bool',
        'boiler_relay' => 'bool',
        'simple' => 'bool',
        'enabled' => 'bool',
        'water_heater' => 'bool',
        'work_time' => 'hours'
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
            '/devices/water_supply/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
        );

        $this->processed_topics = array_fill_keys(array_keys($this->topics), null);
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
        if (array_key_exists($topic, $this->processed_topics)) {
            $this->processed_topics[$topic] = $msg;

            if (isset($this->placeholder_types[$this->topics[$topic]])) {
                switch ($this->placeholder_types[$this->topics[$topic]]) {
                    case 'bool':
                        $this->processed_topics[$topic] = (int)$msg == 0 ? 'off' : 'on';
                    break;

                    case 'hours':
                        $this->processed_topics[$topic] = (int)$msg > 0 ? sprintf('%.1f hr', $msg / 60 / 60) : 'n/a';
                    break;
                }
            }
        }

        $text = array();
        foreach ($this->processed_topics as $t => $val) {
            if (is_null($val)) {
                return;
            }
        }

        $this->text = strtr($this->formatted_msg, array_combine(array_map(function($v) {
            return '[' . $v . ']';
        }, array_values($this->topics)), $this->processed_topics));

        $this->config['mqtt']->close();
    }
}
