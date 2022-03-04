<?php

namespace App\Telegram\Updates;

use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;
use App\Telegram\BotCreate;
use Illuminate\Support\Str;

class Message extends BotService
{
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
    }

    /**
     * @return array|mixed|void
     */
    public function index()
    {
        if (Str::lower($this->text) === '/start') {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Assalomu alaykum')
            ]);
            return $this->sendMainMenu();
        }

        $this->setActions();
    }
}
