<?php


namespace App\Modules\Telegram\Updates;

/**
 * Class Chat
 * @package App\Modules\Telegram\Updates
 */
class Chat
{
    /**
     * @var array
     */
    private $chat;

    /**
     * Chat constructor.
     * @param array $chat
     */
    public function __construct(array $chat)
    {
        $this->chat = $chat;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->chat['id'];
    }

}
