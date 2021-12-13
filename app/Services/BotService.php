<?php

namespace App\Services;

use App\Constants\ActionConstants;
use App\Constants\MainMenuButtons;
use App\Constants\MessageTypeConstants;
use App\Models\Action;
use App\Models\Message;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\Validation\Validation;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Keyboards;
use App\Telegram\MainMenu;
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
    /**
     * @var WebhookUpdates $updates
     */
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
    /**
     * @var string
     */
    protected $language;

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        $this->telegram = $telegram;
        $this->updates = $updates;
        $this->chat_id = $updates->chat();
        $this->text = $updates->text();
        $this->bot_user = new \App\Modules\Telegram\BotUser($this->chat_id);
        $this->validation = new Validation($this->text);
        $this->language = $this->setLocale();
    }

    /**
     * Входной порог бота
     * @throws \Exception
     */
    public function init()
    {
        if ($this->updates->isChatMember()) {
            if (!in_array($this->updates->myChatMember()->newChatMember()->status(), [
                'creator',
                'administrator',
                'member'
            ])) {
                $this->bot_user->fetchUser()->delete();
            } else {
                $this->bot_user->alterChatMember($this->updates->myChatMember()->newChatMember()->status());
            }
            return;
        }

        if (!$this->bot_user->isRegistrationFinished()) {
            (new RegisterBotUser($this->telegram, $this->updates))->index();
            return;
        }

        if ($this->text === '/start') {
            $this->telegram->send('sendMessage', [
                "chat_id" => $this->chat_id,
                'text' => __("Assalomu alaykum")
            ]);
            $this->sendMainMenu();
        }

        if (in_array($this->text, MainMenuButtons::list())
            || in_array($this->action()->action, ActionConstants::mainActionsList())
        ) {
            (new MainMenu($this->telegram, $this->updates))->index();
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
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);

        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Bo'limni tanlang"),
            'reply_markup' => $keyboard->keyboard(Keyboards::sendMainMenu())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'action' => null,
                'sub_action' => null
            ]);
        }
    }

    /**
     * Метод задаёт язык для программы
     * @return string
     */
    private function setLocale(): string
    {
        $bot_user = $this->bot_user->fetchUser();
        $language = $bot_user->language ?: "ru";
        app()->setLocale($language);

        return $language;
    }

    protected function sendErrorMessages()
    {
        $message_text = "";
        foreach ($this->validation->details() as $detail) {
            $message_text .= $message_text ? PHP_EOL . $detail : $detail;
        }
        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => $message_text
        ]);
    }

    /**
     * @throws \Exception
     */
    protected function deleteMessage(int $message_id)
    {
        $messages = Message::query()->where('message_id', '=', $message_id)->get();
        if (empty($messages->toArray())) {
            return;
        }
        $this->sendDeleteRequest($messages, false);
    }

    /**
     * @param mixed $message_types
     */
    public function deleteMessages($message_types = "", int $except_message_id = 0)
    {
        $message_query = Message::query()
            ->where('bot_user_id', '=', $this->chat_id)
            ->where('message_id', '!=', $except_message_id);
        if (is_array($message_types)) {
            $message_query->whereIn('message_type', $message_types);
        } else {
            $message_query->where('message_type', '=', $message_types);
        }

        $messages = $message_query->get();
        $this->sendDeleteRequest($messages, true);
    }

    private function sendDeleteRequest($messages, bool $all_messages = false)
    {
        $message = $messages->first();
        $method = "deleteMessage";
        if ($message) {
            if (time() - $message->created_at->timestamp > 60 * 60 * 24 * 2) {
                $method = "editMessageReplyMarkup";
            }
        }
        if (!$all_messages) {
            $request = $this->telegram->send($method, [
                'chat_id' => $this->chat_id,
                'message_id' => $message->message_id
            ]);
        }

        foreach ($messages as $message) {
            if ($all_messages) {
                $request = $this->telegram->send($method, [
                    'chat_id' => $this->chat_id,
                    'message_id' => $message->message_id
                ]);
            }
            $ok = $request['ok'] ?? true;
            if ($ok) {
                $message->delete();
            }
        }
    }
}
