<?php


namespace App\Modules\Telegram;


use App\Modules\Telegram\Updates\CallbackQuery;
use App\Modules\Telegram\Updates\Message;
use App\Modules\Telegram\Updates\MyChatMember;

class WebhookUpdates
{

    /**
     * @var mixed
     */
    private $updates;

    private $update_methods = [
        'message',
        'edited_message',
        'channel_post',
        'edited_channel_post',
        'inline_query',
        'callback_query',
        'shipping_query',
        'my_chat_member',
        'chat_member',
    ];

    /**
     * WebhookUpdates constructor.
     * @param string $updates
     */
    public function __construct(string $updates)
    {
        $this->updates = json_decode($updates, true);
    }

    public function json()
    {
        return $this->updates;
    }

    public function body()
    {
        return json_encode($this->updates);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        foreach ($this->update_methods as $method) {
            if (isset($this->json()[$method])) {
                return $method;
            }
        }
        return false;
    }

    /**
     * @return Message
     */
    public function message(): Message
    {
        return new Message($this->updates['message']);
    }

    /**
     * @return Message
     */
    public function editedMessage(): Message
    {
        return new Message($this->updates['edited_message']);
    }

    /**
     * @return bool
     */
    public function isCallbackQuery(): bool
    {
        return isset($this->updates['callback_query']);
    }

    /**
     * @return CallbackQuery
     */
    public function callbackQuery(): CallbackQuery
    {
        return new CallbackQuery($this->updates['callback_query']);
    }

    /**
     * @return bool
     */
    public function isChatMember(): bool
    {
        return isset($this->updates['my_chat_member']);
    }

    /**
     * @return MyChatMember
     */
    public function myChatMember(): MyChatMember
    {
        return new MyChatMember($this->updates['my_chat_member']);
    }

    /**
     * @return null|int
     */
    public function chat(): ?int
    {
        switch ($this->getMethod()) {
            case 'message':
                return $this->message()->chat()->id();
            case 'my_chat_member':
                return $this->myChatMember()->chat()->id();
            case 'callback_query':
                return $this->callbackQuery()->message()->chat()->id();
            default:
                return null;
        }
    }

    /**
     * @return null|string
     */
    public function text(): ?string
    {
        switch ($this->getMethod()) {
            case 'message':
                return $this->message()->getText();
            case 'callback_query':
                return $this->callbackQuery()->message()->getText();
            default:
                return null;
        }
    }


    /**
     * @return bool
     */
    public function isChannel(): bool
    {
        return isset($this->updates['channel_post']) || isset($this->updates['edited_channel_post']);
    }

    /**
     * @return bool
     */
    public function isGroup(): bool
    {
        return isset($this->updates['message']) && in_array($this->message()->chat()->type(), ['group', 'supergroup']);
    }


    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return isset($this->updates['message']) && $this->message()->chat()->type() === 'private';
    }
}
