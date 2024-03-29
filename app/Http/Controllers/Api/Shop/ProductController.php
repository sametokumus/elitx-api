<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductStatusHistory;
use App\Models\ProductVariation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class ProductController extends Controller
{
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

            $product_id = Product::query()->insertGetId([
                'brand_id' => $request->brand_id,
                'sku' => $request->sku,
                'name' => $request->name,
                'description' => $request->description,
                'stock_quantity' => $request->stock_quantity,
                'base_price' => $request->base_price,
                'discounted_price' => $discounted_price,
                'discount_type' => $request->discount_type,
                'discount_rate' => $request->discount_rate,
                'currency' => $request->currency,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'status_id' => 0,
                'owner_type' => 1,
                'owner_id' => $shop->id,
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
                    ProductVariation::query()->insert([
                        'product_id' => $product_id,
                        'variation_group_id' => $variation->group_id,
                        'name' => $variation->name,
                        'stock_quantity' => $variation->stock_quantity,
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

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['product_id' => $product_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function getProducts()
    {
        try {
            $shop = Auth::user();

            $products = Product::query()
                ->selectRaw('products.*')
                ->where('products.active', 1)
                ->where('products.owner_id', $shop->id)
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
