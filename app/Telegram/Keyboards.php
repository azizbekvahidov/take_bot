<?php


namespace App\Telegram;


class Keyboards
{

    /**
     * @return \array[][]
     */
    public static function sendMainMenu(): array
    {
        return [
            [
                [
                    'text' => __('Menyu')
                ],
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    public static function languagesList(): array
    {
        return [
            [
                [
                    'text' => trans_choice("🇺🇿", 0),
                ],
                [
                    'text' => trans_choice("🇷🇺", 0),
                ],
                [
                    'text' => trans_choice("🇬🇧", 0),
                ],
            ]
        ];
    }

    public static function phoneRequest(): array
    {
        return [
            [
                [
                    'text' => __("Raqamni ulashish"),
                    'request_contact' => true,
                ]
            ]
        ];
    }
}
