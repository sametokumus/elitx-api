<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class OrderStatusController extends Controller
{
    public function addOrderStatus(Request $request){
        try {
            $order_status = OrderStatus::query()->where('name',$request->name)->first();
            if ($order_status){
                return response(['message' => 'Böyle bir kayıt veritabanında bulunmakta','status' => 'query-002']);
            }
            OrderStatus::query()->insert([
                'name' => $request->name,
                'run_on' => $request->run_on,
                'is_default' => $request->is_default
            ]);

            return response(['message' => 'Sipariş durumu ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateOrderStatus(Request $request,$id){
        try {

            OrderStatus::query()->where('id',$id)->update([
                'name' => $request->name,
                'run_on' => $request->run_on,
                'is_default' => $request->is_default
            ]);

            return response(['message' => 'Sipariş durumu güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateOrderAllStatus(Request $request){
        try {
            $ids = explode(',',$request->order_ids);

            foreach ($ids as $order_id) {
                $order = Order::query()->where('id', $order_id)->first();
                Order::query()->where('id', $order_id)->update([
                    'status_id' => $request->status_id
                ]);
                OrderStatusHistory::query()->insert([
                    'status_id' => $request->status_id,
                    'order_id' => $order->order_id
                ]);
            }

            return response(['message' => 'Sipariş durumu güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function deleteOrderStatus($id){
        try {
            OrderStatus::query()->where('id',$id)->update([
                'active' =>0
            ]);
            return response(['message' => 'Sipariş durumu silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function getOrderStatuses(){
        try {
            $order_statuses = OrderStatus::query()->where('active',1)->get();
            return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['order_statuses' => $order_statuses]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function getOrderStatusById($id){
        try {
            $order_status = OrderStatus::query()->where('id', $id)->where('active',1)->first();
            return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['order_status' => $order_status]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

}
