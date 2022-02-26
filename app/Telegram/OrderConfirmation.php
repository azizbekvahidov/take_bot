<?php

namespace App\Telegram;

use App\Constants\MethodConstant;
use App\Constants\OrderTypeConstant;
use App\Exceptions\ApiServerException;
use App\Exceptions\MenuListEmptyException;
use App\Models\Basket;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\ReplyMarkup;
use App\Services\BotService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderConfirmation extends BotService
{
    public function sendNameConfirmationRequest()
    {
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Ismingizni tasdiqlang") . ": {$this->fetchUser()->name}",
            'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton(false))
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_CONFIRM_NAME_SEND_CONFIRMATION_FOR_PHONE
            ]);
        }
    }

    public function confirmNameSendConfirmationForPhone()
    {
        $name = $this->text;
        if ($this->text === __('Tasdiqlayman')) {
            $name = $this->fetchUser()->name;
        }
        if (Str::length($this->text) > 200) {
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Ismingizni to\'g\'ri kiriting (200 dan ko\'p harf kiritish mumkin emas)')
            ]);
        }

        $this->updateUnServedProducts([
            'name' => $name
        ]);
        $this->sendPhoneConfirmRequest();
    }

    protected function sendPhoneConfirmRequest()
    {
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Telefon raqamini tasdiqlang') . ": {$this->fetchUser()->phone()}",
            'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_CONFIRM_PHONE_AND_REQUEST_ORDER_TYPE
            ]);
        }
    }

    public function confirmPhoneAndRequestOrderType()
    {
        if ($this->text === __("Ortga")) {
            $this->sendNameConfirmationRequest();
            return;
        }

        $phone = preg_replace("/[+]/", "", $this->text);
        if ($this->text === __('Tasdiqlayman')) {
            $phone = $this->fetchUser()->phone;
        } else {
            if (!preg_match('/^998\d{9}$/', $this->text)) {
                return $this->telegram->send('sendMessage', [
                    'chat_id' => $this->chat_id,
                    'text' => __('Telefon raqamingizni +998YYXXXXXXX formatida kiriting'),
                ]);
            }
        }


        $this->updateUnServedProducts([
            'phone' => $phone
        ]);
        $this->sendOrderTypeRequest();
    }

    protected function sendOrderTypeRequest()
    {
        $this->deleteMessages();
        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Buyurtma turini tanlang'),
            'reply_markup' => $keyboard->keyboard(Keyboards::orderTypes())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_CONFIRM_ORDER_TYPE_GO_NEXT_STEP
            ]);
        }
    }

    /**
     * @throws \App\Exceptions\MenuListEmptyException
     * @throws \App\Exceptions\ApiServerException
     */
    public function confirmOrderTypeGoNextStep()
    {
        if ($this->text === __('Ortga')) {
            $this->sendPhoneConfirmRequest();
            return;
        }

        $params = [
            'is_delivery' => false
        ];
        switch ($this->text) {
            case __('Olib ketish'):
                $params['type'] = OrderTypeConstant::TAKE;
                $params['address'] = null;
                $this->sendFilialList();
                break;
            case __('Yetkazib berish'):
                $params['is_delivery'] = true;
                $params['type'] = OrderTypeConstant::DELIVERY;
                $this->sendAddressRequest();
                break;
            case __('Joyida'):
                $params['type'] = OrderTypeConstant::BOOKING;
                $params['address'] = null;
                $this->sendFilialList();
                break;
            default:
                return;
        }

        $this->updateUnServedProducts($params);

    }

    protected function sendAddressRequest()
    {
        $this->deleteMessages();
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Manzilingizni kiriting"),
            'reply_markup' => $keyboard->keyboard(Keyboards::backButton())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_GET_ADDRESS
            ]);
        }
    }

    /**
     * @throws \App\Exceptions\MenuListEmptyException
     * @throws \App\Exceptions\ApiServerException
     */
    public function getAddress()
    {
        if ($this->text === __('Ortga')) {
            $this->sendOrderTypeRequest();
            return;
        }

        if (Str::length($this->text) > 200) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Manzilni to\'g\'ri kiriting (200 dan ko\'p harf kiritish mumkin emas)')
            ]);
            return;
        }

        $this->updateUnServedProducts([
            'address' => $this->text
        ]);

        $this->sendFilialList();
    }

    /**
     * @throws \App\Exceptions\MenuListEmptyException
     * @throws \App\Exceptions\ApiServerException
     */
    protected function sendFilialList()
    {
        $keyboard = new ReplyMarkup(true, true);

        $list = HttpRequest::getFilialList();
//        $list = json_decode(file_get_contents(storage_path('list/filial.json')), true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Filialni tanlang"),
            'reply_markup' => $keyboard->keyboard(Keyboards::getFilialList($list)),
            'parse_mode' => 'html'
        ]);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_GET_FILIAL
            ]);
        }
    }

    /**
     * @throws MenuListEmptyException
     * @throws ApiServerException
     */
    public function getFilial()
    {
        if ($this->text === __('Ortga')) {
            $is_delivery = $this->getBasket()->is_delivery;
            if ($is_delivery) {
                $this->sendAddressRequest();
            } else {
                $this->sendOrderTypeRequest();
            }
            return;
        }

        $list = HttpRequest::getFilialList();
//        $list = json_decode(file_get_contents(storage_path('list/filial.json')), true);


        if (empty($list)) {
            throw new ApiServerException('Filiallar ro\'yhati bo\'sh, serverda xatolik');
        } elseif (empty($list['data'])) {
            throw new MenuListEmptyException(__('Filiallar bo\'sh'));
        }

        $plucked = Arr::pluck($list['data'], "name", 'id');
        if (!in_array($this->text, $plucked)) {
            $keyboard = new ReplyMarkup(true, true);
            return $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Filialni tanlang'),
                'reply_markup' => $keyboard->keyboard(Keyboards::getFilialList($list))
            ]);
        }

        $this->updateUnServedProducts([
            'filial_id' => array_flip($plucked)[($this->text)]
        ]);

        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => $this->getOrderedProductsList(),
            'reply_markup' => $keyboard->keyboard(Keyboards::orderProducts()),
            'parse_mode' => 'html'
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => MethodConstant::MENU_ORDER_PRODUCTS
            ]);
        }
    }


    /**
     * @return string
     */
    private function getOrderedProductsList(): string
    {
        $lang = app()->getLocale();
        $product_list = "";
        $order_prepare_time = "";
        $total_price = 0;
        $products = Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->get();
        foreach ($products as $key => $product) {
//            $product_detail = HttpRequest::getProductDetail($product->product_id, $product->product_type)['data'];
            $product_detail = json_decode(file_get_contents(storage_path('list/product.json')), true)['data'];
            $product_name = $product_detail["name_{$lang}"] ?: $product_detail["name_uz"];
            if ($key === 0) {
                switch ($product->type) {
                    case OrderTypeConstant::TAKE:
                        $order_type = __('Olib ketish');
                        $order_prepare_time = __("Buyurtmangiz 5-20 daqiqa ichida tayyor bo'ladi");
                        break;
                    case OrderTypeConstant::BOOKING:
                        $order_type = __("Joyida");
                        $order_prepare_time = "";
                        break;
                    case OrderTypeConstant::DELIVERY:
                        $order_type = __('Yetkazib berish');
                        $order_prepare_time = __("Buyurtmangiz 20-40 daqiqa ichida yetkazib beriladi");
                        $product_list = "<strong>" . __("Manzil") . ":</strong> {$product->address}";
                        break;
                }

                $filial = HttpRequest::getFilialDetail($product->filial_id)['data'];
                $product_list .= PHP_EOL . "<strong>" . __("Ismingiz") . ":</strong> {$product->name}"
                    . PHP_EOL . "<strong>" . __("Telefon raqam") . ":</strong> {$product->phone()}"
                    . PHP_EOL . "<strong>" . __("Filial") . ":</strong> {$filial['name']}"
                    . PHP_EOL . "<strong>" . __("Buyurtma turi") . ":</strong> {$order_type}";
            }
            $price = $product->amount * $product_detail['price'];
            $total_price += $price;
            $product_list .= PHP_EOL . PHP_EOL . "<strong>{$product_name}</strong>"
                . PHP_EOL . "<strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                . PHP_EOL . "<strong>" . __("Narxi") . ":</strong> {$price} " . __("so'm");
        }

        $product_list .= PHP_EOL . PHP_EOL . "<strong>" . __("Umumiy narxi") . ":</strong> {$total_price} " . __("so'm")
            . PHP_EOL . PHP_EOL . "<strong>{$order_prepare_time}</strong>";

        return $product_list;
    }


    /**
     * @throws MenuListEmptyException
     * @throws ApiServerException
     */
    public function orderProducts()
    {

        if ($this->text === __('Ortga')) {
            $this->sendFilialList();
            return;
        }
        if ($this->text !== __("Buyurtma berish")) {
            return;
        }
        $basket_query = Basket::query()
            ->where('is_finished', '=', true)
            ->where('bot_user_id', '=', $this->chat_id);

        HttpRequest::postData($basket_query->get());

        $basket_query->delete();

        $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Sizning buyurtmangiz qabul qilindi, tez orada siz bilan bog\'lanamiz'),
        ]);

        $this->sendMainMenu();
    }

    private function updateUnServedProducts(array $params)
    {
        Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->update($params);
    }

    /**
     * @return Basket
     */
    private function getBasket(): Basket
    {
        return Basket::where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->first(['id', 'name', 'address', 'phone', 'is_delivery']);
    }
}
