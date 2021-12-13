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
            ],
            [
                [
                    'text' => __(MainMenuButtons::BASKET)
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
                    'text' => trans_choice("uz", 0),
                ],
                [
                    'text' => trans_choice("ru", 0),
                ],
                [
                    'text' => trans_choice("en", 0),
                ],
            ]
        ];
    }

    public static function inlineLanguagesList(string $lang = ""): array
    {
        return [
            [
                [
                    'text' => trans_choice("uz", $lang === 'uz' ? 1 : 0),
                    'callback_data' => 'uz',
                ],
            ],
            [
                [
                    'text' => trans_choice("ru", $lang === 'ru' ? 1 : 0),
                    'callback_data' => 'ru',
                ],
            ],
            [
                [
                    'text' => trans_choice("en", $lang === 'en' ? 1 : 0),
                    'callback_data' => 'en',
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
        $language = app()->getLocale();
        foreach ($list as $key => $item) {
            $name = $item["name_{$language}"] ?: $item["name_uz"];
            array_push($temp_array, [
                'text' => $name,
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
                    'text' => 1,
                    'callback_data' => json_encode(['event' => 1]),
                ],
                [
                    'text' => 2,
                    'callback_data' => json_encode(['event' => 2]),
                ],
                [
                    'text' => 3,
                    'callback_data' => json_encode(['event' => 3]),
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
    public static function sendConfirmButton(bool $send_back_button = true): array
    {
        $buttons = [
            [
                [
                    'text' => __('Tasdiqlayman')
                ],
            ]
        ];

        if ($send_back_button) {
            array_unshift($buttons[0], [
                'text' => __('Ortga qaytish')
            ]);
        }

        return $buttons;
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
            if ($key === $last_position) {
                array_push($return_array, [
                    [
                        'text' => __("Ortga qaytish"),
                        'callback_data' => "filial_back"
                    ]
                ]);
            }
        }
        return $return_array;

    }

    /**
     * @return \array[][]
     */
    public static function backButton(): array
    {
        return [
            [
                [
                    'text' => __("Ortga qaytish")
                ]
            ]
        ];
    }

    public static function orderProducts(): array
    {
        return [
            [
                [
                    'text' => __('Ortga qaytish')
                ],
                [
                    'text' => __('Buyurtma berish')
                ],
            ]
        ];
    }

    /**
     * @param array $product_ids
     * @return array
     */
    public static function getOrderedProductsList(array $product_ids): array
    {
        $return_data = [
            [
                [
                    'text' => __('Buyurtma berish') . " ✅",
                    'callback_data' => 'order'
                ]
            ]
        ];
        $temp_array = [];
        $last_index = count($product_ids) - 1;
        foreach ($product_ids as $key => $id) {
            array_push($temp_array, [
                'text' => ($key + 1) . " ❌",
                'callback_data' => $id
            ]);
            if (count($temp_array) === 4 || $key == $last_index) {
                array_push($return_data, $temp_array);
                $temp_array = [];
            }
        }
        array_push($return_data, [
            [
                'text' => __("Ortga qaytish"),
                'callback_data' => 'basket_back'
            ]
        ]);
        return $return_data;
    }

    public static function orderTypes(): array
    {
        return [
            [
                [
                    'text' => __('Olib ketish')
                ],
                [
                    'text' => __('Yetkazib berish')
                ],
            ],
            [
                [
                    'text' => __('Ortga qaytish')
                ]
            ]
        ];
    }
}
