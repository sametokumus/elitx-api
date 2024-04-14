<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSeo;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\User;
use App\Models\UserTypeDiscount;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
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

    public function filterProducts(Request $request, $user_id)
    {
        try {
            $products = Product::query();
            $products = $products
                ->leftJoin('product_categories', 'product_categories.product_id', '=', 'products.id');
            if ($request->category_id != "") {
                $category_explodes = explode(",", "$request->category_id");
                $q = '(product_categories.category_id ' . '=' . $category_explodes[0];
                for ($i = 1; $i < (count($category_explodes)); $i++) {
                    $q = $q . ' OR product_categories.category_id ' . '=' . $category_explodes[$i];
                }
                $q = $q . ')';
                $products->whereRaw($q);
            }

            $products = $products
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id');

            if ($request->brand_id != "") {
                $brand_explodes = explode(",", "$request->brand_id");
                $q = '(products.brand_id ' . '=' . $brand_explodes[0];
                for ($i = 1; $i < (count($brand_explodes)); $i++) {
                    $q = $q . ' OR products.brand_id ' . '=' . $brand_explodes[$i];
                }
                $q = $q . ')';
                $products->whereRaw($q);
            }

            $products = $products
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id');

            if ($request->type_id != "") {
                $type_explodes = explode(",", "$request->type_id");
                $q = '(products.type_id ' . '=' . $type_explodes[0];
                for ($i = 1; $i < (count($type_explodes)); $i++) {
                    $q = $q . ' OR products.type_id ' . '=' . $type_explodes[$i];
                }
                $q = $q . ')';
                $products->whereRaw($q);
            }

            $products = $products
                ->leftJoin('product_variation_groups', 'product_variation_groups.product_id', '=', 'products.id')
                ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id');

            if ($request->color != "") {
                $color_explodes = explode(",", "$request->color");
                $q = '(product_variations.name ' . '= \'' . $color_explodes[0] . '\'';
                for ($i = 1; $i < (count($color_explodes)); $i++) {
                    $q = $q . ' OR product_variations.name ' . '= \'' . $color_explodes[$i] . '\'';
                }
                $q = $q . ')';
                $products->whereRaw($q);
            }

            $products = $products
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id');


            $products = $products
                ->leftJoin('product_seos', 'product_seos.product_id', '=', 'products.id');
            if ($request->search_keywords != null) {
                $q = ' (product_seos.search_keywords LIKE "%' . $request->search_keywords . '%" OR products.sku LIKE "%' . $request->search_keywords . '%" OR products.name LIKE "%' . $request->search_keywords . '%" OR products.description LIKE "%' . $request->search_keywords . '%" OR products.short_description LIKE "%' . $request->search_keywords . '%")';
                $products = $products->whereRaw($q);
            }

            $products = $products->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, product_variations.name as variation_name, products.*');


            $products = $products
                ->where('products.active', 1)
                ->where('product_categories.active', 1)
                ->where('product_types.active', 1)
                ->where('brands.active', 1)
                ->get();



            foreach ($products as $product){
                $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                $count = ProductVariation::query()->where('variation_group_id' , $vg->id)->count();
                $product['variation_count'] = $count;
            }

            foreach ($products as $product){
                $product['image'] = ProductImage::query()->where('variation_id',$product->featured_variation)->first()->image;
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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
