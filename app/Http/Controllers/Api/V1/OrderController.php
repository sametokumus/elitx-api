<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BankRequest;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\City;
use App\Models\CorporateAddresses;
use App\Models\Country;
use App\Models\Coupons;
use App\Models\DeliveryPrice;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderRefund;
use App\Models\OrderRefundStatus;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductRule;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\User;
use App\Models\UserTypeDiscount;
use DateTime;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class OrderController extends Controller
{

    public function addOrder(Request $request)
    {
        try {
            $cart = Cart::query()->where('cart_id', $request->cart_id)->where('active', 1)->first();
            $user = Auth::user();

            if(isset($cart)) {

                $order_status = OrderStatus::query()->where('is_default', 1)->first();
                $order_quid = Uuid::uuid();
                $shipping_id = $request->shipping_address_id;
                $billing_id = $request->billing_address_id;
                $shipping = Address::query()->where('id', $shipping_id)->first();
                $country = Country::query()->where('id', $shipping->country_id)->first();
                $city = City::query()->where('id', $shipping->city_id)->first();

                $shipping_address = $shipping->name . " " . $shipping->surname . " - " . $shipping->address_1 . " " . $shipping->address_2 . " - " . $shipping->postal_code . " - " . $shipping->phone . " - " . $city->name . " / " . $country->name;
                if ($shipping->type == 2){
                    $shipping_corporate_address = CorporateAddresses::query()->where('address_id',$shipping_id)->first();
                    $shipping_address = $shipping_address." - ".$shipping_corporate_address->tax_number." - ".$shipping_corporate_address->tax_office." - ".$shipping_corporate_address->company_name;
                }


                $billing = Address::query()->where('id', $billing_id)->first();
                $billing_country = Country::query()->where('id', $billing->country_id)->first();
                $billing_city = City::query()->where('id', $billing->city_id)->first();
                $billing_address = $billing->name . " " . $billing->surname . " - " . $billing->address_1 . " " . $billing->address_2 . " - " . $billing->postal_code . " - " . $billing->phone . " - " . $billing_city->name . " / " . $billing_country->name;

                if ($billing->type == 2){
                    $billing_corporate_address = CorporateAddresses::query()->where('address_id',$billing_id)->first();
                    $billing_address = $billing_address." - ".$billing_corporate_address->tax_number." - ".$billing_corporate_address->tax_office." - ".$billing_corporate_address->company_name;
                }

                $order_id = Order::query()->insertGetId([
                    'order_id' => $order_quid,
                    'user_id' => $user->id,
                    'cart_id' => $request->cart_id,
                    'status_id' => $order_status->id,
                    'shipping_address_id' => $request->shipping_address_id,
                    'billing_address_id' => $request->billing_address_id,
                    'shipping_address' => $shipping_address,
                    'billing_address' => $billing_address,
                    'comment' => $request->comment,
                    'payment_method' => $request->payment_method,
                    'shipping_price' => $request->shipping_price,
                    'subtotal' => $request->subtotal,
                    'total' => $request->total,
                    'coupon_code' => $request->coupon_code
                ]);

                Cart::query()->where('cart_id', $request->cart_id)->update([
                    'user_id' => $user->id,
                    'is_order' => 1,
                    'active' => 0
                ]);

                $user_discount = User::query()->where('id', $request->user_id)->first()->user_discount;
                $carts = CartDetail::query()->where('cart_id', $request->cart_id)->get();
                foreach ($carts as $cart) {
                    $product = Product::query()->where('id', $cart->product_id)->first();

                    $product_price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                    $price = $product_price->base_price;
                    $discounted_price = null;
                    if ($product_price->discounted_price != null && $product_price->discount_rate != null && $product_price->discount_rate != '0.00') {
                        $discounted_price = $product_price->discounted_price;
                    }
                    $currency = $product_price->currency;

                    if (!empty($cart->variation_id)) {
                        $variation = ProductVariation::query()->where('product_id', $product->id)->where('id', $request->variation_id)->where('active', 1)->first();

                        if ($variation) {
                            $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $request->variation_id)->orderByDesc('id')->first();
                            $price = $variation_price->price;
                        }

                        $discounted_price = null;
                    }

                    if ($discounted_price == null){
                        $total = $price * $cart->quantity;
                    }else{
                        $total = $discounted_price * $cart->quantity;
                    }

                    OrderProduct::query()->insert([
                        'order_id' => $order_quid,
                        'product_id' => $product->id,
                        'variation_id' => $cart->variation_id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => $price,
                        'discounted_price' => $discounted_price,
                        'quantity' => $cart->quantity,
                        'total' => $total
                    ]);
                }

                OrderStatusHistory::query()->insert([
                    'order_id' => $order_quid,
                    'status_id' => $order_status->id
                ]);

                if ($request->coupon_code != "") {
                    $coupon_info = Coupons::query()->where('code', $request->coupon_code)->where('active', 1)->first();
                    $used_count = $coupon_info->count_of_used + 1;
                    $coupon_active = 1;
                    if ($coupon_info->count_of_uses == $used_count) {
                        $coupon_active = 0;
                    }
                    Coupons::query()->where('code', $request->coupon_code)->where('active', 1)->update([
                        'count_of_used' => $used_count,
                        'active' => $coupon_active
                    ]);
                }


                //ÖDEME
                if ($request->payment_type == 1){
                    //Kredi Kartı
                    $response_code = 200;

                    if ($response_code == 200){
                        Order::query()->where('id', $order_id)->update([
                            'is_paid' => 1
                        ]);
                        $payment_quid = Uuid::uuid();
                        Payment::query()->insert([
                            'order_id' => $order_quid,
                            'payment_id' => $payment_quid,
                            'price' => $request->total,
                            'type' => $request->payment_type,
                            'installment' => $request->installment_count,
                            'is_paid' => 1
                        ]);
                    }else{
                        return response(['message' => 'Ödeme Hatası.', 'status' => 'pay-001']);
                    }
                }else{
                    //Diğer
                    $response_code = 200;

                    if ($response_code == 200){
                        Order::query()->where('id', $order_id)->update([
                            'is_paid' => 1
                        ]);
                        $payment_quid = Uuid::uuid();
                        Payment::query()->insert([
                            'order_id' => $order_quid,
                            'payment_id' => $payment_quid,
                            'price' => $request->total,
                            'type' => $request->payment_type,
                            'installment' => $request->installment_count,
                            'is_paid' => 1
                        ]);
                    }else{
                        return response(['message' => 'Ödeme Hatası.', 'status' => 'pay-001']);
                    }
                }

                return response(['message' => 'Sipariş başarılı.', 'status' => 'success', 'object' => ['order_id' => $order_quid]]);
            }else{

                return response(['message' => 'Sepet Bulunamadı.', 'status' => 'cart-001']);
            }

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getOrdersByUserId($user_id){
        try {
            $orders = Order::query()->where('user_id',$user_id)->where('active', 1)->orderByDesc('id')->get(['id', 'order_id', 'cart_id', 'created_at as order_date', 'total', 'status_id','payment_method']);
            foreach ($orders as $order){
                $product_count = OrderProduct::query()->where('order_id', $order->order_id)->get()->count();
                $product = OrderProduct::query()->where('order_id', $order->order_id)->first();
                $product_image_row = ProductImage::query()->where('variation_id', $product->variation_id)->first();
                if ($product_image_row){
                    $product_image = $product_image_row->image;
                }else{
                    $product_image = '';
                }
//                $product_image = ProductImage::query()->where('variation_id', $product->variation_id)->first()->image;
                $status_name = OrderStatus::query()->where('id', $order->status_id)->first()->name;
                $payment_method = PaymentMethod::query()->where('id',$order->payment_method)->first()->name;
                $payment_type = PaymentType::query()->where('id',$order->payment_method)->first()->name;

                $order['product_count'] = $product_count;
                $order['product_image'] = $product_image;
                $order['payment_type'] = $payment_type;
                $order['payment_type_id'] = $order->payment_method;
                $order['payment_method'] = $payment_method;
                $order['status_name'] = $status_name;

                $created_at = $order->order_date;

                $start = new DateTime($created_at);
                $end = Carbon::now();

                $interval = $end->diff($start);
                $final = $interval->format('%a');

                if ($final <= 15){
                    $order['is_refundable'] = 1;
                }else{
                    $order['is_refundable'] = 'timeout';
                }

                $refund = OrderRefund::query()->where('order_id',$order->id)->first();
                if (isset($refund)){
                    $order['is_refundable'] = 0;
                    $refund['status_name'] = OrderRefundStatus::query()->where('id', $refund->status)->first()->name;
                    $order['refund'] = $refund;
                }
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['orders' => $orders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getOrderById($order_id){
        try {
            $order = Order::query()->where('order_id',$order_id)->first();
            $order['status_name'] = OrderStatus::query()->where('id', $order->status_id)->first()->name;
            if ($order->carrier_id != 0) {
                $order['carrier_name'] = Carrier::query()->where('id', $order->carrier_id)->first()->name;
            }
            if ($order->shipping_type == 0){
                $shipping_type = "Mağazadan Teslimat";
            }else {
                $shipping_type = Carrier::query()->where('id', $order->shipping_type)->first()->name;
            }
            $order['shipping_name'] = $shipping_type;
            $order['payment_method'] = PaymentMethod::query()->where('id', $order->payment_method)->first()->name;
            $order_details = OrderProduct::query()->where('order_id', $order_id)->get();
            $order_price = 0;
            $order_tax = 0;
            $weight = 0;
            foreach ($order_details as $order_detail){
                $product = Product::query()->where('id',$order_detail->product_id)->first();
                $brand_name = Brand::query()->where('id',$product->brand_id)->first()->name;
                $variation = ProductVariation::query()->where('id',$order_detail->variation_id)->first();
                $rule = ProductRule::query()->where('variation_id',$order_detail->variation_id)->first();
                $image = ProductImage::query()->where('variation_id',$order_detail->variation_id)->first();



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
                $product['brand_name'] = $brand_name;
                $order_detail['product'] = $product;

                if($order->user_id != null) {
                    $user = User::query()->where('id', $order->user_id)->first();
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
                        if ($order_detail->discounted_price == null || $order_detail->discount_rate == 0){
                            $price = $order_detail->regular_price - ($order_detail->regular_price / 100 * $total_user_discount);
                        }else{
                            $price = $order_detail->regular_price - ($order_detail->regular_price / 100 * ($total_user_discount + $order_detail->discount_rate));
                        }
                        $rule['extra_discount_price'] = number_format($price, 2,".","");
                        $rule['extra_discount_tax'] = number_format(($price / 100 * $product->tax_rate), 2,".","");


                        $order_detail_price = $price * $order_detail->quantity;
                        $order_detail_tax = ($price * $order_detail->quantity) / 100 * $rule->tax_rate;
                    }else{
                        if ($order_detail->discounted_price == null || $order_detail->discount_rate == 0){
                            $order_detail_price = $order_detail->regular_price * $order_detail->quantity;
                            $order_detail_tax = $order_detail->regular_tax * $order_detail->quantity;
                        }else{
                            $order_detail_price = $order_detail->discounted_price * $order_detail->quantity;
                            $order_detail_tax = $order_detail->discounted_tax * $order_detail->quantity;
                        }
                    }
                }else{
                    if ($order_detail->discounted_price == null || $order_detail->discount_rate == 0){
                        $order_detail_price = $order_detail->regular_price * $order_detail->quantity;
                        $order_detail_tax = $order_detail->regular_tax * $order_detail->quantity;
                    }else{
                        $order_detail_price = $order_detail->discounted_price * $order_detail->quantity;
                        $order_detail_tax = $order_detail->discounted_tax * $order_detail->quantity;
                    }
                }


//                if ($order_detail->discounted_price == null || $order_detail->discount_rate == 0){
//                    $order_detail_price = $order_detail->regular_price * $order_detail->quantity;
//                    $order_detail_tax = $order_detail->regular_tax * $order_detail->quantity;
//                }else{
//                    $order_detail_price = $order_detail->discounted_price * $order_detail->quantity;
//                    $order_detail_tax = $order_detail->discounted_tax * $order_detail->quantity;
//                }
                $weight = $weight + $rule->weight;
//                if($product->is_free_shipping == 1){
//                    $order_detail_delivery_price = 0.00;
//                }


                if ($rule->currency == "EUR"){
                    $order_detail['sub_total_price'] = convertEURtoTRY($order_detail_price);
                    $order_detail['sub_total_tax'] = convertEURtoTRY($order_detail_tax);
//                    $order_price += convertEURtoTRY($order_detail_price);
//                    $order_tax += convertEURtoTRY($order_detail_tax);
                }else if ($rule->currency == "USD") {
                    $order_detail['sub_total_price'] = convertUSDtoTRY($order_detail_price);
                    $order_detail['sub_total_tax'] = convertUSDtoTRY($order_detail_tax);
//                    $order_price += convertUSDtoTRY($order_detail_price);
//                    $order_tax += convertUSDtoTRY($order_detail_tax);
                }else{

                    $order_detail['sub_total_price'] = $order_detail_price;
                    $order_detail['sub_total_tax'] = $order_detail_tax;
//                    $order_price += $order_detail_price;
//                    $order_tax += $order_detail_tax;
                }

            }
            $order['order_details'] = $order_details;
//            $order['total_price'] = number_format($order_price, 2,".","");
//            $order['total_tax'] = number_format($order_tax, 2,".","");
//
//            if($order->coupon_code != "null"){
//                $coupon = Coupons::query()->where('code', $order->coupon_code)->where('active', 1)->first();
//                if ($coupon->discount_type == 1){
//                    $coupon_message = $coupon->discount." TL indirim.";
//                    $coupon_price = ($order_price + $order_tax) - $coupon->discount;
//                }elseif ($coupon->discount_type == 2){
//                    $coupon_message = "%".$coupon->discount." indirim.";
//                    $coupon_price = $order_price - (($order_price + $order_tax) / 100 * $coupon->discount);
//                }
//
//                $order['coupon_price'] = number_format($coupon_price, 2,".","");
//                $order['coupon_message'] = $coupon_message;
//            }


//            $delivery_price = DeliveryPrice::query()->where('min_value', '<=', $weight)->where('max_value', '>', $weight)->first();
//            $order['total_delivery'] = $delivery_price;
            $order['total_weight'] = $weight;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order' => $order]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getOrderByPaymentId($payment_id){
        try {
            $order_id = Payment::query()->where('payment_id', $payment_id)->where('active', 1)->first()->order_id;

            $order = Order::query()->where('order_id',$order_id)->first();
            $order['status_name'] = OrderStatus::query()->where('id', $order->status_id)->first()->name;
//            $order['carrier_name'] = Carrier::query()->where('id', $order->carrier_id)->first()->name;
            if ($order->shipping_type == 0){
                $order['shipping_name'] = "Mağazadan Teslimat";
            }else {
                $order['shipping_name'] = Carrier::query()->where('id', $order->shipping_type)->first()->name;
            }
            $order['payment_method'] = PaymentMethod::query()->where('id', $order->payment_method)->first()->name;
            $order_details = OrderProduct::query()->where('order_id', $order_id)->get();
            $order_price = 0;
            $order_tax = 0;
            $weight = 0;
            foreach ($order_details as $order_detail){
                $product = Product::query()->where('id',$order_detail->product_id)->first();
                $brand_name = Brand::query()->where('id',$product->brand_id)->first()->name;
                $variation = ProductVariation::query()->where('id',$order_detail->variation_id)->first();
                $rule = ProductRule::query()->where('variation_id',$order_detail->variation_id)->first();
                $image = ProductImage::query()->where('variation_id',$order_detail->variation_id)->first();

                $variation['rule'] = $rule;
                $variation['image'] = $image;
                $product['variation'] = $variation;
                $product['brand_name'] = $brand_name;
                $order_detail['product'] = $product;
                if ($order_detail->discounted_price == null || $order_detail->discount_rate == 0){
                    $order_detail_price = $order_detail->regular_price * $order_detail->quantity;
                    $order_detail_tax = $order_detail->regular_tax * $order_detail->quantity;
                }else{
                    $order_detail_price = $order_detail->discounted_price * $order_detail->quantity;
                    $order_detail_tax = $order_detail->discounted_tax * $order_detail->quantity;
                }
                $weight = $weight + $rule->weight;
//                if($product->is_free_shipping == 1){
//                    $order_detail_delivery_price = 0.00;
//                }
                $order_detail['sub_total_price'] = $order_detail_price;
                $order_detail['sub_total_tax'] = $order_detail_tax;
                $order_price += $order_detail_price;
                $order_tax += $order_detail_tax;

            }
            $order['order_details'] = $order_details;
            $order['total_price'] = $order_price;
            $order['total_tax'] = $order_tax;

            $delivery_price = DeliveryPrice::query()->where('min_value', '<=', $weight)->where('max_value', '>', $weight)->first();
            $order['total_delivery'] = $delivery_price;
            $order['total_weight'] = $weight;

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order' => $order]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addBankRequest(Request $request)
    {
        try {
            BankRequest::query()->insert([
                'payment_id' => $request->payment_id,
                'pos_request' => $request->pos_request,
                'pos_response' => $request->pos_response,
                'type' => 1,
                'success' => $request->success,
                'transaction_id' => $request->transaction_id
            ]);
            return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateBankRequest(Request $request)
    {
        try {
            $req = BankRequest::query()->where('payment_id', $request->payment_id)->where('success', 0)->where('type', 1)->orderByDesc('id')->first();
            if ($req) {
                BankRequest::query()->where('id', $req->id)->update([
                    'pos_response' => $request->pos_response,
                    'success' => $request->success
                ]);
            }
            return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function addPayment(Request $request)
    {
        try {

            $is_proforma = Payment::query()->where('order_id', $request->order_id)->where('type', 3)->where('active', 1)->count();

            if ($is_proforma > 0){
                $payment_quid = Uuid::uuid();
                Payment::query()->where('order_id', $request->order_id)->where('type', 3)->where('active', 1)->update([
                    'payment_id' => $payment_quid,
                    'default_price' => $request->default_price,
                    'paid_price' => $request->paid_price,
                    'type' => $request->type,
                    'bank_id' => $request->bank_id,
                    'installment' => $request->installment_count
                ]);

            }else {

                $payment_quid = Uuid::uuid();
                Payment::query()->insert([
                    'order_id' => $request->order_id,
                    'payment_id' => $payment_quid,
                    'default_price' => $request->default_price,
                    'paid_price' => $request->paid_price,
                    'type' => $request->type,
                    'bank_id' => $request->bank_id,
                    'installment' => $request->installment_count
                ]);
            }

            return response(['message' => 'Ödeme oluşturuldu.', 'status' => 'success', 'object' => ['payment_id' => $payment_quid]]);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updatePayment(Request $request)
    {
        try {

            Payment::query()->where('payment_id', $request->payment_id)->update([
                'return_code' => $request->return_code,
                'response' => $request->response,
                'transaction_id' => $request->transaction_id,
                'transaction_date' => $request->transaction_date,
                'hostrefnum' => $request->hostrefnum,
                'authcode' => $request->authcode,
                'is_preauth' => 1,
                'is_paid'=> 0
            ]);
            $order_id = Payment::query()->where('payment_id', $request->payment_id)->where('active')->get()->order_id;

            $order = Order::query()->where('order_id', $order_id)->where('active', 1)->first();
            $order_payments = Payment::query()->where('order_id', $order_id)->where('active', 1)->get();
            $payment_totals = 0.00;
            foreach ($order_payments as $order_payment){
                $payment_totals += $order_payment->default_price;
            }
            if ($payment_totals == $order->total){
                Order::query()->where('order_id', $order_id)->update([
                    'is_preauth' => 1,
                    'status_id' => 3
                ]);
            }

            return response(['message' => 'Ödeme güncellendi.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getOrderPaymentStatusByPaymentId($payment_id){
        try {

            $order_id = Payment::query()->where('payment_id', $payment_id)->where('active', 1)->first()->order_id;

            $order = Order::query()->where('order_id', $order_id)->where('active', 1)->first();
            $order_payments = Payment::query()->where('order_id', $order_id)->where('active', 1)->get();
            $payment_default_totals = 0.00;
            $payment_totals = 0.00;
            $i = 0;
            foreach ($order_payments as $order_payment){
                $payment_default_totals += $order_payment->default_price;
                $payment_totals += $order_payment->paid_price;
                $i++;
            }
            $payment_details = array();
            $payment_details['order_payment_methods'] = $order->payment_method;
            $payment_details['order_total'] = number_format($order->total, 2,".","");
            $payment_details['payment_total'] = number_format($payment_totals, 2,".","");
            $payment_details['payment_default_total'] = number_format($payment_default_totals, 2,".","");
            $payment_details['count'] = $i;
            $payment_details['order_payments'] = $order_payments;

            return response(['message' => 'Başarılı.', 'status' => 'success', 'object' => ['payment_details' => $payment_details]]);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }


}
