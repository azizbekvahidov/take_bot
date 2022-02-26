<?php

namespace App\Telegram;

use App\Constants\LanguageConstant;
use App\Constants\MethodConstant;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Updates\Message;
use Exception;


class BotCreate extends Message
{
    /**
     * @param Telegram $telegram
     * @param WebhookUpdates $updates
     */
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);

        if ($this->action()->action !== self::class) {
            $this->action()->update([
                'action' => self::class,
                'sub_action' => MethodConstant::SEND_LANGUAGES_LIST
            ]);
        }
    }

    /**
     * @return void
     */
    public function index()
    {
        $method = $this->action()->sub_action;
        if (method_exists($this, $method)) {
            try {
                $this->$method();
            } catch (Exception $exception) {
                info($exception->getMessage());
            }
        }
    }

    /**
     * Метод отправляет список языков
     * @return void
     */
    public function sendLanguagesList()
    {
        $keyboard = new ReplyMarkup();
        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Tilni tanlang'),
            'reply_markup' => $keyboard->oneTimeKeyboard()
                ->resizeKeyboard()
                ->keyboard(Keyboards::langs())
        ]);

        $this->action()->update([
            'sub_action' => MethodConstant::GET_LANGUAGE_SEND_NAME_REQUEST
        ]);
    }

    /**
     * Метод получает язык и отправляет запрос на имя
     * @return array|mixed|void
     */
    public function getLanguageSendNameRequest()
    {
        if (!in_array($this->text, LanguageConstant::list())) {
            $keyboard = new ReplyMarkup();
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('To\'g\'ri tilni tanlang'),
                'reply_markup' => $keyboard->oneTimeKeyboard()
                    ->resizeKeyboard()
                    ->keyboard(Keyboards::langs())
            ]);
        }

        $this->fetchUser()->update([
            'language' => LanguageConstant::getKey()[$this->text],
        ]);

        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Ismingizni kiriting')
        ]);

        $this->action()->update([
            'sub_action' => MethodConstant::GET_NAME_SEND_PHONE_REQUEST
        ]);
    }

    /**
     * Метод получает имя и отправляет запрос на номер телефона
     * @return array|mixed|void
     */
    public function getNameSendPhoneRequest()
    {
        if ($this->updates->message()->isFile()) {
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Ismingizni kiriting')
            ]);
        }
        if (mb_strlen($this->text) > 200) {
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Ismingizni to\'g\'ri kiriting (200 dan ko\'p harf kiritish mumkin emas)')
            ]);
        }

        $this->fetchUser()->update([
            'name' => $this->text,
        ]);

        $keyboard = new ReplyMarkup();
        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Telefon raqamingizni +998YYXXXXXXX formatida kiriting yoki "Raqamni ulashish" tugmasini bosing"'),
            'reply_markup' => $keyboard->resizeKeyboard()
                ->oneTimeKeyboard()
                ->keyboard(Keyboards::sendPhoneRequest())
        ]);

        $this->action()->update([
            'sub_action' => MethodConstant::GET_PHONE_FINISH_REGISTRATION
        ]);
    }

    /**
     * Метод получает имя и закончит регистрацию
     * @return array|mixed|void
     */
    public function getPhoneFinishRegistration()
    {
        dump(preg_match('/^\+998\d{9}$/', $this->text));
        if (
            $this->updates->message()->isFile()
            || (
                !$this->updates->message()->isContact()
                && !preg_match('/^\+998\d{9}$/', $this->text)
            )
        ) {
            $keyboard = new ReplyMarkup();
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Telefon raqamingizni +998YYXXXXXXX formatida kiriting yoki "Raqamni ulashish" tugmasini bosing"'),
                'reply_markup' => $keyboard->resizeKeyboard()
                    ->oneTimeKeyboard()
                    ->keyboard(Keyboards::sendPhoneRequest())
            ]);
        }

        $this->fetchUser()->update([
            'phone' => str_replace('+', '', $this->updates->message()->getContact()),
            'is_finished' => true
        ]);

        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Siz ro\'yhatdan muvoffaqiyatli o\'tdingiz')
        ]);

        $this->sendMainMenu();
    }
}
