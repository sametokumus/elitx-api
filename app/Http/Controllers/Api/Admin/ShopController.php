<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function getShops(){
        try {
            $shops = Shop::query()
                ->where('shops.register_completed', 1)
                ->get(['shops.id', 'shops.user_name', 'shops.name', 'shops.email', 'shops.phone_number',
                    'shops.register_completed', 'shops.verified', 'shops.confirmed', 'shops.confirmed_at', 'shops.created_at']);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shops' => $shops]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getShopById($id){
        try {
            $shop = Shop::query()->where('id',$id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shop' => $shop]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
