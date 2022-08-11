<?php

namespace App\Telegram;

use App\Exceptions\ApiServerException;
use App\Exceptions\MenuListEmptyException;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Updates\Message;
use Exception;

class MenuList extends Message
{
    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
    }

    public function index()
    {
        try {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Tez kunda...')
            ]);
        } catch (MenuListEmptyException $exception) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => $exception->getMessage()
            ]);
            $this->sendMainMenu();
        } catch (Exception|ApiServerException $exception) {
            $this->sendErrorToAdmin($exception->getFile(), $exception->getLine(), $exception->getMessage());
        }
    }
}
