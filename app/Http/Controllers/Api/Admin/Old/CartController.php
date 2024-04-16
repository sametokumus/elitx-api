<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Database\QueryException;

class CartController extends Controller
{
    public function getAllCart(){
        try {
            $carts = Cart::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['carts' => $carts]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

}
