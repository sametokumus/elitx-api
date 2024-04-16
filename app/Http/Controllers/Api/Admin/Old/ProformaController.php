<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\City;
use App\Models\CorporateAddresses;
use App\Models\Country;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductRule;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use App\Models\User;
use App\Models\UserTypeDiscount;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class ProformaController extends Controller
{
    public function getDuplicateProformaOrder($order_id){

        try {
            //getOrder
            $order = Order::query()->where('order_id', $order_id)->first();
            $order_products = OrderProduct::query()->where('order_id', $order_id)->where('active', 1)->get();
            $cart = Cart::query()->where('cart_id', $order->cart_id)->first();
            $cart_details = CartDetail::query()->where('cart_id', $order->cart_id)->where('active', 1)->get();
            $new_order_id = Uuid::uuid();
            $new_cart_id = Uuid::uuid();

            //createCart
            Cart::query()->insert([
                'user_id' => $cart->user_id,
                'cart_id' => $new_cart_id,
                'is_order' => 0,
                'active' => 1
            ]);

            //createCartDetails
            foreach ($cart_details as $cart_detail){
                CartDetail::query()->insert([
                    'cart_id' => $new_cart_id,
                    'product_id' => $cart_detail->product_id,
                    'variation_id' => $cart_detail->variation_id,
                    'price' => $cart_detail->price,
                    'quantity' => $cart_detail->quantity,
                    'active' => 1
                ]);
            }

            //createOrder
            Order::query()->insert([
                'order_id' => $new_order_id,
                'cart_id' => $new_cart_id,
                'user_id' => $order->user_id,
                'carrier_id' => $order->carrier_id,
                'shipping_address_id' => $order->shipping_address_id,
                'billing_address_id' => $order->billing_address_id,
                'status_id' => 1,
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
                'comment' => $order->comment,
                'shipping_number' => $order->shipping_number,
                'invoice_number' => $order->invoice_number,
                'invoice_date' => $order->invoice_date,
                'shipping_date' => $order->shipping_date,
                'shipping_type' => $order->shipping_type,
                'payment_method' => 3,
                'shipping_price' => $order->shipping_price,
                'subtotal' => $order->subtotal,
                'total' => $order->total,
                'coupon_code' => $order->coupon_code,
                'is_partial' => 0,
                'is_preauth' => 0,
                'is_paid' => 0,
                'active' => 1
            ]);

            //createOrderProducts
            foreach ($order_products as $order_product){
                OrderProduct::query()->insert([
                    'order_id' => $new_order_id,
                    'product_id' => $order_product->product_id,
                    'variation_id' => $order_product->variation_id,
                    'name' => $order_product->name,
                    'sku' => $order_product->sku,
                    'regular_price' => $order_product->regular_price,
                    'regular_tax' => $order_product->regular_tax,
                    'discounted_price' => $order_product->discounted_price,
                    'discounted_tax' => $order_product->discounted_tax,
                    'discount_rate' => $order_product->discount_rate,
                    'tax_rate' => $order_product->tax_rate,
                    'user_discount' => $order_product->user_discount,
                    'quantity' => $order_product->quantity,
                    'total' => $order_product->total,
                    'active' => 1
                ]);
            }

            return response(['message' => 'Proforma sipariş kopyalama işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function addProformaOrder(Request $request){

        try {
            //createCart
            $user_id = $request->user_id;
            $cart_id = Uuid::uuid();
            $added_cart_id = Cart::query()->insertGetId([
                'cart_id' => $cart_id
            ]);

            foreach ($request->products as $product){
                $this->addCart($user_id, $cart_id, $product['product_id'], $product['variation_id'], $product['quantity']);
            }


            //createOrder
            $order_quid = Uuid::uuid();
            $this->addOrder($user_id, $cart_id, $order_quid, $request->shipping_address_id, $request->billing_address_id, $request->shipping_type);

            return response(['message' => 'Proforma sipariş ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['order_id' => $order_quid, 'cart_id' => $cart_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    private function addCart($user_id, $cart_id, $product_id, $variation_id, $quantity){

        $rule = ProductRule::query()->where('variation_id',$variation_id)->first();
        if (!empty($user_id)) {
            $product = Product::query()->where('id', $product_id)->first();

            $user = User::query()->where('id', $user_id)->where('active', 1)->first();
            $total_user_discount = $user->user_discount;

            $type_discount = UserTypeDiscount::query()->where('user_type_id', $user->user_type)->where('brand_id', $product->brand_id)->where('type_id', $product->type_id)->where('active', 1)->first();
            if (!empty($type_discount)) {
                $total_user_discount = $total_user_discount + $type_discount->discount;
            }

            if ($total_user_discount > 0) {
                if ($rule->discount_rate > 0) {
                    $price = $rule->regular_price - ($rule->regular_price / 100 * ($total_user_discount + $rule->discount_rate));
                } else {
                    $price = $rule->regular_price - ($rule->regular_price / 100 * $total_user_discount);
                }
            } else {
                if ($rule->discount_rate > 0) {
                    $price = $rule->discounted_price;
                } else {
                    $price = $rule->regular_price;
                }
            }
        }else{
            if ($rule->discount_rate > 0) {
                $price = $rule->discounted_price;
            } else {
                $price = $rule->regular_price;
            }
        }
        $cart_detail = CartDetail::query()->where('variation_id',$variation_id)
            ->where('cart_id',$cart_id)
            ->where('product_id',$product_id)
            ->where('active',1)
            ->first();
        if (isset($cart_detail)){
            $quantity = $cart_detail->quantity+$quantity;
            CartDetail::query()->where('cart_id',$cart_id)
                ->where('variation_id',$variation_id)
                ->where('product_id',$product_id)
                ->update([
                    'quantity' => $quantity
                ]);
        }else{
            CartDetail::query()->insert([
                'cart_id' => $cart_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'price' => $price,
            ]);
        }

        if (!empty($user_id)){
            Cart::query()->where('cart_id',$cart_id)->update([
                'user_id' => $user_id
            ]);
        }
    }

    private function addOrder($user_id, $cart_id, $order_quid, $shipping_address_id, $billing_address_id, $shipping_type){

        $cart = Cart::query()->where('cart_id', $cart_id)->where('active', 1)->first();


            $order_status = OrderStatus::query()->where('is_default', 1)->first();

            $shipping_id = $shipping_address_id;
            $billing_id = $billing_address_id;
            $shipping = Address::query()->where('id', $shipping_id)->first();
            $country = Country::query()->where('id', $shipping->country_id)->first();
            $city = City::query()->where('id', $shipping->city_id)->first();
            $district = District::query()->where('id', $shipping->district_id)->first();

            $shipping_address = $shipping->name . " " . $shipping->surname . " - " . $shipping->address_1 . " " . $shipping->address_2 . " - " . $shipping->postal_code . " - " . $shipping->phone . " - " . $district->name . " / " . $city->name . " / " . $country->name;
            if ($shipping->type == 2){
                $shipping_corporate_address = CorporateAddresses::query()->where('address_id',$shipping_id)->first();
                $shipping_address = $shipping_address." - ".$shipping_corporate_address->tax_number." - ".$shipping_corporate_address->tax_office." - ".$shipping_corporate_address->company_name;
            }


            $billing = Address::query()->where('id', $billing_id)->first();
            $billing_country = Country::query()->where('id', $billing->country_id)->first();
            $billing_city = City::query()->where('id', $billing->city_id)->first();
            $billing_district = District::query()->where('id', $billing->district_id)->first();
            $billing_address = $billing->name . " " . $billing->surname . " - " . $billing->address_1 . " " . $billing->address_2 . " - " . $billing->postal_code . " - " . $billing->phone . " - " . $billing_district->name . " / " . $billing_city->name . " / " . $billing_country->name;

            if ($shipping->type == 2){
                $billing_corporate_address = CorporateAddresses::query()->where('address_id',$billing_id)->first();
                $billing_address = $billing_address." - ".$billing_corporate_address->tax_number." - ".$billing_corporate_address->tax_office." - ".$billing_corporate_address->company_name;
            }

            $order_id = Order::query()->insertGetId([
                'order_id' => $order_quid,
                'user_id' => $user_id,
                'carrier_id' => 0,
                'cart_id' => $cart_id,
                'status_id' => $order_status->id,
                'shipping_address_id' => $shipping_address_id,
                'billing_address_id' => $billing_address_id,
                'shipping_address' => $shipping_address,
                'billing_address' => $billing_address,
                'comment' => "",
                'shipping_type' => $shipping_type,
                'payment_method' => 3,
                'shipping_price' => 0,
                'subtotal' => 0,
                'total' => 0,
                'is_partial' => 0,
                'is_paid' => 0,
                'coupon_code' => "null"
            ]);

            Cart::query()->where('cart_id', $cart_id)->update([
                'user_id' => $user_id,
                'is_order' => 1,
                'active' => 0
            ]);
            $user_discount = User::query()->where('id', $user_id)->first()->user_discount;
            $carts = CartDetail::query()->where('cart_id', $cart_id)->get();
            foreach ($carts as $cart) {
                $product = Product::query()->where('id', $cart->product_id)->first();
                $variation = ProductVariation::query()->where('id', $cart->variation_id)->first();
                $rule = ProductRule::query()->where('variation_id', $variation->id)->first();
                if ($rule->discounted_price == null || $rule->discount_rate == 0){
                    $price = $rule->regular_price - ($rule->regular_price / 100 * $user_discount);
                    $tax = $price / 100 * $rule->tax_rate;
                    $total = ($price + $tax) * $cart->quantity;
                }else{
                    $price = $rule->regular_price - ($rule->regular_price / 100 * ($user_discount + $rule->discount_rate));
                    $tax = $price / 100 * $rule->tax_rate;
                    $total = ($price + $tax) * $cart->quantity;
                }
                OrderProduct::query()->insert([
                    'order_id' => $order_quid,
                    'product_id' => $product->id,
                    'variation_id' => $variation->id,
                    'name' => $product->name,
                    'sku' => $variation->sku,
                    'regular_price' => $rule->regular_price,
                    'regular_tax' => $rule->regular_tax,
                    'discounted_price' => $rule->discounted_price,
                    'discounted_tax' => $rule->discounted_tax,
                    'discount_rate' => $rule->discount_rate,
                    'tax_rate' => $rule->tax_rate,
                    'user_discount' => $user_discount,
                    'quantity' => $cart->quantity,
                    'total' => $total
                ]);
            }

            OrderStatusHistory::query()->insert([
                'order_id' => $order_quid,
                'status_id' => $order_status->id
            ]);

    }

    public function getProformaProducts()
    {
        try {
            $products = Product::query()->where('active', 1)->get();
            foreach ($products as $product) {
                $product_variation_groups = ProductVariationGroup::query()->where('product_id', $product->id)->get();
                foreach ($product_variation_groups as $product_variation_group) {
                    $product_variation_group['variations'] = ProductVariation::query()->where('variation_group_id', $product_variation_group->id)->get();
                }
                $product['variations'] = $product_variation_group['variations'];
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function getProformaProductsByFilter(Request $request)
    {
        try {
            $products = Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_types', 'product_types.id', '=', 'products.type_id')
                ->selectRaw('products.*')
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

            foreach ($products as $product) {
                $product_variation_groups = ProductVariationGroup::query()->where('product_id', $product->id)->get();
                foreach ($product_variation_groups as $product_variation_group) {
                    $product_variation_group['variations'] = ProductVariation::query()->where('variation_group_id', $product_variation_group->id)->get();
                }
                $product['variations'] = $product_variation_group['variations'];
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
