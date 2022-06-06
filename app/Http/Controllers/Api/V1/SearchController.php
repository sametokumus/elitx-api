<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSeo;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function categoryByIdSearch(Request $request){
        try {
            $products = ProductCategory::query()
                ->leftJoin('products','products.id','=','product_categories.product_id')
                ->leftJoin('brands','brands.id','=','products.brand_id')
                ->leftJoin('product_types','product_types.id','=','products.type_id')
                ->leftJoin('product_variation_groups','product_variation_groups.product_id','=','products.id')
                ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                ->leftJoin('product_variations','product_variations.id','=','product_variation_groups.id')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id order by id asc limit 1) as image'))
                ->leftJoin('product_rules','product_rules.variation_id','=','product_variations.id')
                ->selectRaw('products.* ,brands.name as brand_name,product_types.name as type_name, product_rules.*,product_seos.*')
                ->leftJoin('product_seos','product_seos.product_id','=','products.id')
                ->where('products.active',1)
                ->where('product_categories.active',1)
                ->where('product_categories.category_id',$request->category_id)
                ->where('product_seos.search_keywords','like','%'.$request->search_keywords.'%')
                ->get();

            if ($request->category_id == 0){
                $products = ProductCategory::query()
                    ->leftJoin('products','products.id','=','product_categories.product_id')
                    ->leftJoin('brands','brands.id','=','products.brand_id')
                    ->leftJoin('product_types','product_types.id','=','products.type_id')
                    ->leftJoin('product_variation_groups','product_variation_groups.product_id','=','products.id')
                    ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                    ->leftJoin('product_variations','product_variations.id','=','product_variation_groups.id')
                    ->select(DB::raw('(select image from product_images where variation_id = product_variations.id order by id asc limit 1) as image'))
                    ->leftJoin('product_rules','product_rules.variation_id','=','product_variations.id')
                    ->selectRaw('products.* ,brands.name as brand_name,product_types.name as type_name, product_rules.*,product_seos.*')
                    ->leftJoin('product_seos','product_seos.product_id','=','products.id')
                    ->where('products.active',1)
                    ->where('product_categories.active',1)
                    ->where('product_seos.search_keywords','like','%'.$request->search_keywords.'%')
                    ->get();
                return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);

            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        }
    }

    public function filterProducts(Request $request){
        try {
            $product_categories = ProductCategory::query()->where('category_id',$request->category_id)->get();
            foreach ($product_categories as $product_category){
                $products = Product::query()->where('id',$product_category->product_id)->get();
                foreach ($products as $product){
                    $brand_name = Brand::query()->where('id',$request->brand_id)->first()->name;
                    $product_variation_groups = ProductVariationGroup::query()->where('product_id',$product->id)->get();
                    foreach ($product_variation_groups as $product_variation_group){
                        $product_variations = ProductVariation::query()->where('name',$request->color)->first()->name;
                        $product['variation_group'] = $product_variation_group;
                        $product['variation'] = $product_variations;
                    }
                    $product['brand_name'] = $brand_name;
                }
                $product_category['products'] = $product;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_categories' => $product_categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        }
    }
}