<?php

namespace App\Telegram;

use App\Constants\MethodConstant;
use App\Exceptions\ApiServerException;
use App\Exceptions\MenuListEmptyException;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Updates\Message;
use Exception;

class Basket extends Message
{

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => MethodConstant::SEND_PRODUCTS_LIST
            ]);
        }
    }

    public function index()
    {
        try {
            $method = $this->action()->sub_action;
            if (method_exists($this, $method)) {
                $this->$method();
            }
        } catch (MenuListEmptyException $exception) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => $exception->getMessage()
            ]);
        } catch (ApiServerException|Exception $exception) {
            $this->sendErrorToAdmin($exception->getMessage());
        }
    }

    /**
     * @throws ApiServerException
     * @throws MenuListEmptyException
     */
    public function sendProductsList()
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
            $product_ids[] = $product->id;
//            $product_detail = HttpRequest::getProductDetail($product->product_id, $product->product_type);

            $product_detail = json_decode(file_get_contents(storage_path('list/product.json')), true);

            if (empty($product_detail)) {
                throw new ApiServerException('Savatda maxsulot ma\'lumotlari kelmadi, server ishlamadi');
            } elseif (empty($product_detail['data'])) {
                throw new MenuListEmptyException(__('Maxsulot topilmadi'));
            }

            $product_detail = $product_detail['data'];
            $product_name = $product_detail["name_{$lang}"] ?: $product_detail['name_uz'];
            if ($key === 0) {
                $message_text = ($key + 1) . ")  <strong>{$product_name}</strong>"
                    . PHP_EOL . "     <strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                    . PHP_EOL . "     <strong>" . __("Narxi") . ":</strong> " . ($product->amount * $product_detail['price']) . " " . __("so'm");
                continue;
            }
            $message_text .= PHP_EOL . PHP_EOL . ($key + 1) . ")  <strong>{$product_name}</strong>"
                . PHP_EOL . "     <strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                . PHP_EOL . "     <strong>" . __("Narxi") . ":</strong> " . ($product->amount * $product_detail['price']) . " " . __("so'm");;
        }
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => $message_text,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::getOrderedProductsList($product_ids))
        ]);
        (new MessageLog($message))->createLog();

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::GET_PRODUCT
            ]);
        }
    }

    /**
     * @param $data
     * @return void
     */
    public function productManipulation($data)
    {
        $this->deleteMessages();

        if ($data === 'order') {
            return $this->basket()->unlessEmpty(function () {
                (new OrderConfirmation($this->telegram, $this->updates))->sendNameConfirmationRequest();
            });
        }

        if ($data === 'Ortga') {
            $this->sendMainMenu();
            return;
        }

        try {

            \App\Models\Basket::query()->where('id', '=', $data)->delete();
            $this->sendProductsList();

        } catch (MenuListEmptyException $exception) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => $exception->getMessage()
            ]);
        } catch (ApiServerException|Exception $exception) {
            $this->sendErrorToAdmin($exception->getMessage());
        }
    }

    public function confirmName()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->sendNameConfirmationRequest();
    }

    public function confirmNameSendConfirmationForPhone()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->confirmNameSendConfirmationForPhone();
    }

    public function confirmPhoneAndRequestOrderType()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->confirmPhoneAndRequestOrderType();
    }

    /**
     * @throws MenuListEmptyException
     * @throws ApiServerException
     */
    public function confirmOrderTypeGoNextStep()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->confirmOrderTypeGoNextStep();
    }

    /**
     * @throws MenuListEmptyException
     * @throws ApiServerException
     */
    public function getAddress()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->getAddress();
    }

    /**
     * @throws MenuListEmptyException
     * @throws ApiServerException
     */
    public function getFilial()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->getFilial();
    }

    public function orderProducts()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->orderProducts();
    }

    protected function basket()
    {
        return \App\Models\Basket::query()
            ->where('is_finished', '=', true)
            ->where('bot_user_id', '=', $this->chat_id)
            ->where('is_served', '=', false)
            ->get();
    }
}
