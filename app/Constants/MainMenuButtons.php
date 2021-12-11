<?php


namespace App\Constants;


class MainMenuButtons
{
    const MENU = "Menyu";

    const ALTER_LANGUAGE = "Tilni o'zgartirish";

    const BASKET = 'Savat';

    /**
     * @return array
     */
    public static function list(): array
    {
        return [
            __(self::MENU),
            __(self::ALTER_LANGUAGE),
            __(self::BASKET)
        ];
    }
}
