<?php


namespace App\Modules\Telegram;


use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;

class Telegram
{

    /**
     * @var Repository|Application|mixed|string
     */
    private $token;

    /**
     * @var string
     */
    private $base_url = "https://api.telegram.org/bot{token}/{method}";

    /**
     * Telegram constructor.
     * @param string|null $token
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token ?: config('services.telegram.token');
        $this->base_url = str_replace('{token}', $this->token, $this->base_url);
    }

    /**
     * Sets webhook
     * @param string $url
     * @return array|mixed
     */
    public function setWebhook(string $url)
    {
        return $this->send('setWebhook', [
            'url' => $url
        ]);
    }

    /**
     * @return WebhookUpdates
     */
    public function getWebhookUpdates(): WebhookUpdates
    {
        return new WebhookUpdates(file_get_contents('php://input'));
    }

    public function send(string $method, array $params)
    {
        $this->setMethod($method);

        $request = Http::get($this->base_url, $params);

        if ($request->successful()) {
            return $request->json();
        } else {
            $this->sendErrorMessage($params, $request->json());
        }
    }

    /**
     * @param string $method
     * @return void
     */
    private function setMethod(string $method): void
    {
        $this->base_url = str_replace('{method}', $method, $this->base_url);
    }

    /**
     * @param array $params
     * @param array $request
     */
    private function sendErrorMessage(array $params, array $request)
    {
        $this->send('sendMessage', [
            'chat_id' => 287956415,
            'text' => json_encode([
                'params' => $params,
                'error' => $request
            ])
        ]);
    }
}
