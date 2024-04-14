<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\CustomSeo;
use Illuminate\Database\QueryException;

class SeoController extends Controller
{
    public function getSeos()
    {
        try {
            $seos = CustomSeo::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['seos' => $seos]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getSeoById($seo_id){
        try {
            $seos = CustomSeo::query()->where('id',$seo_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['seos' => $seos]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
