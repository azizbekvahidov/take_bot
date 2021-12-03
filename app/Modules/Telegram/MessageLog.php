<?php


namespace App\Modules\Telegram;


use App\Models\Message;
use Carbon\Carbon;

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

    /**
     * @param string $message_type
     * @param string|null $comment
     * @param bool $is_bot
     */
    public function createLog(string $message_type, ?string $comment = null, bool $is_bot = true)
    {
        $create_message = [];
        $date = null;
        if (isset($this->message[0])) {
            foreach ($this->message as $key => $message) {
                if ($key === 0) {
                    $date = Carbon::createFromTimestamp($message['result']['date']);
                }
                array_push($create_message, [
                    'message_id' => $message['result']['message_id'],
                    'message' => $message['result']['text'] ?? $message['result']['caption'],
                    'bot_user_id' => $message['result']['chat']['id'],
                    'message_type' => $message_type,
                    'comment' => $comment,
                    'is_bot' => $is_bot,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

            }
        } else {
            $date = Carbon::createFromTimestamp($this->message['result']['date']);
            $create_message = [
                [
                    'message_id' => $this->message['result']['message_id'],
                    'message' => $this->message['result']['text'] ?? $this->message['result']['caption'],
                    'bot_user_id' => $this->message['result']['chat']['id'],
                    'message_type' => $message_type,
                    'comment' => $comment,
                    'is_bot' => true,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            ];
        }
        Message::query()->insert($create_message);
    }
}
