<?php

namespace App\Telegram;

use App\Constants\LanguageConstant;
use App\Constants\MainMenuButtons;
use App\Constants\MethodConstant;
use App\Exceptions\ApiServerException;
use App\Exceptions\MenuListEmptyException;
use App\Modules\Cafe\HttpRequest;

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

    /**
     * @throws \Exception
     */
    public static function menusList(array $list): array
    {
        if (empty($list)) {
            throw new ApiServerException(__('Menyuni ro\'yhati kelmadi, Serverda hatolik ro\'y berdi'));
        } elseif (empty($list['data'])) {
            throw new MenuListEmptyException(__('Menyu bo\'sh'));
        }
        $lang = app()->getLocale();
        $return_items = [];
        $temp = [];
        $last = (count($list['data']) - 1);
        foreach ($list['data'] as $key => $item) {
            $temp[] = [
                'text' => $item["name_{$lang}"] ?? $item['name_uz'],
                'callback_data' => implode('|', [
                    'class' => Menu::class,
                    'method' => MethodConstant::GET_MENU_SEND_PRODUCTS_LIST,
                    'data' => $item['id']
                ])
            ];
            if (count($temp) === 2 || $key === $last) {
                $return_items[] = $temp;
                $temp = [];

                if ($key === $last) {
                    $return_items[][] = ['text' => __('Ortga'),
                        'callback_data' => implode('|', [
                            'class' => Menu::class,
                            'method' => MethodConstant::GET_MENU_SEND_PRODUCTS_LIST,
                            'data' => 'Ortga'
                        ])
                    ];
                }
            }
        }
        return $return_items;
    }

    /**
     * @param array $list
     * @return array
     * @throws ApiServerException
     * @throws MenuListEmptyException
     */
    public static function productsList(array $list): array
    {
        if (empty($list)) {
            throw new ApiServerException('Maxsulotlar ro\'yhati kelmadi, Serverda hatolik ro\'y berdi');
        } elseif (empty($list['data'])) {
            throw new MenuListEmptyException(__('Maxsulotlar ro\'yhati bo\'sh'));
        }
        $lang = app()->getLocale();
        $return_items = [];
        $temp = [];
        $last = (count($list['data']) - 1);
        foreach ($list['data'] as $key => $item) {
            $temp[] = [
                'text' => $item['product']["name_{$lang}"] ?? $item['product']['name_uz'],
                'callback_data' => implode('|', [
                    'class' => Menu::class,
                    'method' => MethodConstant::GET_PRODUCT_DETAIL,
                    'data' => "{$item['id']},{$item['type']}"
                ])
            ];
            if (count($temp) === 2 || $key === $last) {
                $return_items[] = $temp;
                $temp = [];

                if ($key === $last) {
                    $return_items[][] = ['text' => __('Ortga'),
                        'callback_data' => implode('|', [
                            'class' => Menu::class,
                            'method' => MethodConstant::GET_PRODUCT_DETAIL,
                            'data' => 'Ortga'
                        ])
                    ];
                }
            }
        }
        return $return_items;
    }

    /**
     * @param float $count
     * @return \array[][]
     */
    public static function productDetails(float $count = 1): array
    {
        return [
            [
                [
                    'text' => '➖',
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::COUNT_AMOUNT_OF_PRODUCT,
                        'data' => -0.5
                    ]),
                ],
                [
                    'text' => $count,
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::COUNT_AMOUNT_OF_PRODUCT,
                        'data' => 0
                    ]),
                ],
                [
                    'text' => '➕',
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::COUNT_AMOUNT_OF_PRODUCT,
                        'data' => 0.5
                    ]),
                ],
            ],
            [
                [
                    'text' => __('Savatga qo\'shish'),
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::ORDER_PRODUCT,
                        'data' => 'another'
                    ]),
                ],
                [
                    'text' => __('Buyurtma berish'),
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::ORDER_PRODUCT,
                        'data' => 'order'
                    ]),
                ],
            ],
            [
                [
                    'text' => __('Ortga'),
                    'callback_data' => implode('|', [
                        'class' => Menu::class,
                        'method' => MethodConstant::ORDER_PRODUCT,
                        'data' => 'Ortga'
                    ]),
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
                'text' => __('Ortga')
            ]);
        }

        return $buttons;
    }

    /**
     * @throws ApiServerException
     * @throws MenuListEmptyException
     */
    public static function getFilialList(array $list): array
    {
        if (empty($list)) {
            throw new ApiServerException('Filiallar ro\'yhati bo\'sh, serverda xatolik');
        } elseif (empty($list['data'])) {
            throw new MenuListEmptyException(__('Filiallar bo\'sh'));
        }

        $lang = app()->getLocale();
        $last_position = count($list['data']) - 1;
        $return_array = [];
        $temp_array = [];
        foreach ($list['data'] as $key => $item) {
            $temp_array[] = [
                'text' => ($item["name_{$lang}"] ?? $item["name"])
            ];
            if (count($temp_array) == 2 || $key === $last_position) {
                $return_array[] = $temp_array;
                $temp_array = [];
            }
        }
        $return_array[] = [
            [
                'text' => __("Ortga"),
                'callback_data' => implode('|', [
                    'class' => OrderConfirmation::class,
                    'method' => '',
                    'data' => 'filial_back'
                ])
            ]
        ];
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
                    'text' => __('Ortga')
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
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
                [
                    'text' => __('Joyida')
                ]
            ],
            [
                [
                    'text' => __('Ortga')
                ]
            ]
        ];
    }

    /**
     * @return \array[][]
     */
    public static function orderProducts(): array
    {
        return [
            [
                [
                    'text' => __('Ortga')
                ],
                [
                    'text' => __('Buyurtma berish')
                ],
            ]
        ];
    }
}
