<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopDocument;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
}
