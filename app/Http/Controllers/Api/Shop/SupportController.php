<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductComment;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
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
    public function addSupportRequest(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required',
                'title' => 'required',
                'message' => 'required'
            ]);
            $shop = Auth::user();

            $request_id = SupportRequest::query()->insertGetId([
                'user_id' => $shop->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'status_id' => 1,
            ]);

            SupportMessage::query()->insert([
                'request_id' => $request_id,
                'message' => $request->message,
                'user_id' => $shop->id,
                'user_type' => 2,
            ]);

            return response(['message' => 'Talep ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function addSupportMessage(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required',
                'message' => 'required'
            ]);
            $shop = Auth::user();

            SupportMessage::query()->insert([
                'request_id' => $request->request_id,
                'message' => $request->message,
                'user_id' => $shop->id,
                'user_type' => 2,
            ]);

            SupportRequest::query()->where('id', $request->request_id)->update([
                'status_id' => 1,
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
            $shop = Auth::user();
            $supports = SupportRequest::query()
                ->leftJoin('support_categories', 'support_categories.id', '=', 'support_requests.category_id')
                ->leftJoin('support_statuses', 'support_statuses.id', '=', 'support_requests.status_id')
                ->selectRaw('support_requests.*, support_categories.name as category_name, support_statuses.name as status_name')
                ->where('support_requests.active', 1)
                ->where('support_requests.user_id', $shop->id)
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['supports' => $supports]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function getSupportConversation($request_id)
    {
        try {
            $shop = Auth::user();
            $support = SupportRequest::query()
                ->leftJoin('support_categories', 'support_categories.id', '=', 'support_requests.category_id')
                ->leftJoin('support_statuses', 'support_statuses.id', '=', 'support_requests.status_id')
                ->selectRaw('support_requests.*, support_categories.name as category_name, support_statuses.name as status_name')
                ->where('support_requests.active', 1)
                ->where('support_requests.user_id', $shop->id)
                ->where('support_requests.id', $request_id)
                ->first();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['support' => $support]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
