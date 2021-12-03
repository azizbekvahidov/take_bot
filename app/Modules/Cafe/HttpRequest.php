<?php


namespace App\Modules\Cafe;


use Illuminate\Support\Facades\Http;

class HttpRequest
{

    const BASE_URL = "http://87.237.234.154/api/v1";


    public static function getMenuList()
    {
        $request = Http::get(self::BASE_URL . '/menu/category');

        if ($request->successful()) {
            return $request->json();
        }

        return !$request->successful();
    }

    public static function getProductList(int $category_id)
    {
        $request = Http::get(self::BASE_URL . "/menu/list?category_id={$category_id}");

        if ($request->successful()) {
            return $request->json();
        }

        return !$request->successful();
    }

    public static function getFilialList()
    {
        $request = Http::get(self::BASE_URL . "/filial");

        if ($request->successful()) {
            return $request->json();
        }

        return !$request->successful();
    }
}
