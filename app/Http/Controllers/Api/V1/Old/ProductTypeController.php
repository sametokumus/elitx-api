<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Database\QueryException;

class ProductTypeController extends Controller
{
    public function getProductTypes()
    {
        try {
            $product_type = ProductType::query()->where('active',1)->orderBy('order')->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_type' => $product_type]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getProductTypeById($type_id)
    {
        try {
            $product_type = ProductType::query()->where('active',1)->where('id',$type_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_type' => $product_type]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
