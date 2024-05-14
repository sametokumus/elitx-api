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
            $cart_tax = 0;
            $weight = 0;
            foreach ($cart_details as $cart_detail){
                $product = Product::query()->where('id',$cart_detail->product_id)->first();
                $variation = ProductVariation::query()->where('id',$cart_detail->variation_id)->first();
                $rule = ProductRule::query()->where('variation_id',$cart_detail->variation_id)->first();
                $image = ProductImage::query()->where('variation_id',$cart_detail->variation_id)->first();

                if($cart->user_id != null) {
                    $user = User::query()->where('id', $cart->user_id)->where('active', 1)->first();
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


                        $cart_detail_price = $price * $cart_detail->quantity;
                        $cart_detail_tax = ($price * $cart_detail->quantity) / 100 * $rule->tax_rate;
                    }else{
                        if ($rule->discounted_price == null || $rule->discount_rate == 0){
                            $cart_detail_price = $rule->regular_price * $cart_detail->quantity;
                            $cart_detail_tax = $rule->regular_tax * $cart_detail->quantity;
                        }else{
                            $cart_detail_price = $rule->discounted_price * $cart_detail->quantity;
                            $cart_detail_tax = $rule->discounted_tax * $cart_detail->quantity;
                        }
                    }
                }else{
                    if ($rule->discounted_price == null || $rule->discount_rate == 0){
                        $cart_detail_price = $rule->regular_price * $cart_detail->quantity;
                        $cart_detail_tax = $rule->regular_tax * $cart_detail->quantity;
                    }else{
                        $cart_detail_price = $rule->discounted_price * $cart_detail->quantity;
                        $cart_detail_tax = $rule->discounted_tax * $cart_detail->quantity;
                    }
                }

                if ($rule->currency == "EUR"){
                    $try_currency = array();
                    $try_currency['regular_price'] = convertEURtoTRY($rule->regular_price);
                    $try_currency['regular_tax'] = convertEURtoTRY($rule->regular_tax);
                    $try_currency['discounted_price'] = convertEURtoTRY($rule->discounted_price);
                    $try_currency['discounted_tax'] = convertEURtoTRY($rule->discounted_tax);
                    $try_currency['currency'] = "TL";
                    if ($rule['extra_discount'] == 1){
                        $try_currency['extra_discount_price'] = convertEURtoTRY($rule['extra_discount_price']);
                        $try_currency['extra_discount_tax'] = convertEURtoTRY($rule['extra_discount_tax']);
                    }
                    $rule['try_currency'] = $try_currency;
                }else if ($rule->currency == "USD") {
                    $try_currency = array();
                    $try_currency['regular_price'] = convertUSDtoTRY($rule->regular_price);
                    $try_currency['regular_tax'] = convertUSDtoTRY($rule->regular_tax);
                    $try_currency['discounted_price'] = convertUSDtoTRY($rule->discounted_price);
                    $try_currency['discounted_tax'] = convertUSDtoTRY($rule->discounted_tax);
                    $try_currency['currency'] = "TL";
                    if ($rule['extra_discount'] == 1){
                        $try_currency['extra_discount_price'] = convertUSDtoTRY($rule['extra_discount_price']);
                        $try_currency['extra_discount_tax'] = convertUSDtoTRY($rule['extra_discount_tax']);
                    }
                    $rule['try_currency'] = $try_currency;
                }

                $variation['rule'] = $rule;
                $variation['image'] = $image;
                $product['variation'] = $variation;
                $cart_detail['product'] = $product;

                $weight = $weight + $rule->weight;
