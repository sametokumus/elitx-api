<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\ShippingType;
use Illuminate\Database\QueryException;

class ShippingTypeController extends Controller
{
    public function getShippingTypes(){
        try {
            $shipping_types = ShippingType::query()->where('active',1)->get();
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success','object' => ['shipping_types' => $shipping_types]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }
    public function getShippingTypeById($id){
        try {
            $shipping_type = ShippingType::query()->where('id', $id)->where('active',1)->first();
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success','object' => ['shipping_type' => $shipping_type]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }
}
