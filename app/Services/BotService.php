<?php

namespace App\Services;

use App\Constants\MessageTypeConstants;
use App\Models\Action;
use App\Models\BotUser;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\Validation\Validation;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Keyboards;
use App\Telegram\RegisterBotUser;

/**
 * Class BotService
 * @package App\Services
 */
class BotService
{
    /**
     * @var Telegram
     */
    protected $telegram;
    protected $updates;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $chat_id;

    /**
     * @var \App\Modules\Telegram\BotUser
     */
    protected $bot_user;
    /**
     * @var Validation
     */
    protected $validation;

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        $this->telegram = $telegram;
        $this->updates = $updates;
        $this->chat_id = $updates->chat();
        $this->text = $updates->text();
        $this->bot_user = new \App\Modules\Telegram\BotUser($this->chat_id);
        $this->validation = new Validation($this->text);
    }

    /**
     * Входной порог бота
     */
    public function init()
    {

        if ($this->updates->isChatMember()) {
            $this->bot_user->alterChatMember($this->updates->myChatMember()->newChatMember()->status());
            return;
        } elseif (!$this->bot_user->isMember()) {
            $this->bot_user->alterChatMember('member');
        }

        if (!$this->bot_user->isRegistrationFinished()) {
            (new RegisterBotUser($this->telegram, $this->updates))->index();
            return;
        }

        if ($this->text === '/start') {
            $this->sendMainMenu();
        }

    }

    /**
     * @return Action
     */
    protected function action(): Action
    {
        return Action::firstOrCreate([
            'bot_user_id' => $this->chat_id
        ]);
    }

    protected function sendMainMenu()
    {
        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Assalomu alaykum"),
            'reply_markup' => $keyboard->keyboard(Keyboards::sendMainMenu())
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::MAIN_MENU);

        if ($message['ok']) {
            $this->action()->update([
                'action' => null,
                'sub_action' => null
            ]);
        }
    }
}
