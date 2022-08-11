<?php

namespace App\Telegram;

use App\Constants\MethodConstant;
use App\Exceptions\ApiServerException;
use App\Exceptions\MenuListEmptyException;
use App\Models\Basket;
use App\Modules\Cafe\HttpRequest;
use App\Modules\Telegram\MessageLog;
use App\Modules\Telegram\ReplyMarkup;
use App\Modules\Telegram\Telegram;
use App\Modules\Telegram\WebhookUpdates;
use App\Telegram\Updates\Message;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Menu extends Message
{

    public function __construct(Telegram $telegram, WebhookUpdates $updates)
    {
        parent::__construct($telegram, $updates);
        if (is_null($this->action()->sub_action)) {
            $this->action()->update([
                'sub_action' => MethodConstant::SEND_MENUS_LIST
            ]);
        }
    }

    public function index()
    {
        $method = $this->action()->sub_action;
        try {
            if (method_exists($this, $method)) {
                $this->$method();
            }
        } catch (MenuListEmptyException $exception) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => $exception->getMessage()
            ]);
            $this->sendMainMenu();
        } catch (Exception|ApiServerException $exception) {
            $this->sendErrorToAdmin($exception->getFile(), $exception->getLine(), $exception->getMessage());
        }
    }

    public function sendMenusList(bool $is_edit = false)
    {
        if (!$is_edit) {
            $this->deleteMessages();
        }
        $list = HttpRequest::getMenuList();
//        $list = json_decode(file_get_contents(storage_path('list/category.json')), true);
        $keyboard = new ReplyMarkup();
        $message = $this->telegram->send($is_edit ? 'editMessageText' : 'sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => __('Menyuni tanlang'),
            'message_id' => $is_edit ? $this->updates->callbackQuery()->message()->getMessageId() : 0,
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::menusList($list))
        ]);
        if (!$is_edit) {
            (new MessageLog($message))->createLog();
        }
    }

    /**
     * @param $category_id
     * @param bool $is_new
     * @return array|mixed|void
     */
    public function getMenuSendProductsList($category_id, bool $is_new = false)
    {
        if ($category_id === 'Ortga') {
            $this->deleteMessages();
            return $this->sendMainMenu();
        }
        $this->getBasket()->update([
            'category_id' => $category_id
        ]);
        $list = HttpRequest::getProductList($category_id);
//        $list = json_decode(file_get_contents(storage_path('list/menuList.json')), true);
        try {
            $keyboard = new ReplyMarkup();
            $message = $this->telegram->send($is_new ? 'sendMessage' : 'editMessageText', [
                'chat_id' => $this->chat_id,
                'message_id' => $this->updates->callbackQuery()->message()->getMessageId(),
                'text' => __('Maxsulotni tanlang'),
                'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productsList($list))
            ]);
            if ($is_new) {
                (new MessageLog($message))->createLog();
            }
        } catch (MenuListEmptyException $exception) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => $exception->getMessage()
            ]);
        } catch (Exception|ApiServerException $exception) {
            $this->sendErrorToAdmin($exception->getFile(), $exception->getLine(), $exception->getMessage());
        }

    }

    /**
     * @throws FileNotFoundException
     */
    public function getProductDetail($data)
    {
        if ($data === 'Ortga') {
            $this->sendMenusList(true);
            return;
        }

        list($product_id, $product_type) = explode(',', $data);

        $product = HttpRequest::getProductDetail($product_id, $product_type);
//        $product = json_decode(file_get_contents(storage_path('list/product.json')), true);

        if (empty($product)) {
            $this->sendErrorToAdmin('', '', 'Maxsulot ma\'lumotlari kelmadi, serverda xatolik ro\'y berdi');
            return;
        } elseif (empty($product['data'])) {
            $this->telegram->send('sendMessage', [
                'chat_id' => $this->chat_id,
                'text' => __('Maxsulot topilmadi')
            ]);
            return;
        }
        if ($basket = $this->checkProduct($product_id, $product_type)) {
            if ($basket->is_finished) {
                $this->getBasket()->delete();
            }
            $basket->update([
                'is_modify' => true,
                'is_finished' => false
            ]);
        } else {
            $this->getBasket()->update([
                'product_id' => $product_id,
                'product_type' => $product_type,
                'amount' => 0
            ]);
        }

        $this->deleteMessages();
        $keyboard = new ReplyMarkup();
        $message = $this->telegram->send('sendPhoto', [
            'chat_id' => $this->chat_id,
            'caption' => $this->preparedText($product['data']),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productDetails())
        ], [
            'type' => 'photo',
            'content' => $this->getImage($product['data']['image']),
            'name' => 'product'
        ]);
        (new MessageLog($message))->createLog();
    }

    /**
     * @param string $data
     * @return void
     */
    public function countAmountOfProduct(string $data)
    {
        if ((int)$data < 1) {
            return;
        }

        $keyboard = new ReplyMarkup();
        $this->telegram->send('editMessageReplyMarkup', [
            'chat_id' => $this->chat_id,
            'message_id' => $this->updates->callbackQuery()->message()->getMessageId(),
            'reply_markup' => $keyboard->inline()->keyboard(Keyboards::productDetails(($data)))
        ]);
    }

    /**
     * @param string $data
     * @return void
     */
    public function orderProduct(string $data)
    {
        $this->deleteMessages();
        if ($data === 'order') {
            if ($modified_product = $this->getModifiedProduct()) {
                $modified_product->update([
                    'is_finished' => true,
                    'is_modify' => false
                ]);
            }
            $this->confirmName();

        } elseif ($data === 'Ortga') {

            $basket = $this->getBasket();

            if ($basket->is_modify) {
                $basket->update([
                    'is_finished' => true,
                    'is_modify' => false
                ]);
            }
            $this->getMenuSendProductsList($basket->category_id, true);

        } elseif (preg_match('/^another/', $data)) {
            $amount = explode(',', $data)[1];
            $this->getBasket()->update([
                'amount' => DB::raw("amount + {$amount}"),
                'is_finished' => true,
                'is_modify' => false
            ]);
            $this->sendMenusList();
        }
    }

    /**
     * @return void
     */
    public function confirmName()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->sendNameConfirmationRequest();
    }

    /**
     * @return void
     * @throws ApiServerException
     * @throws MenuListEmptyException
     */
    public function confirmNameSendConfirmationForPhone()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->confirmNameSendConfirmationForPhone();
    }

    /**
     * @return void
     */
    public function confirmPhoneAndRequestCoordinates()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->confirmPhoneAndRequestCoordinates();
    }

    /**
     * @return void
     */
    public function getCoordinatesAndRequestOrderType()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->getCoordinatesAndRequestOrderType();
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

    /**
     * @return void
     * @throws ApiServerException
     * @throws MenuListEmptyException
     */
    public function orderProducts()
    {
        (new OrderConfirmation($this->telegram, $this->updates))->orderProducts();
    }

    /**
     * @param array $product
     * @return string
     */
    protected function preparedText(array $product): string
    {
        $lang = app()->getLocale();
        return ($product["name_{$lang}"] ?? $product['name_uz'])
            . PHP_EOL . PHP_EOL . __("Narxi") . ": " . ($product['price'] ?? 0) . " " . __('so\'m');
    }

    /**
     * @param string|null $url
     * @return string
     * @throws FileNotFoundException
     */
    private function getImage(?string $url = null): string
    {
        if (!is_null($url) && @getimagesize($url)) {
            return file_get_contents($url);
        }
        return Storage::disk('assets')->get('products/default-image.jpg');
    }

    /**
     * @return Basket
     */
    protected function getBasket(): Basket
    {
        return Basket::firstOrCreate([
            'bot_user_id' => $this->chat_id,
            'is_finished' => false
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @return Model|null
     */
    protected function checkProduct(int $id, int $type): ?Model
    {
        return Basket::query()->firstWhere([
            ['product_id', '=', $id],
            ['product_type', '=', $type],
            ['is_finished', '=', true],
            ['bot_user_id', '=', $this->chat_id]
        ]);
    }

    public function getModifiedProduct()
    {
        return Basket::query()->firstWhere([
            ['is_finished', '=', false],
            ['is_modified', '=', true],
            ['bot_user_id', '=', $this->chat_id]
        ]);
    }
}
