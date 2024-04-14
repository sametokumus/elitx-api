<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Database\QueryException;

class TagController extends Controller
{
    public function getTags()
    {
        try {
            $tags = Tag::query()->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['tags' => $tags]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getTagById($id)
    {
        try {
            $tags = Tag::query()->where('active',1)->where('id',$id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['tags' => $tags]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
