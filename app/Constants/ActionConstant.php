<?php

namespace App\Constants;

use App\Telegram\Basket;
use App\Telegram\Menu;

class ActionConstant
{
    const MENU = Menu::class;

    const BASKET = Basket::class;

    /**
     * @return string[]
     */
    public static function mainActionsList(): array
    {
        return [
            self::MENU,
            self::BASKET,
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
//                __(MainMenuButtons::ALTER_LANGUAGE) => self::ALTER_LANGUAGE,
                __(MainMenuButtons::BASKET) => self::BASKET
            ][$button] ?? null;
    }
}
