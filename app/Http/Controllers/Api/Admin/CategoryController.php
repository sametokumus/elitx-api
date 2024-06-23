<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CategoryController extends Controller
{
    public function getCategories()
    {
        try {
            $categories = Category::query()->where('active', 1)->get();
            foreach ($categories as $category){
                if ($category->parent_id != null && $category->parent_id != 0){
                    $category['parent'] = Category::query()->where('id', $category->parent_id)->first();
                }
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['categories' => $categories]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function getCategoryById($category_id)
    {
        try {
            $category = Category::query()->where('id', $category_id)->first();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['category' => $category]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        }
    }
    public function addCategory(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'slug' => 'required'
            ]);
            $category_id = Category::query()->insertGetId([
                'name' => $request->name,
                'slug' => $request->slug
            ]);

            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Category/'), $image_name);
                $image_path = "/images/Category/" . $image_name;
                Category::query()->where('id', $category_id)->update([
                    'image_url' => $image_path
                ]);
            }
            return response(['message' => 'Kategori ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateCategory(Request $request, $category_id){
        try {
            $request->validate([
                'name' => 'required',
                'slug' => 'required',
            ]);

            $category = Category::query()->where('id', $category_id)->update([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            if ($request->hasFile('image_url')) {
                $rand = uniqid();
                $image = $request->file('image_url');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Category/'), $image_name);
                $image_path = "/images/Category/" . $image_name;
                Category::query()->where('id', $category_id)->update([
                    'image_url' => $image_path
                ]);
            }

            return response(['message' => 'Kategori güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteCategory($id){
        try {

            Category::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Kategori silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
