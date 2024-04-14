<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\City;
use App\Models\CorporateAddresses;
use App\Models\Country;
use App\Models\District;
use App\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;

class AddressController extends Controller
{

    public function getAddressesByUser()
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;

            $addresses = Address::query()->where('user_id', $user_id)->where('active',1)->get();
            foreach ($addresses as $address){
                $address['country'] = Country::query()->where('id', $address->country_id)->first();
                $address['city'] = City::query()->where('id', $address->city_id)->first();
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['addresses' => $addresses]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function getUserAddress($address_id)
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;

            $address = Address::query()->where('user_id', $user_id)->where('id', $address_id)->where('active',1)->first();

            if(!$address) {
                if ($address->type == 2) {
                    $corporate_address = CorporateAddresses::query()->where('address_id', $address_id)->first();
                    $address['company_name'] = $corporate_address->company_name;
                    $address['tax_number'] = $corporate_address->tax_number;
                    $address['tax_office'] = $corporate_address->tax_office;
                }

                $address['country'] = Country::query()->where('id', $address->country_id)->first();
                $address['city'] = City::query()->where('id', $address->city_id)->first();
            }else{
                return response(['message' => 'Adres bulunamadı.', 'status' => 'address-001']);
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['address' => $address]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }

    public function addUserAddress(Request $request)
    {
        try {
            $request->validate([
                'country_id' => 'required|exists:countries,id',
                'city_id' => 'required|exists:cities,id',
                'title' => 'required',
                'name' => 'required',
                'address_1' => 'required',
                'phone' => 'required',
                'type' => 'required',
            ]);

            $user = Auth::user();
            $user_id = $user->id;

            $address_id = Address::query()->insertGetId([
                'user_id' => $user_id,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'title' => $request->title,
                'name' => $request->name,
                'citizen_number' => $request->citizen_number,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'comment' => $request->comment,
                'type' => $request->type,
            ]);

            if ($request->type == 2) {
                CorporateAddresses::query()->insert([
                    'address_id' => $address_id,
                    'tax_number' => $request->tax_number,
                    'tax_office' => $request->tax_office,
                    'company_name' => $request->company_name
                ]);
            }
            return response(['message' => 'Adres ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','a' => $throwable->getMessage()]);
        }
    }

    public function updateUserAddress(Request $request, $address_id){
        try {
            $request->validate([
                'city_id' => 'required|exists:cities,id',
                'name' => 'required',
                'address_1' => 'required',
                'postal_code' => 'required',
                'phone' => 'required',
                'type' => 'required',
            ]);

            $user = Auth::user();
            $user_id = $user->id;

            $address = Address::query()->where('user_id', $user_id)->where('id', $address_id)->where('active',1)->first();
            if (!$address) {
                Address::query()->where('user_id', $user_id)->where('id', $address_id)->update([
                    'user_id' => $user_id,
                    'country_id' => $request->country_id,
                    'city_id' => $request->city_id,
                    'title' => $request->title,
                    'name' => $request->name,
                    'citizen_number' => $request->citizen_number,
                    'address_1' => $request->address_1,
                    'address_2' => $request->address_2,
                    'postal_code' => $request->postal_code,
                    'phone' => $request->phone,
                    'comment' => $request->comment,
                    'type' => $request->type
                ]);

                if ($request->type == 2) {
                    CorporateAddresses::query()->where('id', $address_id)->updateOrCreate([
                        'tax_number' => $request->tax_number,
                        'tax_office' => $request->tax_office,
                        'company_name' => $request->company_name
                    ]);
                }
            }else{
                return response(['message' => 'Adres bulunamadı.', 'status' => 'address-001']);
            }

            return response(['message' => 'Adres güncelleme işlemi başarılı.','status' => 'success','object' => ['address' => $address]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteUserAddress($id){
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $address = Address::query()->where('user_id', $user_id)->where('id', $id)->where('active',1)->first();
            if (!$address) {

                $address = Address::query()->where('id', $id)->update([
                    'active' => 0,
                ]);

            }else{
                return response(['message' => 'Adres bulunamadı.', 'status' => 'address-001']);
            }

            return response(['message' => 'Adres silme işlemi başarılı.','status' => 'success','object' => ['address' => $address]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

}
