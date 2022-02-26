<?php

namespace App\Constants;

use App\Telegram\Menu;

class ActionConstant
{
    const MENU = Menu::class;

    public static function mainActionsList()
    {
        return [
            self::MENU
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
//                __(MainMenuButtons::BASKET) => self::BASKET
            ][$button] ?? null;
    }
}
