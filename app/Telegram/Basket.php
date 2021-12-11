<?php


namespace App\Telegram;


use App\Constants\ActionMethodConstants;
use App\Constants\MessageCommentConstants;
use App\Constants\MessageTypeConstants;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;
use Exception;

class Basket extends BotService
{


    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);

        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::BASKET_SEND_BASKET_LIST
            ]);
        }
    }

    public function index()
    {
        try {
            $method = explode('.', $this->action()->sub_action)[1];
            if (!method_exists($this, $method)) {
                $this->telegram->send('sendMessage', [
                    'chat_id' => 287956415,
                    'text' => "Menu page, trying to find method: {$method}"
                ]);
                return;
            }
            $this->$method();
        } catch (\Exception $exception) {
            info($exception->getMessage());
            info($exception->getTraceAsString());
        }

    }

    protected function sendBasketList()
    {
        $lang = app()->getLocale();

        $keyboard = new ReplyMarkup();
        $message_text = "";
        $product_ids = [];
        $basket = $this->basket();

        if ($this->basket()->isEmpty()) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __("Savatingiz bo'sh"),
            ]);
            return;
        }

        foreach ($basket as $key => $product) {
            array_push($product_ids, $product->id);
            $product_detail = HttpRequest::getProductDetail($product->product_id, $product->product_type)['data'];
            $product_name = $product_detail["name_{$lang}"] ?: $product_detail['name_uz'];
            if ($key === 0) {
                $message_text = ($key + 1) . ")  <strong>{$product_name}</strong>"
                    . PHP_EOL . "     <strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                    . PHP_EOL . "     <strong>" . __("Narxi") . ":</strong> " . $product->amount * $product_detail['price'] . " " . __("so'm");
                continue;
            }
            $message_text .= PHP_EOL . PHP_EOL . ($key + 1) . ")  <strong>{$product_name}</strong>"
                . PHP_EOL . "     <strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                . PHP_EOL . "     <strong>" . __("Narxi") . ":</strong> " . $product->amount * $product_detail['price'] . " " . __("so'm");;
        }
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => $message_text,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::getOrderedProductsList($product_ids))
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::BASKET_SENT_BASKET_LIST);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::BASKET_GET_PRODUCT
            ]);
        }
    }

    public function getProduct()
    {
        if (!$this->updates->isCallbackQuery()) {
            return;
        }

        $callback_data = $this->updates->callbackQuery()->getData();

        if ($callback_data === 'basket_back') {
            $this->sendMainMenu();
            return;
        } elseif ($callback_data === 'order') {
            if ($this->basket()->isNotEmpty()) {
                $this->sendNameConfirmationRequest();
            }
            return;
        }

        \App\Models\Basket::query()->where('id', '=', $callback_data)->delete();
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
        $this->sendBasketList();
    }

    public function confirmNameSendConfirmationForPhone()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }

        (new ConfirmDataForOrder($this->telegram, $this->updates))->confirmNameSendConfirmationForPhone();
    }

    public function confirmPhoneAndRequestAddress()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }

        (new ConfirmDataForOrder($this->telegram, $this->updates))->confirmPhoneAndRequestAddress();
    }


    public function getAddress()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }

        (new ConfirmDataForOrder($this->telegram, $this->updates))->getAddress();
    }

    /**
     * @throws Exception
     */
    public function getFilial()
    {

        if (!$this->updates->isCallbackQuery()) {
            return;
        }

        (new ConfirmDataForOrder($this->telegram, $this->updates))->getFilial();
    }


    public function orderProducts()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }

        (new ConfirmDataForOrder($this->telegram, $this->updates))->orderProducts();
    }

    protected function basket()
    {
        return \App\Models\Basket::query()
            ->where('is_finished', '=', true)
            ->where('bot_user_id', '=', $this->chat_id)
            ->where('is_served', '=', false)
            ->get();
    }

    public function sendNameConfirmationRequest()
    {
        (new ConfirmDataForOrder($this->telegram, $this->updates))->sendNameConfirmationRequest();
    }
}
