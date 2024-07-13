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
use App\Models\OrderProductStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductRule;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
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

                Cart::query()->where('cart_id', $request->cart_id)->update([
                    'user_id' => $user->id,
                    'is_order' => 1,
                    'active' => 0
                ]);

                $total_price = null;
                $total_commission = 0;

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

                    if ($cart->variation_id != null) {
                        $variation = ProductVariation::query()->where('product_id', $cart->product_id)->where('id', $cart->variation_id)->where('active', 1)->first();

                        if ($variation) {
                            $variation_price = ProductVariationPrice::query()->where('product_id', $cart->product_id)->where('variation_id', $cart->variation_id)->orderByDesc('id')->first();
                            $price = $variation_price->price;
                        }

                        $discounted_price = null;
                    }

                    if ($discounted_price == null){
                        $total = $price * $cart->quantity;
                    }else{
                        $total = $discounted_price * $cart->quantity;
                    }
                    $total_price += $total;

                    $product_commission = 0;
                    if ($product->owner_type == 1) {
                        $shop = Shop::query()->where('id', $product->owner_id)->first();
                        $commission_rate = $shop->commission_rate;
                        $product_commission = $total / 100 * $commission_rate;
                        $total_commission += $product_commission;
                    }

                    $order_product_id = OrderProduct::query()->insertGetId([
                        'order_id' => $order_quid,
                        'product_id' => $product->id,
                        'variation_id' => $cart->variation_id,
                        'status_id' => $order_status->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => $price,
                        'discounted_price' => $discounted_price,
                        'quantity' => $cart->quantity,
                        'total' => $total,
                        'commission_rate' => $commission_rate,
                        'commission_total' => $product_commission
                    ]);

                    OrderProductStatusHistory::query()->insert([
                        'status_id' => $order_status->id,
                        'order_id' => $order_quid,
                        'order_product_id' => $order_product_id
                    ]);
                }


                $products_subtotal_price = $total_price;

                if($request->coupon_code != null && $request->coupon_code != ''){
                    $coupon = Coupons::query()->where('code', $request->coupon_code)->first();
                    if ($coupon->discount_type == 1){
                        $coupon_subtotal_price = $products_subtotal_price - $coupon->discount;
                    }elseif ($coupon->discount_type == 2){
                        $coupon_subtotal_price = $products_subtotal_price - ($products_subtotal_price / 100 * $coupon->discount);
                    }
                    $total_price = $coupon_subtotal_price;
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
                    'subtotal' => $products_subtotal_price,
                    'total' => $total_price,
                    'currency' => $request->currency,
                    'coupon_code' => $request->coupon_code,
                    'commission_total' => $total_commission
                ]);

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
                            'price' => $total_price,
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
                            'price' => $total_price,
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
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage(), 'l' => $throwable->getLine()]);
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

            $status_name = OrderStatus::query()->where('id', $order->status_id)->first()->name;
            $order['status_name'] = $status_name;
            $product_count = OrderProduct::query()->where('order_id', $order->order_id)->get()->count();
            $order['product_count'] = $product_count;
            $products = OrderProduct::query()->where('order_id', $order->order_id)->get();
            foreach ($products as $product){
                $product['status_name'] = OrderStatus::query()->where('id', $product->status_id)->first()->name;
            }

            if ($order->is_paid == 1){
                $payment = Payment::query()->where('order_id', $order->order_id)->where('active', 1)->where('is_paid', 1)->first();
                $order['payment'] = $payment;
            }
            $order['user'] = User::query()->where('id', $order->user_id)->first();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order' => $order]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }


}
