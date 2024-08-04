<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    public function addEstate(Request $request)
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
            $estate_id = Estate::query()->insertGetId([
                'advert_no' => $advert_no,
                'title' => $request->title,
                'advert_type' => $request->advert_type,
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

            EstateProp::query()->insert([
                'estate_id' => $estate_id,
                'estate_type' => $request->estate_type,
                'room_id' => $request->room_id,
                'size' => $request->size,
                'building_age' => $request->building_age,
                'floor_id' => $request->floor_id,
                'warming_id' => $request->warming_id,
                'balcony' => $request->balcony,
                'furnished' => $request->furnished,
                'dues' => $request->dues,
                'dues_currency' => $request->dues_currency,
                'condition_id' => $request->condition_id
            ]);

            EstatePrice::query()->insert([
                'estate_id' => $estate_id,
                'price' => $request->price,
                'currency' => $request->currency
            ]);

            EstateStatusHistory::query()->insert([
                'estate_id' => $estate_id,
                'status_id' => 1
            ]);

            if ($request->hasFile('thumbnail')) {
                $rand = uniqid();
                $image = $request->file('thumbnail');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/EstateImage/'), $image_name);
                $image_path = "/images/EstateImage/" . $image_name;
                Estate::query()->where('id', $estate_id)->update([
                    'thumbnail' => $image_path
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $rand = uniqid();
                    $image_name = $rand . "-" . $image->getClientOriginalName();
                    $image->move(public_path('/images/EstateImage/'), $image_name);
                    $image_path = "/images/EstateImage/" . $image_name;
                    EstateImage::query()->insert([
                        'estate_id' => $estate_id,
                        'image' => $image_path
                    ]);
                }
            }

            EstateConfirm::query()->insert([
                'estate_id' => $estate_id
            ]);

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['estate_id' => $estate_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine()]);
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
