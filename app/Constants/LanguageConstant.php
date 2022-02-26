<?php

namespace App\Constants;

class LanguageConstant
{

    const UZ = 'uz';

    const RU = 'ru';

    const EN = 'en';

    /**
     * @return array
     */
    public static function list(): array
    {
        return [
            __(self::UZ),
            __(self::RU),
            __(self::EN),
        ];
    }

    /**
     * @return string[]
     */
    public static function getKey(): array
    {
        return [
            __(self::UZ) => self::UZ,
            __(self::RU) => self::RU,
            __(self::EN) => self::EN,
        ];
    }
}
