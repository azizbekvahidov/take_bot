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
        if (!$this->botUser()->isRegistrationFinished()) {
            if (Str::lower($this->text) === '/start') {
                $this->action()->update([
                    'action' => null,
                    'sub_action' => null
                ]);
            }
            return (new BotCreate($this->telegram, $this->updates))->index();

        }

        if (Str::lower($this->text) === '/start') {
            return $this->sendMainMenu();
        }

        $this->setActions();
    }
}
