<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Mail\UserWelcome;
use App\Models\ContactRule;
use App\Models\Shop;
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
                'name' => 'required',
                'email' => 'required|email',
                'phone_number' => 'required',
                'password' => 'required'
            ]);

            $shopActiveCheck = Shop::query()->where('email', $request->email)->where('active', 0)->count();

            if ($shopActiveCheck > 0) {
                throw new \Exception('shop-004');
            }

            $shopCheck = Shop::query()->where('email', $request->email)->count();

            if ($shopCheck > 0) {
                throw new \Exception('shop-002');
            }

            $userPhoneCheck = Shop::query()->where('phone_number', $request->phone_number)->where('active', 1)->count();

            if ($userPhoneCheck > 0) {
                throw new \Exception('shop-003');
            }

            $userId = Shop::query()->insertGetId([
                'email' => $request->email,
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'token' => Str::random(60)
            ]);

            // Oluşturulan kullanıcıyı çekiyor
            $user = Shop::query()->whereId($userId)->first();

            //Oluşturulan Kullanıcıyı mail yolluyor
//            $user->sendApiConfirmAccount($user);

            return response(['message' => 'Mağazanız başarıyla oluşturuldu sisteme giriş için epostanızı kontrol ediniz.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','error' => $queryException->getMessage()]);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'shop-002'){
                return  response(['message' => 'Girdiğiniz eposta adresi kullanılmaktadır.','status' => 'auth-002']);
            }
            if ($exception->getMessage() == 'shop-003'){
                return  response(['message' => 'Girdiğiniz telefon numarası kullanılmaktadır.','status' => 'auth-003']);
            }
            if ($exception->getMessage() == 'shop-004'){
                return  response(['message' => 'Bu e-posta adresi ile bir mağaza bulunmaktadır. Yeniden giriş yaparak hesabınızı aktifleştirebilirsiniz.','status' => 'auth-003']);
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

            $user = Shop::query()->where('email', $request->email)->first();

            if ($user->active == 0){
                Shop::query()->where('email', $request->email)->update([
                    'active' => 1
                ]);
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw new \Exception('auth-001');
            }

            $userToken = $user->createToken('api-token', ['role:shop'])->plainTextToken;
            Shop::query()->where('id', $user->id)->update([
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
                return  response(['message' => 'Eposta veya şifre hatalı.','status' => 'auth-001']);
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
            $user = Shop::query()->where('token', $request['token'])->first();
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
