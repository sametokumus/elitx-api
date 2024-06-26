<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private function buildTree($categories, $parentId = null)
    {
        $branch = [];
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        return $branch;
    }
    public function getCategories()
    {
        try {
            $categories = Category::query()->where('active', 1)->get();

            $categoryTree = $this->buildTree($categories);

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categoryTree]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'error' => $queryException->getMessage()]);
        }
    }
    public function getCategoriesByParentId($id)
    {
        try {
            $categories = Category::query()->where('active', 1)->where('parent_id', $id)->get();

            $categoryTree = $this->buildTree($categories, $id);

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'error' => $queryException->getMessage()]);
        }
    }
}
