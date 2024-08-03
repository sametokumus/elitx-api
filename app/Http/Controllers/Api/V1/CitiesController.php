<?php

namespace App\Http\Controllers\Api\V1;

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
                $cities = City::query()->where('country_id',$country_id)->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','cities' => $cities]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getDistrictsByCityId($city_id){
        try {
            $districts = District::query()->where('city_id',$city_id)->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','districts' => $districts]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getNeighbourhoodsByDistrictId($district_id){
        try {
            $neighbourhoods = Neighbourhood::query()->where('district_id',$district_id)->where('active', 1)->get();
            return response(['message' => 'İşlem Başarılı.', 'status' => 'success','neighbourhoods' => $neighbourhoods]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }


}