//                if($product->is_free_shipping == 1){
//                    $cart_detail_delivery_price = 0.00;
//                }
                $cart_detail['sub_total_price'] = number_format($cart_detail_price, 2,",",".");
                $cart_detail['sub_total_tax'] = number_format($cart_detail_tax, 2,",",".");
                $cart_detail['sub_total_price_with_tax'] = number_format(($cart_detail_price + $cart_detail_tax), 2,",",".");
                $cart_detail['currency'] = "TL";


                if ($rule->currency == "EUR"){
                    $cart_detail['currency'] = "EUR";
                    $try_currency = array();
                    $try_currency['price'] = convertEURtoTRY($cart_detail_price);
                    $try_currency['sub_total_price'] = number_format(convertEURtoTRY($cart_detail_price), 2,",",".");
                    $try_currency['sub_total_tax'] = number_format(convertEURtoTRY($cart_detail_tax), 2,",",".");
                    $try_currency['sub_total_price_with_tax'] = number_format(convertEURtoTRY($cart_detail_price + $cart_detail_tax), 2,",",".");
                    $try_currency['currency'] = "TL";
                    $cart_detail['try_currency'] = $try_currency;
                }else if ($rule->currency == "USD") {
                    $cart_detail['currency'] = "USD";
                    $try_currency = array();
                    $try_currency['price'] = convertUSDtoTRY($cart_detail_price);
                    $try_currency['sub_total_price'] = number_format(convertUSDtoTRY($cart_detail_price), 2,",",".");
                    $try_currency['sub_total_tax'] = number_format(convertUSDtoTRY($cart_detail_tax), 2,",",".");
                    $try_currency['sub_total_price_with_tax'] = number_format(convertUSDtoTRY($cart_detail_price + $cart_detail_tax), 2,",",".");
                    $try_currency['currency'] = "TL";
                    $cart_detail['try_currency'] = $try_currency;
                }


                if ($rule->currency == "EUR"){
                    $cart_price += convertEURtoTRY($cart_detail_price);
                    $cart_tax += convertEURtoTRY($cart_detail_tax);
                }else if ($rule->currency == "USD") {
                    $cart_price += convertUSDtoTRY($cart_detail_price);
                    $cart_tax += convertUSDtoTRY($cart_detail_tax);
                }else{

                    $cart_price += $cart_detail_price;
                    $cart_tax += $cart_detail_tax;
                }

            }
            $cart['cart_details'] = $cart_details;
            $cart['total_price'] = number_format($cart_price, 2,",",".");$cart_price;
            $cart['total_tax'] = number_format($cart_tax, 2,",",".");$cart_tax;
            $cart['total_price_with_tax'] = number_format(($cart_price + $cart_tax), 2,",",".");


            $delivery_price = DeliveryPrice::query()->where('min_value', '<=', $weight)->where('max_value', '>', $weight)->first();
            $cart['total_delivery'] = $delivery_price;
            $cart['total_weight'] = $weight;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['cart' => $cart]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getUserAllCartById($user_id){
        try {
            $user_cart = Cart::query()->where('user_id',$user_id)->get();
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
            $address_id = $request->address_id;
            $coupon_code = $request->coupon_code;

            $checkout_prices = array();
            $products_subtotal_price = null;
            $products_cart_price = null;
            $products_cart_tax = null;
            $extra_discount = false;
            $coupon_message = null;
            $coupon_subtotal_price = null;
            $delivery_price = null;
            $total_price = null;
            $total_price_with_delivery = null;


            $address = Address::query()->where('id', $address_id)->where('active', 1)->first();
            $carriers = Carrier::query()
                ->leftJoin('district_deliveries', 'district_deliveries.carrier_id', '=', 'carriers.id')
                ->selectRaw('carriers.*, district_deliveries.category as category')
                ->where('carriers.active', 1)
                ->where('district_deliveries.active', 1)
                ->where('district_deliveries.district_id', $address->district_id)
                ->get();
            foreach ($carriers as $carrier){
                if ($carrier->category == 0){
                    $carrier['is_delivery'] = 0;
                }else{
                    $carrier['is_delivery'] = 1;
                }
            }


            $material_array = array();
            $materials = ProductMaterial::query()->where('active', 1)->get();
            foreach ($materials as $material){
                $material['desi'] = 0;
                $material_array[$material->id] = $material;
            }


//            $user_discount_rate = User::query()->where('id', $user_id)->where('active', 1)->first()->user_discount;
//            if($user_discount_rate > 0){$user_discount = true;}


            //ürünlerin ara toplam fiyatı
            //ürünlerin kullanıcı indirimi dahil ara toplam fiyatı

            // products_subtotal_price, user_discount_rate, coupon_subtotal_price, delivery_price, total_price, total_price_with_delivery

            $cart = Cart::query()->where('cart_id',$cart_id)->first();
            $cart_details = CartDetail::query()->where('cart_id',$cart->cart_id)->where('active',1)->get();
            $cart_price = 0;
            $cart_tax = 0;
            $weight = 0;
            $all_free_shipping = 1;
            foreach ($cart_details as $cart_detail){
                $rule = ProductRule::query()->where('variation_id',$cart_detail->variation_id)->first();
                $product = Product::query()->where('id',$cart_detail->product_id)->first();

                $user = User::query()->where('id', $user_id)->first();
                $total_user_discount = $user->user_discount;

                $type_discount = UserTypeDiscount::query()->where('user_type_id', $user->user_type)->where('brand_id', $product->brand_id)->where('type_id', $product->type_id)->where('active', 1)->first();
                if (!empty($type_discount)) {
                    $total_user_discount = $total_user_discount + $type_discount->discount;
                }

                if ($total_user_discount > 0) {
                    $extra_discount = true;
                }

                if ($rule->discounted_price == null || $rule->discount_rate == 0){
                    if($extra_discount){
                        $cart_detail_price = ($rule->regular_price - ($rule->regular_price / 100 * $total_user_discount)) * $cart_detail->quantity;
                        $cart_detail_tax = $cart_detail_price / 100 * $rule->tax_rate;
                    }else{
                        $cart_detail_price = $rule->regular_price * $cart_detail->quantity;
                        $cart_detail_tax = $rule->regular_tax * $cart_detail->quantity;
                    }
                }else{
                    if($extra_discount){
                        $cart_detail_price = ($rule->regular_price - ($rule->regular_price / 100 * ($total_user_discount + $rule->discount_rate))) * $cart_detail->quantity;
                        $cart_detail_tax = $cart_detail_price / 100 * $rule->tax_rate;
                    }else{
                        $cart_detail_price = $rule->discounted_price * $cart_detail->quantity;
                        $cart_detail_tax = $rule->discounted_tax * $cart_detail->quantity;
                    }
                }
                if ($product->is_free_shipping == 0) {
                    $weight = $weight + ($cart_detail->quantity * $rule->weight);
                    $material_array[$rule->material]['desi'] = $material_array[$rule->material]['desi'] + ($cart_detail->quantity * $rule->weight);

                    $all_free_shipping = 0;
                }else{
                    $weight = $weight + 0;
                    $material_array[$rule->material]['desi'] = $material_array[$rule->material]['desi'] + 0;
                }

                $step_desi = $rule->weight * $rule->quantity_step;
                foreach ($carriers as $carrier){
                    if ($carrier->category == 1){
                        if ($step_desi > $carrier->max_desi) {
                            $carrier['is_delivery'] = 0;
                        }
                    }
                }

//                $cart_price += $cart_detail_price;
//                $cart_tax += $cart_detail_tax;

                if ($rule->currency == "EUR"){
                    $cart_price += convertEURtoTRY($cart_detail_price);
                    $cart_tax += convertEURtoTRY($cart_detail_tax);
                }else if ($rule->currency == "USD") {
                    $cart_price += convertUSDtoTRY($cart_detail_price);
                    $cart_tax += convertUSDtoTRY($cart_detail_tax);
                }else{
                    $cart_price += $cart_detail_price;
                    $cart_tax += $cart_detail_tax;
                }

            }
            $products_cart_price = $cart_price;
            $products_cart_tax = $cart_tax;
            $products_subtotal_price = $cart_price + $cart_tax;
            $total_price = $products_subtotal_price;

            if($coupon_code != "null"){
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


            $checkout_prices['products_subtotal_price'] = number_format($products_subtotal_price, 2,",",".");
            $checkout_prices['products_cart_price'] = number_format($products_cart_price, 2,",",".");
            $checkout_prices['products_cart_tax'] = number_format($products_cart_tax, 2,",",".");
//            $checkout_prices['user_discount'] = $user_discount;
//            $checkout_prices['user_discount_rate'] = $user_discount_rate;
            $checkout_prices['all_free_shipping'] = $all_free_shipping;
            $checkout_prices['coupon_code'] = $coupon_code;
            $checkout_prices['material'] = $material_array;
            $checkout_prices['coupon_message'] = $coupon_message;
            $checkout_prices['coupon_subtotal_price'] = number_format($coupon_subtotal_price, 2, ",", ".");
            $checkout_prices['total_price'] = number_format($total_price, 2,",",".");

            foreach ($carriers as $carrier){
                if ($carrier['is_delivery'] == 1){
                    $shipment_price = 0;
                    foreach ($materials as $material){

                        $weight = $material_array[$material->id]['desi'];

                        //Kargo Çarpanı

                        $weight = $weight * $material->multiplier;

                        if ($weight == 0){
                        }else{

                            $delivery_price = DeliveryPrice::query()->where('carrier_id', $carrier->id)->where('min_value', '<=', $weight)->where('max_value', '>', $weight)->first();
                            if ($delivery_price){
                                $shipment_price += $delivery_price->{'cat_'.$carrier->category.'_price'};
                            }else{
                                $delivery_price_max = DeliveryPrice::query()->where('carrier_id', $carrier->id)->orderByDesc('max_value')->first();
                                $increses_weight = IncreasingDesi::query()->where('carrier_id', $carrier->id)->first();

                                $diff_price = ($weight - $delivery_price_max->max_value) * ($increses_weight->{'cat_'.$carrier->category.'_price'});
                                $shipment_price += ($delivery_price_max->{'cat_'.$carrier->category.'_price'} + $diff_price);

                            }

                        }

                    }
                    $delivery_price_without_tax = $shipment_price / 118 * 100;
                    $delivery_price_tax = $shipment_price - $delivery_price_without_tax;
                    $total_price_with_delivery = $total_price + $shipment_price;

                    $carrier['delivery_price'] = number_format($shipment_price, 2,",",".");
                    $carrier['delivery_price_without_tax'] = number_format($delivery_price_without_tax, 2,",",".");
                    $carrier['delivery_price_tax'] = number_format($delivery_price_tax, 2,",",".");
                    $carrier['total_price_with_delivery'] = number_format($total_price_with_delivery, 2,",",".");
                }
            }

            $checkout_prices['carriers'] = $carriers;

//            $delivery_price = DeliveryPrice::query()->where('min_value', '<=', $weight)->where('max_value', '>', $weight)->first();
//            $regional_delivery_price = RegionalDeliveryPrice::query()->where('delivery_price_id', $delivery_price->id)->where('city_id', $address->city_id)->first();
//            $regional_delivery_price_without_tax = $regional_delivery_price->price / 118 * 100;
//            $regional_delivery_price_tax = $regional_delivery_price->price - $regional_delivery_price_without_tax;
//            $total_price_with_delivery = $total_price + $regional_delivery_price->price;


//
//            $checkout_prices['delivery_price'] = number_format($regional_delivery_price->price, 2,",",".");
//            $checkout_prices['delivery_price_tax'] = number_format($regional_delivery_price_tax, 2,",",".");
//            $checkout_prices['delivery_price_without_tax'] = number_format($regional_delivery_price_without_tax, 2,",",".");
//
//            $checkout_prices['total_price_with_delivery'] = number_format($total_price_with_delivery, 2,",",".");

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
