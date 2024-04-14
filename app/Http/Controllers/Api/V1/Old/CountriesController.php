<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Country;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CountriesController extends Controller
{
    public function getCountries(){
        try {
             $countries = Country::all();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['countries' => $countries]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addCountries(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required',
                'name' => 'required',
            ]);
            Country::query()->insert([
                'code' => $request->code,
                'name' => $request->name,
            ]);

            return response(['message' => 'Ülke ekleme işlemi başarılı.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }
}
