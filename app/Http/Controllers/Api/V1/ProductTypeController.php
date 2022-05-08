<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function getProductType()
    {
        try {
            $product_type = ProductType::query()->where('active',1)->get();
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
