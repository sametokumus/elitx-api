<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\UserType;
use Illuminate\Database\QueryException;

class SliderController extends Controller
{
    public function getSliders()
    {
        try {
            $sliders = Slider::query()
                ->where('active',1)
                ->orderBy('order')
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['sliders' => $sliders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getSliderById($slider_id){
        try {
            $sliders = Slider::query()->where('id',$slider_id)->first();
            if($sliders->user_type == 0){
                $sliders['user_type_name'] = "Tüm Kullanıcılar";
            }else{
                $user_type = UserType::query()->where('id', $sliders->user_type)->first();
                $sliders['user_type_name'] = $user_type->name;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['sliders' => $sliders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
