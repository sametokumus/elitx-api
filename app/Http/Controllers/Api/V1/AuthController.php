<?php

namespace App\Http\Controllers\Api\V1;

use App\Mail\UserWelcome;
use App\Models\ContactRule;
use App\Models\User;
use App\Models\UserContactRule;
use App\Models\UserDocumentCheck;
use App\Models\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $userActiveCheck = User::query()->where('email', $request->email)->where('active', 0)->count();

            if ($userActiveCheck > 0) {
                throw new \Exception('auth-004');
            }

            $userCheck = User::query()->where('email', $request->email)->count();

            if ($userCheck > 0) {
                throw new \Exception('auth-002');
            }

            //Önce Kullanıcıyı oluşturuyor
            $userId = User::query()->insertGetId([
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'token' => Str::random(60)
            ]);

            //Kullanıcının dökümanlarını ekliyor
            $user_document_checks = $request->user_document_checks;
            foreach ($user_document_checks as $user_document_check){
                UserDocumentCheck::query()->insert([
                    'user_id' => $userId,
                    'document_id' => $user_document_check['document_id'],
                    'value' => $user_document_check['value']
                ]);
            }

            // Oluşturulan kullanıcıyı çekiyor
            $user = User::query()->whereId($userId)->first();

            //Oluşturulan Kullanıcıya mail yolluyor
//            $user->sendApiConfirmAccount($user);

            return response(['message' => 'Kullanıcı başarıyla oluşturuldu sisteme giriş için epostanızı kontrol ediniz.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','error' => $queryException->getMessage()]);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'auth-002'){
                return  response(['message' => 'Girdiğiniz eposta adresi kullanılmaktadır.','status' => 'auth-002']);
            }
            if ($exception->getMessage() == 'auth-003'){
                return  response(['message' => 'Girdiğiniz telefon numarası kullanılmaktadır.','status' => 'auth-003']);
            }
            if ($exception->getMessage() == 'auth-004'){
                return  response(['message' => 'Bu e-posta adresi ile bir hesap bulunmaktadır. Yeniden giriş yaparak hesabınızı aktifleştirebilirsiniz.','status' => 'auth-003']);
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001', 'err' => $exception->getMessage()]);
        }

    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::query()->where('email', $request->email)->first();
            if(!$user){
                throw new \Exception('auth-001');
            }

            if ($user->active == 0){
                throw new \Exception('auth-002');
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw new \Exception('auth-003');
            }

            $userToken = $user->createToken('api-token', ['role:user'])->plainTextToken;
            User::query()->where('id', $user->id)->update([
                'token' => $userToken
            ]);

            $user->token = $userToken;

            return  response(['message' => 'Başarılı.','status' => 'success', 'object' => ['user'=>$user]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'auth-001'){
                return  response(['message' => 'Eposta hatalı.','status' => 'auth-001']);
            }else if ($exception->getMessage() == 'auth-002'){
                return  response(['message' => 'Hesap kapalı.','status' => 'auth-002']);
            }else if ($exception->getMessage() == 'auth-003'){
                return  response(['message' => 'Şifre hatalı.','status' => 'auth-003']);
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }

    public function logout()
    {
        try {
            auth()->user()->tokens()->delete();
            return response(['message' => 'Çıkış başarılı.','status' => 'success']);
        } catch (\Exception $exception){
            return response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    protected function verify(Request $request)
    {
        try {
            $user = User::query()->where('token', $request['token'])->first();
            if ($user->verified == 1 && $user->email_verified_at != null) {
                throw new \Exception('validation-002');
            }
            $user->email_verified_at = now();
            $user->verified = true;
            $user->active = true;
            $user->token = null;
            $user->save();
            /*
               $setDelay = Carbon::parse($user->email_verified_at)->addSeconds(10);
               Bu kısımda isterseniz Kullanıcıya Hoşgeldinizi Maili İçin Gecikme Verebilirsiniz.
               Mail::queue(new \App\Mail\UserWelcome($user->name, $user->email))->delay($setDelay);
              */
            Mail::queue(new UserWelcome($user->name, $user->email));
            return response(['message' => 'Kullanıcı epostası doğrulandı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-002'){
                return response('Eposta adresi daha önceden doğrulanmış.');
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }


    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    protected function resend(Request $request)
    {

        try {
            $user = User::query()->where('email', $request['email'])->first();
            if ($user->hasVerifiedEmail()) {
                throw new \Exception('validation-002');
            }
            $user->sendApiConfirmAccount($user);
            return response(['message' => 'Yeniden eposta gönderildi.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'validation-002'){
                return response('Eposta adresi daha önceden doğrulanmış.');
            }
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }
    }
}
