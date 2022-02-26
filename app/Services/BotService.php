<?php

namespace App\Services;

use App\Models\Action;
use App\Modules\Telegram\BotUser;
use App\Modules\Telegram\CheckUpdateType;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Keyboards;
use App\Telegram\Updates\Message;

/**
 * Class BotService
 * @package App\Services
 */
class BotService
{
    /**
     * @var Telegram
     */
    public $telegram;
    /**
     * @var WebhookUpdates
     */
    public $updates;
    /**
     * @var mixed
     */
    private $json;
    /**
     * @var int|null
     */
    public $chat_id;
    /**
     * @var string|null
     */
    public $text;

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        $this->telegram = $telegram;
        $this->updates = $updates;
        $this->json = $this->updates->json();

        $this->chat_id = $this->updates->chat();
        $this->text = $this->updates->text();
    }

    public function init()
    {
        if (CheckUpdateType::isChatMember($this->json)) {
            //
        } elseif (CheckUpdateType::isMessage($this->json)) {
            (new Message($this->telegram, $this->updates))->index();
        } elseif (CheckUpdateType::isCallbackQuery($this->json)) {
            //
        }
    }

    /**
     * @return Action
     */
    public function action(): Action
    {
        return Action::firstOrCreate([
            'bot_user_id' => $this->chat_id
        ]);
    }

    /**
     * @return BotUser
     */
    public function botUser(): BotUser
    {
        return new BotUser($this->chat_id);
    }

    /**
     * @return \App\Models\BotUser|null
     */
    public function fetchUser(): ?\App\Models\BotUser
    {
        return $this->botUser()->fetchUser();
    }

    /**
     * @return array|mixed
     */
    public function sendMainMenu()
    {
        $this->action()->update([
            'action' => null,
            'sub_action' => null
        ]);

        $keyboard = new ReplyMarkup();
        return $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Bo\'limni tanlang'),
            'reply_markup' => $keyboard
                ->resizeKeyboard()
                ->oneTimeKeyboard()
                ->keyboard(Keyboards::mainMenuButtons())
        ]);
    }
}
