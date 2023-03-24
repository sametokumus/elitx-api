<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactForm;
use App\Models\ContactRule;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function getContactForms()
    {
        try {
            $contact_forms = ContactForm::query()->where('active',1)->orderByDesc('created_at')->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['contact_forms' => $contact_forms]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
