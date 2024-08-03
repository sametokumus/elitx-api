<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\EstateAdvertType;
use App\Models\EstateCondition;
use App\Models\EstateFloor;
use App\Models\EstateRoom;
use App\Models\EstateType;
use App\Models\EstateWarming;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class EstateController extends Controller
{
    public function getEstateOptions(){
        try {
            $estate_options = array();
            $advert_types = EstateAdvertType::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'İlan Türü']);
            $types = EstateType::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'Bina Türü']);
            $conditions = EstateCondition::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'Bina Durumu']);
            $floors = EstateFloor::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'Bulunduğu Kat']);
            $rooms = EstateRoom::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'Oda Sayısı']);
            $warmings = EstateWarming::query()->where('active', 1)->get();
            array_push($estate_options, ['advert_types' => $advert_types, 'pretty_name' => 'Isınma Tipi']);



            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'estate_options' => $estate_options
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
