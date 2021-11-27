<?php


namespace App\Constants;


class LanguageConstants
{
    const UZ = 'uz';
    const RU = 'ru';
    const EN = 'en';


    static public function list()
    {
        return [
            self::UZ => '🇺🇿',
            self::RU => '🇷🇺',
            self::EN => '🇬🇧',
        ];
    }

    /**
     * @param string $lang
     * @return string
     */
    static public function key(string $lang): string
    {
        if (!in_array($lang, self::list())) {
            return "";
        }

        return [
            '🇺🇿' => self::UZ,
            '🇷🇺' => self::RU,
            '🇬🇧' => self::EN,
        ][$lang];
    }
}
