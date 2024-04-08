<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\LanguageLibrary;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class LanguageController extends Controller
{
    public function getLibraryByPlatform($platform){
        try {
            $libraries = LanguageLibrary::query()->where('platform', $platform)->get();

            $libraryById = [];
            foreach ($libraries as $library) {
                $libraryById[$library->id] = $library->toArray();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => $libraryById]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addLibrary(Request $request)
    {
        try {
            $request->validate([
                'platform' => 'required'
            ]);
            LanguageLibrary::query()->insertGetId([
                'tr' => $request->tr,
                'en' => $request->en,
                'de' => $request->de,
                'platform' => $request->platform,
                'page' => $request->page
            ]);
            return response(['message' => 'Ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }

    public function updateLibrary(Request $request, $id){
        try {
            $request->validate([
                'platform' => 'required'
            ]);

            LanguageLibrary::query()->where('id', $id)->update([
                'tr' => $request->tr,
                'en' => $request->en,
                'de' => $request->de,
                'platform' => $request->platform,
                'page' => $request->page
            ]);
            return response(['message' => 'Güncelleme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteLibrary($id){
        try {
            LanguageLibrary::query()->where('id', $id)->update([
                'active' => 0
            ]);
            return response(['message' => 'Silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }


}
