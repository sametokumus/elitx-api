<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\IncreasingDesi;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CarrierController extends Controller
{
    public function addCarrier(Request $request){
        try {
            $carrier_id = Carrier::query()->insertGetId([
                'name' => $request->name,
                'max_desi' => $request->max_desi
            ]);
            IncreasingDesi::query()->insert([
                'carrier_id' => $carrier_id
            ]);
            return response(['message' => 'Kargo firması ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function updateCarrier(Request $request,$id){
        try {
            Carrier::query()->where('id',$id)->update([
                'name' => $request->name,
                'max_desi' => $request->max_desi
            ]);
            return response(['message' => 'Kargo firması güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function deleteCarrier($id)
    {
        try {
            Carrier::query()->where('id',$id)->update([
                'active' =>0
            ]);
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }

    }

    public function getCarriers(){
        try {
           $carriers = Carrier::query()->where('active',1)->get();
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success','object' => ['carriers' => $carriers]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function getIncreasingDesis(){
        try {
            $increasing_desis = IncreasingDesi::query()
                ->leftJoin('carriers', 'carriers.id', '=', 'increasing_desis.carrier_id')
                ->where('carriers.active', 1)
                ->get(['increasing_desis.*', 'carriers.name as carrier_name']);
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success','object' => ['increasing_desis' => $increasing_desis]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function getIncreasingDesiById($carrier_id){
        try {
            $increasing_desi = IncreasingDesi::query()
                ->leftJoin('carriers', 'carriers.id', '=', 'increasing_desis.carrier_id')
                ->where('carriers.active', 1)
                ->where('carriers.id', $carrier_id)
                ->first(['increasing_desis.*', 'carriers.name as carrier_name']);
            return response(['message' => 'Kargo silme işlemi başarılı.', 'status' => 'success','object' => ['increasing_desi' => $increasing_desi]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage()]);
        }
    }

    public function updateIncreasingDesi(Request $request){
        try {
            IncreasingDesi::query()->where('carrier_id', $request->carrier_id)->update([
                'cat_1_price' => $request->cat_1_price,
                'cat_2_price' => $request->cat_2_price,
                'cat_3_price' => $request->cat_3_price,
            ]);
            return response(['message' => 'Artan güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

}
