<?php


namespace App\Telegram;


use App\Constants\MainMenuButtons;
use App\Modules\Cafe\HttpRequest;

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
                    'text' => __(MainMenuButtons::MENU)
                ],
                [
                    'text' => __(MainMenuButtons::ALTER_LANGUAGE)
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

    /**
     * @return array
     */
    public static function menuList(): array
    {
        $list = HttpRequest::getMenuList()['data'];
        $last_position = count($list) - 1;
        $return_array = [];
        $temp_array = [];
        foreach ($list as $key => $item) {
            array_push($temp_array, [
                'text' => $item["name_uz"],
                'callback_data' => "category={$item['id']}"
            ]);
            if (count($temp_array) % 2 == 0 || $key === $last_position) {
                array_push($return_array, $temp_array);
                $temp_array = [];
            }
            if ($key === $last_position) {
                array_push($return_array, [
                    [
                        'text' => __("Ortga qaytish"),
                        'callback_data' => "category_back"
                    ]
                ]);
            }
        }
        return $return_array;
    }

    /**
     * @param array $product
     * @return array
     */
    public static function product(array $product): array
    {
        return [
            [
                [
                    'text' => __('Buyurtma qilish'),
                    'callback_data' => json_encode([
                        'product_id' => (int)$product['product']['id'],
                        'product_type' => (int)$product['type'],
                        'category_id' => (int)$product['category_id'],
                    ])
                ],
            ],
            [
                [
                    'text' => __('Ortga qaytish'),
                    'callback_data' => 'product_back'
                ],
            ],
        ];
    }

    /**
     * @param int $category_id
     * @return \array[][]
     */
    public static function productAmountList(int $category_id): array
    {
        return [
            [
                [
                    'text' => 0.5,
                    'callback_data' => json_encode(['event' => 0.5]),
                ],
                [
                    'text' => 1,
                    'callback_data' => json_encode(['event' => 1]),
                ],
            ],
            [
                [
                    'text' => 1.5,
                    'callback_data' => json_encode(['event' => 1.5]),
                ],
                [
                    'text' => 2,
                    'callback_data' => json_encode(['event' => 2]),
                ],
            ],
            [
                [
                    'text' => 2.5,
                    'callback_data' => json_encode(['event' => 2.5]),
                ],
                [
                    'text' => __('Boshqa'),
                    'callback_data' => json_encode(['event' => 'other']),
                ],
            ],
            [
                [
                    'text' => __('Ortga qaytish'),
                    'callback_data' => json_encode([
                        'event' => 'product_amount_back',
                        'category_id' => $category_id
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function productCustomAmountBack(): array
    {
        return [
            [
                [
                    'text' => __('Ortga qaytish'),
                    'callback_data' => 'product_amount_back'
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    public static function suggestAnotherProductOrFinishChoice(): array
    {
        return [
            [
                [
                    'text' => __('Yana boshqa ovqat tanlash'),
                    'callback_data' => 'another_meal'
                ],
            ],
            [
                [
                    'text' => __('Buyurtma berish'),
                    'callback_data' => 'order'
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    public static function sendConfirmButton(): array
    {
        return [
            [
                [
                    'text' => __('Tasdiqlayman')
                ]
            ]
        ];
    }

    public static function getFilialList(): array
    {
        $list = HttpRequest::getFilialList()['data'];

        $last_position = count($list) - 1;
        $return_array = [];
        $temp_array = [];
        foreach ($list as $key => $item) {
            array_push($temp_array, [
                'text' => ($item["name_uz"] ?? $item["name"]),
                'callback_data' => $item['id']
            ]);
            if (count($temp_array) % 2 == 0 || $key === $last_position) {
                array_push($return_array, $temp_array);
                $temp_array = [];
            }
        }
        return $return_array;

    }

    public static function orderProducts(): array
    {
        return [
            [
                [
                    'text' => __('Buyurtma berish')
                ]
            ]
        ];
    }
}
