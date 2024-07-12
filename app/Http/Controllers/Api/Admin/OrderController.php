<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\CreditCard;
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
use App\Models\ProductRule;
use App\Models\ProductVariation;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserTypeDiscount;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class OrderController extends Controller
{
    public function updateOrder(Request $request, $id)
    {
        try {
            $order = Order::query()->where('order_id', $id)->first();
            Order::query()->where('order_id', $id)->update([
                'order_id' => $request->order_id,
                'user_id' => $request->user_id,
                'carrier_id' => $request->carrier_id,
                'cart_id' => $request->cart_id,
                'status_id' => $request->status_id,
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' => $request->billing_address_id,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
                'comment' => $request->comment,
                'shipping_number' => $request->shipping_number,
                'shipping_date' => $request->shipping_date,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'total_discount' => $request->total_discount,
                'total_discount_tax' => $request->total_discount_tax,
                'total_shipping' => $request->total_shipping,
                'total_shipping_tax' => $request->total_shipping_tax,
                'total' => $request->total,
                'total_tax' => $request->total_tax,
                'is_partial' => $request->is_partial,
                'is_paid' => $request->is_paid
            ]);
            if ($order->status_id != $request->status_id) {
                OrderStatusHistory::query()->insert([
                    'status_id' => $request->status_id,
                    'order_id' => $order->order_id
                ]);
            }
            return response(['message' => 'Sipariş güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getOnGoingOrders()
    {
        try {
            $orders = Order::query()
                ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status_id')
                ->where('order_statuses.run_on', 1)
                ->where('orders.active', 1)
                ->get(['orders.id', 'orders.order_id', 'orders.created_at as order_date', 'orders.updated_at as order_update_date', 'orders.total', 'orders.currency', 'orders.status_id',
                    'orders.user_id', 'orders.is_paid', 'orders.commission_total'
                ]);
            foreach ($orders as $order) {
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

            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['orders' => $orders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'â' => $queryException->getMessage()]);
        }
    }

    public function getCompletedOrders()
    {
        try {
            $orders = Order::query()
                ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status_id')
                ->where('order_statuses.run_on', 0)
                ->where('orders.active', 1)
                ->get(['orders.id', 'orders.order_id', 'orders.created_at as order_date', 'orders.updated_at as order_update_date', 'orders.total', 'orders.currency', 'orders.status_id',
                    'orders.user_id', 'orders.is_paid', 'orders.commission_total'
                ]);
            foreach ($orders as $order) {
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

            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['orders' => $orders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'â' => $queryException->getMessage()]);
        }
    }

    public function getOrderById($order_id){
        try {
            $order = Order::query()->where('id', $order_id)->first();
            if ($order) {
                $status_name = OrderStatus::query()->where('id', $order->status_id)->first()->name;
                $order['status_name'] = $status_name;
                $product_count = OrderProduct::query()->where('order_id', $order->order_id)->get()->count();
                $order['product_count'] = $product_count;
                $products = OrderProduct::query()->where('order_id', $order->order_id)->get();
                foreach ($products as $product){
                    $product['status_name'] = OrderStatus::query()->where('id', $product->status_id)->first()->name;
                    $detail = Product::query()->where('id', $product->product_id)->first();
                    if (!empty($product->variation_id)){
                        $variation = ProductVariation::query()->where('product_id', $product->id)->where('id', $product->variation_id)->first();
                        $detail['variation'] = $variation;
                    }
                    $detail['owner_name'] = Shop::query()->where('id', $product->owner_id)->first()->name;
                    $product['detail'] = $detail;
                }

                if ($order->is_paid == 1){
                    $payment = Payment::query()->where('order_id', $order->order_id)->where('active', 1)->where('is_paid', 1)->first();
                    $payment['type_name'] = PaymentType::query()->where('id', $payment->type)->first()->name;
                    $order['payment'] = $payment;
                }
                $order['products'] = $products;
                $order['user'] = User::query()->where('id', $order->user_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order' => $order]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getOrderStatusHistoriesById($id)
    {
        try {
            $order_guid = Order::query()->where('id', $id)->first()->order_id;
            $order_status_histories = OrderStatusHistory::query()
                ->leftJoin('order_statuses', 'order_statuses.id', '=', 'order_status_histories.status_id')
                ->selectRaw('order_status_histories.*, order_statuses.is_notified as notify, order_statuses.name as status_name')
                ->where('order_id', $order_guid)
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order_status_histories' => $order_status_histories]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function deleteOrder($order_id)
    {
        try {
            Order::query()->where('order_id', $order_id)->update([
                'active' => 0
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

    public function confirmOrder($order_id)
    {
        try {
            $payments = Payment::query()
                ->where('order_id', $order_id)
                ->where('type', 1)
                ->where('is_paid', 0)
                ->where('is_preauth', 1)
                ->get();

            $val = true;

            foreach ($payments as $payment){
                $return = PaymentHelper::confirmPayment($payment->payment_id);
                if (!$return){
                    $val = false;
                }
            }

            if ($val){
                Order::query()->where('order_id', $order_id)->update([
                    'status_id' => 3
                ]);
                OrderStatusHistory::query()->insert([
                    'order_id' => $order_id,
                    'status_id' => 3
                ]);
                return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
            }else{
                return response(['message' => 'İşlem başarısız.', 'status' => 'false']);
            }

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function cancelOrder($order_id)
    {
        try {
            $payments = Payment::query()
                ->where('order_id', $order_id)
                ->where('type', 1)
                ->where('is_paid', 1)
                ->where('is_preauth', 1)
                ->where('is_cancel_preauth', 0)
                ->where('is_refund', 0)
                ->get();

            $val = true;

            foreach ($payments as $payment){
                $return = PaymentHelper::cancelPayment($payment->payment_id);
                if (!$return){
                    $val = false;
                }else{
                    Payment::query()
                        ->where('payment_id', $payment->payment_id)
                        ->update([
                            'is_refund' => 1
                        ]);
                }
            }

            if ($val){
                Order::query()->where('order_id', $order_id)->update([
                    'status_id' => 12
                ]);
                OrderStatusHistory::query()->insert([
                    'order_id' => $order_id,
                    'status_id' => 12
                ]);
                return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
            }else{
                return response(['message' => 'İşlem başarısız.', 'status' => 'false']);
            }

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function cancelProvision($order_id)
    {
        try {
            $payments = Payment::query()
                ->where('order_id', $order_id)
                ->where('type', 1)
                ->where('is_paid', 0)
                ->where('is_preauth', 1)
                ->where('is_cancel_preauth', 0)
                ->where('is_refund', 0)
                ->get();

            $val = true;

            foreach ($payments as $payment){
                $return = PaymentHelper::cancelPreauth($payment->payment_id);
                if (!$return){
                    $val = false;
                }else{
                    Payment::query()
                        ->where('payment_id', $payment->payment_id)
                        ->update([
                            'is_cancel_preauth' => 1
                        ]);
                }
            }

            if ($val){
                Order::query()->where('order_id', $order_id)->update([
                    'status_id' => 10
                ]);
                OrderStatusHistory::query()->insert([
                    'order_id' => $order_id,
                    'status_id' => 10
                ]);
                return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
            }else{
                return response(['message' => 'İşlem başarısız.', 'status' => 'false']);
            }


        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateOrderStatus(Request $request)
    {
        try {
            OrderStatusHistory::query()->insert([
                'order_id' => $request->order_id,
                'status_id' => $request->status_id
            ]);
            Order::query()->where('order_id', $request->order_id)->update([
                'status_id' => $request->status_id
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

    public function updateOrderInfo(Request $request, $order_id)
    {
        /**sipariş durumu teslimat türü update olacak**/

        try {
            Order::query()->where('order_id', $order_id)->update([
                'status_id' => $request->status_id,
                'shipping_type' => $request->shipping_type
            ]);
            return response(['message' => 'Sipariş durumu güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateOrderShipment(Request $request, $order_id)
    {
        /**firma gönderi takip kodu update olacak**/

        try {
            $carrier_id = $request->carrier_id;
            if ($carrier_id == ""){
                $carrier_id = 0;
            }
            Order::query()->where('order_id', $order_id)->update([
                'shipping_number' => $request->shipping_number,
                'carrier_id' => $carrier_id
            ]);
            return response(['message' => 'Sipariş bilgileri güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateOrderBilling(Request $request, $order_id)
    {
        /**ad soyad telefon adees posta kodu ilçe il ülke corporate adrestekiler güncellenecek**/
        try {
            $billing_address = $request->name . " - " . $request->address . " - " . $request->postal_code . " - " . $request->phone . " - " . $request->district . " / " . $request->city . " / " . $request->country;
            if ($request->company_name != ''){
                $billing_address = $billing_address." - ".$request->tax_number." - ".$request->tax_office." - ".$request->company_name;
            }
            Order::query()->where('order_id', $order_id)->update([
                'billing_address' => $billing_address
            ]);
            return response(['message' => 'Sipariş adresi güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateOrderShipping(Request $request, $order_id)
    {
        try {
            $shipping_address = $request->name . " - " . $request->address . " - " . $request->postal_code . " - " . $request->phone . " - " . $request->district . " / " . $request->city . " / " . $request->country;
            if ($request->company_name != ''){
                $shipping_address = $shipping_address." - ".$request->tax_number." - ".$request->tax_office." - ".$request->company_name;
            }
            Order::query()->where('order_id', $order_id)->update([
                'shipping_address' => $shipping_address
            ]);
            return response(['message' => 'Sipariş adresi güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getRefundOrders(){
        try {
            $order_refunds = OrderRefund::query()
                ->leftJoin('order_refund_statuses','order_refund_statuses.id','=','order_refunds.status')
                ->leftJoin('user_profiles','user_profiles.user_id','=','order_refunds.user_id')
                ->where('order_refunds.active',1)
                ->selectRaw('order_refunds.*, user_profiles.name, user_profiles.surname,order_refund_statuses.name as status_name')
                ->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order_refunds' => $order_refunds]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }

    public function getOrderRefundStatuses(){
        try {
            $order_refund_statuses = OrderRefundStatus::query()
                ->where('active',1)
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order_refund_statuses' => $order_refund_statuses]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','err' => $queryException->getMessage()]);
        }
    }

    public function updateRefundStatus(Request $request, $order_id){
        try {
            OrderRefund::query()->where('order_id',$order_id)->update([
                'status' => $request->status,
            ]);
            return response(['message' => 'İade durumu güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

    public function getOrderPaymentInfoById($order_id){
        try {
            $payment_info = array();
            $order = Order::query()->where('order_id',$order_id)->first();
            $payments = Payment::query()->where('order_id',$order_id)->where('active', 1)->get();

            $payment_info['payment_method'] = $order->payment_method;
            $payment_info['payment_method_name'] = PaymentMethod::query()->where('id', $order->payment_method)->first()->name;

            foreach ($payments as $payment){
                $payment['type_name'] = PaymentType::query()->where('id', $payment->type)->first()->name;
                $payment['is_preauth_message'] = "Provizyon onaylanmadı";
                $payment['is_paid_message'] = "Ödeme onaylanmadı";
                $payment['bank'] = "";
                if ($payment->is_preauth == 1){
                    $payment['is_preauth_message'] = "Provizyon onaylandı";
                }
                if ($payment->is_paid == 1){
                    $payment['is_paid_message'] = "Ödeme onaylandı";
                }
                if ($payment->bank_id != null){
                    $payment['bank'] = CreditCard::query()->where('member_no', $payment->bank_id)->first();
                }
            }

            $payment_info['payments'] = $payments;



            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['payment_info' => $payment_info]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getOrderPaymentProvizyonById($payment_id){
        try {
            $payment = Payment::query()->where('payment_id',$payment_id)->where('active', 1)->first();

            $payment['type_name'] = PaymentType::query()->where('id', $payment->type)->first()->name;
            $payment['is_preauth_message'] = "Provizyon onaylanmadı";
            $payment['is_paid_message'] = "Ödeme onaylanmadı";
            $payment['bank'] = "";
            if ($payment->is_preauth == 1){
                $payment['is_preauth_message'] = "Provizyon onaylandı";
            }
            if ($payment->is_paid == 1){
                $payment['is_paid_message'] = "Ödeme onaylandı";
            }
            if ($payment->bank_id != null){
                $payment['bank'] = CreditCard::query()->where('member_no', $payment->bank_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['payment' => $payment]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getOrderBillingInfoById($order_id){
        try {
            $billing_info = Order::query()->where('order_id',$order_id)->first(['id', 'order_id', 'billing_address_id', 'billing_address']);

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['billing_info' => $billing_info]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function updateOrderBillingInfoById(Request $request, $id)
    {
        try {

            $billing_address = $request->name . " - " . $request->address . " - " . $request->postal_code . " - " . $request->phone . " - " . $request->district . " / " . $request->city . " / " . $request->country;

            if ($request->tax_number != '' && $request->tax_office != '' && $request->company_name != ''){
                $billing_address = $billing_address." - ".$request->tax_number." - ".$request->tax_office." - ".$request->company_name;
            }

            Order::query()->where('order_id', $id)->update([
                'billing_address' => $billing_address
            ]);

            return response(['message' => 'Fatura adresi güncellendi.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getOrderShippingInfoById($order_id){
        try {
            $shipping_info = Order::query()->where('order_id',$order_id)->first(['id', 'order_id', 'shipping_address_id', 'shipping_address']);

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shipping_info' => $shipping_info]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function updateOrderShippingInfoById(Request $request, $id)
    {
        try {
            $shipping_address = $request->name . " - " . $request->address . " - " . $request->postal_code . " - " . $request->phone . " - " . $request->district . " / " . $request->city . " / " . $request->country;

            if ($request->tax_number != '' && $request->tax_office != '' && $request->company_name != ''){
                $shipping_address = $shipping_address." - ".$request->tax_number." - ".$request->tax_office." - ".$request->company_name;
            }

            Order::query()->where('order_id', $id)->update([
                'shipping_address' => $shipping_address
            ]);

            return response(['message' => 'Teslimat adresi güncellendi.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getOrderShipmentInfoById($order_id){
        try {
            $shipment_info = Order::query()->where('order_id',$order_id)->first(['id', 'order_id', 'carrier_id', 'shipping_number']);
            if ($shipment_info->carrier_id == 0){
                $shipment_info['carrier_name'] = "";
            }else{
                $carrier = Carrier::query()->where('id', $shipment_info->carrier_id)->where('active', 1)->first();
                $shipment_info['carrier_name'] = $carrier->name;
            }
            if ($shipment_info->shipping_number == null){
                $shipment_info['shipping_number'] = "";
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shipment_info' => $shipment_info]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

}
