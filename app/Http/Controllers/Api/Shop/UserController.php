<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Shop;
use App\Models\ShopBankInfo;
use App\Models\ShopDocument;
use App\Models\ShopDocumentType;
use App\Models\ShopPayment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Faker\Provider\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class UserController extends Controller
{
    public function getShopProfile(){
        try {
            $shop = Auth::user();

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['shop' => $shop]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function checkRegisterDocuments(){
        try {
            $user = Auth::user();

            $document_count = ShopDocument::query()->where('active', 1)->where('shop_id', $user->id)->count();

            if ($document_count){
                $documents = ShopDocument::query()->where('active', 1)->where('shop_id', $user->id)->get();
                foreach ($documents as $document){
                    $document->file_type_name = ShopDocumentType::query()->where('id', $document->file_type)->first()->name;
                }
                return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['check_documents' => 1, 'documents' => $documents]]);

            }else{
                return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['check_documents' => 0]]);

            }

        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function getRegisterDocuments(){
        try {
            $user = Auth::user();

            $documents = ShopDocument::query()->where('active', 1)->where('shop_id', $user->id)->get();

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['documents' => $documents]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function deleteRegisterDocument($id){
        try {
            $user = Auth::user();
            $document = ShopDocument::query()->where('id', $id)->first();
            if ($user->id == $document->shop_id){
                ShopDocument::query()->where('id', $id)->update([
                    'active' => 0
                ]);
                return response(['message' => 'İşlem Başarılı.','status' => 'success']);
            }else{
                return response(['message' => 'Yetki yok.','status' => 'auth-006']);
            }


        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }


    public function addBankInfo(Request $request)
    {
        try {
            $request->validate([
                'account_bank_name' => 'required',
                'account_owner' => 'required',
                'account_number' => 'required',
                'account_currency' => 'required'
            ]);
            $shop = Auth::user();

            ShopBankInfo::query()->insertGetId([
                'shop_id' => $shop->id,
                'account_bank_name' => $request->account_bank_name,
                'account_owner' => $request->account_owner,
                'account_number' => $request->account_number,
                'account_currency' => $request->account_currency
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
    public function updateBankInfo(Request $request, $id)
    {
        try {
            $request->validate([
                'account_bank_name' => 'required',
                'account_owner' => 'required',
                'account_number' => 'required',
                'account_currency' => 'required'
            ]);

            ShopBankInfo::query()->where('id', $id)->update([
                'account_bank_name' => $request->account_bank_name,
                'account_owner' => $request->account_owner,
                'account_number' => $request->account_number,
                'account_currency' => $request->account_currency
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
    public function getBankInfos(){
        try {
            $user = Auth::user();

            $bank_infos = ShopBankInfo::query()->where('active', 1)->where('shop_id', $user->id)->get();

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['bank_infos' => $bank_infos]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function getBankInfoById($id){
        try {
            $user = Auth::user();

            $bank_info = ShopBankInfo::query()->where('active', 1)->where('id', $id)->first();

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['bank_info' => $bank_info]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        }
    }
    public function getPaymentDetails(){
        try {
            $shop = Auth::user();

            $details = OrderProduct::query()
                ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
                ->leftJoin('orders', 'orders.order_id', '=', 'order_products.order_id')
                ->where('products.owner_type', 1)
                ->where('products.owner_id', $shop->id)
                ->where('order_products.active', 1)
                ->where('orders.is_paid', 1)
                ->selectRaw('orders.currency as currency')
                ->distinct()
                ->get();

            foreach ($details as $currency){
                $sales = OrderProduct::query()
                    ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
                    ->leftJoin('orders', 'orders.order_id', '=', 'order_products.order_id')
                    ->where('products.owner_type', 1)
                    ->where('products.owner_id', $shop->id)
                    ->where('order_products.active', 1)
                    ->where('orders.is_paid', 1)
                    ->where('orders.currency', $currency->currency)
                    ->selectRaw('order_products.*')
                    ->get();

                $sale_total = 0;
                $commission_total = 0;
                foreach ($sales as $sale){
                    $sale_total += $sale->total;
                    $commission_total += $sale->commission_total;
                }

                $payed_total = 0;
                $pays = ShopPayment::query()->where('shop_id', $shop->id)->where('currency', $currency->currency)->where('active', 1)->get();
                foreach ($pays as $pay){
                    $payed_total += $pay->payed_price;
                }

                $shop_total = $sale_total - $commission_total;
                $remaining_total = $shop_total - $payed_total;

                $currency['sale_total'] = $sale_total;
                $currency['commission_total'] = $commission_total;
                $currency['pay_total'] = $sale_total-$commission_total;
                $currency['payed_total'] = $payed_total;
                $currency['remaining_total'] = $remaining_total;
            }

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['details' => $details]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001', 'e' => $queryException->getMessage()]);
        }
    }
    public function getPayments(){
        try {
            $shop = Auth::user();

            $payments = ShopPayment::query()->where('shop_id', $shop->id)->where('active', 1)->get();

            foreach ($payments as $payment){
                $order_product = OrderProduct::query()->where('id', $payment->order_product_id)->first();
                $order = Order::query()->where('order_id', $order_product->order_id)->first();
                $product = Product::query()->where('id', $order_product->product_id)->first();
                $payment['order_product'] = $order_product;
                $payment['order'] = $order;
                $payment['product'] = $product;
            }

            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' => ['payments' => $payments]]);
        } catch (QueryException $queryException){
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001', 'e' => $queryException->getMessage()]);
        }
    }





}
