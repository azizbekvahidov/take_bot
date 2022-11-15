<?php

namespace App\Http\Controllers;

use App\Modules\Telegram\Telegram;
use Illuminate\Http\Request;

class MrSaddamController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $telegram = new Telegram('5006864791:AAGAZ_YI0_paYIwH9ZUqxSq1fZbRRgrOLxw');
        $updates = $telegram->getWebhookUpdates();
        $telegram->send("sendMessage", [
            "chat_id" => $updates->chat(),
            "text" => "У нас бот поменялся на @mc_messo_bot"
        ]);
    }
}
