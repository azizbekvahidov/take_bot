<?php

namespace App\Services;

use App\Interfaces\SetActions;
use App\Models\Action;
use App\Modules\Telegram\BotUser;
use App\Modules\Telegram\CheckUpdateType;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Keyboards;
use App\Telegram\Updates\Message;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class BotService
 * @package App\Services
 */
class BotService implements SetActions
{
    use \App\Traits\SetActions;

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
            $data = $this->updates->callbackQuery()->getData();
            list($class, $method, $data) = explode('|', $data);
            (new $class($this->telegram, $this->updates))->$method($data);
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
        $this->deleteMessages();

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

    public function deleteMessages(?int $message_id = null)
    {
        $messages = \App\Models\Message::query()
            ->when($message_id, function (Builder $q) use ($message_id) {
                $q->where('message_id', '=', $message_id);
            })
            ->where([
                'bot_user_id' => $this->chat_id
            ])->get();

        /** @var \App\Models\Message $message */
        foreach ($messages as $message) {
            $method = now()->diffInDays($message->created_at) >= 2
                ? 'editMessageReplyMarkup'
                : 'deleteMessage';

            $this->telegram->send($method, [
                'chat_id' => $this->chat_id,
                'message_id' => $message->message_id
            ]);
            $message->delete();
        }
    }
}
