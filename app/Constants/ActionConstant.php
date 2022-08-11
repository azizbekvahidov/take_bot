<?php

namespace App\Constants;

use App\Telegram\Basket;
use App\Telegram\Language;
use App\Telegram\Menu;
use App\Telegram\MenuList;

class ActionConstant
{
    const MENU = Menu::class;

    const BASKET = Basket::class;

    const ALTER_LANGUAGE = Language::class;

    const MENU_LIST = MenuList::class;

    /**
     * @return string[]
     */
    public static function mainActionsList(): array
    {
        return [
            self::MENU,
            self::BASKET,
            self::ALTER_LANGUAGE,
            self::MENU_LIST,
        ];
    }

    /**
     * @param string $button
     * @return string|null
     */
    public static function getActionWithMainMenuButton(string $button): ?string
    {
        return [
                __(MainMenuButtons::MENU) => self::MENU,
                __(MainMenuButtons::ALTER_LANGUAGE) => self::ALTER_LANGUAGE,
                __(MainMenuButtons::BASKET) => self::BASKET,
                __(MainMenuButtons::MENU_LIST) => self::MENU_LIST
            ][$button] ?? null;
    }
}
