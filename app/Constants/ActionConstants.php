<?php


namespace App\Constants;


use App\Telegram\Language;
use App\Telegram\Menu;
use App\Telegram\RegisterBotUser;

class ActionConstants
{
    const REGISTRATION = RegisterBotUser::class;

    const MENU = Menu::class;

    const ALTER_LANGUAGE = Language::class;

    /**
     * @return array
     */
    public static function mainActionsList(): array
    {
        return [
            __(self::MENU),
            __(self::ALTER_LANGUAGE)
        ];
    }

    /**
     * @param string $button
     * @return string|null
     */
    public static function getActionWithMainMenuButton(string $button): ?string
    {
        return [
                __(MainMenuButtons::MENU) => ActionConstants::MENU,
                __(MainMenuButtons::ALTER_LANGUAGE) => ActionConstants::ALTER_LANGUAGE
            ][$button] ?? null;
    }
}
