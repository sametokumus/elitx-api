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

    public function changePassword(Request $request,$user_id){
        try {
            $change_password = User::query()->where('id',$user_id)->update([
                'password' => Hash::make($request->password)
            ]);
            return response(['message' => 'Şifre değiştirme işlemi başarılı.','status' => 'success','object' => ['change_password' => $change_password]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

    public function updateUser(Request $request,$user_id){
        try {
            $profile = json_decode($request->profile);
            $productArray = $this->objectToArray($profile);
            Validator::make($productArray, [
                'name' => 'required',
                'email' => 'required',
                'phone_number' => 'required'
            ]);
//            $request->validate([
//                'user_name' => 'required',
//                'email' => 'required',
//                'phone_number' => 'required'
//            ]);

            $user = User::query()->where('id',$user_id)->update([
                'user_name' => $profile->user_name,
                'email' => $profile->email,
                'phone_number' => $profile->phone_number
            ]);

            $user_profile = UserProfile::query()->where('user_id',$user_id)->update([
                'name' => $profile->name,
                'surname' => $profile->surname,
                'birthday' => \Illuminate\Support\Carbon::parse($profile->birthday)->format('Y-m-d'),
                'gender' => $profile->gender,
                'tc_citizen' => $profile->tc_citizen,
                'tc_number' => $profile->tc_number
            ]);
            if ($request->hasFile('profile_photo')) {
                $rand = uniqid();
                $image = $request->file('profile_photo');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/ProfilePhoto/'), $image_name);
                $image_path = "/images/ProfilePhoto/" . $image_name;
                $user_profile = UserProfile::query()->where('user_id',$user_id)->update([
                    'profile_photo' => $image_path
                ]);
            }
            $user_document_checks = $profile->user_document_checks;
            foreach ($user_document_checks as $user_document_check){
                UserDocumentCheck::query()->where('user_id',$user_id)->where('document_id',$user_document_check->document_id)->update([
                    'value' => $user_document_check->value
                ]);
            }
            return response(['message' => 'Güncelleme işlemi başarılı.','status' => 'success','object' => ['user' => $user,'user_profile' => $user_profile]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','e' => $throwable->getMessage()]);
        }
    }

    public function deleteUser($id){
        try {

            $user = User::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Kullanıcı silme işlemi başarılı.','status' => 'success','object' => ['user' => $user]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function addUserFavorite(Request $request){
        try {
            $user_favorite = UserFavorite::query()
                ->where('user_id', $request->user_id)
                ->where('product_id', $request->product_id)
                ->where('variation_id', $request->variation_id)
                ->count();
            if ($user_favorite > 0){
                UserFavorite::query()
                    ->where('user_id',$request->user_id)
                    ->where('product_id',$request->product_id)
                    ->where('variation_id',$request->variation_id)
                    ->update([
                        'active' => 1
                    ]);
            }else{
                UserFavorite::query()->insert([
                    'user_id' => $request->user_id,
                    'product_id' => $request->product_id,
                    'variation_id' => $request->variation_id
                ]);
            }


            return response(['message' => 'Favori ürün ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function removeUserFavorite(Request $request){
        try {
            $user_favorite = UserFavorite::query()
                ->where('user_id',$request->user_id)
                ->where('product_id',$request->product_id)
                ->where('variation_id',$request->variation_id)
                ->count();
            if ($user_favorite > 0){
                UserFavorite::query()
                    ->where('user_id',$request->user_id)
                    ->where('product_id',$request->product_id)
                    ->where('variation_id',$request->variation_id)
                    ->update([
                        'active' => 0
                    ]);
            }


            return response(['message' => 'Favori ürün silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }


    public function getUserFavorites($id){
        try {
            $user_favorites = UserFavorite::query()
                ->leftJoin('products', 'products.id', '=', 'user_favorites.product_id')
                ->leftJoin('product_categories','product_categories.product_id','=','products.id')
                ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->select(DB::raw('(select id from product_variation_groups where product_id = user_favorites.product_id order by id asc limit 1) as variation_group'))
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = user_favorites.variation_id order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('user_favorites.active', 1)
                ->where('user_favorites.user_id',$id)
                ->get();

            foreach ($user_favorites as $product){
                $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                $count = ProductVariation::query()->where('variation_group_id' , $vg->id)->count();
                $product['variation_count'] = $count;
            }
//            ->where('user_favorites.product_id', '=','products.id')
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['user_favorites' => $user_favorites]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }

    public function addRefundRequest(Request $request){
        try {
                OrderRefund::query()->insert([
                    'user_id' => $request->user_id,
                    'order_id' => $request->order_id,
                    'note' => $request->note
                ]);
                Order::query()->where('id',$request->order_id)->update([
                    'status_id' => 11
                ]);
            return response(['message' => 'İade talebiniz alındı.','status' => 'success']);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }
}
