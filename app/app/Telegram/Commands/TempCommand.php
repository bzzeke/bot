<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

use Bot\Subscriber\Temp;
use Bot\Renderer\Markdown;

/**
 * User "/temp" command
 */
class TempCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'temp';
    protected $description = 'Get temperature';
    protected $usage = '/temp';
    protected $version = '0.1.0';

    /**#@-*/
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $subscriber = new Temp($this->config['mqtt']);
        $result = $subscriber->run();

        if ($result == false) {
            $text = 'Mqtt subscription reset by timeout';
        } else {
            $renderer = new Markdown($result);
            $text = $renderer->render();
        }

        $keyboard = new Keyboard($this->config['keyboards']);
        $keyboard->setResizeKeyboard(true);
        $chat_id = $this->getMessage()->getChat()->getId();
        $this->config['storage']->set('Chats', $chat_id, true);

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard
        ]);
    }
}
