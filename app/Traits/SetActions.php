<?php

namespace App\Traits;

use App\Constants\ActionConstant;
use App\Constants\MainMenuButtons;

trait SetActions
{

    public function setActions()
    {
        if (!in_array($this->action()->action, ActionConstant::mainActionsList())
            || in_array($this->text, MainMenuButtons::list())
        ) {
            $this->deleteMessages();
            $this->action()->update([
                'action' => ActionConstant::getActionWithMainMenuButton($this->text),
                'sub_action' => null
            ]);
        }

        $action = $this->action()->action;
        (new $action($this->telegram, $this->updates))->index();
    }

    /**
     * @param string $message
     * @return void
     */
    public function sendErrorToAdmin(string $message)
    {
        $this->telegram->send('sendMessage', [
            'chat_id' => 287956415,
            'text' => $message
        ]);
    }
}
