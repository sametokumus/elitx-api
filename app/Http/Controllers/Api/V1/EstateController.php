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
            $types = EstateType::query()->where('active', 1)->get();
            $conditions = EstateCondition::query()->where('active', 1)->get();
            $floors = EstateFloor::query()->where('active', 1)->get();
            $rooms = EstateRoom::query()->where('active', 1)->get();
            $warmings = EstateWarming::query()->where('active', 1)->get();

            $estate_options['advert_types'] = $advert_types;
            $estate_options['types'] = $types;
            $estate_options['conditions'] = $conditions;
            $estate_options['floors'] = $floors;
            $estate_options['rooms'] = $rooms;
            $estate_options['warmings'] = $warmings;



            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'estate_options' => $estate_options
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
}
