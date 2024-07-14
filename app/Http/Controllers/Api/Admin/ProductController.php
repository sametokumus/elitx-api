<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductConfirm;
use App\Models\ProductPrice;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\Type;
use App\Models\User;
use App\Models\UserFavorite;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function getProducts()
    {
        try {

            $products = Product::query()
                ->selectRaw('products.*')
                ->where('products.active', 1)
                ->get();

            foreach ($products as $product){
                if ($product->owner_type == 1){
                    $shop = Shop::query()->where('id', $product->owner_id)->first();
                    $types = ShopType::query()
                        ->leftJoin('types', 'types.id', '=', 'shop_types.type_id')
                        ->selectRaw('shop_types.*, types.name as name')
                        ->where('shop_types.shop_id', $shop->id)
                        ->where('shop_types.active', 1)
                        ->get();
                    $type_words = $types->implode('name', ', ');
                    $shop['types'] = $types;
                    $shop['type_words'] = $type_words;
                    $product['shop'] = $shop;
                }else if ($product->owner_type == 2){
                    $product['user'] = User::query()->where('id', $product->owner_id)->first();
                }


                $price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $product['base_price'] = $price->base_price;
                $product['discounted_price'] = $price->discounted_price;
                $product['discount_rate'] = $price->discount_rate;
                $product['currency'] = $price->currency;

                if ($product->has_variation == 1) {
                    $variations = ProductVariation::query()->where('product_id', $product->id)->where('active', 1)->get();
                    foreach ($variations as $variation){
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $variation->id)->orderByDesc('id')->first();
                        $variation['price'] = $variation_price->price;
                    }
                }

                $confirm = ProductConfirm::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                if ($confirm) {
                    if ($confirm->confirmed == 0) {
                        $product['confirmed'] = 0;
                    }else if ($confirm->confirmed == 1) {
                        $product['confirmed'] = 1;
                    }else if ($confirm->confirmed == 2) {
                        $product['confirmed'] = 2;
                    }
                }else{
                    $product['confirmed'] = 0;
                }
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductById($product_id)
    {
        try {


            $product = Product::query()
                ->where('active', 1)
                ->where('id', $product_id)
                ->first();

            if ($product->owner_type == 1) {
                $shop = Shop::query()->where('id', $product->owner_id)->first();
                $types = ShopType::query()
                    ->leftJoin('types', 'types.id', '=', 'shop_types.type_id')
                    ->selectRaw('shop_types.*, types.name as name')
                    ->where('shop_types.shop_id', $shop->id)
                    ->where('shop_types.active', 1)
                    ->get();
                $type_words = $types->implode('name', ', ');
                $shop['types'] = $types;
                $shop['type_words'] = $type_words;
                $product['shop'] = $shop;
            } else if ($product->owner_type == 2) {
                $product['user'] = User::query()->where('id', $product->owner_id)->first();
            }

            $brand = Brand::query()->where('id', $product->brand_id)->first();
            $product['brand'] = $brand;

            $categories = ProductCategory::query()
                ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                ->selectRaw('product_categories.*, categories.name as name')
                ->where('product_categories.active', 1)
                ->where('product_categories.product_id', $product->id)
                ->get();
            $product['categories'] = $categories;


            $price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
            $product['base_price'] = $price->base_price;
            $product['discounted_price'] = $price->discounted_price;
            $product['discount_rate'] = $price->discount_rate;
            $product['discount_type'] = $price->discount_type;
            $product['currency'] = $price->currency;

            if ($product->has_variation == 1) {
                $variations = ProductVariation::query()->where('product_id', $product->id)->where('active', 1)->get();
                foreach ($variations as $variation) {
                    $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $variation->id)->orderByDesc('id')->first();
                    $variation['price'] = $variation_price->price;
                }
                $product['variations'] = $variations;
            }

            $fav_count = UserFavorite::query()->where('product_id', $product->id)->count();
            $product['fav_count'] = $fav_count;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getProductConfirmed($id){
        try {
            $admin = Auth::user();
            ProductConfirm::query()->insert([
                'product_id' => $id,
                'admin_id' => $admin->id,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductRejected($id){
        try {
            Product::query()->where('id',$id)->update([
                'status_id' => 3
            ]);
            $admin = Auth::user();
            ProductConfirm::query()->insert([
                'product_id' => $id,
                'admin_id' => $admin->id,
                'confirmed' => 2,
                'confirmed_at' => Carbon::now()
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
