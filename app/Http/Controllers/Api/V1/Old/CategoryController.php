<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    public function getCategory()
    {
        try {
            $categories = Category::query()->where('parent_id',0)->where('active',1)->get();
            foreach ($categories as $category){
                $sub_categories = Category::query()->where('parent_id',$category->id)->where('active',1)->get();
                $category['sub_categories'] = $sub_categories;
            }
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getParentCategory()
    {
        try {
            $categories = Category::query()->where('parent_id',0)->where('active',1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getCategoryById($category_id)
    {
        try {
            $category = Category::query()->where('id',$category_id)->where('active',1)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['category' => $category]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
