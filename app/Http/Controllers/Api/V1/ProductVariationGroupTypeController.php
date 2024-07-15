<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductVariationGroupType;
use Illuminate\Database\QueryException;

class ProductVariationGroupTypeController extends Controller
{
    public function getProductVariationGroupTypes()
    {
        try {
            $variation_group_types = ProductVariationGroupType::query()->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['variation_group_types' => $variation_group_types]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductVariationGroupTypeById($id)
    {
        try {
            $variation_group_type = ProductVariationGroupType::query()->where('id', $id)->where('active', 1)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['variation_group_type' => $variation_group_type]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
