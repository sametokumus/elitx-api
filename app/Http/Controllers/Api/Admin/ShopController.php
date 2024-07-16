<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopBankInfo;
use App\Models\ShopDocument;
use App\Models\ShopPayment;
use Carbon\Carbon;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class ShopController extends Controller
{
    public function getShops(){
        try {
            $shops = Shop::query()
                ->where('shops.register_completed', 1)
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shops' => $shops]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getShopById($id){
        try {
            $shop = Shop::query()->where('id',$id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shop' => $shop]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getShopConfirmed($id){
        try {
            Shop::query()->where('id',$id)->update([
                'confirmed' => 1,
                'account_confirmed_at' => Carbon::now()
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getShopRejected($id){
        try {
            Shop::query()->where('id',$id)->update([
                'register_completed' => 2
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getShopRegisterDocuments($id){
        try {
            $shop = Shop::query()->where('id', $id)->first();
            $documents = ShopDocument::query()
                ->leftJoin('shop_document_types', 'shop_document_types.id', '=', 'shop_documents.file_type')
                ->selectRaw('shop_documents.*, shop_document_types.name as file_type_name')
                ->where('shop_documents.active', 1)
                ->where('shop_documents.shop_id', $id)
                ->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['shop' => $shop, 'documents' => $documents]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getOpenShop($id){
        try {
            Shop::query()->where('id',$id)->update([
                'active' => 1
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getCloseShop($id){
        try {
            Shop::query()->where('id',$id)->update([
                'active' => 0
            ]);
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addShopPayment(Request $request)
    {
        try {
            $request->validate([
                'shop_id' => 'required',
                'order_id' => 'required',
                'shop_bank_info_id' => 'required'
            ]);
            $pay_guid = Uuid::uuid();
            $order = Order::query()->where('order_id', $request->order_id)->first();

            ShopPayment::query()->insertGetId([
                'shop_id' => $request->shop_id,
                'order_id' => $request->order_id,
                'payment_guid' => $pay_guid,
                'shop_bank_info_id' => $request->shop_bank_info_id,
                'payed_price' => $order->total,
                'currency' => $order->currency
            ]);

            return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
}
