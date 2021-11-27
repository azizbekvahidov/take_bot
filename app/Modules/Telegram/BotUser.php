<?php


namespace App\Modules\Telegram;

use App\Models\BotUser as BotUserModel;

class BotUser
{

    /**
     * @var int
     */
    private $chat_id;

    /**
     * @var BotUserModel
     */
    private $bot_user;

    public function __construct(int $chat_id)
    {
        $this->chat_id = $chat_id;
        $this->bot_user = $this->getBotUser();
    }

    /**
     * @param string $status
     * @return int
     */
    public function alterChatMember(string $status): int
    {
        if (!in_array($status, [
            'creator',
            'administrator',
            'member',
            'restricted',
            'kicked',
            'left'
        ])) {
            return 0;
        }

        return BotUserModel::query()->update([
            'status' => $status
        ]);
    }

    /**
     * @return bool
     */
    public function isMember(): bool
    {
        return !in_array($this->bot_user->status, [
            'kicked',
            'left'
        ]);
    }

    /**
     * @return ?BotUserModel
     */
    public function fetchUser(): ?BotUserModel
    {
        return $this->bot_user;
    }

    /**
     * @return bool
     */
    public function isRegistrationFinished(): bool
    {
        return $this->bot_user->is_finished ?? false;
    }


    /**
     * @return BotUserModel|null
     */
    private function getBotUser(): ?BotUserModel
    {
        return BotUserModel::where('chat_id', '=', $this->chat_id)->first();
    }
}
