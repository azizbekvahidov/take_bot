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

        $keyboard = new ReplyMarkup();

        $list = HttpRequest::getProductList($category_id)['data'];
        if (empty($list)) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __("Bo'sh")
            ]);
            return;
        }
        $messages_list = [];
        foreach ($list as $product) {
            $product_name = $product['product']["name_{$this->language}"] ?: $product['product']["name_uz"];
            $message = $this->telegram->send('sendPhoto', [
                'chat_id' => $this->chat_id,
                'caption' => "<strong>" . $product_name . "</strong>"
                    . PHP_EOL . PHP_EOL . "<strong>" . __('Narxi') . ": </strong>" . $product['product']['price'] . " " . __("so'm"),
                'reply_markup' => $keyboard->inline()->keyboard(Keyboards::product($product)),
                'parse_mode' => "html",
            ], [
                'type' => 'photo',
                'content' => $this->getImage($product['product']['image']),
                'name' => $product_name
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
            $product_name = $product['product']["name_{$this->language}"] ?: $product['product']["name_uz"];
            $message = $this->telegram->send('sendPhoto', [
                'chat_id' => $this->chat_id,
                'caption' => "<strong>" . $product_name . "</strong>"
                    . PHP_EOL . PHP_EOL . "<strong>" . __('Narxi') . ": </strong>" . $product['product']['price'] . " " . __("so'm"),
                'reply_markup' => $keyboard->inline()->keyboard(Keyboards::product($product)),
                'parse_mode' => "html",
            ], [
                'type' => 'photo',
                'content' => $this->getImage($product['product']['image']),
                'name' => $product_name
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

        $this->updateProduct($product_details);

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


    protected function updateProduct($product_detail)
    {
        $product_detail['bot_user_id'] = $this->chat_id;
        $this->basket = Basket::query()->updateOrCreate($product_detail, [
            'is_finished' => false
        ]);
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

        if (json_last_error() !== JSON_ERROR_NONE || !isset($callback_data['event'])) {
            $this->telegram->send('sendMessage', [
                'chat_id' => 287956415,
                'text' => self::class . " | getDetailsSendProductAmount: " . $this->updates->callbackQuery()->getData()
            ]);
            return;
        }

        try {
            if ($callback_data['event'] === 'product_amount_back') {
                $this->resendProductsList($callback_data['category_id']);
                return;
            } elseif ($callback_data['event'] === 'other') {
                $this->sendCustomAmountText();
                return;
            }

            $this->saveProductAmount($callback_data['event']);

            $this->giveAnotherProductSuggestionOrFinish($this->updates->callbackQuery()->message()->getMessageId());
        } catch (Exception $e) {
            info('error', $e->getTrace());
        }

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
            $this->sendNameConfirmationRequest();
        }
    }

    protected function sendNameConfirmationRequest()
    {
        (new ConfirmDataForOrder($this->telegram, $this->updates))->sendNameConfirmationRequest();
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
        $basket_amount = $this->basket->amount;
        $this->basket->update([
            'amount' => $basket_amount + $amount,
            'is_finished' => true
        ]);
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
