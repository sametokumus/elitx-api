<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\UserFavorite;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class ProductController extends Controller
{

    public function addFavorite($product_id){
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $user_favorite = UserFavorite::query()
                ->where('user_id', $user_id)
                ->where('product_id', $product_id)
                ->count();
            if ($user_favorite > 0){
                UserFavorite::query()
                    ->where('user_id',$user_id)
                    ->where('product_id',$product_id)
                    ->update([
                        'active' => 1
                    ]);
            }else{
                UserFavorite::query()->insert([
                    'user_id' => $user_id,
                    'product_id' => $product_id
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

    public function removeFavorite($product_id){
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $user_favorite = UserFavorite::query()
                ->where('user_id',$user_id)
                ->where('product_id',$product_id)
                ->count();
            if ($user_favorite > 0){
                UserFavorite::query()
                    ->where('user_id',$user_id)
                    ->where('product_id',$product_id)
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

    public function getFavorites(){
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $products = UserFavorite::query()
                ->leftJoin('products', 'products.id', '=', 'user_favorites.product_id')
                ->selectRaw('products.*')
                ->where('user_favorites.active', 1)
                ->where('user_favorites.user_id', $user_id)
                ->get();

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['products' => $products]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }
}
