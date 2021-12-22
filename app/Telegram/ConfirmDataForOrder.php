<?php


namespace App\Telegram;


use App\Constants\ActionMethodConstants;
use App\Constants\MessageCommentConstants;
use App\Constants\MessageTypeConstants;
use App\Constants\OrderTypeConstants;
use App\Models\Basket;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Services\BotService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConfirmDataForOrder extends BotService
{

    public function sendNameConfirmationRequest()
    {
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Ismingizni tasdiqlang") . ": {$this->bot_user->fetchUser()->name}",
            'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton(false))
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_CONFIRM_NAME_SEND_CONFIRMATION_FOR_PHONE
            ]);
        }
    }


    public function confirmNameSendConfirmationForPhone()
    {
        $name = $this->text;
        if ($this->text === __('Tasdiqlayman')) {
            $name = $this->bot_user->fetchUser()->name;
        }
        if ($this->validation->attributes($name)->check('name')->fails()) {
            $this->sendErrorMessages();
            return;
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
            'text' => __('Telefon raqamini tasdiqlang') . ": {$this->bot_user->fetchUser()->phone()}",
            'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_CONFIRM_PHONE_AND_REQUEST_ORDER_TYPE
            ]);
        }
    }

    public function confirmPhoneAndRequestOrderType()
    {
        if ($this->text === __("Ortga qaytish")) {
            $this->sendNameConfirmationRequest();
            return;
        }


        $phone = preg_replace("/[+]/", "", $this->text);
        if ($this->text === __('Tasdiqlayman')) {
            $phone = $this->bot_user->fetchUser()->phone;
        }

        if ($this->validation->attributes($phone)->check('regex:/^\+?998\d{9}$/')->fails()) {
            $this->sendErrorMessages();
            return;
        }


        $this->updateUnServedProducts([
            'phone' => $phone
        ]);
        $this->sendOrderTypeRequest();
    }

    protected function sendOrderTypeRequest()
    {
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Buyurtma turini tanlang'),
            'reply_markup' => $keyboard->keyboard(Keyboards::orderTypes())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_CONFIRM_ORDER_TYPE_GO_NEXT_STEP
            ]);
        }
    }

    public function confirmOrderTypeGoNextStep()
    {
        if ($this->text === __('Ortga qaytish')) {
            $this->sendPhoneConfirmRequest();
            return;
        }

        $params = [
            'is_delivery' => false
        ];
        switch ($this->text) {
            case __('Olib ketish'):
                $params['type'] = OrderTypeConstants::TAKE;
                $params['address'] = null;
                $this->sendFilialList();
                break;
            case __('Yetkazib berish'):
                $params['is_delivery'] = true;
                $params['type'] = OrderTypeConstants::DELIVERY;
                $this->sendAddressRequest();
                break;
            case __('Joyida'):
                $params['type'] = OrderTypeConstants::BOOKING;
                $params['address'] = null;
                $this->sendFilialList();
                break;
            default:
                return;
        }

        $this->updateUnServedProducts($params);

    }

    protected
    function sendAddressRequest()
    {
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Manzilingizni kiriting"),
            'reply_markup' => $keyboard->keyboard(Keyboards::backButton())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_ADDRESS
            ]);
        }
    }

    public
    function getAddress()
    {
        if ($this->text === __('Ortga qaytish')) {
            $this->sendOrderTypeRequest();
            return;
        }

        if ($this->validation->check('max:255')->fails()) {
            $this->sendErrorMessages();
            return;
        }

        $this->updateUnServedProducts([
            'address' => $this->text
        ]);

        $this->sendFilialList();
    }

    protected
    function sendFilialList()
    {
        $keyboard = new ReplyMarkup();

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Filialni tanlang"),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::getFilialList()),
            'parse_mode' => 'html'
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MENU_SEND_FILIAL_LIST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_FILIAL
            ]);
        }
    }

    public
    function getFilial()
    {
        $callback_data = $this->updates->callbackQuery()->getData();
        if ($callback_data === "filial_back") {
            $is_delivery = $this->getBasket()->is_delivery;
            if ($is_delivery) {
                $this->sendAddressRequest();
            } else {
                $this->sendOrderTypeRequest();
            }
            return;
        }

        $this->updateUnServedProducts([
            'filial_id' => $callback_data
        ]);

        $this->deleteMessage($this->updates->callbackQuery()->message()->getMessageId());

        $keyboard = new ReplyMarkup(true, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => $this->getOrderedProductsList(),
            'reply_markup' => $keyboard->keyboard(Keyboards::orderProducts()),
            'parse_mode' => 'html'
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_ORDER_PRODUCTS
            ]);
        }
    }

    public function orderProducts()
    {


        if ($this->text === __('Ortga qaytish')) {
            $this->sendFilialList();
            return;
        }
        if ($this->text !== __("Buyurtma berish")) {
            return;
        }
        $basket_query = Basket::query()->where('is_finished', '=', true)
            ->where('bot_user_id', '=', $this->chat_id);
        HttpRequest::postData($basket_query->get());

        $basket_query->delete();

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Sizning buyurtmangiz qabul qilindi, tez orada siz bilan bog\'lanamiz'),
        ]);

        $this->sendMainMenu();
    }


    /**
     * @return string
     */
    private
    function getOrderedProductsList(): string
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
            $product_detail = HttpRequest::getProductDetail($product->product_id, $product->product_type)['data'];
            $product_name = $product_detail["name_{$lang}"] ?: $product_detail["name_uz"];
            if ($key === 0) {
                switch ($product->type) {
                    case OrderTypeConstants::TAKE:
                        $order_type = __('Olib ketish');
                        $order_prepare_time = __("Buyurtmangiz 5-20 daqiqa ichida tayyor bo'ladi");
                        break;
                    case OrderTypeConstants::BOOKING:
                        $order_type = __("Joy band qilish");
                        $order_prepare_time = "";
                        break;
                    case OrderTypeConstants::DELIVERY:
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
                . PHP_EOL . "<strong>" . __("Narxi") . ":</strong> {$price}";
        }

        $product_list .= PHP_EOL . PHP_EOL . "<strong>" . __("Umumiy narxi") . ":</strong> {$total_price}"
            . PHP_EOL . PHP_EOL . "<strong>{$order_prepare_time}</strong>";

        return $product_list;
    }

    private
    function updateUnServedProducts(array $params)
    {
        Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->update($params);
    }

    /**
     * @return Builder|Model|object|null
     */
    private
    function getBasket()
    {
        return Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->first(['id', 'name', 'address', 'phone', 'is_delivery']);
    }
}
