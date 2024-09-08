<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EstateAdvertType;
use App\Models\EstateCondition;
use App\Models\EstateFloor;
use App\Models\EstateRoom;
use App\Models\EstateType;
use App\Models\EstateWarming;
use App\Models\Message;
use App\Models\NotifyOption;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\UserNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getCreateOldNotifies(){
        try {
            $messages = Message::query()->where('active', 1)->get();
            foreach ($messages as $message){
                UserNotification::query()->insert([
                    'user_id' => $message->receiver_id,
                    'option_id' => 1,
                    'type_id' => 1
                ]);
            }

            $histories = OrderStatusHistory::query()->where('active', 1)->get();
            foreach ($histories as $history){
                $order = Order::query()->where('order_id', $history->order_id)->first();
                if ($history->status_id == 5) {
                    UserNotification::query()->insert([
                        'user_id' => $order->user_id,
                        'option_id' => 2,
                        'type_id' => 1
                    ]);
                }
                if ($history->status_id == 6) {
                    UserNotification::query()->insert([
                        'user_id' => $order->user_id,
                        'option_id' => 3,
                        'type_id' => 1
                    ]);
                }
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getNotifies(){
        try {
            $user = Auth::user();

            $notifies = UserNotification::query()->where('active', 1)->where('type_id', 1)->where('user_id', $user->id)->get();
            foreach ($notifies as $notify){
                $option = NotifyOption::query()->where('id', $notify->option_id)->first();
                $notify['message'] = $option->message_tr;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'notifies' => $notifies
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
