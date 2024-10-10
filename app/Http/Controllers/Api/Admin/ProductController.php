<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarImage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComment;
use App\Models\ProductConfirm;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
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
use Nette\Schema\ValidationException;

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

            $comment_count = ProductComment::query()->where('product_id', $product->id)->where('confirmed', 1)->where('active', 1)->count();
            $product['comment_count'] = $comment_count;

            $product['images'] = ProductImage::query()->where('product_id', $product->id)->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getProductConfirmed($id){
        try {
            Product::query()->where('id',$id)->update([
                'status_id' => 2
            ]);
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

    public function addProduct(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'base_price' => 'required',
                'currency' => 'required'
            ]);
            $shop = Auth::user();

            $discounted_price = null;

            if ($request->discount_type == 2){
                $discounted_price = $request->base_price / 100 * (100 - $request->discount_rate);
            }if ($request->discount_type == 3){
                $discounted_price = $request->discounted_price;
            }

            $brand_id = null;
            if ($request->brand_name != null){
                $brand_id = Brand::query()->insertGetId([
                    'name' => $request->brand_name
                ]);
            }

            $stock_quantity = ($request->stock_quantity != '') ? $request->stock_quantity : 1;
            $product_id = Product::query()->insertGetId([
                'brand_id' => $brand_id,
                'sku' => $request->sku,
                'name' => $request->name,
                'description' => $request->description,
                'stock_quantity' => $stock_quantity,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'status_id' => 1,
                'owner_type' => 1,
                'owner_id' => $shop->id,
            ]);

            ProductPrice::query()->insert([
                'product_id' => $product_id,
                'base_price' => $request->base_price,
                'discounted_price' => $discounted_price,
                'discount_type' => $request->discount_type,
                'discount_rate' => $request->discount_rate,
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

            $variations = json_decode($request->variations);
            if ($variations != null && count($variations) > 0) {
                Product::query()->where('id', $product_id)->update([
                    'has_variation' => 1
                ]);
                foreach ($variations as $variation) {
                    $stock_quantity = ($variation->stock_quantity != '') ? $variation->stock_quantity : 1;
                    $variation_id = ProductVariation::query()->insertGetId([
                        'product_id' => $product_id,
                        'variation_group_id' => $variation->group_id,
                        'name' => $variation->name,
                        'stock_quantity' => $stock_quantity
                    ]);
                    ProductVariationPrice::query()->insert([
                        'product_id' => $product_id,
                        'variation_id' => $variation_id,
                        'price' => $variation->price
                    ]);
                }
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

            ProductConfirm::query()->insert([
                'product_id' => $product_id
            ]);

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['product_id' => $product_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function updateProduct(Request $request, $product_id)
    {
        try {
            $request->validate([
                'name' => 'required',
                'base_price' => 'required',
                'currency' => 'required'
            ]);
            $shop = Auth::user();
            $product = Product::query()->where('id', $product_id)->first();

            $discounted_price = null;

            if ($request->discount_type == 2){
                $discounted_price = $request->base_price / 100 * (100 - $request->discount_rate);
            }if ($request->discount_type == 3){
                $discounted_price = $request->discounted_price;
            }

            if ($product->brand_id != null) {
                $brand = Brand::query()->where('id', $product->brand_id)->first();
                $brand_id = $brand->id;
                if ($brand->name != $request->brand_name) {
                    if ($request->brand_name != null) {
                        $brand_id = Brand::query()->insertGetId([
                            'name' => $request->brand_name
                        ]);
                    }else{
                        $brand_id = null;
                    }
                }
            }else{
                $brand_id = null;
                if ($request->brand_name != null) {
                    $brand_id = Brand::query()->insertGetId([
                        'name' => $request->brand_name
                    ]);
                }
            }

            $stock_quantity = ($request->stock_quantity != '') ? $request->stock_quantity : 1;
            Product::query()->where('id', $product_id)->update([
                'brand_id' => $brand_id,
                'sku' => $request->sku,
                'name' => $request->name,
                'description' => $request->description,
                'stock_quantity' => $stock_quantity,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'status_id' => $request->status_id,
                'owner_type' => 1,
                'owner_id' => $shop->id,
            ]);

            $old_price = ProductPrice::query()->where('product_id', $product_id)->orderByDesc('id')->first();
            if ($old_price->base_price != $request->base_price
                || $discounted_price != $request->discounted_price
                || $old_price->discount_rate != $request->discount_rate
                || $old_price->discount_type != $request->discount_type
                || $old_price->currency != $request->currency) {
                ProductPrice::query()->insert([
                    'product_id' => $product_id,
                    'base_price' => $request->base_price,
                    'discounted_price' => $discounted_price,
                    'discount_type' => $request->discount_type,
                    'discount_rate' => $request->discount_rate,
                    'currency' => $request->currency
                ]);
            }

            $old_status = ProductStatusHistory::query()->where('product_id', $product_id)->orderByDesc('id')->first();
            if ($old_status->status_id != $request->status_id) {
                ProductStatusHistory::query()->insert([
                    'product_id' => $product_id,
                    'status_id' => $request->status_id,
                ]);
            }

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

            $variations = json_decode($request->variations);
            if ($variations != null && count($variations) > 0) {
                Product::query()->where('id', $product_id)->update([
                    'has_variation' => 1
                ]);
                ProductVariation::query()->where('product_id', $product_id)->update([
                    'active' => 0
                ]);

                foreach ($variations as $variation) {
                    $old_variation = ProductVariation::query()
                        ->where('product_id', $product_id)
                        ->where('variation_group_id', $variation->group_id)
                        ->where('name', $variation->name)
                        ->first();

                    if ($old_variation){
                        $stock_quantity = ($variation->stock_quantity != '') ? $variation->stock_quantity : 1;
                        ProductVariation::query()->where('id', $old_variation->id)->update([
                            'product_id' => $product_id,
                            'variation_group_id' => $variation->group_id,
                            'name' => $variation->name,
                            'stock_quantity' => $stock_quantity,
                            'active' => 1
                        ]);
                        $old_price = ProductVariationPrice::query()->where('product_id', $product_id)->where('variation_id', $old_variation->id)->orderByDesc('id')->first();
                        if ($old_price->price != $variation->price){
                            ProductVariationPrice::query()->insert([
                                'product_id' => $product_id,
                                'variation_id' => $old_variation->id,
                                'price' => $variation->price
                            ]);
                        }

                    }else {
                        $stock_quantity = ($variation->stock_quantity != '') ? $variation->stock_quantity : 1;
                        $variation_id = ProductVariation::query()->insertGetId([
                            'product_id' => $product_id,
                            'variation_group_id' => $variation->group_id,
                            'name' => $variation->name,
                            'stock_quantity' => $stock_quantity
                        ]);
                        ProductVariationPrice::query()->insert([
                            'product_id' => $product_id,
                            'variation_id' => $variation_id,
                            'price' => $variation->price
                        ]);
                    }
                }
            }

            ProductCategory::query()->where('product_id', $product_id)->update([
                'active' => 0
            ]);
            $categories = json_decode($request->categories);
            foreach ($categories as $category){
                $old_category = ProductCategory::query()->where('product_id', $product_id)->where('category_id', $category)->first();
                if ($old_category){
                    ProductCategory::query()
                        ->where('product_id', $product_id)
                        ->where('category_id', $category)
                        ->update([
                            'active' => 1
                        ]);
                }else {
                    ProductCategory::query()->insert([
                        'product_id' => $product_id,
                        'category_id' => $category
                    ]);
                }
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

            $old_confirm = ProductConfirm::query()->where('product_id', $product_id)->orderByDesc('id')->first();
            if ($old_confirm) {
                if ($old_confirm->confirmed != 0) {
                    ProductConfirm::query()->insert([
                        'product_id' => $product_id
                    ]);
                }
            }else{
                ProductConfirm::query()->insert([
                    'product_id' => $product_id
                ]);
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
