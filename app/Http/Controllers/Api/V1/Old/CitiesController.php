<?php

namespace App\Http\Controllers\Api\V1\Old;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CitiesController extends Controller
{
    public function getCitiesByCountryId($country_id)
    {
        try {
                $cities = City::query()->where('country_id',$country_id)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','cities' => $cities]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getDistrictsByCityId($city_id){
        try {
            $districts = District::query()->where('city_id',$city_id)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','districts' => $districts]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getNeighbourhoodsByDistrictId($district_id){
        try {
            $neighbourhoods = Neighbourhood::query()->where('district_id',$district_id)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','neighbourhoods' => $neighbourhoods]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getNeighbourhoodById($neighbourhood_id){
        try {
            $neighbourhood = Neighbourhood::query()->where('id',$neighbourhood_id)->first();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','neighbourhood' => $neighbourhood]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addCities(Request $request,$country_id)
    {
        try {
            $request->validate([
                'name' => 'required',
            ]);
            City::query()->insert([
                'country_id' => $country_id,
                'name' => $request->name
            ]);

            return response(['message' => 'Şehir ekleme işlemi başarılı.', 'status' => 'success']);

        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001']);
        }
    }

}
