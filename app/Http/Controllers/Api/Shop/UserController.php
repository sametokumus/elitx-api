<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopDocument;
use App\Models\ShopDocumentType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
