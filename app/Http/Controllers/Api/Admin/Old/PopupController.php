<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class PopupController extends Controller
{
    public function addPopup(Request $request)
    {
        try {
            $request->validate([
                'image_url' => 'required'
            ]);
            Popup::query()->update([
               'show_form' => 0
            ]);
            $popup_id = Popup::query()->insertGetId([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'description' => $request->description,
                'pages' => $request->pages,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]);
            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Popup/'), $image_name);
                $image_path = "/images/Popup/" . $image_name;
                Popup::query()->where('id',$popup_id)->update([
                    'image_url' => $image_path
                ]);
            }
            return response(['message' => 'Popup ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updatePopup(Request $request,$id){
        try {
//            $request->validate([
//                '' => 'required'
//            ]);

            $popup = Popup::query()->where('id',$id)->update([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'description' => $request->description,
                'pages' => $request->pages,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]);

            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Popup/'), $image_name);
                $image_path = "/images/Popup/" . $image_name;
                Popup::query()->where('id',$id)->update([
                    'image_url' => $image_path
                ]);
            }
            return response(['message' => 'Popup güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deletePopup($id){
        try {

            Popup::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Popup silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function changePopupStatus($id, $status){
        try {
            if ($status == 0) {
                Popup::query()->where('id', $id)->update([
                    'show_popup' => 0,
                ]);
            }else{
                Popup::query()->where('id', $id)->update([
                    'show_popup' => 1
                ]);
            }
            return response(['message' => 'Popup durum işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function changePopupFormStatus($id, $status){
        try {
            if ($status == 0) {
                Popup::query()->where('id', $id)->update([
                    'show_form' => 0,
                ]);
            }else{
                Popup::query()->where('id', $id)->update([
                    'show_form' => 1
                ]);
            }
            return response(['message' => 'Popup form durum işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
