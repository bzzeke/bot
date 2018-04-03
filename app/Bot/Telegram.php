<?php

namespace Bot\Telegram;

class Telegram extends Longman\TelegramBot\Telegram
{
    public function processUpdate(Update $update)
    {
        $this->update = $update;

        if ($this->isAdmin()) {
            return parent::processUpdate($update);
        }
    }
}
