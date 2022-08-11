<?php

namespace App\Constants;

class MainMenuButtons
{

    const MENU = 'Menyu';

    const BASKET = 'Savat';

    const ALTER_LANGUAGE = 'Tilni o\'zgartirish';

    const MENU_LIST = 'Menyu (rasm)';

    /**
     * @return array
     */
    public static function list(): array
    {
        return [
            __(self::MENU),
            __(self::BASKET),
            __(self::ALTER_LANGUAGE),
            __(self::MENU_LIST),
        ];
    }

    /**
     * @return string[]
     */
    public static function getIndex(): array
    {
        return [
            __(self::MENU) => self::MENU,
            __(self::BASKET) => self::BASKET,
            __(self::ALTER_LANGUAGE) => self::ALTER_LANGUAGE,
            __(self::MENU_LIST) => self::MENU_LIST,
        ];
    }
}
