<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComment;
use App\Models\ProductCommentAnswer;
use App\Models\ProductConfirm;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
use App\Models\Shop;
use App\Models\ShopDocument;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class CommentController extends Controller
{
    public function addProductComment(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required',
                'product_id' => 'required'
            ]);
            $user = Auth::user();

            ProductComment::query()->insertGetId([
                'message' => $request->message,
                'product_id' => $request->product_id,
                'user_id' => $user->id,
            ]);

            return response(['message' => 'Yorum ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
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
                    $shop['logo'] = ShopDocument::query()
                        ->where('shop_id', $answer->shop_id)
                        ->where('file_type', 1)
                        ->first();
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
