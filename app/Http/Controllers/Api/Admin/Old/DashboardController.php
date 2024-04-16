<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        try {

            $total_user = User::query()->where('active', 1)->count();
            $total_order = Order::query()->where('is_paid', 1)->count();
            $total_cost = Order::query()
                ->leftJoin('payments', 'payments.order_id', '=', 'orders.order_id')
                ->where('orders.is_paid', 1)
                ->sum('payments.paid_price');
            $total_product = Product::query()->where('active', 1)->count();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['total_user' => $total_user, 'total_order' => $total_order, 'total_cost' => $total_cost, 'total_product' => $total_product]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }

    public function getLastOrders()
    {
        try {
            $orders = Order::query()
                ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status_id')
                ->where('order_statuses.run_on', 1)
                ->where('orders.active', 1)
                ->orderByDesc('orders.id')
                ->limit(10)
                ->get(['orders.id', 'orders.order_id', 'orders.created_at as order_date', 'orders.total', 'orders.status_id',
                    'orders.shipping_type', 'orders.user_id', 'orders.payment_method'
                ]);
            foreach ($orders as $order) {
                $product_count = OrderProduct::query()->where('order_id', $order->order_id)->get()->count();
                $product = OrderProduct::query()->where('order_id', $order->order_id)->first();
                $product_image_row = ProductImage::query()->where('variation_id', $product->variation_id)->first();
                if ($product_image_row) {
                    $product_image = $product_image_row->image;
                }
                $status_name = OrderStatus::query()->where('id', $order->status_id)->first()->name;
//                $shipping_type = ShippingType::query()->where('id', $order->shipping_type)->first()->name;
                if ($order->shipping_type == 0){
                    $shipping_type = "Mağazadan Teslimat";
                }else {
                    $shipping_type = Carrier::query()->where('id', $order->shipping_type)->first()->name;
                }
                $user_profile = UserProfile::query()->where('user_id', $order->user_id)->first(['name', 'surname']);
                $payment_method = PaymentMethod::query()->where('id', $order->payment_method)->first()->name;

                $order['product_count'] = $product_count;
                $order['product_image'] = $product_image;
                $order['payment_method'] = $order->payment_method;
                $order['status_name'] = $status_name;
                $order['shipping_number'] = $order->shipping_number;
                $order['shipping_type_name'] = $shipping_type;
                $order['user_profile'] = $user_profile;
                $order['payment_method_name'] = $payment_method;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['orders' => $orders]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'â' => $queryException->getMessage()]);
        }
    }
}
