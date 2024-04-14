<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Database\QueryException;

class CarrierController extends Controller
{
    public function getCarriers()
    {
        try {
            $carriers = Carrier::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['carriers' => $carriers]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getCarrierById($id)
    {
        try {
            $carrier = Carrier::query()->where('id',$id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['carrier' => $carrier]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

}
