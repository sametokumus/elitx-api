<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\User;
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
                    $product['shop'] = Shop::query()->where('id', $product->owner_id)->first();
                }else if ($product->owner_type == 2){
                    $product['user'] = User::query()->where('id', $product->owner_id)->first();
                }


                $price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $product['base_price'] = $price->base_price;
                $product['discounted_price'] = $price->discounted_price;
                $product['discount_rate'] = $price->discount_rate;
                $product['currency'] = $price->currency;

                if ($product->has_variations == 1) {
                    $variations = ProductVariation::query()->where('product_id', $product->id)->where('active', 1)->get();
                    foreach ($variations as $variation){
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $variation->id)->orderByDesc('id')->first();
                        $variation['price'] = $variation_price->price;
                    }
                }
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
