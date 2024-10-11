<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\User;
use App\Models\UserDocumentCheck;
use App\Models\UserFavorite;
use App\Models\UserProfile;
use App\Models\UserSession;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nette\Schema\ValidationException;

class UserController extends Controller
{
    function objectToArray(&$object)
    {
        return @json_decode(json_encode($object), true);
    }

    public function getUser(){
        try {
            $user = Auth::user();

            if ($user) {
                return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user' => $user]]);
            } else {
                return response(['message' => 'Kullanıcı bulunamadı.', 'status' => 'user-001']);
            }
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }

    public function changePassword(Request $request){
        try {
            $user = Auth::user();

            if ($user) {
                $change_password = User::query()->where('id', $user->id)->update([
                    'password' => Hash::make($request->password)
                ]);
                return response(['message' => 'Şifre değiştirme işlemi başarılı.','status' => 'success','object' => ['change_password' => $change_password]]);
            } else {
                return response(['message' => 'Kullanıcı bulunamadı.', 'status' => 'user-001']);
            }

        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

    public function updateUser(Request $request){
        try {
            $profile = json_decode($request->profile);
            $productArray = $this->objectToArray($profile);
            Validator::make($productArray, [
                'name' => 'required',
                'email' => 'required',
                'phone_number' => 'required'
            ]);

            $user = Auth::user();

            if ($user) {
                User::query()->where('id', $user->id)->update([
                    'email' => $profile->email,
                    'phone_number' => $profile->phone_number,
                    'name' => $profile->name,
                    'birthday' => Carbon::parse($profile->birthday)->format('Y-m-d'),
                    'gender' => $profile->gender
                ]);

                if ($request->hasFile('profile_photo')) {
                    $rand = uniqid();
                    $image = $request->file('profile_photo');
                    $image_name = $rand . "-" . $image->getClientOriginalName();
                    $image->move(public_path('/images/ProfilePhoto/'), $image_name);
                    $image_path = "/images/ProfilePhoto/" . $image_name;
                    User::query()->where('id',$user->id)->update([
                        'profile_photo' => $image_path
                    ]);
                }

                $user_document_checks = $profile->user_document_checks;
                foreach ($user_document_checks as $user_document_check) {
                    UserDocumentCheck::query()
                        ->where('user_id', $user->id)
                        ->where('document_id', $user_document_check->document_id)
                        ->update([
                            'value' => $user_document_check->value
                        ]);
                }

                return response(['message' => 'Güncelleme işlemi başarılı.','status' => 'success']);
            } else {
                return response(['message' => 'Kullanıcı bulunamadı.', 'status' => 'user-001']);
            }

        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

    public function deleteUser(){
        try {

            $user = Auth::user();

            if ($user) {

                User::query()->where('id', $user->id)->update([
                    'active' => 0,
                ]);
                return response(['message' => 'Kullanıcı silme işlemi başarılı.','status' => 'success']);

            } else {
                return response(['message' => 'Kullanıcı bulunamadı.', 'status' => 'user-001']);
            }
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function addUserSession(Request $request){
        try {
            $country = Country::query()->where('iso' ,$request->countryCode)->first();
            if ($country->active == 1) {

                $session_guid = Uuid::uuid();
                $lang = $request->session_lang;
                if ($lang == null) {
                    $lang = $country->lang;
                }


                UserSession::query()->insert([
                    'session_id' => $session_guid,
                    'session_lang' => $lang,
                    'as' => $request->as,
                    'city' => $request->city,
                    'country' => $request->country,
                    'countryCode' => $request->countryCode,
                    'district' => $request->district,
                    'isp' => $request->isp,
                    'lat' => $request->lat,
                    'lon' => $request->lon,
                    'org' => $request->org,
                    'ip' => $request->ip,
                    'region' => $request->region,
                    'regionName' => $request->regionName
                ]);

                return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => [
                    'sale_this_country' => 1,
                    'session_lang' => $lang,
                    'session_id' => $session_guid,
                    'currency' => $country->currency,
                    'currency_icon' => $country->currency_icon,
                    'country_id' => $country->id
                ]]);
            }else{
                return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['sale_this_country' => 0]]);
            }
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

}
