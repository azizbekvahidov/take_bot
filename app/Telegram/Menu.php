<?php


namespace App\Telegram;


use App\Constants\ActionMethodConstants;
use App\Constants\MessageCommentConstants;
use App\Constants\MessageTypeConstants;
use App\Models\Basket;
use App\Models\Message;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Services\BotService;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Menu extends BotService
{
    /**
     * @var Builder|Model
     */
    private $basket;

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);

        $this->basket = Basket::query()
            ->where('is_finished', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->first();

        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_SEND_MENU_LIST
            ]);
        }
    }

    public function index()
    {
        $method = explode('.', $this->action()->sub_action)[1];
        if (!method_exists($this, $method)) {
            $this->telegram->send('sendMessage', [
                'chat_id' => 287956415,
                'text' => "Menu page, trying to find method: {$method}"
            ]);
            return;
        }
        $this->$method();
    }

    protected function sendMenuList()
    {
        $this->basket = Basket::query()->firstOrCreate([
            'bot_user_id' => $this->chat_id,
            'is_finished' => false
        ]);
        $this->deleteMessages([MessageTypeConstants::INLINE_KEYBOARD]);
        $keyboard = new ReplyMarkup(false, false, true);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Menyuni tanlang'),
            'reply_markup' => $keyboard->keyboard(Keyboards::menuList())
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MENU_SEND_CATEGORY);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_MENU_LIST_SEND_PRODUCT_LIST
            ]);
        }
    }

    /**
     * @throws Exception
     */
    protected function getMenuListSendProductList()
    {
        if (!$this->updates->isCallbackQuery()) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Menyuni tanlang')
            ]);
            return;
        }
        $callback_query = $this->updates->callbackQuery();
        if ($callback_query->getData() === 'category_back') {
            $this->sendMainMenu();
            return;
        }
        $category_id = (int)explode('=', $callback_query->getData())[1];
        $this->basket->update([
            'category_id' => $category_id
        ]);
        $keyboard = new ReplyMarkup();

        $list = HttpRequest::getProductList($category_id)['data'];
        $messages_list = [];
        foreach ($list as $product) {
            $message = $this->telegram->send('sendPhoto', [
                'chat_id' => $this->chat_id,
                'caption' => "<strong>" . ($product['product']["name_{$this->language}"] ?? $product['product']["name_uz"]) . "</strong>"
                    . PHP_EOL . PHP_EOL . "<strong>" . __('Narxi') . ": </strong>" . $product['product']['price'] . " " . __("so'm"),
                'reply_markup' => $keyboard->inline()->keyboard(Keyboards::product($product)),
                'parse_mode' => "html",
            ], [
                'type' => 'photo',
                'content' => $this->getImage($product['product']['image']),
                'name' => ($product['product']["name_{$this->language}"] ?? $product['product']["name_uz"])
            ]);
            array_push($messages_list, $message);
        }
        (new MessageLog($messages_list))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MAIN_SEND_PRODUCT_LIST);

        $this->deleteMessage($callback_query->message()->getMessageId());

        $this->action()->update([
            'sub_action' => ActionMethodConstants::MENU_GET_PRODUCT_LIST_SEND_PRODUCT_DETAIL
        ]);
    }

    /**
     * @throws Exception
     */
    public function resendProductsList(int $category_id)
    {
        $keyboard = new ReplyMarkup();
        $callback_query = $this->updates->callbackQuery();

        $list = HttpRequest::getProductList($category_id)['data'];
        $messages_list = [];
        foreach ($list as $product) {
            $message = $this->telegram->send('sendPhoto', [
                'chat_id' => $this->chat_id,
                'caption' => "<strong>" . ($product['product']["name_{$this->language}"] ?? $product['product']["name_uz"]) . "</strong>"
                    . PHP_EOL . PHP_EOL . "<strong>" . __('Narxi') . ": </strong>" . $product['product']['price'] . " " . __("so'm"),
                'reply_markup' => $keyboard->inline()->keyboard(Keyboards::product($product)),
                'parse_mode' => "html",
            ], [
                'type' => 'photo',
                'content' => $this->getImage($product['product']['image']),
                'name' => ($product['product']["name_{$this->language}"] ?? $product['product']["name_uz"])
            ]);
            array_push($messages_list, $message);
        }
        (new MessageLog($messages_list))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MAIN_SEND_PRODUCT_LIST);

        $this->deleteMessage($callback_query->message()->getMessageId());


        $this->action()->update([
            'sub_action' => ActionMethodConstants::MENU_GET_PRODUCT_LIST_SEND_PRODUCT_DETAIL
        ]);
    }

    public function getProductListSendProductDetail()
    {
        $callback_query = $this->updates->callbackQuery();
        if (!$callback_query) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Maxsulotni tanlang')
            ]);
            return;
        }
        if ($callback_query->getData() === 'product_back') {
            $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD);
            $this->SendMenuList();
            return;
        }

        $product_details = json_decode($callback_query->getData(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->telegram->send('sendMessage', [
                'chat_id' => 287956415,
                'text' => $callback_query->getData()
            ]);
            return;
        }
        $this->basket->update($product_details);
        $keyboard = new ReplyMarkup();
        $this->deleteMessages(MessageTypeConstants::INLINE_KEYBOARD, $callback_query->message()->getMessageId());
        $message = $this->telegram->send('editMessageCaption', [
            'chat_id' => $this->chat_id,
            'message_id' => $this->updates->callbackQuery()->message()->getMessageId(),
            'caption' => $this->text . PHP_EOL . PHP_EOL . __('Miqdorini tanlang'),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productAmountList($this->basket->category_id))
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MENU_SEND_PRODUCT_AMOUNT_REQUEST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_DETAILS_SEND_PRODUCT_AMOUNT
            ]);
        }
    }

    private function resendProductAmountList()
    {
        $keyboard = new ReplyMarkup();
        $message = $this->telegram->send('editMessageReplyMarkup', [
            'chat_id' => $this->chat_id,
            'message_id' => $this->updates->callbackQuery()->message()->getMessageId(),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productAmountList($this->basket->category_id))
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::INLINE_KEYBOARD, MessageCommentConstants::MENU_SEND_PRODUCT_AMOUNT_REQUEST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_DETAILS_SEND_PRODUCT_AMOUNT
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function getDetailsSendProductAmount()
    {
        $callback_data = $this->updates->callbackQuery()->getData();
        if (!$callback_data) {
            return;
        }
        $callback_data = json_decode($callback_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->telegram->send('sendMessage', [
                'chat_id' => 287956415,
                'text' => self::class . " | getDetailsSendProductAmount: " . $this->updates->callbackQuery()->getData()
            ]);
            return;
        }

        if ($callback_data['event'] === 'product_amount_back') {
            $this->resendProductsList($callback_data['category_id']);
        } elseif ($callback_data['event'] === 'other') {
            $this->sendCustomAmountText();
            return;
        }

        $this->saveProductAmount($callback_data['event']);

        $this->giveAnotherProductSuggestionOrFinish($this->updates->callbackQuery()->message()->getMessageId());

    }

    protected function sendCustomAmountText()
    {
        $keyboard = new ReplyMarkup();
        $this->telegram->send('editMessageReplyMarkup', [
            'chat_id' => $this->chat_id,
            'message_id' => $this->updates->callbackQuery()->message()->getMessageId(),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productCustomAmountBack())
        ]);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Miqdorini kiriting')
        ]);

        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_PRODUCT_CUSTOM_AMOUNT_REQUEST);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_PRODUCT_CUSTOM_AMOUNT,
            ]);
        }
    }

    public function getProductCustomAmount()
    {
        if ($this->updates->isCallbackQuery()) {
            $callback_query = $this->updates->callbackQuery();

            if ($callback_query->getData() === 'product_amount_back') {
                $this->deleteMessages([MessageTypeConstants::NO_KEYBOARD]);
                $this->resendProductAmountList();
                return;
            }
            return;
        }

        if ($this->updates->message()->isFile()) {
            return;
        }

        if ($this->validation->check("amount")->fails()) {
            $this->sendErrorMessages();
            return;
        }

        $amount = (double)str_replace(',', '.', $this->text);

        $this->saveProductAmount($amount);

        $message = Message::query()
            ->where('bot_user_id', '=', $this->chat_id)
            ->where('message_type', '=', MessageTypeConstants::INLINE_KEYBOARD)
            ->orderByDesc('id')
            ->first();
        $this->giveAnotherProductSuggestionOrFinish($message->message_id);
    }

    /**
     * @throws Exception
     */
    public function finishOrSendAnotherProduct()
    {
        if (!$this->updates->isCallbackQuery()) {
            return;
        }
        $callback_data = $this->updates->callbackQuery()->getData();

        if ($callback_data === 'another_meal') {

            $this->deleteMessage($this->updates->callbackQuery()->message()->getMessageId());
            $this->sendMenuList();
        } elseif ($callback_data === 'order') {
            $this->deleteMessage($this->updates->callbackQuery()->message()->getMessageId());
            $keyboard = new ReplyMarkup(true, true);
            $message = $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __("Ismingizni tasdiqlang, {$this->bot_user->fetchUser()->name}"),
                'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton())
            ]);
            (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_NAME_CONFIRM_BUTTON);
            if ($message['ok']) {
                $this->action()->update([
                    'sub_action' => ActionMethodConstants::MENU_CONFIRM_NAME_SEND_CONFIRMATION_FOR_PHONE
                ]);
            }
        }
    }

    public function confirmNameSendConfirmationForPhone()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }


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
        $keyboard = new ReplyMarkup(true, true);
        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Telefon raqamini tasdiqlang: ' . $this->bot_user->fetchUser()->phone()),
            'reply_markup' => $keyboard->keyboard(Keyboards::sendConfirmButton())
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_NAME_CONFIRM_BUTTON);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_CONFIRM_PHONE_AND_CHECK_ORDER_PRODUCT_LIST
            ]);
        }
    }

    public function confirmPhoneAndCheckOrderProductList()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
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

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __("Manzilingizni kiriting"),
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_SEND_ADDRESS_REQUEST);
        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_GET_ADDRESS
            ]);
        }
    }

    public function getAddress()
    {
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }


        if ($this->validation->check('max:255')->fails()) {
            $this->sendErrorMessages();
            return;
        }

        $this->updateUnServedProducts([
            'address' => $this->text
        ]);


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

    /**
     * @throws Exception
     */
    public function getFilial()
    {


        if (!$this->updates->isCallbackQuery()) {
            return;
        }
        $this->updateUnServedProducts([
            'filial_id' => $this->updates->callbackQuery()->getData()
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
        if ($this->updates->isCallbackQuery() || $this->updates->message()->isFile()) {
            return;
        }


        $basket_query = Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id);
        HttpRequest::postData($basket_query->get());

        $basket_query->update(['is_served' => true]);

        $message = $this->telegram->send('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Sizning buyurtmangiz qabul qilindi, tez orada siz bilan bog\'lanamiz'),
        ]);
        (new MessageLog($message))->createLog(MessageTypeConstants::NO_KEYBOARD, MessageCommentConstants::MENU_ORDERED);
        $this->sendMainMenu();
    }

    protected function giveAnotherProductSuggestionOrFinish(int $message_id)
    {
        $keyboard = new ReplyMarkup();
        $message = $this->telegram->send('editMessageReplyMarkup', [
            'chat_id' => $this->chat_id,
            'message_id' => $message_id,
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::suggestAnotherProductOrFinishChoice())
        ]);

        if ($message['ok']) {
            $this->action()->update([
                'sub_action' => ActionMethodConstants::MENU_FINISH_OR_SEND_ANOTHER_PRODUCT
            ]);
        }
    }

    private function saveProductAmount($amount)
    {
        $this->basket->update([
            'amount' => $amount,
            'is_finished' => true
        ]);
    }

    /**
     * @return string
     */
    private function getOrderedProductsList(): string
    {
        $product_list = "";

        $products = Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->get();
        foreach ($products as $key => $product) {
            if ($key === 0) {
                $product_list = "<strong>" . __("Manzil") . ":</strong> {$product->address}"
                    . PHP_EOL . "<strong>" . __("Ismingiz") . ":</strong> {$product->name}"
                    . PHP_EOL . "<strong>" . __("Telefon raqam") . ":</strong> {$product->phone()}"
                    . PHP_EOL . "<strong>" . __("Filial") . ":</strong> {$product->filial_id}"
                    . PHP_EOL . PHP_EOL . "<strong>{$product->product_id}</strong>"
                    . PHP_EOL . "<strong>" . __("Miqdori") . ":</strong> {$product->amount}";
                continue;
            }
            $product_list .= PHP_EOL . PHP_EOL . "<strong>{$product->product_id}</strong>"
                . PHP_EOL . "<strong>" . __("Miqdori") . ":</strong> {$product->amount}";
        }

        return $product_list;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getImage(?string $url = null)
    {
        $path = Storage::disk('assets')->get('products/default-image.jpg');
        if (!is_null($url) && @getimagesize($url)) {
            return file_get_contents($url);
        }
        return $path;
    }

    private function updateUnServedProducts(array $params)
    {
        Basket::query()->where('is_finished', '=', true)
            ->where('is_served', '=', false)
            ->where('bot_user_id', '=', $this->chat_id)
            ->update($params);
    }
}
