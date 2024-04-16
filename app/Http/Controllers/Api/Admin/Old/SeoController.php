<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\CustomSeo;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class SeoController extends Controller
{
    public function addSeo(Request $request)
    {
        try {
            $request->validate([
                'page' => 'required',
                'title' => 'required'
            ]);
            $seo_id = CustomSeo::query()->insertGetId([
                'page' => $request->page,
                'title' => $request->title,
                'keywords' => $request->keywords,
                'description' => $request->description
            ]);

            return response(['message' => 'SEO ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }
    public function updateSeo(Request $request,$id){
        try {
            $request->validate([
                'page' => 'required',
                'title' => 'required'
            ]);

            $seo = CustomSeo::query()->where('id',$id)->update([
                'page' => $request->page,
                'title' => $request->title,
                'keywords' => $request->keywords,
                'description' => $request->description
            ]);

            return response(['message' => 'SEO güncelleme işlemi başarılı.','status' => 'success','object' => ['seo' => $seo]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
