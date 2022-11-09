<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Telegram\Telegram;
use App\Services\BotService;
use App\Traits\SetActions;
use Throwable;

class TelegramController extends Controller
{
    use SetActions;

    /**
     * @var Telegram
     */
    private $telegram;

    /**
     * Handle the incoming request.
     *
     */
    public function __invoke()
    {
        $this->telegram = new Telegram();
        try {
            $updates = $this->telegram->getWebhookUpdates();
            $bot_service = new BotService($this->telegram, $updates);
            $bot_service->init();
        } catch (Throwable $exception) {
            $this->sendErrorToAdmin($exception->getFile(), $exception->getLine(), $exception->getMessage());
            info($exception->getMessage());
            info($exception->getTraceAsString());
        }

    }
}
