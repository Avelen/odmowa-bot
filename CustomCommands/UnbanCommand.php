<?php

/**
 * User "/unban" command
 *
 * Example of the Conversation functionality in form of a simple unban.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class UnbanCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'unban';

    /**
     * @var string
     */
    protected $description = 'Попросить о разбане';

    /**
     * @var string
     */
    protected $usage = '/unban';

    /**
     * @var string
     */
    protected $version = '0.4.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var Conversation
     */
    protected $conversation;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat       = $message->getChat();
        $user       = $message->getFrom();
        $user_name  = $user->getUsername();
        $user_login = $user->getFirstName();
        $text       = trim($message->getText(true));
        $chat_id    = $chat->getId();
        $user_id    = $user->getId();

        // Preparing response
        $data = [
            'chat_id'      => $chat_id,
            // Remove any keyboard by default
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            // Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        // Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        // Load any existing notes from this conversation
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        // Load the current state of the conversation
        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();

        // State machine
        // Every time a step is achieved the state is updated
        switch ($state) {
            case 0:

                $text = '';

            // No break!
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Расскажи, что по твоему мнению могло послужить причиной бана и почему тебя нужно разбанить?';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['question'] = $text;
                $text             = '';

            case 2:
                $this->conversation->update();
                $out_text = 'Пользователь: id' . $user_id . PHP_EOL;
                $out_text .= 'Ник: @' . $user_name . PHP_EOL;
                $out_text .= 'Имя: ' . $user_login . PHP_EOL;
                $out_text .= PHP_EOL .'Просит о разбане:';

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . $v;
                }

                $data['chat_id']   = $this->config['chatId'];
                $data['text'] = $out_text;

                $this->conversation->stop();
                $result = Request::sendMessage($data);

                return Request::sendMessage([
                    'chat_id' => $user_id,
                    'text'    => 'Спасибо. Я передал твое сообщение админам.',
                ]);

                break;
        }

        return $result;
    }
}
