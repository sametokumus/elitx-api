<?php

namespace App\Http\Controllers\Api\V1\Old;

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
                ->where('active',1);
            $sliders = $sliders->get();

            foreach ($sliders as $slider){
                if($slider->user_type == 0){
                    $slider['user_type_name'] = "Tüm Kullanıcılar";
                }else{
                    $user_type = UserType::query()->where('id', $slider->user_type)->first();
                    $slider['user_type_name'] = $user_type->name;
                }
            }
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
