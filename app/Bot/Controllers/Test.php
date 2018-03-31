<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Exception\TelegramException;

class Test extends Controller
{
    public function run()
    {
        $cmd = $this->request->get('cmd');

        try {
            $this->app['telegram']->executeCommand($cmd);
        } catch (TelegramException $e) {
            return $e;
        }
        return 'ok';

    }
}