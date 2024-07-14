<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductComment;
use App\Models\ProductCommentAnswer;
use App\Models\Shop;
use App\Models\ShopDocument;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function getCommentsByProductId($product_id)
    {
        try {
            $comments = ProductComment::query()
                ->where('product_id', $product_id)
                ->where('confirmed', 1)
                ->where('active', 1)
                ->get();

            foreach ($comments as $comment){
                $user = User::query()->where('id', $comment->user_id)->first();
                $comment['user'] = $user;

                $answers = ProductCommentAnswer::query()
                    ->where('comment_id', $comment->id)
                    ->where('confirmed', 1)
                    ->where('active', 1)
                    ->get();

                foreach ($answers as $answer){
                    $shop = Shop::query()->where('id', $answer->shop_id)->first();
                    $shop_doc = ShopDocument::query()
                        ->where('shop_id', $answer->shop_id)
                        ->where('file_type', 1)
                        ->first();
                    $shop['logo'] = null;
                    if ($shop_doc){
                        $shop['logo'] = $shop_doc->file_url;
                    }
                    $answer['shop'] = $shop;
                }

                $comment['answers'] = $answers;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['comments' => $comments]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
