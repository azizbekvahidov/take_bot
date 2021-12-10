<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Telegram\Telegram;
use App\Services\BotService;
use Exception;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws Exception
     */
    public function __invoke()
    {
        $telegram = new Telegram();
        $updates = $telegram->getWebhookUpdates();
        $bot_service = new BotService($telegram, $updates);
        $bot_service->init();
    }
}
