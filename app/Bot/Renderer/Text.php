<?php

namespace Bot\Renderer;

class Text
{
    protected $data = [];

    protected $formatted_msg = <<<EOT
Первый этаж: насос [floor1_pump], температура [floor1_temp], нагрев до [floor1].
Второй этаж: насос [floor2_pump], температура [floor2_temp], нагрев до [floor2].
Подвал: насос [basement_pump], температура [basement_temp], нагрев до [basement].
Котел: [boiler_relay], температура [boiler_temp].
Простой режим - [simple], режим котла [enabled].
Давление в котле - [pressure] бар.
Время работы котла - [work_time].
Чердак - [garret_temp].
Улица - [outside_temp].
Баня - [bathhouse_temp].
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
        'work_time' => 'hours',
        'floor1_temp' => 'int',
        'floor1' => 'int',
        'floor2_temp' => 'int',
        'floor2' => 'int',
        'basement_temp' => 'int',
        'basement' => 'int',
        'boiler_temp' => 'int',
        'garret_temp' => 'int',
        'outside_temp' => 'int',
        'bathhouse_temp' => 'int',
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

                    case 'int':
                        $this->data[$name] = (int)$value;
                    break;
                }
            }
        }

        return strtr($this->formatted_msg, array_combine(array_map(function($v) {
            return '[' . $v . ']';
        }, array_keys($this->data)), $this->data));
    }
}
