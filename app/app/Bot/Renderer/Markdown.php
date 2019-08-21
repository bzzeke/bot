<?php

namespace Bot\Renderer;

class Markdown
{
    protected $data = [];

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

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render()
    {
        foreach ($this->data as $name => $value) {
            if (isset($this->placeholder_types[$name])) {
                switch ($this->placeholder_types[$name]) {
                    case 'bool':
                        $this->data[$name] = (int)$value == 0 ? 'off' : 'on';
                    break;

                    case 'hours':
                        $this->data[$name] = (int)$value > 0 ? sprintf('%.1f hr', $value / 60 / 60) : 'n/a';
                    break;
                }
            }
        }

        return strtr($this->formatted_msg, array_combine(array_map(function($v) {
            return '[' . $v . ']';
        }, array_keys($this->data)), $this->data));
    }
}
