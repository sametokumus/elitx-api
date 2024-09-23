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
                'category_id' => $request->category_id,
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
                ->whereRaw('car_prices.id IN (SELECT MAX(id) FROM car_prices GROUP BY car_id)');

            $cars = Car::query()
                ->selectRaw('cars.*, latest_prices.price, latest_prices.currency')
                ->leftJoinSub($latestCarPrices, 'latest_prices', function ($join) {
                    $join->on('cars.id', '=', 'latest_prices.car_id');
                })
                ->leftJoin('car_props', 'car_props.car_id', '=', 'cars.id')
                ->where('cars.status_id', 2)
                ->where('cars.active', 1);

            $cars = $cars
                ->leftJoin(DB::raw('(SELECT * FROM car_confirms WHERE id IN (SELECT MAX(id) FROM car_confirms GROUP BY car_id)) as cc'), 'cc.car_id', '=', 'cars.id')
                ->where('cc.confirmed', 1);

            if ($request->search_word != "" && $request->search_word != null){
                $cars = $cars->where('cars.title', 'like', '%'.$request->search_word.'%');
            }

            if ($request->min_price != "" && $request->min_price != null){
                $cars = $cars->where('latest_prices.price', '>=', $request->min_price);
            }

            if ($request->max_price != "" && $request->max_price != null){
                $cars = $cars->where('latest_prices.price', '<=', $request->max_price);
            }

            if ($request->neighbourhood_id != "" && $request->neighbourhood_id != null){
                $cars = $cars->where('cars.neighbourhood_id', $request->neighbourhood_id);
            }else if ($request->district_id != "" && $request->district_id != null){
                $cars = $cars->where('cars.district_id', $request->district_id);
            }else if ($request->city_id != "" && $request->city_id != null){
                $cars = $cars->where('cars.city_id', $request->city_id);
            }else if ($request->country_id != "" && $request->country_id != null){
                $cars = $cars->where('cars.country_id', $request->country_id);
            }

            if ($request->category_id != "" && $request->category_id != null){
                $cars = $cars->where('car_props.category_id', $request->category_id);
            }

            if ($request->model_id != "" && $request->model_id != null){
                $cars = $cars->where('car_props.model_id', $request->model_id);
            }else if ($request->serie_id != "" && $request->serie_id != null){
                $cars = $cars->where('car_props.serie_id', $request->serie_id);
            }else if ($request->brand_id != "" && $request->brand_id != null){
                $cars = $cars->where('car_props.brand_id', $request->brand_id);
            }

            if ($request->min_year != "" && $request->min_year != null){
                $cars = $cars->where('car_props.year', '>=', $request->min_year);
            }

            if ($request->max_year != "" && $request->max_year != null){
                $cars = $cars->where('car_props.year', '<=', $request->max_year);
            }

            if ($request->fuel_id != "" && $request->fuel_id != null){
                $cars = $cars->whereIn('car_props.fuel_id', $request->fuel_id);
            }

            if ($request->gear_id != "" && $request->gear_id != null){
                $cars = $cars->whereIn('car_props.gear_id', $request->gear_id);
            }

            if ($request->condition_id != "" && $request->condition_id != null){
                $cars = $cars->whereIn('car_props.condition_id', $request->condition_id);
            }

            if ($request->body_type_id != "" && $request->body_type_id != null){
                $cars = $cars->whereIn('car_props.body_type_id', $request->body_type_id);
            }

            if ($request->traction_id != "" && $request->traction_id != null){
                $cars = $cars->whereIn('car_props.traction_id', $request->traction_id);
            }

            if ($request->door_id != "" && $request->door_id != null){
                $cars = $cars->whereIn('car_props.door_id', $request->door_id);
            }

            if ($request->min_km != "" && $request->min_km != null){
                $cars = $cars->where('car_props.km', '>=', $request->min_km);
            }

            if ($request->max_km != "" && $request->max_km != null){
                $cars = $cars->where('car_props.km', '<=', $request->max_km);
            }

            if ($request->min_hp != "" && $request->min_hp != null){
                $cars = $cars->where('car_props.hp', '>=', $request->min_hp);
            }

            if ($request->max_hp != "" && $request->max_hp != null){
                $cars = $cars->where('car_props.hp', '<=', $request->max_hp);
            }

            if ($request->min_cc != "" && $request->min_cc != null){
                $cars = $cars->where('car_props.cc', '>=', $request->min_cc);
            }

            if ($request->max_cc != "" && $request->max_cc != null){
                $cars = $cars->where('car_props.cc', '<=', $request->max_cc);
            }

            if ($request->color != "" && $request->color != null){
                $cars = $cars->where('car_props.color', 'like', '%'.$request->color.'%');
            }

            $cars = $cars->get();

            return response(['message' => 'Arama işlemi başarılı.', 'status' => 'success', 'object' => ['cars' => $cars]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
        }

    }
    public function getCarById($car_id){
        try {
            $latestCarPrices = CarPrice::query()
                ->select('car_prices.*')
                ->whereRaw('car_prices.id IN (SELECT MAX(id) FROM car_prices WHERE car_id = '.$car_id.' GROUP BY car_id)');
            $car = Car::query()
                ->selectRaw('car_props.*, cars.*, latest_prices.price, latest_prices.currency')
                ->leftJoinSub($latestCarPrices, 'latest_prices', function ($join) {
                    $join->on('cars.id', '=', 'latest_prices.car_id');
                })
                ->leftJoin('car_props', 'car_props.car_id', '=', 'cars.id')
                ->where('cars.status_id', 2)
                ->where('cars.active', 1)
                ->where('cars.id', $car_id)
                ->first();

            $car['body_type'] = CarBodyType::query()->where('id', $car->body_type_id)->where('active', 1)->first();
            $car['condition'] = CarCondition::query()->where('id', $car->condition_id)->where('active', 1)->first();
            $car['door'] = CarDoor::query()->where('id', $car->door_id)->where('active', 1)->first();
            $car['fuel'] = CarFuel::query()->where('id', $car->fuel_id)->where('active', 1)->first();
            $car['gear'] = CarGear::query()->where('id', $car->gear_id)->where('active', 1)->first();
            $car['traction'] = CarTraction::query()->where('id', $car->traction_id)->where('active', 1)->first();

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => [
                'car' => $car]]);
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
