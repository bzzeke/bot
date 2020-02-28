<?php

namespace Bot\Renderer;

class Markdown
{
    protected $data = [];
    protected $widgets = [];

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

    public function __construct($data, $widgets)
    {
        $this->data = $data;
        $this->widgets = $widgets;
    }

    public function render()
    {
        $text = [];
        foreach ($this->widgets as $widget) {
            $text[] = sprintf("\n*%s*", $widget["title"]);
            foreach ($widget["controls"] as $control) {
                if (!empty($control["format"])) {
                    $text[] = sprintf("%s: %s", $control["title"], $this->formatValue($control["format"], $this->data[$control["statusTopic"]]));
                } else {
                    $text[] = sprintf("%s: %s", $control["title"], $this->data[$control["statusTopic"]]);
                }
            }
        }

        return implode("\n", $text);
    }

    protected function formatValue($format, $value)
    {
        if (in_array($format, ["temperature", "pressure", "energy"])) {
            return sprintf("%.1f", $value);

        } elseif (in_array($format, ["humidity", "ppm", "ppb", "voltage", "power", "rpm"])) {
            return sprintf("%d", $value);

        } elseif ($format == "current") {
            return sprintf("%.2f", $value);

        } elseif ($format == "datediff") {
            return sprintf("%d", (time() - $value) / 60);

        } elseif ($format == "hours") {
            $hours = (int)($value / 3600);
            $minutes = (int)(($value - $hours * 3600) / 60);

            return sprintf("%02d:%02d", $hours, $minutes);
        } elseif ($format == "checkbox") {
            return !empty($value) ? "☑️": "⚪️";
        }
    }
}
