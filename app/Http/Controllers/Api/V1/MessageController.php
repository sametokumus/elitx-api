<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\CorporateAddresses;
use App\Models\Country;
use App\Models\Message;
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
                ->select(DB::raw('MAX(id) as id'), 'user_product_id')
                ->selectRaw('CASE WHEN sender_id <> '.$user_id.' THEN sender_id ELSE receiver_id END AS conversation_partner_id')
                ->where('sender_id', $user_id)
                ->orWhere('receiver_id', $user_id)
                ->groupBy('conversation_partner_id', 'user_product_id')
                ->get();
            foreach ($messages as $message){
                $message['last_message'] = Message::query()->where('id', $message->id)->first();
                $message['conversation_partner'] = User::query()->where('id', $message->conversation_partner_id)->first();
//                $message['user_product'] = UserProduct::query()->where('id', $message->user_product_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['messages' => $messages]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getMessagesByUserProductIdAndUserId($user_product_id, $user_id)
    {
        try {
            $auth_user = Auth::user();
            $auth_user_id = $auth_user->id;

            $messages = Message::query()->where('sender_id', $user_id)->where('receiver_id', $user_id)->where('sender_id', $user_id)->where('active',1)->get();
            foreach ($addresses as $address){
                $address['country'] = Country::query()->where('id', $address->country_id)->first();
                $address['city'] = City::query()->where('id', $address->city_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['addresses' => $addresses]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getUserAddress($address_id)
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;

            $address = Address::query()->where('user_id', $user_id)->where('id', $address_id)->where('active',1)->first();

            if($address) {
                if ($address->type == 2) {
                    $corporate_address = CorporateAddresses::query()->where('address_id', $address_id)->first();
                    $address['company_name'] = $corporate_address->company_name;
                    $address['tax_number'] = $corporate_address->tax_number;
                    $address['tax_office'] = $corporate_address->tax_office;
                }

                $address['country'] = Country::query()->where('id', $address->country_id)->first();
                $address['city'] = City::query()->where('id', $address->city_id)->first();
            }else{
                return response(['message' => 'Adres bulunamadı.', 'status' => 'address-001']);
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['address' => $address]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
//                'user_product_id' => 'required|exists:user_products,id',
                'receiver_id' => 'required|exists:users,id',
                'text' => 'required',
            ]);

            $sender = Auth::user();
            $sender_id = $sender->id;

            Message::query()->insert([
                'user_product_id' => $request->user_product_id,
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
