<?php

namespace App\Console\Commands;

use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;
use App\Traits\SetActions;
use Illuminate\Console\Command;
use Throwable;

class BotDev extends Command
{
    use SetActions;

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
     * @var Telegram
     */
    private $telegram;

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
        $this->telegram = new Telegram();

        beginning:
        try {
            $updates = $this->telegram->getUpdates()['result'];
            $last = end($updates);
            $last_update_id = $last ? $last['update_id'] : 0;
            start:
            $updates = $this->telegram->getUpdates(['offset' => $last_update_id])['result'];
            foreach ($updates as $update) {
                if ($last_update_id < $update['update_id']) {
                    $this->info("Request");
                    $last_update_id = $update['update_id'];
                    $message = new WebhookUpdates(json_encode($update));

                    if ($message->isGroup() || $message->isChannel()) {
                        return;
                    }

                    $start = new BotService($this->telegram, $message);
                    $start->init();
                }
            }
            goto start;

        } catch (Throwable $exception) {
            $this->sendErrorToAdmin(
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage(),
                $updates->json()
            );
            goto beginning;
        }

    }
}
