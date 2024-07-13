<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserAddressesByOrderId($order_id){
        try {
            $order = Order::query()->where('id', $order_id)->first();
            $addresses = Address::query()
                ->leftJoin('cities', 'cities.id', '=', 'addresses.city_id')
                ->leftJoin('countries', 'countries.id', '=', 'addresses.country_id')
                ->selectRaw('addresses.*, cities.name as city_name, countries.name as country_name')
                ->where('user_id', $order->user_id)
                ->where('active', 1)
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'addresses' => $addresses,
                'billing_address_id' => $order->billing_address_id,
                'shipping_address_id' => $order->shipping_address_id
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
