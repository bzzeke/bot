<?php

namespace Bot;

class Util {
    static public function d()
    {
        static $i = 1;
        $args = func_get_args();


        if (defined('CONSOLE')) {

            echo(PHP_EOL);
            foreach ($args as $v) {
                echo(print_r($v, true) . PHP_EOL);
            }
            echo(PHP_EOL);

        } else {

            echo('<ol style="font-family: Courier; font-size: 12px; border: 1px solid #dedede; background-color: #efefef; float: left; padding-right: 20px;" start="' . $i . '">');
            foreach ($args as $v) {
                $i++;
                echo('<li><pre>' . htmlspecialchars(print_r($v, true)) . "\n" . '</pre></li>');
            }
            echo('</ol><div style="clear:left;"></div>');
        }
    }
}