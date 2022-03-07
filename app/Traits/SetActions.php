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
        if (is_null($action) || !class_exists($action)) {
            $this->deleteMessages();
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Что-то пошло не так, пожалуйста нажмите на /start')
            ]);
        }

        (new $action($this->telegram, $this->updates))->index();
    }

    /**
     * @param string $file
     * @param string $line
     * @param string $message
     * @return void
     */
    public function sendErrorToAdmin(string $file, string $line, string $message)
    {
        $this->telegram->send('sendMessage', [
            'chat_id' => 287956415,
            'text' => $file . PHP_EOL . $line . PHP_EOL . $message
        ]);
    }
}
