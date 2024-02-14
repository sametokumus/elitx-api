<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
