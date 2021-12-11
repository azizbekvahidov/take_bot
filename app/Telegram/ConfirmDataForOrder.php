<?php


namespace App\Telegram;


use App\Constants\ActionMethodConstants;
use App\Constants\MessageCommentConstants;
use App\Constants\MessageTypeConstants;
use App\Models\Basket;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Services\BotService;

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
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_NAME_CONFIRM_BUTTON);
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
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_NAME_CONFIRM_BUTTON);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_CONFIRM_PHONE_AND_REQUEST_ADDRESS
            ]);
        }
    }

    public function getAddress()
    {
        if ($this->text === __('Ortga qaytish')) {
            $this->sendPhoneConfirmRequest();
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

    protected function sendFilialList()
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

    public function confirmPhoneAndRequestAddress()
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

        $this->sendAddressRequest();
    }

    protected function sendAddressRequest()
    {
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Manzilingizni kiriting"),
            'reply_markup' => $keyboard->keyboard(Keyboards::backButton())
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_ADDRESS_REQUEST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_ADDRESS
            ]);
        }
    }

    public function getFilial()
    {
        $callback_data = $this->updates->callbackQuery()->getData();
        if ($callback_data === "filial_back") {
            $this->sendAddressRequest();
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

        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_ORDERED_PRODUCTS_LIST);

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
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_ORDERED);
        $this->sendMainMenu();
    }


    /**
     * @return string
     */
    private function getOrderedProductsList(): string
    {
        $lang = app()->getLocale();
        $product_list = "";
        $total_price = 0;
        $products = Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->get();
        foreach ($products as $key => $product) {
            $product_detail = HttpRequest::getProductDetail($product->product_id, $product->product_type)['data'];
            $product_name = $product_detail["name_{$lang}"] ?: $product_detail["name_uz"];
            if ($key === 0) {
                $filial = HttpRequest::getFilialDetail($product->filial_id)['data'];
                $product_list = "<strong>" . __("Manzil") . ":</strong> {$product->address}"
                    . PHP_EOL . "<strong>" . __("Ismingiz") . ":</strong> {$product->name}"
                    . PHP_EOL . "<strong>" . __("Telefon raqam") . ":</strong> {$product->phone()}"
                    . PHP_EOL . "<strong>" . __("Filial") . ":</strong> {$filial['name']}";
            }
            $price = $product->amount * $product_detail['price'];
            $total_price += $price;
            $product_list .= PHP_EOL . PHP_EOL . "<strong>{$product_name}</strong>"
                . PHP_EOL . "<strong>" . __("Miqdori") . ":</strong> {$product->amount}"
                . PHP_EOL . "<strong>" . __("Narxi") . ":</strong> {$price}";
        }

        $product_list .= PHP_EOL . PHP_EOL . "<strong>" . __("Umumiy narxi") . ":</strong> {$total_price}";

        return $product_list;
    }

    private function updateUnServedProducts(array $params)
    {
        Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->update($params);
    }

}
