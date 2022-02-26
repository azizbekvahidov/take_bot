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
        $this->one_time_keyboard = $one_time_keyboard;
        $this->resize_keyboard = $resize_keyboard;
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
     * @return $this
     */
    public function inline(): ReplyMarkup
    {
        $this->is_inline = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function oneTimeKeyboard(): ReplyMarkup
    {
        $this->one_time_keyboard = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function resizeKeyboard(): ReplyMarkup
    {
        $this->resize_keyboard = true;
        return $this;
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
        $this->keyboard['one_time_keyboard'] = $this->one_time_keyboard;
        $this->keyboard['resize_keyboard'] = $this->resize_keyboard;
    }
}
