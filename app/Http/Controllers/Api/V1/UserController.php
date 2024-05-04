<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\User;
use App\Models\UserDocumentCheck;
use App\Models\UserFavorite;
use App\Models\UserProfile;
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
                'email' => 'required'
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

                $user_document_checks = $request->user_document_checks;
                foreach ($user_document_checks as $user_document_check) {
                    UserDocumentCheck::query()
                        ->where('user_id', $user->id)
                        ->where('document_id', $user_document_check['document_id'])
                        ->update([
                            'value' => $user_document_check['value']
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

}
