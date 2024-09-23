<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarConfirm;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    public function getCarConfirmed($id){
        try {
            $admin = Auth::user();
            CarConfirm::query()->insert([
                'car_id' => $id,
                'admin_id' => $admin->id,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);
            $car = Car::query()->where('id', $id)->first();
            if ($car->owner_type == 2){
                Car::query()->where('id',$id)->update([
                    'status_id' => 2
                ]);
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getCarRejected($id){
        try {
            Car::query()->where('id',$id)->update([
                'status_id' => 3
            ]);
            $admin = Auth::user();
            CarConfirm::query()->insert([
                'car_id' => $id,
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
