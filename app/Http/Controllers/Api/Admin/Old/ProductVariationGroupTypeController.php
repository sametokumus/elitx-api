<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\ProductVariationGroupType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class ProductVariationGroupTypeController extends Controller
{
    public function addProductVariationGroupType(Request $request)
    {
        try {

            $request->validate([
                'name' => 'required'
            ]);

            ProductVariationGroupType::query()->insertGetId([
                'name' => $request->name
            ]);
            return response(['message' => 'Ürün varyasyon grup türü ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateProductVariationGroupType(Request $request, $variation_group_type_id)
    {
        try {

            $request->validate([
                'name' => 'required',
            ]);

            ProductVariationGroupType::query()->where('id',$variation_group_type_id)->update([
                'name' => $request->name
            ]);

            return response(['message' => 'Ürün varyasyon grup türü güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function deleteProductVariationGroupType($variation_group_type_id)
    {
        try {

            ProductVariationGroupType::query()->where('id',$variation_group_type_id)->update([
                'active' => 0
            ]);

            return response(['message' => 'Ürün varyasyon grup türü silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }
}
