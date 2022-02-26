<?php

namespace App\Console\Commands;

use App\Modules\Telegram\Telegram;
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
     * @return void
     */
    public function handle()
    {
        $this->info("Bot started");

        beginning:
        try {
            $telegram = new Telegram();
            $updates = $telegram->getUpdates()['result'];
            $last = end($updates);
            $last_update_id = $last ? $last['update_id'] : 0;
            start:
            $updates = $telegram->getUpdates(['offset' => $last_update_id])['result'];
            foreach ($updates as $update) {
                if ($last_update_id < $update['update_id']) {
                    $this->info("Request");
                    $last_update_id = $update['update_id'];
                    $message = new WebhookUpdates(json_encode($update));
                    $start = new BotService($telegram, $message);
                    $start->init();
                }
            }
            goto start;

        } catch (\Exception $e) {
            info($e->getMessage());
            info($e->getTraceAsString());
            goto beginning;
        }

    }
}
