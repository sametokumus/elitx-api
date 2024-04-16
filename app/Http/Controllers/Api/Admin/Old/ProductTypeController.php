<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class ProductTypeController extends Controller
{
    public function addProductType(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required'
            ]);
            $slug = preg_replace("/[^a-zA-Z0-9]+/", "-", $request->name);
            $slug = strtolower($slug);

            $order = ProductType::query()->where('active', 1)->orderByDesc('order')->first()->order;
            $order = $order + 1;

            ProductType::query()->insert([
                'name' => $request->name,
                'slug' => $slug,
                'order' => $order
            ]);
            return response(['message' => 'Ürün tipi ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }

    public function updateProductType(Request $request,$id){
        try {
            $request->validate([
                'name' => 'required',
            ]);

            $slug = preg_replace("/[^a-zA-Z0-9]+/", "-", $request->name);
            $slug = strtolower($slug);
            $product_type = ProductType::query()->where('id',$id)->update([
                'name' => $request->name,
                'slug' => $slug
            ]);

            return response(['message' => 'Ürün tipi güncelleme işlemi başarılı.','status' => 'success','object' => ['product_type' => $product_type]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteProductType($id){
        try {

            $product_type = ProductType::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            return response(['message' => 'Ürün tipi silme işlemi başarılı.','status' => 'success','object' => ['product_type' => $product_type]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function updateProductTypeOrder(Request $request)
    {
        try {
            foreach ($request->types as $type){
                ProductType::query()->where('id', $type['id'])->where('active', 1)->update([
                    'order' => $type['order']
                ]);
            }

            return response(['message' => 'Kampanyalı ürün sıralaması düzenlendi.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }

    }

}
