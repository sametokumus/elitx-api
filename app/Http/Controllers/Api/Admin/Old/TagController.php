<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class TagController extends Controller
{

    public function addTag(Request $request)
    {

        try {

            $request->validate([
                'name'=>'required'
            ]);
            Tag::query()->insert([
                'name' => $request->name
            ]);
            return response(['message' => 'Etiket ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateTag(Request $request,$id){
        try {
            Tag::query()->where('id',$id)->update([
                'name' => $request->name
            ]);

            return response(['message' => 'Etiket güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','ar' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteTag($id){
        try {
            Tag::query()->where('id',$id)->update([
                'active'=>0
            ]);
            return response(['message' => 'Etiket silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','ar' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

}
