<?php


namespace App\Modules\Cafe;


use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @param mixed $orders
     * @return array|bool|mixed
     */
    public static function postData(Collection $orders)
    {

        $request = Http::post(self::BASE_URL . '/delivery/store', self::params($orders));
        if ($request->successful()) {
            return $request->json();
        }

        return !$request->successful();
    }

    /**
     * @param Collection $orders
     * @return array
     */
    protected static function params(Collection $orders): array
    {
        $prepared_data = [];
        foreach ($orders as $key => $order) {
            if ($key === 0) {
                $prepared_data = [
                    'phone' => "+{$order->phone}",
                    'name' => $order->name,
                    'address' => $order->address,
                    'filial_id' => $order->filial_id,
                    'products' => []
                ];
            }
            array_push($prepared_data['products'], [
                'product_id' => $order->product_id,
                'type' => $order->product_type,
                'amount' => (double)$order->amount
            ]);
        }
        return $prepared_data;
    }
}
