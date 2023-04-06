<?php

/**
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 *
 * When using deep-linking, the parameter can be accessed by getting the command text.
 *
 * @see https://core.telegram.org/bots#deep-linking
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {

        $inline_keyboard = new Keyboard([
                ['text' => 'У меня письмо или отказ'],
                ['text' => 'Хочу помочь'],
            ],[
                ['text' => 'Посмотреть статистику'],
                ['text' => 'У меня вопрос или предложение'],
            ],[
                ['text' => 'Перейти в чат "Мы не угроза для Польши"'],
                ['text' => 'Меня забанили в группе'],
            ]
        );

        $message  = 'Привет. Я бот группы Мы не угроза для Польши.' . PHP_EOL;
        $message .= PHP_EOL . 'С помощью меня ты можешь поделиться своей *историей отказа*, *задать вопрос* или *записаться в волонтеры*.' . PHP_EOL;
        $message .= PHP_EOL . 'Если тебя забанили в группе, то тут ты можешь *попросить о разбане*.' . PHP_EOL;

        return $this->replyToChat($message, [
            'parse_mode' => 'markdown',
            'reply_markup' => $inline_keyboard,
        ]);
    }
}
