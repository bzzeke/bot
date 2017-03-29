<?php

namespace Bot;

class ChatStorage {
    static public function set($id)
    {
        $data = self::get();

        if (empty($data[$id])) {
            $data[$id] = true;
            static::save($data);
        }

        return $id;
    }

    static public function get()
    {
        $data = [];
        if (file_exists(APP_DIR . '/var/storage')) {
            $data = json_decode(file_get_contents(APP_DIR . '/var/storage'), true);
        }

        return $data;
    }

    static protected function save($data)
    {
        if (!is_dir(APP_DIR . '/var')) {
            mkdir(APP_DIR . '/var');
        }

        file_put_contents(APP_DIR . '/var/storage', json_encode($data));
    }
}