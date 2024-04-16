<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class SliderController extends Controller
{

    public function addSlider(Request $request)
    {
        try {
            $request->validate([
                'content_text' => 'required'
            ]);
            $slider_id = Slider::query()->insertGetId([
                'content_title' => $request->content_title,
                'content_text' => $request->content_text,
                'order' => $request->order,
                'user_type' => $request->user_type
            ]);
            if ($request->hasFile('bg_url')) {
                $rand = uniqid();
                $image = $request->file('bg_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Slider/'), $image_name);
                $image_path = "/images/Slider/" . $image_name;
                Slider::query()->where('id',$slider_id)->update([
                    'bg_url' => $image_path
                ]);
            }
            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Slider/'), $image_name);
                $image_path = "/images/Slider/" . $image_name;
                Slider::query()->where('id',$slider_id)->update([
                    'image_url' => $image_path
                ]);
            }
            return response(['message' => 'Slider ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updateSlider(Request $request,$id){
        try {
            $request->validate([
                'content_text' => 'required'
            ]);

            $slider = Slider::query()->where('id',$id)->update([
                'content_title' => $request->content_title,
                'content_text' => $request->content_text,
                'order' => $request->order,
                'user_type' => $request->user_type
            ]);

            if ($request->hasFile('bg_url')) {
                $rand = uniqid();
                $image = $request->file('bg_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Slider/'), $image_name);
                $image_path = "/images/Slider/" . $image_name;
                Slider::query()->where('id',$id)->update([
                    'bg_url' => $image_path
                ]);
            }
            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Slider/'), $image_name);
                $image_path = "/images/Slider/" . $image_name;
                Slider::query()->where('id',$id)->update([
                    'image_url' => $image_path
                ]);
            }
            return response(['message' => 'Slider güncelleme işlemi başarılı.','status' => 'success','object' => ['slider' => $slider]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deleteSlider($id){
        try {

            Slider::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Slider silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
