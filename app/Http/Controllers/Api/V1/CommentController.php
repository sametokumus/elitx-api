<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductComment;
use App\Models\ProductCommentAnswer;
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
                $answers = ProductCommentAnswer::query()
                    ->where('comment_id', $comment->id)
                    ->where('confirmed', 1)
                    ->where('active', 1)
                    ->get();

                $comment['answers'] = $answers;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['comments' => $comments]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
}
