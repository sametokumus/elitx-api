<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarBodyType;
use App\Models\CarCondition;
use App\Models\CarConfirm;
use App\Models\CarDoor;
use App\Models\CarFuel;
use App\Models\CarGear;
use App\Models\CarImage;
use App\Models\CarPrice;
use App\Models\CarProp;
use App\Models\CarStatusHistory;
use App\Models\CarTraction;
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
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class CarController extends Controller
{
    public function getCarOptions(){
        try {
            $body_types = CarBodyType::query()->where('active', 1)->get();
            $conditions = CarCondition::query()->where('active', 1)->get();
            $doors = CarDoor::query()->where('active', 1)->get();
            $fuels = CarFuel::query()->where('active', 1)->get();
            $gears = CarGear::query()->where('active', 1)->get();
            $traction = CarTraction::query()->where('active', 1)->get();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'body_types' => $body_types,
                'conditions' => $conditions,
                'doors' => $doors,
                'fuels' => $fuels,
                'gears' => $gears,
                'traction' => $traction
            ]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function addCar(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required',
                'price' => 'required',
                'currency' => 'required'
            ]);
            $user = Auth::user();

            $advert_no = $this->generateUnique12DigitNumber();
            $now = Carbon::now()->format('Y-m-d');
            $car_id = Car::query()->insertGetId([
                'advert_no' => $advert_no,
                'title' => $request->title,
                'listing_date' => $now,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'neighbourhood_id' => $request->neighbourhood_id,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status_id' => 1,
                'owner_type' => 2,
                'owner_id' => $user->id
            ]);

            CarProp::query()->insert([
                'car_id' => $car_id,
                'brand_id' => $request->brand_id,
                'serie_id' => $request->serie_id,
                'model_id' => $request->model_id,
                'year' => $request->year,
                'fuel_id' => $request->fuel_id,
                'gear_id' => $request->gear_id,
                'condition_id' => $request->condition_id,
                'body_type_id' => $request->body_type_id,
                'traction_id' => $request->traction_id,
                'door_id' => $request->door_id,
                'km' => $request->km,
                'hp' => $request->hp,
                'cc' => $request->cc,
                'color' => $request->color
            ]);

            CarPrice::query()->insert([
                'car_id' => $car_id,
                'price' => $request->price,
                'currency' => $request->currency
            ]);

            CarStatusHistory::query()->insert([
                'car_id' => $car_id,
                'status_id' => 1
            ]);

            if ($request->hasFile('thumbnail')) {
                $rand = uniqid();
                $image = $request->file('thumbnail');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/CarImage/'), $image_name);
                $image_path = "/images/CarImage/" . $image_name;
                Car::query()->where('id', $car_id)->update([
                    'thumbnail' => $image_path
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $rand = uniqid();
                    $image_name = $rand . "-" . $image->getClientOriginalName();
                    $image->move(public_path('/images/CarImage/'), $image_name);
                    $image_path = "/images/CarImage/" . $image_name;
                    CarImage::query()->insert([
                        'car_id' => $car_id,
                        'image' => $image_path
                    ]);
                }
            }

            CarConfirm::query()->insert([
                'car_id' => $car_id
            ]);

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['car_id' => $car_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function filterCar(Request $request)
    {
        try {

            $latestCarPrices = CarPrice::query()
                ->select('car_prices.*')
                ->whereRaw('car_prices.id IN (SELECT MAX(id) FROM estate_prices GROUP BY estate_id)');

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
