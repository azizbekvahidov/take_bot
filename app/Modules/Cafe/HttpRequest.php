<?php


namespace App\Modules\Cafe;


use App\Exceptions\ApiServerException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class HttpRequest
{

    /**
     * @return array|bool|mixed
     */
    public static function getMenuList()
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::get($base_url . '/menu/category');

        if ($request->successful()) {
            return $request->json();
        }

        return [];
    }

    /**
     * @param int $category_id
     * @return array|bool|mixed
     */
    public static function getProductList(int $category_id)
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::get($base_url . "/menu/list?category_id={$category_id}");

        if ($request->successful()) {
            return $request->json();
        }

        return [];
    }

    /**
     * @param int $product_id
     * @param int $type
     * @return array|bool|mixed
     */
    public static function getProductDetail(int $product_id, int $type)
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::withHeaders([
            'Accept' => 'application/json'
        ])->get($base_url . "/product", [
            'product_id' => $product_id,
            'type' => $type,
        ]);
        if ($request->successful()) {
            return $request->json();
        }
        return [];
    }

    /**
     * @return array|bool|mixed
     */
    public static function getFilialList()
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::get($base_url . "/filial");

        if ($request->successful()) {
            return $request->json();
        }

        return [];
    }

    /**
     * @param int $filial_id
     * @return array|bool|mixed
     */
    public static function getFilialDetail(int $filial_id)
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::get($base_url . "/filial/{$filial_id}");

        if ($request->successful()) {
            return $request->json();
        }

        return [];
    }

    /**
     * @param mixed $orders
     * @return array|bool|mixed
     * @throws ApiServerException
     */
    public static function postData(Collection $orders)
    {
        $base_url = config('services.telegram.cafe_client_url');
        $request = Http::withHeaders([
            'Accept' => 'application/json'
        ])->post($base_url . '/delivery/store', self::params($orders));

        if (!$request->successful()) {
            throw new ApiServerException('Serverda hatolik, buyurtma bazaga yozilmadi');
        }
        return $request->json();
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
                    'order_type' => $order->type,
                    'products' => [],
                    'latitude' => $order->latitude,
                    'longitude' => $order->longitude
                ];
            }
            $prepared_data['products'][] = [
                'product_id' => $order->product_id,
                'type' => $order->product_type,
                'amount' => (double)$order->amount
            ];
        }
        return $prepared_data;
    }
}
