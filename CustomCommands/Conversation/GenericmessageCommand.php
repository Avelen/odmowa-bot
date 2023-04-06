<?php

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 *
 * In this conversation-related context, we must ensure that active conversations get executed correctly.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Command execute method if MySQL is required but not available
     *
     * @return ServerResponse
     */
    public function executeNoDb(): ServerResponse
    {
        // Do nothing
        return Request::emptyResponse();
    }

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $message_text = $message->getText(true);

        if($message_text == 'У меня вопрос или предложение'){
            return $this->telegram->executeCommand('survey');
        }

        if($message_text == 'Меня забанили в группе'){
            return $this->telegram->executeCommand('unban');
        }

        if($message_text == 'Хочу помочь'){
            return $this->telegram->executeCommand('canhelp');
        }

        if($message_text == 'У меня письмо или отказ'){
            return $this->telegram->executeCommand('needhelp');
        }

        if($message_text == 'Перейти в чат "Мы не угроза для Польши"'){
            $message  = 'Вот ссылка: Мы не угроза для Польши.';

            return $this->replyToChat($message, [
                'parse_mode' => 'markdown',
            ]);
        }

        if($message_text == 'Посмотреть статистику'){
            $message  = 'Вот ссылка: Статистика.';

            return $this->replyToChat($message, [
                'parse_mode' => 'markdown',
            ]);
        }

        // If a conversation is busy, execute the conversation command after handling the message.
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        // Fetch conversation command if it exists and execute it.
        if ($conversation->exists() && $command = $conversation->getCommand()) {
            return $this->telegram->executeCommand($command);
        }

        

        return Request::emptyResponse();
    }
}
