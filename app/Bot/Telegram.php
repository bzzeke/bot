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
}
