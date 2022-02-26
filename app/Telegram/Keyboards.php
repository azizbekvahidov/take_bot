<?php

namespace App\Telegram;

use App\Constants\LanguageConstant;
use App\Constants\MainMenuButtons;

class Keyboards
{

    /**
     * @return \array[][]
     */
    public static function mainMenuButtons(): array
    {
        return [
            [
                [
                    'text' => __(MainMenuButtons::MENU)
                ],
            ],
            [
                [
                    'text' => __(MainMenuButtons::BASKET)
                ],
                [
                    'text' => __(MainMenuButtons::ALTER_LANGUAGE)
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    public static function langs(): array
    {
        return [
            [
                [
                    'text' => __(LanguageConstant::UZ)
                ],
                [
                    'text' => __(LanguageConstant::RU)
                ],
                [
                    'text' => __(LanguageConstant::EN)
                ],
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    public static function sendPhoneRequest(): array
    {
        return [
            [
                [
                    'text' => __('Raqamni ulashish'),
                    'request_contact' => true
                ]
            ]
        ];
    }
}
