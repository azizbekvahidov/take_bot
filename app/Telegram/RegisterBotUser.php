<?php


namespace App\Telegram;

use App\Constants\ActionConstants;
use App\Constants\ActionMethodConstants;
use App\Constants\LanguageConstants;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;

class RegisterBotUser extends BotService
{

    /**
     * RegisterBotUser constructor.
     * @param Telegram $telegram
     * @param WebhookUpdates $updates
     */
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
        if ($this->action()->action !== ActionConstants::REGISTRATION || $this->text === '/start') {
            $this->action()->update([
                'action' => ActionConstants::REGISTRATION,
                'sub_action' => ActionMethodConstants::REGISTER_SEND_LANGUAGES_LIST
            ]);
        }

    }

    /**
     * Метод отвечает за порядок выполнения всех задач, всех шагов
     */
    public function index()
    {
        $method = explode('.', $this->action()->sub_action)[1];
        if (method_exists($this, $method) && !($this->updates->message()->isFile() || $this->updates->isCallbackQuery())) {
            $this->$method();
        }
    }

    /**
     * Метод отправляет список языков
     */
    public function sendLanguagesList()
    {
        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Tilni kiriting'),
            'reply_markup' => $keyboard->keyboard(Keyboards::languagesList())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_LANGUAGE_SEND_NAME_REQUEST
            ]);
        }
    }

    /**
     * Метод получает язык и отправляет запрос на ввод имени
     */
    public function getLanguageSendNameRequest()
    {

        $this->validation->check('in:🇺🇿,🇷🇺,🇬🇧');

        if ($this->validation->fails()) {
            $this->sendErrorMessages();
            return;
        }
        $lang = LanguageConstants::key($this->text);
        $this->bot_user->fetchUser()->update([
            'language' => $lang
        ]);
        app()->setLocale($lang);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Ismingizni kiriting'),
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_NAME_SEND_PHONE_REQUEST
            ]);
        }
    }

    /**
     * Метод получает имя и отправляет запрос на ввод номера телефона
     */
    public function getNameSendPhoneRequest()
    {
        $this->validation->check('name');

        if ($this->validation->fails()) {
            $this->sendErrorMessages();
            return;
        }

        $keyboard = new ReplyMarkup(true, true);

        $this->bot_user->fetchUser()->update([
            'name' => $this->text
        ]);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Telefon raqamingizni jo\'nating'),
            'reply_markup' => $keyboard->keyboard(Keyboards::phoneRequest())

        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_PHONE_AND_FINISH_REGISTRATION
            ]);
        }
    }

    /**
     * Метод получает номер телефона и закончит регистрацию и отправляет главное меню
     */
    public function registerGetPhoneAndFinishRegistration()
    {

        $validation = $this
            ->validation
            ->attributes($phone = $this
                ->updates
                ->message()
                ->getContact()
            )
            ->check('regex:/^\+?998\d{9}$/', "isContact:{$this->updates->message()->isContact()}");

        if ($validation->fails()) {
            $this->sendErrorMessages();
            return;
        }

        $this->bot_user->fetchUser()->update([
            'phone' => preg_replace("/[+]/", "", $phone),
            'username' => $this->updates->message()->chat()->username(),
            'is_finished' => true,
        ]);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Siz muvaffaqiyatli registratsiyadan o'tdingiz")
        ]);

        if ($message['ok']) {
            $this->sendMainMenu();
        }
    }
}
