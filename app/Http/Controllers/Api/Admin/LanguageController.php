<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageLibrary;
use App\Models\LanguageLibraryOptions;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class LanguageController extends Controller
{
    public function getLibraryLastUpdateByPlatform($platform){
        try {
            $option = LanguageLibraryOptions::query()->where('platform', $platform)->first();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['option' => $option]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getLibraryByPlatform($platform){
        try {
            $libraries = LanguageLibrary::query()->where('platform', $platform)->get();

            $result = [];

            foreach ($libraries as $library) {
                $item = ['id' => "lib-".$library->id];

                if ($library->placeholder == 0) {
                    $langs = [];
                    $langs['tr'] = $library->tr;
                    $langs['en'] = $library->en;
                    $langs['de'] = $library->de;

                    $item['langs'] = $langs;
                }else{
                    $placeholder = [];
                    $placeholder['tr'] = $library->tr;
                    $placeholder['en'] = $library->en;
                    $placeholder['de'] = $library->de;

                    $attr = [];
                    $attr['placeholder'] = $placeholder;

                    $item['attr'] = $attr;
                }

                array_push($result, $item);
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['library' => $result]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getLibrary(){
        try {
            $libraries = LanguageLibrary::query()->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['libraries' => $libraries]]);
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
