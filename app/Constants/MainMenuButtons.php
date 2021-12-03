<?php


namespace App\Constants;


class MainMenuButtons
{
    const MENU = "Menyu";

    const ALTER_LANGUAGE = "Tilni o'zgartirish";

    /**
     * @return array
     */
    public static function list(): array
    {
        return [
            __(self::MENU),
            __(self::ALTER_LANGUAGE)
        ];
    }
}
