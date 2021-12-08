<?php


namespace App\Telegram;


use App\Constants\ActionMethodConstants;
use App\Constants\MessageCommentConstants;
use App\Constants\MessageTypeConstants;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;

class Language extends BotService
{
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);

        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::LANGUAGE_SEND_LANGUAGES_LIST
            ]);
        }
    }

    public function index()
    {
        $method = explode('.', $this->action()->sub_action)[1];
        if (method_exists($this, $method)) {
            $this->$method();
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

        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::LANGUAGE_SEND_LANGUAGE_LIST);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::LANGUAGE_GET_LANGUAGE
            ]);
        }
    }

    public function getLanguage()
    {
        if (!$this->updates->isCallbackQuery()) {
            return;
        }

        $callback = $this->updates->callbackQuery();

        $this->bot_user->fetchUser()->update([
            'language' => $callback->getData(),
        ]);

        app()->setLocale($callback->getData());

        $this->sendMainMenu();
    }
}
