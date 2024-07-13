<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class OrderController extends Controller
{

    public function getOnGoingOrders()
    {
        try {
            $shop = Auth::user();
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
                    $detail['owner_name'] = Shop::query()->where('id', $detail->owner_id)->first()->name;
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
}
