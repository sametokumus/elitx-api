<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class BrandController extends Controller
{
    public function addBrand(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'slug' => 'required'
            ]);
            $brand_id = Brand::query()->insertGetId([
                'name' => $request->name,
                'slug' => $request->slug
            ]);
            if ($request->hasFile('logo')) {
                $rand = uniqid();
                $image = $request->file('logo');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Admin/Logo/'), $image_name);
                $image_path = "/images/Admin/Logo/" . $image_name;
                Brand::query()->where('id',$brand_id)->update([
                    'logo' => $image_path
                ]);
            }
            return response(['message' => 'Marka ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001','a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001','er' => $throwable->getMessage()]);
        }
    }

    public function updateBrand(Request $request,$id){
        try {
            $request->validate([
                'name' => 'required',
                'slug' => 'required',
            ]);

            $address = Brand::query()->where('id',$id)->update([
                'name' => $request->name,
                'slug' => $request->slug
            ]);

            if ($request->hasFile('logo')) {
                $rand = uniqid();
                $image = $request->file('logo');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/Admin/Logo/'), $image_name);
                $image_path = "/images/Admin/Logo/" . $image_name;
                Brand::query()->where('id',$id)->update([
                    'logo' => $image_path
                ]);
            }
            return response(['message' => 'Marka güncelleme işlemi başarılı.','status' => 'success','object' => ['address' => $address]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function deleteBrand($id){
        try {

            Brand::query()->where('id',$id)->update([
                'active' => 0,
            ]);
            Product::query()->where('brand_id',$id)->update([
                'active' => 0
            ]);
            return response(['message' => 'Marka silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function activeBrand($id){
        try {

            Brand::query()->where('id',$id)->update([
                'active' => 1,
            ]);
            Product::query()->where('brand_id',$id)->update([
                'active' => 1
            ]);
            return response(['message' => 'Marka silme işlemi başarılı.','status' => 'success']);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }

    public function getBrandPassive(){
        try {

            $brands = Brand::query()->where('active',0)->get();
            return response(['message' => 'İşlem Başarılı.','status' => 'success','object' =>['brands' => $brands]]);
        } catch (ValidationException $validationException) {
            return  response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.','status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return  response(['message' => 'Hatalı sorgu.','status' => 'query-001']);
        } catch (\Throwable $throwable) {
            return  response(['message' => 'Hatalı işlem.','status' => 'error-001','ar' => $throwable->getMessage()]);
        }
    }
}
