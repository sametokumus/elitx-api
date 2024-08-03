<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CountriesController extends Controller
{
    public function getCountries(){
        try {
             $countries = Country::query()->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['countries' => $countries]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

}
