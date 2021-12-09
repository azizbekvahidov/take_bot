<?php


namespace App\Telegram;


use App\Constants\ActionConstants;
use App\Constants\MainMenuButtons;
use App\Constants\MessageTypeConstants;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;

class MainMenu extends BotService
{
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
        if (!in_array($this->action()->action, ActionConstants::mainActionsList())
            || in_array($this->text, MainMenuButtons::list())
        ) {
            $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
            $this->action()->update([
                'action' => ActionConstants::getActionWithMainMenuButton($this->text),
                'sub_action' => null
            ]);
        }
    }

    public function index()
    {
        $action = $this->action()->action;
        (new $action($this->telegram, $this->updates))->index();
    }
}
