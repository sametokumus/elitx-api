<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\City;
use App\Models\District;
use App\Models\Districts2;
use App\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CitiesController extends Controller
{
    public function getUpdateNeighbours()
    {
        try {
            $cities = City::query()->where('country_id', 223)->get();
            foreach ($cities as $city){
                District::query()->where('city_id', $city->id)->delete();
            }
            City::query()->where('country_id', 223)->delete();


//            $old_dists = Districts2::query()->get();
//
//            foreach ($old_dists as $old_dist) {
//                // Corrected the LIKE query
//                $new_dist = District::query()->where('name', 'LIKE', '%' . $old_dist->name . '%')->first();
//
//                if ($new_dist) { // Check if $new_dist exists
//                    Neighbourhood::query()->where('district_id', $old_dist->id)->update([
//                        'district_id' => $new_dist->id
//                    ]);
//                }
//            }



            return response(['message' => 'İşlem Başarılı.', 'status' => 'success']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e'=>$queryException->getMessage()]);
        }
    }


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
