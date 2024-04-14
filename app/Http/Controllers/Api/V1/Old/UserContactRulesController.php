<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\UserContactRule;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class UserContactRulesController extends Controller
{
    public function getContactRulesByUserId($user_id)
    {
        try {
            $user_contact_rules = UserContactRule::query()->where('user_id',$user_id)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['user_contact_rules' => $user_contact_rules]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function updateContactRulesByUserId(Request $request,$user_id,$contact_rule_id)
    {
        try {
            $request->validate([
                'value' => 'required'
            ]);
            $user_contact_rules = UserContactRule::query()->where('user_id',$user_id)->where('contact_rule_id',$contact_rule_id)->update([
                'value' => $request->value
            ]);

            return response(['message' => 'Kullanıcı iletişim kuralı düzenleme işlemi başarılı.', 'status' => 'success','object' => ['user_contact_rules' => $user_contact_rules]]);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

}
