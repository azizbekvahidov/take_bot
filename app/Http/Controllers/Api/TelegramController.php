<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Telegram\Telegram;
use App\Services\BotService;
use Throwable;

class TelegramController extends Controller
{
    /**
     * Handle the incoming request.
     *
     */
    public function __invoke()
    {
        try {
            $telegram = new Telegram();
            $updates = $telegram->getWebhookUpdates();
            $bot_service = new BotService($telegram, $updates);
            $bot_service->init();
        } catch (Throwable $exception) {
            info($exception->getMessage());
            info($exception->getTraceAsString());
        }

    }
}
