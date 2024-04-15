<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationPrice;
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

    public function addProduct(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'base_price' => 'required',
                'currency' => 'required'
            ]);
            $user = Auth::user();

            $product_id = Product::query()->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'stock_quantity' => 1,
                'status_id' => 0,
                'owner_type' => 2,
                'owner_id' => $user->id,
            ]);

            ProductPrice::query()->insert([
                'product_id' => $product_id,
                'base_price' => $request->base_price,
                'currency' => $request->currency
            ]);

            ProductStatusHistory::query()->insert([
                'product_id' => $product_id,
                'status_id' => 1
            ]);

            if ($request->hasFile('thumbnail')) {
                $rand = uniqid();
                $image = $request->file('thumbnail');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/ProductImage/'), $image_name);
                $image_path = "/images/ProductImage/" . $image_name;
                Product::query()->where('id', $product_id)->update([
                    'thumbnail' => $image_path
                ]);
            }

            $categories = json_decode($request->categories);
            foreach ($categories as $category){
                ProductCategory::query()->insert([
                    'product_id' => $product_id,
                    'category_id' => $category
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $rand = uniqid();
                    $image_name = $rand . "-" . $image->getClientOriginalName();
                    $image->move(public_path('/images/ProductImage/'), $image_name);
                    $image_path = "/images/ProductImage/" . $image_name;
                    ProductImage::query()->insert([
                        'product_id' => $product_id,
                        'image' => $image_path
                    ]);
                }
            }

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['product_id' => $product_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
}
