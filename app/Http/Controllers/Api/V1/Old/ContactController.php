<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\ContactForm;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class ContactController extends Controller
{
    public function addContactForm(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required',
                'message' => 'required',
            ]);
            ContactForm::query()->insert([
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message
            ]);
            return response(['message' => 'Mesaj gönderme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }
}
