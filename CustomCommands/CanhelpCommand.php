<?php

/**
 * User "/canhelp" command
 *
 * Example of the Conversation functionality in form of a simple canhelp.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class CanhelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'canhelp';

    /**
     * @var string
     */
    protected $description = 'Записаться в волонтеры';

    /**
     * @var string
     */
    protected $usage = '/canhelp';

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
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

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

                    $data['text'] = 'Вы уже получили отказ или письмо от ABW?';
                    $data['reply_markup'] = (new Keyboard(['Да', 'Нет']))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['odmowa'] = $text;

                if ($text === 'Нет'){
                    $text = '';
                }
            
            case 2:
                if ($text === 'Да') {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['text'] = 'Вы состоите в чате Письма и отказы?';
                    $data['parse_mode'] = 'markdown';
                    $data['reply_markup'] = (new Keyboard(['Да', 'Нет']))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                    $result = Request::sendMessage($data);
                    break;
                } else if ($text === ''){
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Расскажи, чем ты можешь помочь?';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['skils'] = $text;
                $text             = '';

            case 3:
                $this->conversation->update();
                $out_text = 'Пользователь: id' . $user_id . PHP_EOL;
                $out_text .= 'Ник: @' . $user_name . PHP_EOL;
                $out_text .= 'Имя: ' . $user_login . PHP_EOL;
                $out_text .= PHP_EOL .'Хочет записаться в волонтеры:';

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                $data['chat_id']   = $this->config['chatId'];
                $data['text'] = $out_text;

                $this->conversation->stop();
                $result = Request::sendMessage($data);

                return Request::sendMessage([
                    'chat_id' => $user_id,
                    'text'    => 'Спасибо. Теперь можешь запросить доступ в Чат Волонтеров!',
                    'parse_mode' => 'markdown',
                ]);

                break;
            
            case 4:
                $notes['inminichat'] = $text;
            
            case 5:
                $this->conversation->update();
                $out_text = 'Пользователь: id' . $user_id . PHP_EOL;
                $out_text .= 'Ник: @' . $user_name . PHP_EOL;
                $out_text .= 'Имя: ' . $user_login . PHP_EOL;
                $out_text .= PHP_EOL .'Хочет записаться в активисты:';

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                $data['chat_id']   = $this->config['chatId'];
                $data['text'] = $out_text;

                $this->conversation->stop();
                $result = Request::sendMessage($data);

                return Request::sendMessage([
                    'chat_id' => $user_id,
                    'text'    => 'Спасибо. Теперь можешь запросить доступ в Чат Активистов!',
                    'parse_mode' => 'markdown',
                ]);

                break;
        }

        return $result;
    }
}
