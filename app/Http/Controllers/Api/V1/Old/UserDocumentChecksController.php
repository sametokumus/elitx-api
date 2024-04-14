<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\UserDocumentCheck;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class UserDocumentChecksController extends Controller
{
    public function getUserDocumentChecksByUserId($user_id)
    {
        try {
            $user_document_checks = UserDocumentCheck::query()->where('user_id',$user_id)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['user_document_checks' => $user_document_checks]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function updateUserDocumentChecksByUserId(Request $request,$document_id,$user_id)
    {
        try {
            $request->validate([
                'value' => 'required'
            ]);
            $user_document_checks = UserDocumentCheck::query()->where('user_id',$user_id)->where('document_id',$document_id)->update([
                'value' => $request->value
            ]);

            return response(['message' => 'Döküman düzenleme işlemi başarılı.', 'status' => 'success','object' => ['user_document_checks' => $user_document_checks]]);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

}
