<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarBodyType;
use App\Models\CarCondition;
use App\Models\CarDoor;
use App\Models\CarFuel;
use App\Models\CarGear;
use App\Models\CarImage;
use App\Models\CarPrice;
use App\Models\CarTraction;
use App\Models\Country;
use App\Models\Estate;
use App\Models\EstateAdvertType;
use App\Models\EstateCondition;
use App\Models\EstateConfirm;
use App\Models\EstateFloor;
use App\Models\EstateImage;
use App\Models\EstatePrice;
use App\Models\EstateProp;
use App\Models\EstateRoom;
use App\Models\EstateStatusHistory;
use App\Models\EstateType;
use App\Models\EstateWarming;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductConfirm;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class EstateController extends Controller
{
    public function getEstateOptions(){
        try {
            $advert_types = EstateAdvertType::query()->where('active', 1)->get();
            $types = EstateType::query()->where('active', 1)->get();
            $conditions = EstateCondition::query()->where('active', 1)->get();
            $floors = EstateFloor::query()->where('active', 1)->get();
            $rooms = EstateRoom::query()->where('active', 1)->get();
            $warmings = EstateWarming::query()->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'advert_types' => $advert_types,
                'types' => $types,
                'conditions' => $conditions,
                'floors' => $floors,
                'rooms' => $rooms,
                'warmings' => $warmings
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function filterEstate(Request $request)
    {
        try {

            $latestEstatePrices = EstatePrice::query()
                ->select('estate_prices.*')
                ->whereRaw('estate_prices.id IN (SELECT MAX(id) FROM estate_prices GROUP BY estate_id)');

            $estates = Estate::query()
                ->selectRaw('estates.*, latest_prices.price, latest_prices.currency')
                ->leftJoinSub($latestEstatePrices, 'latest_prices', function ($join) {
                    $join->on('estates.id', '=', 'latest_prices.estate_id');
                })
                ->leftJoin('estate_props', 'estate_props.estate_id', '=', 'estates.id')
                ->where('estates.status_id', 2)
                ->where('estates.active', 1);

            $estates = $estates
                ->leftJoin(DB::raw('(SELECT * FROM estate_confirms WHERE id IN (SELECT MAX(id) FROM estate_confirms GROUP BY estate_id)) as ec'), 'ec.estate_id', '=', 'estates.id')
                ->where('ec.confirmed', 1);

            if ($request->search_word != "" && $request->search_word != null){
                $estates = $estates->where('estates.title', 'like', '%'.$request->search_word.'%');
            }

            if ($request->min_price != "" && $request->min_price != null){
                $estates = $estates->where('latest_prices.price', '>=', $request->min_price);
            }

            if ($request->max_price != "" && $request->max_price != null){
                $estates = $estates->where('latest_prices.price', '<=', $request->max_price);
            }

            if ($request->neighbourhood_id != "" && $request->neighbourhood_id != null){
                $estates = $estates->where('estates.neighbourhood_id', $request->neighbourhood_id);
            }else if ($request->district_id != "" && $request->district_id != null){
                $estates = $estates->where('estates.district_id', $request->district_id);
            }else if ($request->city_id != "" && $request->city_id != null){
                $estates = $estates->where('estates.city_id', $request->city_id);
            }else if ($request->country_id != "" && $request->country_id != null){
                $estates = $estates->where('estates.country_id', $request->country_id);
            }

            if ($request->advert_type != "" && $request->advert_type != null){
                $estates = $estates->where('estates.advert_type', $request->advert_type);
            }

            if ($request->estate_type != "" && $request->estate_type != null){
                $estates = $estates->where('estate_props.estate_type', $request->estate_type);
            }

            if ($request->room_id != "" && $request->room_id != null){
                $estates = $estates->whereIn('estate_props.room_id', $request->room_id);
            }

            if ($request->min_size != "" && $request->min_size != null){
                $estates = $estates->where('estate_props.size', '>=', $request->min_size);
            }

            if ($request->max_size != "" && $request->max_size != null){
                $estates = $estates->where('estate_props.size', '<=', $request->max_size);
            }

            if ($request->min_age != "" && $request->min_age != null){
                $estates = $estates->where('estate_props.building_age', '>=', $request->min_age);
            }

            if ($request->max_age != "" && $request->max_age != null){
                $estates = $estates->where('estate_props.building_age', '<=', $request->max_age);
            }

            if ($request->floor_id != "" && $request->floor_id != null){
                $estates = $estates->whereIn('estate_props.floor_id', $request->floor_id);
            }

            if ($request->warming_id != "" && $request->warming_id != null){
                $estates = $estates->whereIn('estate_props.warming_id', $request->warming_id);
            }

            if ($request->balcony != "" && $request->balcony != null){
                $estates = $estates->where('estate_props.balcony', $request->balcony);
            }

            if ($request->furnished != "" && $request->furnished != null){
                $estates = $estates->where('estate_props.furnished', $request->furnished);
            }

            if ($request->condition_id != "" && $request->condition_id != null){
                $estates = $estates->where('estate_props.condition_id', $request->condition_id);
            }

            $estates = $estates->get();

            return response(['message' => 'Arama işlemi başarılı.', 'status' => 'success', 'object' => ['estates' => $estates]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function getEstateById($estate_id){
        try {
            $latestEstatePrices = EstatePrice::query()
                ->select('estate_prices.*')
                ->whereRaw('estate_prices.id IN (SELECT MAX(id) FROM estate_prices WHERE estate_id = '.$estate_id.' GROUP BY estate_id)');

            $estate = Estate::query()
                ->selectRaw('estate_props.*, estates.*, latest_prices.price, latest_prices.currency')
                ->leftJoinSub($latestEstatePrices, 'latest_prices', function ($join) {
                    $join->on('estates.id', '=', 'latest_prices.estate_id');
                })
                ->leftJoin('estate_props', 'estate_props.estate_id', '=', 'estates.id')
                ->where('estates.status_id', 2)
                ->where('estates.active', 1)
                ->where('estates.id', $estate_id)
                ->first();

            if ($estate->owner_type == 1) {
                $shop = Shop::query()->where('id', $estate->owner_id)->first();
                $types = ShopType::query()
                    ->leftJoin('types', 'types.id', '=', 'shop_types.type_id')
                    ->selectRaw('shop_types.*, types.name as name')
                    ->where('shop_types.shop_id', $shop->id)
                    ->where('shop_types.active', 1)
                    ->get();
                $type_words = $types->implode('name', ', ');
                $shop['types'] = $types;
                $shop['type_words'] = $type_words;
                $estate['shop'] = $shop;
            } else if ($estate->owner_type == 2) {
                $estate['user'] = User::query()->where('id', $estate->owner_id)->first();
            }

            $estate['advert_type'] = EstateAdvertType::query()->where('id', $estate->advert_type)->where('active', 1)->first();
            $estate['type'] = EstateType::query()->where('id', $estate->estate_type)->where('active', 1)->first();
            $estate['condition'] = EstateCondition::query()->where('id', $estate->condition_id)->where('active', 1)->first();
            $estate['floor'] = EstateFloor::query()->where('id', $estate->floor_id)->where('active', 1)->first();
            $estate['room'] = EstateRoom::query()->where('id', $estate->room_id)->where('active', 1)->first();
            $estate['warming'] = EstateWarming::query()->where('id', $estate->warming_id)->where('active', 1)->first();

            $estate['images'] = EstateImage::query()->where('estate_id', $estate_id)->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'estate' => $estate]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }







    private function generateUnique12DigitNumber()
    {
        do {
            // Generate a random 12-digit number
            $number = $this->generateRandom12DigitNumber();
        } while ($this->numberExistsInDatabase($number));

        return $number;
    }

    private function generateRandom12DigitNumber()
    {
        $number = '';
        for ($i = 0; $i < 12; $i++) {
            $number .= random_int(0, 9);
        }
        return $number;
    }

    private function numberExistsInDatabase($number)
    {
        // Check the database for the number
        return Estate::query()->where('advert_no', $number)->exists();
    }
}
