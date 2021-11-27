<?php


namespace App\Modules\Telegram;


use App\Models\Message;

class MessageLog
{

    /**
     * @var array
     */
    private $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function createLog(string $message_type, ?string $comment = null)
    {
        Message::query()->create([
            'message_id' => $this->message['result']['message_id'],
            'message' => $this->message['result']['text'],
            'bot_user_id' => $this->message['result']['chat']['id'],
            'message_type' => $message_type,
            'comment' => $comment
        ]);
    }
}
