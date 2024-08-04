<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estate;
use App\Models\EstateConfirm;
use App\Models\Product;
use App\Models\ProductConfirm;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstateController extends Controller
{

    public function getEstateConfirmed($id){
        try {
            $admin = Auth::user();
            EstateConfirm::query()->insert([
                'estate_id' => $id,
                'admin_id' => $admin->id,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductRejected($id){
        try {
            Estate::query()->where('id',$id)->update([
                'status_id' => 3
            ]);
            $admin = Auth::user();
            EstateConfirm::query()->insert([
                'product_id' => $id,
                'admin_id' => $admin->id,
                'confirmed' => 2,
                'confirmed_at' => Carbon::now()
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
