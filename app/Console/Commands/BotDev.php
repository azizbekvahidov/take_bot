<?php

namespace App\Console\Commands;

use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\Updates\Message;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;
use Illuminate\Console\Command;

class BotDev extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $telegram = new Telegram();
        $last = end($telegram->getUpdates()['result']);
        $last_update_id = $last['update_id'];
        start:
        $updates = array_reverse($telegram->getUpdates(['offset' => $last_update_id])['result']);
        foreach ($updates as $update) {
            if ($last_update_id < $update['update_id']) {
                $last_update_id = $update['update_id'];
                $message = new WebhookUpdates(json_encode($update));
                $start = new BotService($telegram, $message);
                $start->init();
            }
        }
        goto start;

    }
}
