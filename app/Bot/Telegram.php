<?php

namespace Bot;

use Longman\TelegramBot\Entities\Update;

class Telegram extends \Longman\TelegramBot\Telegram
{
    public function processUpdate(Update $update)
    {
        $this->update = $update;

        if (!$this->getAdminList() || $this->isAdmin()) {
            return parent::processUpdate($update);
        }
    }

    public function serialize($command, $state, $payload)
    {
        return sprintf('%s,%s:%s', $command, $state, $payload);
    }

    public function unserialize($data)
    {
        list($system, $payload) = explode(':', $data, 2);
        list($command, $state) = explode(',', $system);

        return [$command, $state, $payload];
    }
}
