<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductSeo;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationGroupType;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\User;
use App\Models\UserFavorite;
use App\Models\UserTypeDiscount;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{


    public function filters()
    {
        try {

            $group_types = ProductVariationGroupType::query()->where('active', 1)->get(['id', 'name']);
            foreach ($group_types as $type){
                $filter_options = ProductVariation::query()
                    ->selectRaw('product_variations.name')
                    ->where('product_variations.variation_group_id', $type->id)
                    ->distinct()
                    ->get();
                $type['filter_options'] = $filter_options;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['filters' => $group_types]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        }
    }

    public function filterProducts(Request $request)
    {
        try {

            $products = Product::query();

            if ($request->filter_name != '' && $request->filter_name != null){

                $products = $products
                    ->leftJoin('product_variations', 'product_variations.product_id', '=', 'products.id');
                $q = ' (product_variations.name LIKE "%' . $request->filter_name . '%" )';
                $products = $products->whereRaw($q);

            }

            $products = $products
                ->leftJoin(DB::raw('(SELECT * FROM product_confirms WHERE id IN (SELECT MAX(id) FROM product_confirms GROUP BY product_id)) as pc'), 'pc.product_id', '=', 'products.id')
                ->where('pc.confirmed', 1)
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
                if ($price) {
                    $product['base_price'] = $price->base_price;
                    $product['discounted_price'] = $price->discounted_price;
                    $product['discount_rate'] = $price->discount_rate;
                    $product['discount_type'] = $price->discount_type;
                    $product['currency'] = $price->currency;
                }

                if ($product->has_variation == "1") {
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

    public function getSearchProducts($keyword)
    {
        try {
            $products = Product::query()
                ->leftJoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'products.owner_id')
                        ->where('shops.confirmed', 1);
                })
                ->leftJoin('shop_types', function ($join) {
                    $join->on('shop_types.shop_id', '=', 'products.owner_id')
                        ->where(function ($query) {
                            $query->where('shop_types.type_id', 1)
                                ->orWhere('shop_types.type_id', 2);
                        });
                })
                ->leftJoin(DB::raw('(SELECT * FROM product_confirms WHERE id IN (SELECT MAX(id) FROM product_confirms GROUP BY product_id)) as pc'), 'pc.product_id', '=', 'products.id')
                ->selectRaw('products.*')
                ->where(function ($query) {
                    $query->where('products.owner_type', 1)
                        ->orWhere('products.owner_type', 2);
                })
                ->where('pc.confirmed', 1)
                ->where('products.active', 1);

            $q = ' (products.name LIKE "%' . $keyword . '%" OR products.description LIKE "%' . $keyword . '%" OR products.sku LIKE "%' . $keyword . '%")';
            $products = $products->whereRaw($q);
            $products = $products->get();


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

    public function categoryByIdSearch(Request $request, $user_id)
    {
        try {
            $x = 0;
            if ($request->category_id == 0 || $request->category_id == '') {

                $products = ProductSeo::query();
                $products = $products
                    ->leftJoin('products', 'products.id', '=', 'product_seos.product_id')
                    ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                    ->leftJoin('product_variation_groups', 'product_variation_groups.product_id', '=', 'products.id')
                    ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                    ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id')
                    ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                    ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                    ->selectRaw('brands.name as brand_name,product_types.name as type_name, product_rules.id as rule_id, product_rules.*, product_variations.name as variation_name, products.*')
                    ->where('products.active', 1)
                    ->where('product_types.active', 1)
                    ->where('brands.active', 1);

                $q = ' (product_seos.search_keywords LIKE "%' . $request->search_keywords . '%" OR products.sku LIKE "%' . $request->search_keywords . '%" OR products.name LIKE "%' . $request->search_keywords . '%" OR products.description LIKE "%' . $request->search_keywords . '%" OR products.short_description LIKE "%' . $request->search_keywords . '%")';
                $products = $products->whereRaw($q);
                $products = $products->get();

                if($user_id != 0) {
                    $user = User::query()->where('id', $user_id)->where('active', 1)->first();
                    $total_user_discount = $user->user_discount;
                    foreach ($products as $product){

                        $type_discount = UserTypeDiscount::query()->where('user_type_id',$user->user_type)->where('brand_id',$product->brand_id)->where('type_id',$product->type_id)->where('active', 1)->first();
                        if(!empty($type_discount)){
                            $total_user_discount = $total_user_discount + $type_discount->discount;
                        }

                        $product['extra_discount'] = 0;
                        $product['extra_discount_price'] = 0;
                        $product['extra_discount_tax'] = 0;
                        $product['extra_discount_rate'] = number_format($total_user_discount, 2,".","");
                        if ($total_user_discount > 0){
                            $product['extra_discount'] = 1;
                            if ($product->discounted_price == null || $product->discount_rate == 0){
                                $price = $product->regular_price - ($product->regular_price / 100 * $total_user_discount);
                            }else{
                                $price = $product->regular_price - ($product->regular_price / 100 * ($total_user_discount + $product->discount_rate));
                            }
                            $product['extra_discount_price'] = number_format($price, 2,".","");
                            $product['extra_discount_tax'] = number_format(($price / 100 * $product->tax_rate), 2,".","");
                        }
                    }
                }

            } else {
                $products = ProductSeo::query()
                    ->leftJoin('products', 'products.id', '=', 'product_seos.product_id')
                    ->leftJoin('product_categories','product_categories.product_id','=','product_seos.product_id')
                    ->leftJoin('categories','categories.id','=','product_categories.category_id')
                    ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                    ->leftJoin('product_variation_groups', 'product_variation_groups.product_id', '=', 'products.id')
                    ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                    ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id')
                    ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                    ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                    ->selectRaw('brands.name as brand_name,product_types.name as type_name, product_rules.id as rule_id, product_rules.*, product_variations.name as variation_name, products.*')
                    ->where('products.active', 1)
                    ->where('product_categories.active', 1)
                    ->where('product_types.active', 1)
                    ->where('brands.active', 1)
                    ->where('product_categories.category_id', $request->category_id);


                $q = ' (product_seos.search_keywords LIKE "% ' . $request->search_keywords . ' %" OR products.sku LIKE "%' . $request->search_keywords . ' %" OR products.name LIKE "% ' . $request->search_keywords . '%" OR products.description LIKE "% ' . $request->search_keywords . ',%" OR products.short_description LIKE "%' . $request->search_keywords . ',%")';
                $products = $products->whereRaw($q);
                $products = $products->get();



                foreach ($products as $product){
                    $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                    $count = ProductVariation::query()->where('variation_group_id' , $vg->id)->count();
                    $product['variation_count'] = $count;
                }

                if($user_id != 0) {
                    $user = User::query()->where('id', $user_id)->where('active', 1)->first();
                    $total_user_discount = $user->user_discount;
                    foreach ($products as $product){

                        $type_discount = UserTypeDiscount::query()->where('user_type_id',$user->user_type)->where('brand_id',$product->brand_id)->where('type_id',$product->type_id)->where('active', 1)->first();
                        if(!empty($type_discount)){
                            $total_user_discount = $total_user_discount + $type_discount->discount;
                        }

                        $product['extra_discount'] = 0;
                        $product['extra_discount_price'] = 0;
                        $product['extra_discount_tax'] = 0;
                        $product['extra_discount_rate'] = number_format($total_user_discount, 2,".","");
                        if ($total_user_discount > 0){
                            $product['extra_discount'] = 1;
                            if ($product->discounted_price == null || $product->discount_rate == 0){
                                $price = $product->regular_price - ($product->regular_price / 100 * $total_user_discount);
                            }else{
                                $price = $product->regular_price - ($product->regular_price / 100 * ($total_user_discount + $product->discount_rate));
                            }
                            $product['extra_discount_price'] = number_format($price, 2,".","");
                            $product['extra_discount_tax'] = number_format(($price / 100 * $product->tax_rate), 2,".","");
                        }
                    }
                }

            }



            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
