<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\CorporateAddresses;
use App\Models\Country;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class MessageController extends Controller
{
    public function getMessageListByUser()
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;

            $messages = Message::query()
                ->select(DB::raw('MAX(id) as id'), 'product_id')
                ->selectRaw('CASE WHEN sender_id <> '.$user_id.' THEN sender_id ELSE receiver_id END AS conversation_partner_id')
                ->where('sender_id', $user_id)
                ->orWhere('receiver_id', $user_id)
                ->groupBy('conversation_partner_id', 'product_id')
                ->get();
            foreach ($messages as $message){
                $message['last_message'] = Message::query()->where('id', $message->id)->first();
                $message['conversation_partner'] = User::query()->where('id', $message->conversation_partner_id)->first();
                $message['product'] = Product::query()->where('id', $message->product_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['messages' => $messages]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getMessagesByUserProductIdAndUserId($product_id, $partner_id)
    {
        try {
            $auth_user = Auth::user();
            $user_id = $auth_user->id;

            $messages = Message::query()
                ->select('id', 'product_id', 'sender_id', 'receiver_id', 'text')
                ->where(function($query) use ($user_id, $partner_id) {
                    $query->where(function($query) use ($user_id, $partner_id) {
                        $query->where('sender_id', $user_id)
                            ->where('receiver_id', $partner_id);
                    })
                        ->orWhere(function($query) use ($user_id, $partner_id) {
                            $query->where('sender_id', $partner_id)
                                ->where('receiver_id', $user_id);
                        });
                })
                ->where('product_id', $product_id)
                ->get();
            foreach ($messages as $message){
                Message::query()->where('id', $message->id)->update([
                    'is_read' => 1
                ]);
                $message['is_read'] = 1;
                $message['sender'] = User::query()->where('id', $message->sender_id)->first();
                $message['receiver'] = User::query()->where('id', $message->receiver_id)->first();
                $message['product'] = Product::query()->where('id', $message->product_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['messages' => $messages]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
//                'product_id' => 'required|exists:products,id',
                'receiver_id' => 'required|exists:users,id',
                'text' => 'required',
            ]);

            $sender = Auth::user();
            $sender_id = $sender->id;

            Message::query()->insert([
                'product_id' => $request->product_id,
                'sender_id' => $sender_id,
                'receiver_id' => $request->receiver_id,
                'text' => $request->text
            ]);

            return response(['message' => 'Mesaj gönderme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','a' => $throwable->getMessage()]);
        }
    }
}
