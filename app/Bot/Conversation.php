<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bot;

/**
 * Class Conversation
 *
 * Only one conversation can be active at any one time.
 * A conversation is directly linked to a user, chat and the command that is managing the conversation.
 */
class Conversation
{
    /**
     * All information fetched from the database
     *
     * @var array|null
     */
    protected $conversation;

    /**
     * Notes to be stored
     *
     * @var mixed
     */
    public $notes;


    protected $id;

    /**
     * Command to be executed if the conversation is active
     *
     * @var string
     */
    protected $command;

    protected $state;

    /**
     * Conversation contructor to initialize a new conversation
     *
     * @param int    $user_id
     * @param int    $chat_id
     * @param string $command
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function __construct($user_id, $chat_id, $command = null, $reset = false)
    {
        $this->command = $command;
        $this->id = $user_id . '_' . $chat_id;

        if (($reset == true || !$this->load()) && $command !== null) {
            $this->start();
        }
    }

    /**
     * Load the conversation from the database
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    protected function load()
    {
        //Select an active conversation
        $conversation = $this->selectConversation();
        if (!empty($conversation['command'])) {
            //Pick only the first element
            $this->conversation = $conversation;

            //Load the command from the conversation if it hasn't been passed
            $this->command = $this->command ?: $this->conversation['command'];

            if ($this->command !== $this->conversation['command']) {
                $this->stop();
                error_log('stop');
                return false;
            }

            //Load the conversation notes
            $this->notes = $this->conversation['notes'];
            $this->state = $this->conversation['state'];
        }

        return $this->exists();
    }

    /**
     * Check if the conversation already exists
     *
     * @return bool
     */
    public function exists()
    {
        return ($this->conversation !== null);
    }

    /**
     * Start a new conversation if the current command doesn't have one yet
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    protected function start()
    {
        if ($this->command
            && !$this->exists()
            && $this->updateConversation()
        ) {
            return $this->load();
        }

        return false;
    }

    /**
     * Delete the current conversation
     *
     * Currently the Conversation is not deleted but just set to 'stopped'
     *
     * @return bool
     */
    public function stop()
    {
        $this->conversation    = null;
        $this->notes           = null;
        $this->command         = null;
        $this->state           = null;

        if (file_exists(APP_DIR . '/var/' . $this->id)) {
            unlink(APP_DIR . '/var/' . $this->id);
        }

        return true;
    }


    /**
     * Store the array/variable in the database with json_encode() function
     *
     * @return bool
     */
    public function update()
    {
        if ($this->exists()) {
            if ($this->updateConversation()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the command to execute from the conversation
     *
     * @return string|null
     */
    public function getCommand()
    {
        return $this->command;
    }

    protected function updateConversation()
    {
        if (!is_dir(APP_DIR . '/var')) {
            mkdir(APP_DIR . '/var');
        }

        return file_put_contents(APP_DIR . '/var/' . $this->id, json_encode([
            'command' => $this->command,
            'state' => $this->state,
            'notes' => $this->notes
        ]));
    }

    protected function selectConversation()
    {
        $data = [
            'command' => null,
            'state' => '',
            'notes' => []
        ];

        if (file_exists(APP_DIR . '/var/' . $this->id)) {
            $data = json_decode(file_get_contents(APP_DIR . '/var/' . $this->id), true);
        }

        return $data;

    }

    public function setData($key, $value)
    {
        $this->notes[$key] = $value;
    }

    public function getData($key)
    {
        return isset($this->notes[$key]) ? $this->notes[$key] : '';
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }
}
