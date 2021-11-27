<?php


namespace App\Modules\Telegram\Updates;

/**
 * Class Message
 * @package App\Modules\Telegram\Updates
 */
class Message
{
    /**
     * @var string[]
     */
    private $file_types = [
        'audio',
        'document',
        'photo',
        'video',
        'sticker',
        'voice',
        'animation',
    ];

    /**
     * @var array
     */
    private $message;

    /**
     * Message constructor.
     * @param array $message
     */
    public function __construct(array $message)
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->message['message_id'];
    }

    /**
     * @return int
     */
    public function getDate(): int
    {
        return $this->message['date'];
    }

    /**
     * @return Chat
     */
    public function chat(): Chat
    {
        return new Chat($this->message['chat']);
    }

    /**
     * @return From
     */
    public function from(): From
    {
        return new From($this->message['from']);
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->message['text'] ?? ($this->message['caption'] ?? "");
    }

    /**
     * @return bool
     */
    public function isContact(): bool
    {
        return isset($this->message['contact']);
    }

    /**
     * @return Contact
     */
    public function contact(): Contact
    {
        return new Contact($this->message['contact']);
    }

    /**
     * @return bool
     */
    public function isFile(): bool
    {
        foreach ($this->file_types as $file_type) {
            if ($set = isset($this->message[$file_type])) {
                return $set;
            }
        }
        return false;
    }
}
