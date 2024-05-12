<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class SupportController extends Controller
{
    public function getSupportCategories()
    {
        try {
            $categories = SupportCategory::query()->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function addSupportMessage(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required',
                'message' => 'required'
            ]);
            $admin = Auth::user();

            SupportMessage::query()->insert([
                'request_id' => $request->request_id,
                'message' => $request->message,
                'user_id' => $admin->id,
                'user_type' => 1,
            ]);

            SupportRequest::query()->where('id', $request->request_id)->update([
                'status_id' => 2,
            ]);

            return response(['message' => 'Mesaj gönderme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function getSupportList()
    {
        try {
            $supports = SupportRequest::query()
                ->leftJoin('support_categories', 'support_categories.id', '=', 'support_requests.category_id')
                ->leftJoin('support_statuses', 'support_statuses.id', '=', 'support_requests.status_id')
                ->selectRaw('support_requests.*, support_categories.name as category_name, support_statuses.name as status_name')
                ->where('support_requests.active', 1)
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['supports' => $supports]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function getSupportConversation($request_id)
    {
        try {
            $support = SupportRequest::query()
                ->leftJoin('support_categories', 'support_categories.id', '=', 'support_requests.category_id')
                ->leftJoin('support_statuses', 'support_statuses.id', '=', 'support_requests.status_id')
                ->selectRaw('support_requests.*, support_categories.name as category_name, support_statuses.name as status_name')
                ->where('support_requests.active', 1)
                ->where('support_requests.id', $request_id)
                ->first();

            $support['shop'] = Shop::query()->where('id', $support->user_id)->first();
            $support['messages'] = SupportMessage::query()->where('request_id', $request_id)->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['support' => $support]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
