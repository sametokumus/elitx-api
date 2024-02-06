<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CampaignProduct;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInstallment;
use App\Models\ImportProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Models\ProductRule;
use App\Models\ProductSeo;
use App\Models\ProductTab;
use App\Models\ProductTabContent;
use App\Models\ProductTags;
use App\Models\ProductType;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationGroupType;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserTypeDiscount;
use http\QueryString;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function getAllProduct()
    {
        try {
            $products = Product::query()->where('active', 1)->orderBy('name')->get();
            foreach ($products as $product) {
                $brands = Product::query()->where('brand_id', $product->brand_id)->get();
                $product_types = ProductType::query()->where('id', $product->type_id)->get();
                $product_documents = ProductDocument::query()->where('product_id', $product->id)->get();
                $product_variation_groups = ProductVariationGroup::query()->where('product_id', $product->id)->get();
                foreach ($product_variation_groups as $product_variation_group) {
                    $product_variation_group['name'] = ProductVariationGroupType::query()->where('id', $product_variation_group->id)->get();
                    $product_variation_group['variations'] = ProductVariation::query()->where('variation_group_id', $product_variation_group->id)->get();
                    $variations = ProductVariation::query()->where('variation_group_id', $product_variation_group->id)->get();
                    foreach ($variations as $variation) {
                        $product_variation_group['images'] = ProductImage::query()->where('variation_id', $variation->id)->get();
                        $product_variation_group['rule'] = ProductRule::query()->where('variation_id', $variation->id)->get();
                    }
                }
                $product_tags = ProductTags::query()->where('product_id', $product->id)->get();
                foreach ($product_tags as $product_tag) {
                    $product_tag['name'] = Tag::query()->where('id', $product_tag->tag_id)->first()->name;
                }
                $product_categories = ProductCategory::query()->where('product_id', $product->id)->get();
                foreach ($product_categories as $product_category) {
                    $product_category['categories'] = Category::query()->where('id', $product_category->category_id)->get();
                }
                $product['brand'] = $brands;
                $product['product_type'] = $product_types;
                $product['product_documents'] = $product_documents;
                $product['variation_groups'] = $product_variation_groups;
                $product['product_tags'] = $product_tags;
                $product['product_categories'] = $product_categories;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getAllProductById($id)
    {
        try {
            $product = Product::query()->where('id', $id)->where('active', 1)->first();
            $brand = Brand::query()->where('id', $product->brand_id)->first();
            $product_type = ProductType::query()->where('id', $product->type_id)->first();
            $product_documents = ProductDocument::query()->where('product_id', $product->id)->where('active', 1)->get();
            $product_tags = ProductTags::query()
                ->leftJoin('tags', 'tags.id', '=', 'product_tags.tag_id')
                ->selectRaw('tags.*')
                ->where('product_id', $product->id)
                ->where('product_tags.active', 1)
                ->get();
            $product_categories = ProductCategory::query()
                ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                ->selectRaw('categories.*')
                ->where('product_id', $product->id)
                ->where('product_categories.active', 1)
                ->where('product_categories.category_id', '!=', 0)
                ->get();

            $product_variation_groups = ProductVariationGroup::query()
                ->leftJoin('product_variation_group_types', 'product_variation_group_types.id', '=', 'product_variation_groups.group_type_id')
                ->leftJoin('products', 'products.id', '=', 'product_variation_groups.product_id')
                ->selectRaw('product_variation_groups.* , product_variation_group_types.name as type_name')
                ->where('product_variation_groups.active', 1)
                ->where('products.id', $id)
                ->get();
            $variations = ProductVariation::query()
                ->leftJoin('product_variation_groups', 'product_variation_groups.id', '=', 'product_variations.variation_group_id')
                ->leftJoin('products', 'products.id', '=', 'product_variation_groups.product_id')
                ->selectRaw('product_variations.*')
                ->where('product_variations.active', 1)
                ->where('products.active', 1)
                ->where('products.id', $id)
                ->get();

            foreach ($variations as $variation) {
                $rule = ProductRule::query()->where('variation_id', $variation->id)->first();
                $images = ProductImage::query()->where('variation_id', $variation->id)->get();
                $variation['rule'] = $rule;
                $variation['images'] = $images;
            }

            $featured_variation = ProductVariation::query()->where('id', $product->featured_variation)->first();
            $rule = ProductRule::query()->where('variation_id', $featured_variation->id)->first();
            $images = ProductImage::query()->where('variation_id', $featured_variation->id)->where('active', 1)->get();
            $featured_variation['rule'] = $rule;
            $featured_variation['images'] = $images;

            $product['brand'] = $brand;
            $product['product_type'] = $product_type;
            $product['product_documents'] = $product_documents;
            $product['product_tags'] = $product_tags;
            $product['product_categories'] = $product_categories;
            $product['variation_groups'] = $product_variation_groups;
            $product['variations'] = $variations;
            $product['featured_variation'] = $featured_variation;
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getAllProductWithVariationById($user_id, $product_id, $variation_id)
    {
        try {
            $product = Product::query()->where('id', $product_id)->where('active', 1)->first();

            $brand = Brand::query()->where('id', $product->brand_id)->first();
            $product_type = ProductType::query()->where('id', $product->type_id)->first();
            $product_documents = ProductDocument::query()->where('product_id', $product->id)->where('active', 1)->get();
            $product_tags = ProductTags::query()
                ->leftJoin('tags', 'tags.id', '=', 'product_tags.tag_id')
                ->selectRaw('tags.*')
                ->where('product_id', $product->id)
                ->where('product_tags.active', 1)
                ->get();
            $product_categories = ProductCategory::query()
                ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                ->selectRaw('categories.*')
                ->where('product_id', $product->id)
                ->where('product_categories.active', 1)
                ->where('product_categories.category_id','!=',0)
                ->get();

            $product_variation_groups = ProductVariationGroup::query()
                ->leftJoin('product_variation_group_types', 'product_variation_group_types.id', '=', 'product_variation_groups.group_type_id')
                ->leftJoin('products', 'products.id', '=', 'product_variation_groups.product_id')
                ->selectRaw('product_variation_groups.* , product_variation_group_types.name as type_name')
                ->where('product_variation_groups.active', 1)
                ->where('products.id', $product_id)
                ->get();
            $variations = ProductVariation::query()
                ->leftJoin('product_variation_groups', 'product_variation_groups.id', '=', 'product_variations.variation_group_id')
                ->leftJoin('products', 'products.id', '=', 'product_variation_groups.product_id')
                ->selectRaw('product_variations.*')
                ->where('product_variations.active', 1)
                ->where('products.active', 1)
                ->where('products.id', $product_id)
                ->get();

            foreach ($variations as $variation) {
                $rule = ProductRule::query()->where('variation_id', $variation->id)->first();
                $images = ProductImage::query()->where('variation_id', $variation->id)->where('active', 1)->get();

                if ($rule->currency == "EUR"){
                    $try_currency = array();
                    $try_currency['regular_price'] = convertEURtoTRY($rule->regular_price);
                    $try_currency['regular_tax'] = convertEURtoTRY($rule->regular_tax);
                    $try_currency['discounted_price'] = convertEURtoTRY($rule->discounted_price);
                    $try_currency['discounted_tax'] = convertEURtoTRY($rule->discounted_tax);
                    $try_currency['currency'] = "TL";
                    $rule['try_currency'] = $try_currency;
                }else if ($rule->currency == "USD") {
                    $try_currency = array();
                    $try_currency['regular_price'] = convertUSDtoTRY($rule->regular_price);
                    $try_currency['regular_tax'] = convertUSDtoTRY($rule->regular_tax);
                    $try_currency['discounted_price'] = convertUSDtoTRY($rule->discounted_price);
                    $try_currency['discounted_tax'] = convertUSDtoTRY($rule->discounted_tax);
                    $try_currency['currency'] = "TL";
                    $rule['try_currency'] = $try_currency;
                }

                $variation['rule'] = $rule;
                $variation['images'] = $images;
            }

            $featured_variation = ProductVariation::query()->where('id', $variation_id)->first();
            $rule = ProductRule::query()->where('variation_id', $variation_id)->first();

            if($user_id != 0) {
                $user = User::query()->where('id', $user_id)->where('active', 1)->first();
                $total_user_discount = $user->user_discount;

                $type_discount = UserTypeDiscount::query()->where('user_type_id',$user->user_type)->where('brand_id',$product->brand_id)->where('type_id',$product->type_id)->where('active', 1)->first();
                if(!empty($type_discount)){
                    $total_user_discount = $total_user_discount + $type_discount->discount;
                }

                $rule['extra_discount'] = 0;
                $rule['extra_discount_price'] = 0;
                $rule['extra_discount_tax'] = 0;
                $rule['extra_discount_rate'] = number_format($total_user_discount, 2,".","");
                if ($total_user_discount > 0){
                    $rule['extra_discount'] = 1;
                    if ($rule->discounted_price == null || $rule->discount_rate == 0){
                        $price = $rule->regular_price - ($rule->regular_price / 100 * $total_user_discount);
                    }else{
                        $price = $rule->regular_price - ($rule->regular_price / 100 * ($total_user_discount + $rule->discount_rate));
                    }
                    $rule['extra_discount_price'] = number_format($price, 2,".","");
                    $rule['extra_discount_tax'] = number_format(($price / 100 * $product->tax_rate), 2,".","");
                }
            }

            $images = ProductImage::query()->where('variation_id', $variation_id)->get();
            if ($rule->currency == "EUR"){
                $try_currency = array();
                $try_currency['regular_price'] = convertEURtoTRY($rule->regular_price);
                $try_currency['regular_tax'] = convertEURtoTRY($rule->regular_tax);
                $try_currency['discounted_price'] = convertEURtoTRY($rule->discounted_price);
                $try_currency['discounted_tax'] = convertEURtoTRY($rule->discounted_tax);
                $try_currency['extra_discount_price'] = convertEURtoTRY($rule['extra_discount_price']);
                $try_currency['extra_discount_tax'] = convertEURtoTRY($rule['extra_discount_tax']);
                $try_currency['currency'] = "TL";
                $rule['try_currency'] = $try_currency;
            }else if ($rule->currency == "USD") {
                $try_currency = array();
                $try_currency['regular_price'] = convertUSDtoTRY($rule->regular_price);
                $try_currency['regular_tax'] = convertUSDtoTRY($rule->regular_tax);
                $try_currency['discounted_price'] = convertUSDtoTRY($rule->discounted_price);
                $try_currency['discounted_tax'] = convertUSDtoTRY($rule->discounted_tax);
                $try_currency['extra_discount_price'] = convertUSDtoTRY($rule['extra_discount_price']);
                $try_currency['extra_discount_tax'] = convertUSDtoTRY($rule['extra_discount_tax']);
                $try_currency['currency'] = "TL";
                $rule['try_currency'] = $try_currency;
            }
            $featured_variation['rule'] = $rule;
            $featured_variation['images'] = $images;

            $product['brand'] = $brand;
            $product['product_type'] = $product_type;
            $product['product_documents'] = $product_documents;
            $product['product_tags'] = $product_tags;
            $product['product_categories'] = $product_categories;
            $product['variation_groups'] = $product_variation_groups;
            $product['variations'] = $variations;
            $product['featured_variation'] = $featured_variation;
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProduct()
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->orderBy('products.name')
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getFilteredProduct(Request $request)
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->orderBy('products.name')
                ->where('products.active', 1);

            if ($request->brand_id != 0){
                $products = $products->where('brands.id', $request->brand_id);
            }
            if ($request->type_id != 0){
                $products = $products->where('product_types.id', $request->type_id);
            }

            $products = $products->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductsByFilter(Request $request, $user_id)
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->orderBy('products.name')
                ->where('products.active', $request->active);

            if ($request->brands != ""){
                $brands = explode(',',$request->brands);
                $products = $products->where(function ($query) use ($products, $brands){
                    foreach ($brands as $brand){
                        $query = $query->orWhere('brands.id', $brand);
                    }
                });
            }
            if ($request->types != ""){
                $types = explode(',',$request->types);
                $products = $products->where(function ($query) use ($products, $types){
                    foreach ($types as $type){
                        $query = $query->orWhere('product_types.id', $type);
                    }
                });
            }

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductsByCategoryId($category_id)
    {
        try {
            $products = ProductCategory::query()
                ->leftJoin('products', 'products.id', '=', 'product_categories.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variation_groups', 'product_variation_groups.product_id', '=', 'products.id')
                ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('product_categories.active', 1)
                ->where('product_categories.category_id', $category_id)
                ->orderBy('products.name')
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductsWithParentCategory($user_id)
    {
        try {
            $categories = Category::query()->where('parent_id', 0)->get();
            foreach ($categories as $category) {

                $first_id = Category::query()->where('parent_id', $category->id)->first()->id;

                $sub_categories = Category::query()->where('parent_id', $category->id)->get();
                $category['sub_categories'] = $sub_categories;

                $products = ProductCategory::query()
                    ->leftJoin('products', 'products.id', '=', 'product_categories.product_id')
                    ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                    ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                    ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                    ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                    ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                    ->where('products.active', 1)
                    ->where('product_categories.active', 1)
                    ->where('product_categories.category_id', $first_id)
                    ->orderBy('products.name')
                    ->limit(4)
                    ->get();

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

                $category['products'] = $products;

            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductsBySlug($user_id, $slug)
    {
        try {
            $products = ProductCategory::query()
                ->leftJoin('products', 'products.id', '=', 'product_categories.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
//                ->leftJoin('product_variation_groups', 'product_variation_groups.product_id', '=', 'products.id')
//                ->select(DB::raw('(select id from product_variation_groups where product_id = products.id order by id asc limit 1) as variation_group'))
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('product_categories.active', 1)
                ->where('categories.slug', $slug)
                ->orderBy('products.name')
                ->get();

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductsByType($user_id, $slug)
    {
        try {
            $products = ProductType::query()
                ->leftJoin('products', 'products.type_id', '=', 'product_types.id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('product_types.active', 1)
                ->where('product_types.slug', $slug)
                ->orderBy('products.name')
                ->get();

            foreach ($products as $product){
                $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                if ($vg) {
                    $count = ProductVariation::query()->where('variation_group_id', $vg->id)->count();
                    $product['variation_count'] = $count;
                }else{
                    $product['variation_count'] = 0;
                }
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

    public function getProductsByBrand($user_id, $slug)
    {
        try {
            $products = Brand::query()
                ->leftJoin('products', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('brands.slug', $slug)
                ->orderBy('products.name')
                ->get();

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductById($id)
    {
        try {
            $product = Product::query()->where('id', $id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a'=>$queryException->getMessage()]);
        }
    }

    public function getProductTagById($product_id)
    {
        try {
            $product_tags = ProductTags::query()->where('product_id', $product_id)->where('active', 1)->get();
            foreach ($product_tags as $product_tag) {
                $tag_name = Tag::query()->where('id', $product_tag->tag_id)->get();
                $product_tag['tag'] = $tag_name;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_tags' => $product_tags]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductCategoryById($product_id)
    {
        try {
            $product_categories = ProductCategory::query()->where('product_id', $product_id)->where('active', 1)->get();
            foreach ($product_categories as $product_category) {
                $category_name = Category::query()->where('id', $product_category->category_id)->get();
                $product_category['category'] = $category_name;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_categories' => $product_categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductDocumentById($product_id)
    {
        try {
            $product_documents = ProductDocument::query()->where('product_id', $product_id)->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_documents' => $product_documents]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductVariationGroupById($product_id)
    {
        try {
            $product_variation_groups = ProductVariationGroup::query()->where('product_id', $product_id)->get();
            foreach ($product_variation_groups as $product_variation_group) {
                $variation_group_type = ProductVariationGroupType::query()->where('id', $product_variation_group->group_type_id)->first();
                $product_variation_group['variation_group_type'] = $variation_group_type;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_variation_groups' => $product_variation_groups]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductVariationById($id)
    {
        try {
            $product_variation = ProductVariation::query()->where('id', $id)->first();
            $rules = ProductRule::query()->where('variation_id', $id)->first();

            if ($rules->currency == "EUR"){
                $try_currency = array();
                $try_currency['regular_price'] = convertEURtoTRY($rules->regular_price);
                $try_currency['regular_tax'] = convertEURtoTRY($rules->regular_tax);
                $try_currency['discounted_price'] = convertEURtoTRY($rules->discounted_price);
                $try_currency['discounted_tax'] = convertEURtoTRY($rules->discounted_tax);
                $try_currency['currency'] = "TL";
                $rules['try_currency'] = $try_currency;
            }else if ($rules->currency == "USD") {
                $try_currency = array();
                $try_currency['regular_price'] = convertUSDtoTRY($rules->regular_price);
                $try_currency['regular_tax'] = convertUSDtoTRY($rules->regular_tax);
                $try_currency['discounted_price'] = convertUSDtoTRY($rules->discounted_price);
                $try_currency['discounted_tax'] = convertUSDtoTRY($rules->discounted_tax);
                $try_currency['currency'] = "TL";
                $rules['try_currency'] = $try_currency;
            }

            $product_variation['rule'] = $rules;
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_variation' => $product_variation]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductVariationsById($id)
    {
        try {
            $product_variations = ProductVariationGroup::query()
                ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id')
                ->where('product_variation_groups.product_id', $id)
                ->selectRaw('product_variations.*')
                ->where('product_variations.active', 1)
                ->get();

            foreach ($product_variations as $product_variation) {
                $rules = ProductRule::query()->where('variation_id', $product_variation->id)->first();
                $product_variation['rule'] = $rules;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_variations' => $product_variations]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getVariationsImageById($product_id)
    {
        try {
            $product_variations = ProductVariationGroup::query()
                ->leftJoin('product_variations', 'product_variations.variation_group_id', '=', 'product_variation_groups.id')
                ->where('product_variation_groups.product_id', $product_id)
                ->where('product_variations.active', 1)
                ->selectRaw('product_variations.*')
                ->get();

            foreach ($product_variations as $product_variation) {
                $images = ProductImage::query()->where('variation_id', $product_variation->id)->where('active', 1)->get();
                $product_variation['images'] = $images;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_variations' => $product_variations]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getVariationImageById($variation_id)
    {
        try {
            $variation_images = ProductImage::query()->where('variation_id', $variation_id)->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['variation_images' => $variation_images]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductTabsById($product_id)
    {
        try {
            $product_tabs = ProductTabContent::query()->where('product_id', $product_id)->where('active', 1)->get();
            foreach ($product_tabs as $product_tab) {
                $tab = ProductTab::query()->where('id', $product_tab->product_tab_id)->first();
                $product_tab['tab'] = $tab;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_tabs' => $product_tabs]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getProductTabById($tab_id)
    {
        try {
            $product_tab = ProductTabContent::query()->where('id', $tab_id)->first();
            $tab = ProductTab::query()->where('id', $product_tab->product_tab_id)->first();
            $product_tab['tab'] = $tab;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['product_tab' => $product_tab]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getBrandsWithProductsAndLimit($limit)
    {
        try {

            $brands = Brand::query()->where('active', 1)->get();
            foreach ($brands as $brand) {
                $product_count = Product::query()->where('brand_id', $brand->id)->where('active', 1)->count();
                $brand['count'] = $product_count;

                $products = Product::query()->limit($limit)->where('brand_id', $brand->id)->where('active', 1)->get();
                foreach ($products as $product) {

                    $variation = ProductVariation::query()
                        ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                        ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                        ->selectRaw('product_variations.*, product_rules.*')
                        ->where('product_variations.id', $product->featured_variation)
                        ->first();

//                    $rule = ProductRule::query()->where('variation_id',$variation->id)->first();
//                    $image = ProductImage::query()->where('variation_id',$variation->id)->first();
//                    $variation['rule'] = $rule;
//                    $variation['image'] = $image->image;

                    $product['variation'] = $variation;

                    $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                    $count = ProductVariation::query()->where('variation_group_id' , $vg->id)->count();
                    $product['variation_count'] = $count;

                }
                $brand['products'] = $products;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['brands' => $brands]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getAllCampaignProducts($user_id)
    {
        try {
            $products = CampaignProduct::query()
                ->leftJoin('products', 'products.id', '=', 'campaign_products.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*, campaign_products.order')
                ->where('campaign_products.active', 1)
                ->where('products.active', 1)
                ->orderBy('campaign_products.order', 'ASC')
                ->get();

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getCampaignProductsByLimit($user_id, $limit)
    {
        try {
            $products = CampaignProduct::query()
                ->leftJoin('products', 'products.id', '=', 'campaign_products.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*, campaign_products.order')
                ->where('campaign_products.active', 1)
                ->where('products.active', 1)
                ->orderBy('campaign_products.order', 'ASC')
                ->limit($limit)
                ->get();

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


            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getFeaturedProducts($user_id)
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('products.is_featured', 1)
                ->orderBy('products.name')
                ->limit(7)
                ->get();

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getNewProducts($user_id)
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('products.is_new', 1)
                ->orderBy('products.name')
                ->limit(7)
                ->get();

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
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getSimilarProducts($product_id)
    {
        try {
            $product_category = ProductCategory::query()->where('product_id', $product_id)->first();
            $products = ProductCategory::query()
                ->leftJoin('products', 'products.id', '=', 'product_categories.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->leftJoin('product_variations', 'product_variations.id', '=', 'products.featured_variation')
                ->select(DB::raw('(select image from product_images where variation_id = product_variations.id and active = 1 order by id asc limit 1) as image'))
                ->leftJoin('product_rules', 'product_rules.variation_id', '=', 'product_variations.id')
                ->selectRaw('product_rules.*, brands.name as brand_name,product_types.name as type_name, products.*')
                ->where('products.active', 1)
                ->where('product_categories.active', 1)
                ->where('product_categories.category_id', $product_category->category_id)
                ->orderBy('products.name')
                ->limit(5)
                ->get();

            foreach ($products as $product){
                $vg = ProductVariationGroup::query()->where('product_id', $product->id)->first();
                $count = ProductVariation::query()->where('variation_group_id' , $vg->id)->count();
                $product['variation_count'] = $count;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getCategoriesByBranId()
    {
        try {
            $brands = Brand::query()->where('active', 1)->get();
            foreach ($brands as $brand) {
                $brand_categories = ProductCategory::query()
                    ->leftJoin('products', 'products.id', '=', 'product_categories.product_id')
//                    ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                    ->where('products.brand_id',$brand->id)
                    ->where('product_categories.category_id','!=',0)
                    ->groupBy('product_categories.category_id')
                    ->selectRaw('product_categories.category_id')
                    ->get();
                $categories = [];
                foreach ($brand_categories as $brand_category){
                    $category = Category::query()->where('id',$brand_category->category_id)->first();

                    array_push($categories,$category);
                }
                $brand['categories'] = $categories;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['brands' => $brands]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductColors(){
        try {
            $colors = ProductVariationGroup::query()
                ->leftJoin('products','products.id','=','product_variation_groups.product_id')
                ->leftJoin('product_variations','product_variations.variation_group_id','=','product_variation_groups.id')
                ->selectRaw('product_variations.name')
                ->distinct('product_variations.name')
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['colors' => $colors]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getProductSeoById($product_id){
        try {
            $seo = ProductSeo::query()->where('product_id', $product_id)->where('active', 1)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['seo' => $seo]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getCheckProductSku($product_sku){
        try {
            $count = Product::query()->where('sku', $product_sku)->where('active', 1)->count();
            if ($count > 0){
                return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['useSku' => true]]);
            }else{
                $x=1;
                return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['useSku' => false]]);
            }

        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getCheckProductVariationSku($product_sku){
        try {
            $count = ProductVariation::query()->where('sku', $product_sku)->where('active', 1)->count();
            if ($count > 0){
                return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['useSku' => true]]);
            }else{
                return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['useSku' => false]]);
            }

        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getCreditCartInstallments($product_variation_id){
        try {
            $product_rule = ProductRule::query()->where('variation_id', $product_variation_id)->first();

            $price = $product_rule->regular_price;
            if ($product_rule->currency == "EUR"){
                $price = convertEURtoTRY($product_rule->regular_price);
            }else if ($product_rule->currency == "USD") {
                $price = convertUSDtoTRY($product_rule->regular_price);
            }

            $credit_cards = CreditCard::query()->where('active', 1)->get();
            foreach ($credit_cards as $credit_card){

                $installments = CreditCardInstallment::query()
                    ->where('credit_card_id', $credit_card->id)
                    ->where('active', 1)
                    ->orderBy('installment')
                    ->get();
                foreach ($installments as $installment){
                    $installment['short_name'] = $installment->installment;
                    if ($installment->installment_plus != 0){
                        $installment['short_name'] = $installment->installment."+".$installment->installment_plus;
                    }
                    $total_installment = $installment->installment + $installment->installment_plus;

                    if ($product_rule->discount_rate != null && $product_rule->discount_rate != '0.00'){
                        $total_rate = $product_rule->discount_rate - $installment->discount;
                        $installment_total_price = $price / 100 * (100 - $total_rate);
                    }else{
                        $installment_total_price = $price / 100 * (100 + $installment->discount);
                    }

                    $installment_price = $installment_total_price / $total_installment;

                    $installment['installment_total_price'] = number_format($installment_total_price, 2,".","");
                    $installment['installment_price'] = number_format($installment_price, 2,".","");

                }

                $credit_card['installments'] = $installments;

            }


            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['credit_cards' => $credit_cards]]);

        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function reloadImages()
    {
        $imports = ImportProduct::all();

        foreach ($imports as $import) {
            $imageUrl = $import->resim;
            $variations = ProductVariation::query()->where('sku', $import->alt_urun_kod)->get();
            foreach ($variations as $variation){
                ProductImage::query()->where('variation_id', $variation->id)->update([
                    'image' => $imageUrl
                ]);
            }
        }


        return "Güncellendi.";
    }

    public function downloadImages()
    {
        $images = ProductImage::query()
            ->where('image', '!=', '0')
            ->where('id', '>=', 2401)
            ->where('id', '<=', 2800)
            ->get();

        foreach ($images as $image) {
            $imageUrl = $image->image;

            if (!empty($imageUrl)) {
                $ch = curl_init($imageUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $imageData = curl_exec($ch);

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode == 200) {
                    $imageName = basename($imageUrl);
                    file_put_contents(public_path('images/ProductImage/' . $imageName), $imageData);
                }
            }
        }


        return "Görseller indirildi.";
    }

    public function updateImagesUrl()
    {
        $images = ProductImage::query()->where('image', '!=',  '0')->get();

        foreach ($images as $image) {
            $imageUrl = $image->image;
            $fileName = basename($imageUrl);

            ProductImage::query()->where('id', $image->id)->update([
               'image' => '/images/ProductImage/'.$fileName
            ]);

        }


        return "URL'ler güncellendi.";
    }

}

