<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComment;
use App\Models\ProductConfirm;
use App\Models\ProductImage;
use App\Models\ProductPoint;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
use App\Models\ProductUsageStatus;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationGroupType;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class ProductController extends Controller
{

    public function addFavorite($product_id)
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $user_favorite = UserFavorite::query()
                ->where('user_id', $user_id)
                ->where('product_id', $product_id)
                ->count();
            if ($user_favorite > 0) {
                UserFavorite::query()
                    ->where('user_id', $user_id)
                    ->where('product_id', $product_id)
                    ->update([
                        'active' => 1
                    ]);
            } else {
                UserFavorite::query()->insert([
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ]);
            }


            return response(['message' => 'Favori ürün ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function removeFavorite($product_id)
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $user_favorite = UserFavorite::query()
                ->where('user_id', $user_id)
                ->where('product_id', $product_id)
                ->count();
            if ($user_favorite > 0) {
                UserFavorite::query()
                    ->where('user_id', $user_id)
                    ->where('product_id', $product_id)
                    ->update([
                        'active' => 0
                    ]);
            }


            return response(['message' => 'Favori ürün silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getFavorites()
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $products = UserFavorite::query()
                ->leftJoin('products', 'products.id', '=', 'user_favorites.product_id')
                ->selectRaw('products.*')
                ->where('user_favorites.active', 1)
                ->where('user_favorites.user_id', $user_id)
                ->where('products.active', 1)
                ->get();

            foreach ($products as $product) {
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
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getNewProducts()
    {
        try {

            $products = Product::query()
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->leftJoin('shops', 'shops.id', '=', 'products.owner_id')
                ->leftJoin('shop_types', 'shop_types.shop_id', '=', 'products.owner_id')
                ->leftJoin(DB::raw('(SELECT * FROM product_confirms WHERE id IN (SELECT MAX(id) FROM product_confirms GROUP BY product_id)) as pc'), 'pc.product_id', '=', 'products.id')
                ->selectRaw('products.*, product_usage_statuses.name as usage_status_name')
                ->where('products.owner_type', 1)
                ->where('shop_types.type_id', 1)
                ->where('shops.confirmed', 1)
                ->where('products.active', 1)
                ->where('products.is_saled', 0)
                ->where('products.status_id', 2)
                ->where('pc.confirmed', 1)
                ->get();

            foreach ($products as $product) {
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

                $comment_count = ProductComment::query()
                    ->where('product_id', $product->id)
                    ->where('confirmed', 1)
                    ->where('active', 1)
                    ->count();
                $product['comment_count'] = $comment_count;

                $is_favorite = 0;
                if (Auth::user()){
                    $favs = UserFavorite::query()->where('user_id', Auth::user()->id)->where('product_id', $product->id)->where('active', 1)->get();
                    if ($favs){
                        $is_favorite = 1;
                    }
                }
                $product['is_favorite'] = $is_favorite;

                $product['images'] = ProductImage::query()->where('product_id', $product->id)->where('active', 1)->get();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getSecondHandProducts()
    {
        try {
            $products = Product::query()
                ->leftJoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'products.owner_id')
                        ->where('shops.confirmed', 1);
                })
                ->leftJoin('shop_types', function ($join) {
                    $join->on('shop_types.shop_id', '=', 'products.owner_id')
                        ->where('shop_types.type_id', '=', 2);
                })

                ->leftJoin(DB::raw('(SELECT * FROM product_confirms WHERE id IN (SELECT MAX(id) FROM product_confirms GROUP BY product_id)) as pc'), 'pc.product_id', '=', 'products.id')
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->selectRaw('products.*, product_usage_statuses.name as usage_status_name')
                ->where(function ($query) {
                    $query->where('products.owner_type', 1)
                        ->where('shop_types.type_id', 2)
                        ->orWhere('products.owner_type', 2);
                })
                ->where('pc.confirmed', 1)
                ->where('products.active', 1)
                ->where('products.is_saled', 0)
                ->where('products.status_id', 2)
                ->get();

            foreach ($products as $product) {
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

                $fav_count = UserFavorite::query()->where('product_id', $product->id)->where('active', 1)->count();
                $product['fav_count'] = $fav_count;

                $comment_count = ProductComment::query()
                    ->where('product_id', $product->id)
                    ->where('confirmed', 1)
                    ->where('active', 1)
                    ->count();
                $product['comment_count'] = $comment_count;

                $is_favorite = 0;
                if (Auth::user()){
                    $favs = UserFavorite::query()->where('user_id', Auth::user()->id)->where('product_id', $product->id)->where('active', 1)->toSql();
                    if ($favs){
                        $is_favorite = 1;
                    }
                }
                $product['is_favorite'] = $is_favorite;

                $product['images'] = ProductImage::query()->where('product_id', $product->id)->where('active', 1)->get();

            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getNewProductsByCategoryId($category_id)
    {
        try {

            $products = Product::query()
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->leftJoin('shop_types', 'shop_types.shop_id', '=', 'products.owner_id')
                ->selectRaw('products.*, product_usage_statuses.name as usage_status_name')
                ->where('products.owner_type', 1) //Mağaza
                ->where('shop_types.type_id', 1) //Sıfır Ürün Mağazası
                ->where('products.active', 1)
                ->where('products.is_saled', 0)
                ->where('products.status_id', 2)
                ->get();

            foreach ($products as $product) {
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
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getSecondHandProductsByCategoryId($category_id)
    {
        try {
            $products = Product::query()
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->leftJoin('shop_types', function ($join) {
                    $join->on('shop_types.shop_id', '=', 'products.owner_id')
                        ->where('shop_types.type_id', '=', 2);
                })
                ->selectRaw('products.*, product_usage_statuses.name as usage_status_name')
                ->where(function ($query) {
                    $query->where('products.owner_type', 1)
                        ->where('shop_types.type_id', 2);
                })
                ->orWhere('products.owner_type', 2)
                ->where('products.active', 1)
                ->where('products.is_saled', 0)
                ->where('products.status_id', 2)
                ->get();

            foreach ($products as $product) {
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
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getProductUsageStatuses()
    {
        try {


            $usage_statuses = ProductUsageStatus::query()
                ->where('active', 1)
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['usage_statuses' => $usage_statuses]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function getShopProducts($shop_id)
    {
        try {

            $products = Product::query()
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->leftJoin('shops', 'shops.id', '=', 'products.owner_id')
                ->leftJoin('shop_types', 'shop_types.shop_id', '=', 'products.owner_id')
                ->leftJoin(DB::raw('(SELECT * FROM product_confirms WHERE id IN (SELECT MAX(id) FROM product_confirms GROUP BY product_id)) as pc'), 'pc.product_id', '=', 'products.id')
                ->selectRaw('products.*, product_usage_statuses.name as usage_status_name')
                ->where('products.owner_type', 1)
                ->where('shop_types.type_id', 1)
                ->where('shops.confirmed', 1)
                ->where('products.active', 1)
                ->where('products.is_saled', 0)
                ->where('products.status_id', 2)
                ->where('pc.confirmed', 1)
                ->where('shops.id', $shop_id)
                ->get();

            foreach ($products as $product) {
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

                $comment_count = ProductComment::query()
                    ->where('product_id', $product->id)
                    ->where('confirmed', 1)
                    ->where('active', 1)
                    ->count();
                $product['comment_count'] = $comment_count;

                $is_favorite = 0;
                if (Auth::user()){
                    $favs = UserFavorite::query()->where('user_id', Auth::user()->id)->where('product_id', $product->id)->where('active', 1)->get();
                    if ($favs){
                        $is_favorite = 1;
                    }
                }
                $product['is_favorite'] = $is_favorite;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
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
                    $variation['group_name'] = ProductVariationGroupType::query()->where('id', $variation->variation_group_id)->first()->name;
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

    public function addProductPoint($product_id, $point)
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            if ($point > 5 || $point < 1) {
                throw new \Exception('product-001');
            }
            $has_point = ProductPoint::query()
                ->where('user_id', $user_id)
                ->where('product_id', $product_id)
                ->count();
            if ($has_point > 0) {
                ProductPoint::query()
                    ->where('user_id', $user_id)
                    ->where('product_id', $product_id)
                    ->update([
                        'point' => number_format($point, 1)
                    ]);
                $avgPoint = ProductPoint::query()->where('product_id', $product_id)->where('active', 1)->avg('point');
                Product::query()->where('id', $product_id)->update([
                    'point' => $avgPoint
                ]);
            } else {
                ProductPoint::query()
                    ->insert([
                        'point' => number_format($point, 1),
                        'user_id' => $user_id,
                        'product_id' => $product_id
                    ]);
                $totalPoint = ProductPoint::query()->where('product_id', $product_id)->where('active', 1)->sum('point');
                $count = ProductPoint::query()->where('product_id', $product_id)->where('active', 1)->count();
                $avgPoint = $totalPoint / $count;
                Product::query()->where('id', $product_id)->update([
                    'point' => $avgPoint,
                    'point_count' => $count
                ]);
            }


            return response(['message' => 'Puanlama işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Exception $exception){
            if ($exception->getMessage() == 'product-001'){
                return  response(['message' => 'Girdiğiniz değer 1-5 arasında olmalıdır.','status' => 'auth-002']);
            }
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $exception->getMessage()]);
        }
    }
}
