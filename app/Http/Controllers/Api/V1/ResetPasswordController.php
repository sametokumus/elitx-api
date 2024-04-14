<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ResetPassword;
use App\Models\User;
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
                'email' => 'required|string|email|exists:users'
            ]);

            $user = User::query()->where('email', $request['email'])->first();
            if (!$user) {
                throw new \Exception('forgot-001');
            }
            $check = ResetPassword::query()->where('email', $request->email)->first();
            if ($check){
                $resetpassword = ResetPassword::query()->where('email', $request->email)->update(
                    [
                        'token' => Str::random(45),
                    ]
                );
            }else{
                $resetpassword = ResetPassword::query()->insert(
                    [
                        'email' => $user->email,
                        'token' => Str::random(45),
                    ]
                );
            }
            if ($user && $resetpassword) {
                $check = ResetPassword::query()->where('email', $request->email)->first();
                $user->notify(new ResetPasswordNotify($check->token));
            }
            return response()->json(['message' => 'E-Posta adresinize şifre sıfırlama bağlantısı gönderildi.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'forgot-001'){
                return  response(['message' => 'E-Posta adresi bulunamadı.','status' => 'forgot-001']);
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001', 'e'=>$exception->getMessage()]);
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
    public function resetPassword(Request $request) {
//        $validator = $request->validate([
//            'email' => 'required|string|email|exists:users',
//            'token' => 'required|string',
//            'password' => 'required|string|min:6|confirmed',
//        ]);
//        if ($validator->fails()) {
//            $message['errors'] = $validator->errors();
//            return response()->json(['message' => $message, 'code' => 400]);
//        }
        try {
            $resetpassword = ResetPassword::query()->updateOrCreate(
                [
                    'email' => $request->email,
                    'token' => $request->token,
                ]
            )->first();
            if (!$resetpassword) {
                throw new \Exception('validation-003');
            }
            $user = User::query()->where('email', $request->email)->first();
            if (!$user) {
                throw new \Exception('validation-005');
            }
            User::query()->where('email', $request->email)->update([
                'password' => Hash::make($request->password)
            ]);
            ResetPassword::query()->where('email', $request->email)->delete();
            $user->notify(new ResetPasswordSuccess());
            return response()->json(['message' => 'Kullanıcı şifresi başarıyla değiştirildi.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-003'){
                return response('E-Posta adresi bulunamadı.');
            }
            if ($exception->getMessage() == 'validation-005'){
                return response('Kullanıcı bulunamadı.');
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }
}
