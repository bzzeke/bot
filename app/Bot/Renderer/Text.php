<?php

namespace Bot\Renderer;

class Text
{
    protected $data = [];

    protected $formatted_msg = <<<EOT
Первый этаж: насос [floor1_pump], температура [floor1_temp]˚C, нагрев до [floor1]˚C.
Второй этаж: насос [floor2_pump], температура [floor2_temp]˚C, нагрев до [floor2]˚C.
Подвал: насос [basement_pump], температура [basement_temp]˚C, нагрев до [basement]˚C.
Котел: [boiler_relay], температура [boiler_temp]˚C.
Простой режим - [simple], режим котла [enabled].
Давление в котле - [pressure] бар.
Время работы котла - [work_time].
Чердак -[garret_temp]˚C.
Улица - [outside_temp]˚C.
Баня - [bathhouse_temp]˚C.
Водонагреватель - [water_heater].
Давление воды - [water_pressure] бар.
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
                        $this->data[$name] = (int)$value == 0 ? 'выключен' : 'включен';
                    break;

                    case 'hours':
                        $this->data[$name] = (int)$value > 0 ? sprintf('%.1f часов', $value / 60 / 60) : 'нет данных';
                    break;
                }
            }
        }

        return strtr($this->formatted_msg, array_combine(array_map(function($v) {
            return '[' . $v . ']';
        }, array_keys($this->data)), $this->data));
    }
}
