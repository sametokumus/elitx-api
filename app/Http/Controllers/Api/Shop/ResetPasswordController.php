<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\Shop;
use App\Notifications\ResetPasswordNotify;
use App\Notifications\ResetPasswordSuccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class ResetPasswordController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email'
            ]);

            $user = Shop::query()->where('email', $request->email)->first();
            if (!$user) {
                throw new \Exception('validation-003');
            }
            $check = ResetPassword::query()->where('email', $request->email)->first();
            $token = Str::random(45);
            if ($check){
                ResetPassword::query()->where('email', $request->email)->update([
                    'token' => $token
                ]);
            }else{
                ResetPassword::query()->insert([
                    'email' => $request->email,
                    'token' => $token
                ]);
            }
//            if ($user && $resetpassword) {
//                $user->notify(new ResetPasswordNotify($resetpassword->token));
//            }
            return response()->json(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['token' => $token]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-003'){
                return response('E-Posta adresi bulunamadı.');
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001', 'e' => $exception->getMessage()]);
        }
    }

    /**
     * @param $token
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function find($token) {
        try {
            $resetpassword = ResetPassword::query()->where('token', $token)->first();
            if (!$token) {
                throw new \Exception('validation-004');
            }
            if (Carbon::parse($resetpassword->created_at)->addMinutes(720)->isPast()) {
                $resetpassword->delete();
                throw new \Exception('validation-004');
            }
            return response()->json(['message' => 'Başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-004'){
                return response('Token geçersiz.');
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function newPassword(Request $request) {
        try {
            $resetpassword = ResetPassword::query()->where('token', $request->token)->first();
            if (!$resetpassword) {
                throw new \Exception('validation-003');
            }
            $user = Shop::query()->where('email', $resetpassword->email)->where('active', 1)->first();
            if (!$user) {
                throw new \Exception('validation-005');
            }
            Shop::query()->where('email', $resetpassword->email)->update([
                'password' => Hash::make($request->password)
            ]);
            ResetPassword::query()->where('email', $resetpassword->email)->delete();
//            $user->notify(new ResetPasswordSuccess());
            return response()->json(['message' => 'Mağaza şifresi başarıyla değiştirildi.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-003'){
                return  response(['message' => 'Şifre yenileme talebi bulunamadı!','status' => 'validation-003']);
            }
            if ($exception->getMessage() == 'validation-005'){
                return  response(['message' => 'Kullanıcı bulunamadı!','status' => 'validation-003']);
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }
}
