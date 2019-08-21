<?php

namespace Bot\Controllers;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;

class Hook extends Controller
{
    public function register()
    {
        try {
            $result = $this->app['telegram']->setWebhook(getenv('HOOK_URL'));
            if ($result->isOk()) {
                return $result->getDescription();
            }
        } catch (TelegramException $e) {
            error_log($e->getMessage());
            return $e;
        }
    }

    public function unregister()
    {
        try {
            $result = $this->app['telegram']->deleteWebhook();

            if ($result->isOk()) {
                return $result->getDescription();
            }
        }  catch (TelegramException $e) {
            error_log($e->getMessage());
            return $e;
        }
    }

    public function process()
    {
        try {
            $this->app['telegram']->handle();
        } catch (TelegramException $e) {
            error_log($e->getMessage());
            return $e;
        }
        return 'ok';
    }
}