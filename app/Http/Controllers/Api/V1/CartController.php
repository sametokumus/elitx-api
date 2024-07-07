<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Coupons;
use App\Models\DeliveryPrice;
use App\Models\IncreasingDesi;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductMaterial;
use App\Models\ProductPrice;
use App\Models\ProductRule;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserTypeDiscount;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;
use phpDocumentor\Reflection\Types\Array_;

class CartController extends Controller
{
    public function addCart(Request $request){
        try {

            $product = Product::query()->where('id', $request->product_id)->first();

            if ($product->owner_type == 1) {

                //sepet oluşturuldu
                if(!empty($request->cart_id)){
                    $cart_id = $request->cart_id;
                }else{
                    $cart_id = Uuid::uuid();
                    $added_cart_id = Cart::query()->insertGetId([
                        'cart_id' => $cart_id
                    ]);
                }

                //ürün fiyatı
                $product_price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $price = $product_price->base_price;
                if ($product_price->discounted_price != null && $product_price->discount_rate != null && $product_price->discount_rate != '0.00') {
                    $price = $product_price->discounted_price;
                }
                $currency = $product_price->currency;

                if (!empty($request->variation_id)) {
                    $variation = ProductVariation::query()->where('product_id', $product->id)->where('id', $request->variation_id)->where('active', 1)->first();

                    if ($variation) {
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $request->variation_id)->orderByDesc('id')->first();
                        $price = $variation_price->price;
                    }

                }


                //sepet detay
                $cart_detail = CartDetail::query()->where('variation_id', $request->variation_id)
                    ->where('cart_id', $cart_id)
                    ->where('product_id', $request->product_id)
                    ->where('active', 1)
                    ->first();

                if (isset($cart_detail)) {
                    $quantity = $cart_detail->quantity + $request->quantity;
                    CartDetail::query()->where('cart_id', $cart_id)
                        ->where('variation_id', $request->variation_id)
                        ->where('product_id', $request->product_id)
                        ->update([
                            'quantity' => $quantity
                        ]);
                } else {
                    CartDetail::query()->insert([
                        'cart_id' => $cart_id,
                        'shop_id' => $product->owner_id,
                        'product_id' => $request->product_id,
                        'variation_id' => $request->variation_id,
                        'quantity' => $request->quantity,
                        'price' => $price,
                    ]);
                }

                if (!empty($request->user_id)) {
                    Cart::query()->where('cart_id', $cart_id)->update([
                        'user_id' => $request->user_id
                    ]);
                }

            }else{
                return response(['message' => 'Bu ürün sepete eklenemez.', 'status' => 'auth-010']);
            }

            return response(['message' => 'Sepet ekleme işlemi başarılı.', 'status' => 'success','cart' => $cart_id]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function updateCartProduct(Request $request){
        try {
            CartDetail::query()->where('cart_id',$request->cart_id)
                ->where('product_id',$request->product_id)
                ->where('variation_id',$request->variation_id)
                ->update([
                'product_id' => $request->product_id,
                'variation_id' => $request->variation_id,
                'cart_id' => $request->cart_id,
                'quantity' => $request->quantity
            ]);
            if ($request->quantity == 0){
                CartDetail::query()->where('cart_id',$request->cart_id)->where('product_id',$request->product_id)->update([
                    'active' => 0
                ]);
                $cart_product_count = CartDetail::query()->where('cart_id',$request->cart_id)->where('active',1)->count();
                if ($cart_product_count == 0){
                    Cart::query()->where('cart_id',$request->cart_id)->update([
                        'active' => 0
                    ]);
                    return response(['message' => 'Sepet silme işlemi başarılı.','status' => 'success']);
                }

            }
            return response(['message' => 'Sepet güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function deleteCartProduct(Request $request){
        try {
            CartDetail::query()->where('cart_id',$request->cart_id)
                ->where('product_id',$request->product_id)
                ->where('variation_id',$request->variation_id)
                ->update([
                'active' => 0
            ]);
            $cart_details = CartDetail::query()->where('cart_id',$request->cart_id)->where('active', 1)->count();
            if ($cart_details > 0){
                return response(['message' => 'Sepet silme işlemi başarılı.', 'status' => 'success', 'cart_status' => true]);
            }else{
                Cart::query()->where('cart_id',$request->cart_id)->update([
                    'active' => 0
                ]);
                return response(['message' => 'Sepet silme işlemi başarılı.', 'status' => 'success', 'cart_status' => false]);
            }
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function getCartById($cart_id){
        try {
            $cart = Cart::query()->where('cart_id',$cart_id)->first();
            $cart_details = CartDetail::query()->where('cart_id',$cart->cart_id)->where('active',1)->get();
            $cart_price = 0;
            foreach ($cart_details as $cart_detail){
                $product = Product::query()->where('id',$cart_detail->product_id)->first();

                $product_price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $price = $product_price->base_price;
                if ($product_price->discounted_price != null && $product_price->discount_rate != null && $product_price->discount_rate != '0.00') {
                    $price = $product_price->discounted_price;
                }
                $currency = $product_price->currency;

                if (!empty($cart_detail->variation_id)) {
                    $variation = ProductVariation::query()->where('product_id', $product->id)->where('id', $cart_detail->variation_id)->where('active', 1)->first();

                    if ($variation) {
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $cart_detail->variation_id)->orderByDesc('id')->first();
                        $price = $variation_price->price;

                        $product['variation'] = $variation;
                    }
                }

                $product['price'] = $price;
                $product['currency'] = $currency;

                $product['shop'] = Shop::query()->where('id', $product->owner_id)->first();


                $cart_detail['product'] = $product;

                $total_price = $price * $cart_detail->quantity;
                $cart_price += $total_price;

                $cart_detail['total_price'] = number_format($total_price, 2,",",".");
                $cart_detail['currency'] = $currency;

            }
            $cart['cart_details'] = $cart_details;
            $cart['total_price'] = number_format($cart_price, 2,",",".");
            $cart['currency'] = $currency;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['cart' => $cart]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getUserAllCartById($user_id){
        try {
            $user_cart = Cart::query()->where('user_id', $user_id)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['user_cart' => $user_cart]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getClearCartById($cart_id){
        try {
            CartDetail::query()->where('cart_id', $cart_id)
                ->update([
                    'active' => 0
                ]);

            Cart::query()->where('cart_id', $cart_id)->update([
                'active' => 0
            ]);

            return response(['message' => 'Sepet silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function getUserToCart($user_id, $cart_id){
        try {

            Cart::query()->where('cart_id', $cart_id)->where('user_id', null)->update([
                'user_id' => $user_id
            ]);
            $cart_details = CartDetail::query()->where('cart_id', $cart_id)->where('user_id', $user_id)->get();
            foreach ($cart_details as $cart_detail){
                $rule = ProductRule::query()->where('variation_id', $cart_detail->variation_id)->first();
                $product = Product::query()->where('id', $cart_detail->product_id)->first();

                    $user = User::query()->where('id', $user_id)->where('active', 1)->first();
                    $total_user_discount = $user->user_discount;

                    $type_discount = UserTypeDiscount::query()->where('user_type_id',$user->user_type)->where('brand_id',$product->brand_id)->where('type_id',$product->type_id)->where('active', 1)->first();
                    if(!empty($type_discount)){
                        $total_user_discount = $total_user_discount + $type_discount->discount;
                    }

                    if ($total_user_discount > 0){
                        if ($rule->discounted_price == null || $rule->discount_rate == 0){
                            $price = $rule->regular_price - ($rule->regular_price / 100 * $total_user_discount);
                        }else{
                            $price = $rule->regular_price - ($rule->regular_price / 100 * ($total_user_discount + $rule->discount_rate));
                        }

                        CartDetail::query()->where('id', $cart_detail->id)->update([
                            'price' => $price
                        ]);
                    }

            }

            return response(['message' => 'Güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','e'=> $throwable->getMessage()]);
        }
    }

    public function getCheckoutPrices(Request $request){
        try {

            $cart_id = $request->cart_id;
            $user_id = $request->user_id;
            $coupon_code = $request->coupon_code;

            $checkout_prices = array();
            $products_subtotal_price = null;
            $coupon_message = null;
            $coupon_subtotal_price = null;
            $total_price = null;


            $cart = Cart::query()->where('cart_id',$cart_id)->first();
            $cart_details = CartDetail::query()->where('cart_id',$cart->cart_id)->where('active',1)->get();
            $cart_price = 0;
            $currency = "";
            foreach ($cart_details as $cart_detail){

                $cart_product_price = null;
                $product = Product::query()->where('id',$cart_detail->product_id)->first();

                $product_price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $price = $product_price->base_price;
                if ($product_price->discounted_price != null && $product_price->discount_rate != null && $product_price->discount_rate != '0.00') {
                    $price = $product_price->discounted_price;
                }
                $currency = $product_price->currency;

                if (!empty($cart_detail->variation_id)) {
                    $variation = ProductVariation::query()->where('product_id', $product->id)->where('id', $cart_detail->variation_id)->where('active', 1)->first();

                    if ($variation) {
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $cart_detail->variation_id)->orderByDesc('id')->first();
                        $price = $variation_price->price;
                    }
                }

                $cart_product_price = $price * $cart_detail->quantity;
                $total_price += $cart_product_price;
            }

            $cart['currency'] = $currency;

            $products_subtotal_price = $total_price;

            if($coupon_code != null){
                $coupon = Coupons::query()->where('code', $coupon_code)->first();
                if ($coupon->discount_type == 1){
                    $coupon_message = $coupon->discount." TL indirim.";
                    $coupon_subtotal_price = $products_subtotal_price - $coupon->discount;
                }elseif ($coupon->discount_type == 2){
                    $coupon_message = "%".$coupon->discount." indirim.";
                    $coupon_subtotal_price = $products_subtotal_price - ($products_subtotal_price / 100 * $coupon->discount);
                }
                $total_price = $coupon_subtotal_price;
            }


            $checkout_prices['coupon_code'] = $coupon_code;
            $checkout_prices['coupon_message'] = $coupon_message;
            $checkout_prices['coupon_subtotal_price'] = number_format($coupon_subtotal_price, 2, ",", ".");
            $checkout_prices['products_subtotal_price'] = number_format($products_subtotal_price, 2,",",".");
            $checkout_prices['total_price'] = number_format($total_price, 2,",",".");


            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['checkout_prices' => $checkout_prices]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function setIsOrder($cart_id, $is_order){
        try {

            Cart::query()->where('cart_id', $cart_id)->update([
                'is_order' => $is_order
            ]);

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

}
