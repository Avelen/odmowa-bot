<?php

/**
 * User "/needhelp" command
 *
 * Example of the Conversation functionality in form of a simple needhelp.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class NeedhelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'needhelp';

    /**
     * @var string
     */
    protected $description = 'У тебя письмо или отказ';

    /**
     * @var string
     */
    protected $usage = '/needhelp';

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

        $chat           = $message->getChat();
        $user           = $message->getFrom();
        $user_name      = $user->getUsername();
        $user_login     = $user->getFirstName();
        $text           = trim($message->getText(true));
        $chat_id        = $chat->getId();
        $user_id        = $user->getId();

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

                $firstmessage  = '👋 Мы сообщество неравнодушных людей и создали чат и бота, чтобы помогать людям с отказами, потому что сами в этой лодке.' . PHP_EOL;
                $firstmessage .= '🎯 Наша цель - обьединиться и попробовать решить проблему с отказами.' . PHP_EOL;
                $firstmessage .= '✅ Мы пишем статьи в СМИ, рассказываем о нашей проблеме и стараемся привлечь как можно больше правозащитных организаций.' . PHP_EOL;
                $firstmessage .= 'Для этого мы собираем всех людей с отказами, чтобы вместе бороться за наши права.' . PHP_EOL;
                $firstmessage .= PHP_EOL . '❗️ Сейчас я задам тебе несколько вопросов, постарайся ответить на них как можно точно.' . PHP_EOL;
                $firstmessage .= 'Нам это нужно для составления статистики и для понимания масштабов проблемы.' . PHP_EOL;
                $firstmessage .= 'Все, что ты здесь напишешь хранится за семью замками, поэтому можешь смело писать все как есть.' . PHP_EOL;
                $firstmessage .= PHP_EOL . '❗️ Мы *НИКОМУ* не передаем никаких личных данных без согласования с владельцем.' . PHP_EOL;
                $firstmessage .= PHP_EOL . 'Если ты ошибся при заполнении, то можешь отменить диалог с помощью команды /cancel и заново запустить опрос командой /needhelp';

                Request::sendMessage([
                    'chat_id'       => $user_id,
                    'text'          => $firstmessage,
                    'parse_mode'    => 'markdown',
                ]);

                $text = '';

            // No break!
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'На какую карту по счету была подача?' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['card_number'] = $text;
                $text                 = '';
            
            case 2:
                if ($text === '') {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Месяц, год подачи' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['date_of_document_feeding'] = $text;
                $text                              = '';
            
            case 3:
                if ($text === '') {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['text'] = 'В каком воевудстве подавался?' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['vojewodstwo'] = $text;
                $text   = '';

            case 4:
                if ($text === '') {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['text'] = 'Цель получения карты?' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['cel_poluchenia'] = $text;
                $text   = '';

            case 5:
                if ($text === '') {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['text'] = 'Месяц, год сдачи отпечатков' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['otpechatki'] = $text;
                $text   = '';
            
            case 6:
                if ($text === '') {
                    $notes['state'] = 6;
                    $this->conversation->update();

                    $data['text'] = 'Месяц, год письма от ABW (если не было, то напиши - письма не было)' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['abw_list'] = $text;
                $text   = '';

            case 7:
                if ($text === '') {
                    $notes['state'] = 7;
                    $this->conversation->update();

                    $data['text'] = 'Месяц, год когда выписана децизия (если децизии еще нет, то напиши - децизии еще нет)' . PHP_EOL;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['decyzia'] = $text;
                $text   = '';

            case 8:
                if ($text === '') {
                    $notes['state'] = 8;
                    $this->conversation->update();

                    $data['text']  = '❗️ Мы собираем документы для статистики и подтверждения того, что отказ реален.' . PHP_EOL;
                    $data['text'] .= PHP_EOL. '✅ Все, кто приложил свои документы, попадают в статистику как подтвержденные случаи.';
                    $data['text'] .= PHP_EOL. '✅ Всех, кто приложил документы, мы приглашаем в закрытый чат только для отказников.';
                    $data['text'] .= PHP_EOL. 'В этом чате мы составляем коллективные письма в правозащитные организации, делимся контактами юристов и помогаем дополнительными консультациями. Информация в закрытом чате появляется быстрее чем в основном чате "Мы не угроза для Польши".' .PHP_EOL;
                    $data['text'] .= PHP_EOL . '‼️ Те, кто сообщил об отказе, но не приложил документов, мы оставляем в статистике как не подтвержденные.' . PHP_EOL;
                    $data['text'] .= PHP_EOL . '❔ *Хочешь приложить письмо/децизию?*' . PHP_EOL;

                    $data['parse_mode'] = 'markdown';
                    $data['reply_markup'] = (new Keyboard(['Да', 'Нет']))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['send_file'] = $text;

            case 9:
                $message_type = $message->getType();

                if (($text != 'Нет') && (!in_array($message_type, ['audio', 'document', 'photo', 'video', 'voice'], true))) {
                    $notes['state'] = 9;
                    $this->conversation->update();
                    $data['text']  = 'Сейчас ты можешь выслать файл.' . PHP_EOL;
                    $data['text'] .= 'Это может быть первая страница децизии (либо 1 pdf файл со всеми страницами), либо первая страница письма.'. PHP_EOL;
                    $data['text'] .= '❗️ Обязательно оставь не замазанными имя и фамилию, чтобы мы могли потом тебя найти.';
                    return Request::sendMessage($data);
                    break;
    
                } else if(in_array($message_type, ['audio', 'document', 'photo', 'video', 'voice'], true)){
                    $notes['state'] = 9;
                    $this->conversation->update();
                    $doc = $message->{'get' . ucfirst($message_type)}();

                    // For photos, get the best quality!
                    ($message_type === 'photo') && $doc = end($doc);

                    $file_id = $doc->getFileId();
                    $file    = Request::getFile(['file_id' => $file_id]);
                    if ($file->isOk() && Request::downloadFile($file->getResult())) {
                        $data['text'] = '✅ Файл загружен. Для продолжения напиши, что-нибудь.';
                        $notes['state'] = 10;
                    } else {
                        $data['text'] = '❌ Ошибка загрузки.';
                    }
                    $notes['photo_name'] = $file->getResult()->getFilePath();

                    $this->conversation->notes['file_id'] = $file_id;
                    $this->conversation->update();

                    $result = Request::sendMessage($data);

                    break;

                } else if($text == 'Нет'){
                    $this->conversation->update();
                    $out_text = 'Пользователь: id' . $user_id . PHP_EOL;
                    $out_text .= 'Ник: @' . $user_name . PHP_EOL;
                    $out_text .= 'Имя: ' . $user_login . PHP_EOL;
                    $out_text .= PHP_EOL .'Получил отказ или письмо:';
    
                    unset($notes['state']);
                    foreach ($notes as $k => $v) {
                        $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                    }
    
                    $data['chat_id']   = $this->config['chatId'];
                    $data['text'] = $out_text;
    
                    $this->conversation->stop();
                    $result = Request::sendMessage($data);

                    $welcomeMes  = 'Спасибо! 🙏' . PHP_EOL;
                    $welcomeMes .= 'Напиши, пожалуйста, @Odmowaru что ты прошел этот опрос.' . PHP_EOL;
                    $welcomeMes .= 'Мы тебе напишем, как только наш админ обработает твои данные.' . PHP_EOL;
                    $welcomeMes .= PHP_EOL . 'Все отправленные данные будут в полной сохранности и ни в коем случае не будут никому переданы без твоего согласия.' . PHP_EOL;
                    $welcomeMes .= 'Спасибо, что доверяешь нам, вместе мы сможем громче заявить о проблеме отказов и попытаемся что-то изменить!' . PHP_EOL;

                    $welcomeMes .= PHP_EOL . 'Пока админ не написал тебе, приходи в наш чат.' . PHP_EOL;

                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text'    => $welcomeMes,
                        'parse_mode' => 'markdown',
                    ]);

                    $data['chat_id'] = $user_id;
                    $data['text'] = 'Выбери, что хочешь сделать еще';
                    $data['reply_markup'] = (new Keyboard(['Хочу помочь', 'Перейти в чат "Мы не угроза для Польши"']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    return Request::sendMessage($data);
    
                    break;
                }
                
                $text   = '';

            case 10:
                $this->conversation->update();
                $out_text = 'Пользователь: id' . $user_id . PHP_EOL;
                $out_text .= 'Ник: @' . $user_name . PHP_EOL;
                $out_text .= 'Имя: ' . $user_login . PHP_EOL;
                $out_text .= PHP_EOL .'Получил отказ или письмо:';

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                $data['chat_id']   = $this->config['chatId'];
                $data['text'] = $out_text;

                $this->conversation->stop();
                $result = Request::sendMessage($data);

                $welcomeMes  = 'Спасибо! 🙏' . PHP_EOL;
                $welcomeMes .= 'Напиши, пожалуйста, @Odmowaru что ты прошел этот опрос.' . PHP_EOL;
                $welcomeMes .= 'Мы тебе напишем, как только наш админ обработает твои данные.' . PHP_EOL;
                $welcomeMes .= 'Запроси, пожалуйста, доступ в закрытый чат, чтобы мы тебя добавили.' . PHP_EOL;
                $welcomeMes .= PHP_EOL . 'Все отправленные данные будут в полной сохранности и ни в коем случае не будут никому переданы без твоего согласия.' . PHP_EOL;
                $welcomeMes .= 'Спасибо, что доверяешь нам, вместе мы сможем громче заявить о проблеме отказов и попытаемся что-то изменить!' . PHP_EOL;

                Request::sendMessage([
                    'chat_id' => $user_id,
                    'text'    => $welcomeMes,
                    'parse_mode' => 'markdown',
                ]);

                $data['chat_id'] = $user_id;
                $data['text'] = 'Выбери, что хочешь сделать еще';
                $data['reply_markup'] = (new Keyboard(['Хочу помочь', 'Перейти в чат "Мы не угроза для Польши"']))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                return Request::sendMessage($data);

                break;
        }

        return $result;
    }
}
