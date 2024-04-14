<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\ContactRule;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class ContactRulesController extends Controller
{
    public function getContactRules()
    {
        try {
            $contact_rules = ContactRule::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['contact_rules' => $contact_rules]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addContactRules(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'description' => 'required',
                'order' => 'required',
                'active' => 'required'
            ]);
            ContactRule::query()->insert([
                'name' => $request->name,
                'description' => $request->description,
                'order' => $request->order,
                'active' => $request->active
            ]);

            return response(['message' => 'İletişim kuralı ekleme işlemi başarılı.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

    public function updateContactRules(Request $request,$id){
        try {
            $request->validate([
                'name' => 'required',
                'description' => 'required'
            ]);

            $contact_rules = ContactRule::query()->where('id',$id)->update([
                'name' => $request->name,
                'description' => $request->description,
                'order' => $request->order
            ]);

            return response(['message' => 'Güncelleme işlemi başarılı.','status' => 'success','object' => ['contact_rules' => $contact_rules]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }


}
