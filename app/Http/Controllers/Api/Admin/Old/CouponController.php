<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Coupons;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CouponController extends Controller
{
    public function addCoupon(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required',
                'count_of_uses' => 'required',
                'count_of_used' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'discount_type' => 'required',
                'discount' => 'required'
            ]);
            $coupon_id = Coupons::query()->insertGetId([
                'code' => $request->code,
                'count_of_uses' => $request->count_of_uses,
                'count_of_used' => $request->count_of_used,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'discount_type' => $request->discount_type,
                'discount' => $request->discount,
                'user_id' => $request->user_id,
                'group_id' => $request->group_id,
                'coupon_user_type' => $request->coupon_user_type
            ]);
            return response(['message' => 'Kupon ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updateCoupon(Request $request,$id){
        try {
            $request->validate([
                'code' => 'required',
                'count_of_uses' => 'required',
                'count_of_used' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'discount_type' => 'required',
                'discount' => 'required'
            ]);

            $coupon = Coupons::query()->where('id',$id)->update([
                'code' => $request->code,
                'count_of_uses' => $request->count_of_uses,
                'count_of_used' => $request->count_of_used,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'discount_type' => $request->discount_type,
                'discount' => $request->discount,
                'user_id' => $request->user_id
            ]);

            return response(['message' => 'Kupon güncelleme işlemi başarılı.','status' => 'success','object' => ['coupon' => $coupon]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deleteCoupon($id){
        try {
            $coupon = Coupons::query()->where('id', $id)->first();
            if ($coupon->count_of_used > 0){
                return response(['message' => 'Kupon daha önce kullanıldığı için silme işlemi gerçekleştirilemedi.', 'status' => 'error-002']);
            }else {
                Coupons::query()->where('id', $id)->update([
                    'active' => 0,
                ]);
                return response(['message' => 'Kupon silme işlemi başarılı.', 'status' => 'success']);
            }
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function getCoupons()
    {
        try {
            $coupons = Coupons::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['coupons' => $coupons]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getCouponById($coupon_id){
        try {
            $coupon = Coupons::query()->where('id',$coupon_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['coupon' => $coupon]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
