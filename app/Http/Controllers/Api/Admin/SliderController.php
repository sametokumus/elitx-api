<?php

namespace App\Http\Controllers\Api\Admin;

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
                'content_tag' => $request->content_tag,
                'content_text' => $request->content_text,
                'content_subtext' => $request->content_subtext,
                'btn_text' => $request->btn_text,
                'btn_url' => $request->btn_url,
                'order' => $request->order
            ]);
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
                'content_tag' => $request->content_tag,
                'content_text' => $request->content_text,
                'content_subtext' => $request->content_subtext,
                'btn_text' => $request->btn_text,
                'btn_url' => $request->btn_url,
                'order' => $request->order
            ]);
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
