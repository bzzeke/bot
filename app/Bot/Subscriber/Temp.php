<?php

namespace Bot\Subscriber;

class Temp
{
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
        '/devices/relays/controls/K8' => 'boiler_relay',
        '/devices/relays/controls/K1' => 'floor1_pump',
        '/devices/relays/controls/K2' => 'floor2_pump',
        '/devices/relays/controls/K3' => 'basement_pump',
        '/devices/water_supply/controls/Heater' => 'water_heater',
        '/devices/water_supply/controls/Pressure' => 'water_pressure',
    );
    protected $processed_topics = [];

    protected $mqtt;

    public function __construct($mqtt)
    {
        $this->mqtt = $mqtt;
    }


    public function run()
    {
        if(!$this->mqtt->connect()){
            error_log('Failed to connecto to MQTT (temp)');
            return false;
        }

        $topics = array(
            '/devices/thermostat/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
            '/devices/relays/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
            '/devices/water_supply/#' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            ),
        );

        $this->processed_topics = array_fill_keys(array_keys($this->topics), null);
        $this->mqtt->subscribe($topics, 0);

        $time_start = time();
        $timeout = 10;
        while ($this->mqtt->proc()) {
            if (time() - $time_start > $timeout) {
                $this->mqtt->close();
                return false;
            }
        }

        return array_combine(array_values($this->topics), $this->processed_topics);
    }

    public function processmsg($topic, $msg)
    {
        if (array_key_exists($topic, $this->processed_topics)) {
            $this->processed_topics[$topic] = $msg;
        }

        foreach ($this->processed_topics as $t => $val) {
            if (is_null($val)) {
                return;
            }
        }

        $this->mqtt->close();
    }
}
