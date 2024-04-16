<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserContactRule;
use App\Models\UserDocumentCheck;
use App\Models\UserProfile;
use App\Models\UserType;
use App\Models\UserTypeDiscount;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class UserController extends Controller
{
    public function getUsers(){
        try {
            $users = User::query()
                ->leftJoin('user_profiles','user_profiles.user_id','=','users.id')
                ->selectRaw('users.*, user_profiles.name, user_profiles.surname')
                ->where('users.active', 1)
                ->get();
            return response(['message' => 'İşlem başarılı.','status' => 'success','object' => ['users' => $users]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }
    public function getPassiveUsers(){
        try {
            $users = User::query()
                ->leftJoin('user_profiles','user_profiles.user_id','=','users.id')
                ->selectRaw('users.*, user_profiles.name, user_profiles.surname')
                ->where('users.active', 0)
                ->get();
            return response(['message' => 'İşlem başarılı.','status' => 'success','object' => ['users' => $users]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }

    public function getUsersByTypeId($type_id){
        try {
            $users = User::query()
                ->leftJoin('user_profiles','user_profiles.user_id','=','users.id')
                ->selectRaw('users.*, user_profiles.name, user_profiles.surname')
                ->where('users.user_type', $type_id)
                ->get();
            return response(['message' => 'İşlem başarılı.','status' => 'success','object' => ['users' => $users]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }

    public function getUserTypes(){
        try {
            $user_types = UserType::query()->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user_types' => $user_types]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }

    public function getUserTypeById($id){
        try {
            $user_type = UserType::query()->where('id', $id)->first();
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user_type' => $user_type]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function addUserType(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required'
            ]);
            UserType::query()->insert([
                'name' => $request->name,
                'discount' => $request->discount
            ]);
            return response(['message' => 'Kullanıcı türü ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updateUserType(Request $request,$id){
        try {
            $request->validate([
                'name' => 'required'
            ]);

            UserType::query()->where('id',$id)->update([
                'name' => $request->name,
                'discount' => $request->discount
            ]);

            return response(['message' => 'Kullanıcı türü güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deleteUserType($id){
        try {
            UserType::query()->where('id', $id)->update([
                'active' => 0
            ]);
            return response(['message' => 'Kullanıcı türü silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function getUserTypeDiscounts(){
        try {
            $user_type_discounts = UserTypeDiscount::query()
                ->leftJoin('user_types','user_types.id','=','user_type_discounts.user_type_id')
                ->leftJoin('brands','brands.id','=','user_type_discounts.brand_id')
                ->leftJoin('product_types','product_types.id','=','user_type_discounts.type_id')
                ->selectRaw('user_type_discounts.*, user_types.name as user_type_name, brands.name as brand_name, product_types.name as type_name')
                ->where('user_type_discounts.active', 1)
                ->get();
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user_type_discounts' => $user_type_discounts]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }

    public function getUserTypeDiscountById($id){
        try {
            $user_type_discount = UserTypeDiscount::query()
                ->leftJoin('user_types','user_types.id','=','user_type_discounts.user_type_id')
                ->leftJoin('brands','brands.id','=','user_type_discounts.brand_id')
                ->leftJoin('product_types','product_types.id','=','user_type_discounts.type_id')
                ->selectRaw('user_type_discounts.*, user_types.name as user_type_name, brands.name as brand_name, product_types.name as type_name')
                ->where('user_type_discounts.active', 1)
                ->where('user_type_discounts.id', $id)
                ->first();
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user_type_discount' => $user_type_discount]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function addUserTypeDiscount(Request $request)
    {
        try {
            $request->validate([
                'user_types' => 'required',
                'discount' => 'required',
                'brands' => 'required',
                'types' => 'required'
            ]);

            $brands = explode(',',$request->brands);
            $types = explode(',',$request->types);
            $user_types = explode(',',$request->user_types);

            foreach ($user_types as $user_type) {
                foreach ($brands as $brand) {
                    foreach ($types as $type) {
                        $hasData = UserTypeDiscount::query()->where('user_type_id', $user_type)->where('brand_id', $brand)->where('type_id', $type)->first();
                        if (isset($hasData)) {
                            UserTypeDiscount::query()->where('id', $hasData->id)->update([
                                'user_type_id' => $user_type,
                                'discount' => $request->discount,
                                'brand_id' => $brand,
                                'type_id' => $type,
                                'active' => 1
                            ]);
                        } else {
                            UserTypeDiscount::query()->insert([
                                'user_type_id' => $user_type,
                                'discount' => $request->discount,
                                'brand_id' => $brand,
                                'type_id' => $type
                            ]);
                        }
                    }
                }
            }
            return response(['message' => 'Kullanıcı türüne göre indirim ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updateUserTypeDiscount(Request $request,$id){
        try {
            $request->validate([
                'discount' => 'required'
            ]);

            UserTypeDiscount::query()->where('id',$id)->update([
                'discount' => $request->discount
            ]);

            return response(['message' => 'Kullanıcı türüne göre indirim güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
    public function deleteUserTypeDiscount($id){
        try {
            UserTypeDiscount::query()->where('id', $id)->update([
                'active' => 0
            ]);
            return response(['message' => 'İndirim silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function verifyUser($user_id)
    {
        try {
            $user = User::query()->where('id', $user_id)->update([
                'email_verified_at' => now(),
                'verified' => true,
                'active' => true,
                'token' => null
            ]);

            return response(['message' => 'Kullanıcı epostası doğrulandı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }


    }

    public function activateUser($user_id)
    {
        try {
            $user = User::query()->where('id', $user_id)->update([
                'active' => true
            ]);

            return response(['message' => 'Kullanıcı hesabı aktifleştirildi.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }


    }

    public function deleteUser($user_id)
    {
        try {
            $user = User::query()->where('id', $user_id)->update([
                'active' => false
            ]);

            return response(['message' => 'Kullanıcı silme başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Exception $exception){
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001']);
        }


    }
    public function updateTypeToUser(Request $request){
        try {

            User::query()->where('id',$request->id)->update([
                'user_type' => $request->user_type
            ]);

            return response(['message' => 'Kullanıcı türü güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function addUserForAdmin(Request $request)
    {
        try {
            $request->validate([
                'user_name' => 'nullable',
                'email' => 'required|email',
                'phone_number' => 'required',
                'password' => 'required'
            ]);

            $userCheck = User::query()->where('email', $request->email)->count();

            if ($userCheck > 0) {
                throw new \Exception('auth-002');
            }

            $userPhoneCheck = User::query()->where('phone_number', $request->phone_number)->where('active', 1)->count();

            if ($userPhoneCheck > 0) {
                throw new \Exception('auth-003');
            }

            //Önce Kullanıcıyı oluşturuyor
            $userId = User::query()->insertGetId([
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'token' => Str::random(60)
            ]);

            //İletişim Kurallarını oluşturuyor
            $user_contact_rules = $request->user_contact_rules;
            foreach ($user_contact_rules as $user_contact_rule){
                UserContactRule::query()->insert([
                    'user_id' => $userId,
                    'contact_rule_id' => $user_contact_rule['contact_rule_id'],
                    'value' => $user_contact_rule['value']
                ]);
            }

            //Kullanıcının dökümanlarını ekliyor
            $user_document_checks = $request->user_document_checks;
            foreach ($user_document_checks as $user_document_check){
                UserDocumentCheck::query()->insert([
                    'user_id' => $userId,
                    'document_id' => $user_document_check['document_id'],
                    'value' => $user_document_check['value']
                ]);
            }
            //Kullanıcı profilini oluşturuyor
            $name = $request->name;
            $surname = $request->surname;
            UserProfile::query()->insert([
                'user_id' => $userId,
                'name' => $name,
                'surname' => $surname
            ]);

            // Oluşturulan kullanıcıyı çekiyor
            $user = User::query()->whereId($userId)->first();

            //Oluşturulan Kullanıcıyı mail yolluyor
            $user->sendApiConfirmAccount($user);

            return response(['message' => 'Kullanıcı başarıyla oluşturuldu.','status' => 'success']);
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
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001', 'err' => $exception->getMessage()]);
        }

    }

}
