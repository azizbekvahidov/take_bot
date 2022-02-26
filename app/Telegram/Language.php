<?php

namespace App\Telegram;

use App\Constants\LanguageConstant;
use App\Constants\MethodConstant;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Updates\Message;
use Exception;

class Language extends Message
{

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => MethodConstant::SEND_LANGUAGES_LIST
            ]);
        }
    }

    public function index()
    {
        try {
            $method = $this->action()->sub_action;
            if (method_exists($this, $method)) {
                $this->$method();
            }
        } catch (Exception $exception) {
            $this->sendErrorToAdmin($exception->getMessage());
        }
    }

    public function sendLanguagesList()
    {
        $keyboard = new ReplyMarkup();
        $lang = app()->getLocale();
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Tilni tanlang'),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::inlineLanguagesList($lang))
        ]);

        (new MessageLog($message))->createLog();

    }

    public function getLanguage($data)
    {
        if (in_array($data, array_values(LanguageConstant::getKey()))) {
            $this->fetchUser()->update([
                'language' => $data
            ]);
            app()->setLocale($data);
        }

        $this->sendMainMenu();
    }
}
