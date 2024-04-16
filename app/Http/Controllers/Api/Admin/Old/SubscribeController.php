<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class SubscribeController extends Controller
{
    public function getSubscribers()
    {
        try {
            $subscribers = Subscriber::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['subscribers' => $subscribers]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getSubscriberById($subscriber_id){
        try {
            $subscriber = Subscriber::query()->where('id',$subscriber_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['subscriber' => $subscriber]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function updateSubscriber(Request $request,$id){
        try {
            $request->validate([
                'email' => 'required',
                'referrer' => 'required'
            ]);

            $subscriber = Subscriber::query()->where('id',$id)->update([
                'email' => $request->email,
                'referrer' => $request->referrer
            ]);

            return response(['message' => 'Subscriber güncelleme işlemi başarılı.','status' => 'success','object' => ['subscriber' => $subscriber]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deleteSubscriber($id){
        try {

            Subscriber::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Subscriber silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
