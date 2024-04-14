<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\ProductTab;
use Illuminate\Database\QueryException;

class TabController extends Controller
{
    public function getTabs()
    {
        try {
            $tabs = ProductTab::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['tabs' => $tabs]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getTabById($type_id)
    {
        try {
            $tabs = ProductTab::query()->where('active',1)->where('id',$type_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['tabs' => $tabs]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
