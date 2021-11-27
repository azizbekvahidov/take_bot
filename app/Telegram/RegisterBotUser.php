<?php


namespace App\Telegram;

use App\Constants\ActionConstants;
use App\Constants\ActionMethodConstants;
use App\Constants\LanguageConstants;
use App\Constants\MessageTypeConstants;
use App\Models\BotUser;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;

class RegisterBotUser extends BotService
{

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

    public function index()
    {
        $method = explode('.', $this->action()->sub_action)[1];
        $this->$method();
    }

    public function sendLanguagesList()
    {
        $keyboard = new ReplyMarkup(true, true);

        if (is_null($this->bot_user->fetchUser())) {
            BotUser::query()->create([
                'chat_id' => $this->chat_id,
            ]);
        }

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Tilni kiriting'),
            'reply_markup' => $keyboard->keyboard(Keyboards::languagesList())
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::REGISTER_SENT_LANGUAGES_LIST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_LANGUAGE_SEND_NAME_REQUEST
            ]);
        }
    }

    public function getLanguageSendNameRequest()
    {

        $this->validation->check('in:🇺🇿,🇷🇺,🇬🇧');

        if ($this->validation->fails()) {
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

        (new MessageLog($message))->createLog(MessageTypeConstants::REGISTER_SENT_NAME_REQUEST);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_NAME_SEND_PHONE_REQUEST
            ]);
        }
    }

    public function getNameSendPhoneRequest()
    {
        $this->validation->check('name');

        if ($this->validation->fails()) {
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

        (new MessageLog($message))->createLog(MessageTypeConstants::REGISTER_SENT_PHONE_REQUEST);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::REGISTER_GET_PHONE_AND_FINISH_REGISTRATION
            ]);
        }
    }

    public function registerGetPhoneAndFinishRegistration()
    {
        $this
            ->validation
            ->attributes($phone = $this
                ->updates
                ->message()
                ->contact()
                ->phoneNumber()
            )
            ->check('regex:/^\+?998\d{9}$/');

        if ($this->validation->fails()) {
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

        (new MessageLog($message))->createLog(MessageTypeConstants::REGISTER_REGISTRATION_FINISHED);

        if ($message['ok']) {
            $this->sendMainMenu();
        }
    }
}
