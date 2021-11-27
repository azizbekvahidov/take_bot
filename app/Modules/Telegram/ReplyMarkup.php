<?php


namespace App\Modules\Telegram;


class ReplyMarkup
{
    /**
     * @var false|mixed
     */
    private $one_time_keyboard;
    /**
     * @var false|mixed
     */
    private $resize_keyboard;
    /**
     * @var bool
     */
    private $is_inline;

    /**
     * @var array
     */
    private $keyboard = [];

    /**
     * ReplyMarkup constructor.
     * @param bool $one_time_keyboard
     * @param bool $resize_keyboard
     * @param bool $is_inline
     */
    public function __construct(bool $one_time_keyboard = false,
                                bool $resize_keyboard = false,
                                bool $is_inline = false)
    {
        $this->keyboard = [
            'one_time_keyboard' => $one_time_keyboard,
            'resize_keyboard' => $resize_keyboard
        ];
        $this->is_inline = $is_inline;
    }

    /**
     * @param array $keyboard
     * @return false|string
     */
    public function keyboard(array $keyboard)
    {

        return json_encode($this->getKeyboard($keyboard));
    }

    /**
     * @param array $keyboard
     * @return array|bool[]
     */
    private function getKeyboard(array $keyboard): array
    {
        $this->setKeyboardType($keyboard);
        return $this->keyboard;
    }

    /**
     * Set keyboard type (inline or not)
     * @param array $keyboard
     */
    private function setKeyboardType(array $keyboard)
    {
        $this->keyboard[$this->is_inline ? 'inline_keyboard' : 'keyboard'] = $keyboard;
    }
}
