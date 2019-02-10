<?php

namespace Bot;

class Storage {
    protected $data = [];

    public function __construct()
    {
        if (file_exists(APP_DIR . '/var/storage')) {
            $this->data = json_decode(file_get_contents(APP_DIR . '/var/storage'), true);
        }
    }

    public function __destruct()
    {
        if (!is_dir(APP_DIR . '/var')) {
            mkdir(APP_DIR . '/var');
        }

        file_put_contents(APP_DIR . '/var/storage', json_encode($this->data));
    }

    public function set($section, $key, $value)
    {
        if (empty($this->data[$section])) {
            $this->data[$section] = array();
        }

        $this->data[$section][$key] = $value;

        return true;
    }

    public function get($section, $key = null)
    {
        if ($key == null) {
            return !empty($this->data[$section]) ? $this->data[$section] : [];
        }

        return !empty($this->data[$section]) ? $this->data[$section][$key] : null;
    }
}