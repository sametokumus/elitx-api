<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageLibrary;
use App\Models\LanguageLibraryOptions;
use Carbon\Carbon;
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

                if ($library->text != null) {
                    $langs = $library->text;
                    $decoded_langs = json_decode($langs, true);
                    if ($decoded_langs === null) {
                        $item['langs'] = "JSON decoding error: " . json_last_error_msg();
                        // Handle the error appropriately, such as logging it or providing a default value
                    } else {
                        $item['langs'] = $decoded_langs;
                    }
                }

                $attr = [];
                if ($library->placeholder != null) {
                    $placeholder = $library->placeholder;
                    $attr['placeholder'] = json_decode($placeholder, true);
                }
                if ($library->err_msg != null) {
                    $err_msg = $library->err_msg;
                    $attr['data-err-msg'] = json_decode($err_msg, true);
                }

                $item['attr'] = $attr;
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
                'text' => $request->text,
                'placeholder' => $request->placeholder,
                'err_msg' => $request->err_msg,
                'platform' => $request->platform,
                'page' => $request->page
            ]);

            $date = Carbon::now();
            LanguageLibraryOptions::query()->where('platform', $request->platform)->update([
                'last_updated_at' => $date
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
                'text' => $request->text,
                'placeholder' => $request->placeholder,
                'err_msg' => $request->err_msg,
                'platform' => $request->platform,
                'page' => $request->page
            ]);

            $date = Carbon::now();
            LanguageLibraryOptions::query()->where('platform', $request->platform)->update([
                'last_updated_at' => $date
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
