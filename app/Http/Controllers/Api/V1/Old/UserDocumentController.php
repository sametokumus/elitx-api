<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\UserDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class UserDocumentController extends Controller
{
    public function getUserDocuments()
    {
        try {
            $user_document = UserDocument::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['user_document' => $user_document]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addUserDocuments(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'short_description' => 'required',
                'order' => 'required',
                'active' => 'required'
            ]);
            UserDocument::query()->insert([
                'name' => $request->name,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'order' => $request->order,
                'active' => $request->active
            ]);

            return response(['message' => 'Döküman ekleme işlemi başarılı.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

    public function updateUserDocuments(Request $request,$document_id){
        try {
            $request->validate([
                'name' => 'required',
                'order' => 'required',
                'short_description' => 'required',
            ]);

            $user_documents = UserDocument::query()->where('id',$document_id)->update([
                'name' => $request->name,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'order' => $request->order
            ]);

            return response(['message' => 'Döküman güncelleme işlemi başarılı.','status' => 'success','object' => ['user_documents' => $user_documents]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

    public function deleteUserDocuments($document_id){
        try {

            $user_document = UserDocument::query()->where('id',$document_id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Döküman silme işlemi başarılı.','status' => 'success','object' => ['user_document' => $user_document]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }


}
