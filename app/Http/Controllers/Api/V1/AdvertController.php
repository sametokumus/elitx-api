<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Car;
use App\Models\CarConfirm;
use App\Models\CarImage;
use App\Models\CarPrice;
use App\Models\CarProp;
use App\Models\CarStatusHistory;
use App\Models\Estate;
use App\Models\EstateConfirm;
use App\Models\EstateImage;
use App\Models\EstatePrice;
use App\Models\EstateProp;
use App\Models\EstateStatusHistory;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductConfirm;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStatusHistory;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\User;
use App\Models\UserFavorite;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class AdvertController extends Controller
{

    public function addSecondHand(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'base_price' => 'required',
                'currency' => 'required',
                'usage_status_id' => 'required'
            ]);
            $user = Auth::user();

            $product_id = Product::query()->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'stock_quantity' => 1,
                'status_id' => 2,
                'owner_type' => 2,
                'owner_id' => $user->id,
                'usage_status_id' => $request->usage_status_id,
                'active' => 0
            ]);

            ProductPrice::query()->insert([
                'product_id' => $product_id,
                'base_price' => $request->base_price,
                'currency' => $request->currency
            ]);

            ProductStatusHistory::query()->insert([
                'product_id' => $product_id,
                'status_id' => 2
            ]);

            if ($request->hasFile('thumbnail')) {
                $rand = uniqid();
                $image = $request->file('thumbnail');
                $image_name = $rand . "-" . $image->getClientOriginalName();
                $image->move(public_path('/images/ProductImage/'), $image_name);
                $image_path = "/images/ProductImage/" . $image_name;
                Product::query()->where('id', $product_id)->update([
                    'thumbnail' => $image_path
                ]);
            }

//            $categories = json_decode($request->categories);
//            foreach ($categories as $category) {
//                ProductCategory::query()->insert([
//                    'product_id' => $product_id,
//                    'category_id' => $category
//                ]);
//            }

            ProductCategory::query()->insert([
                'product_id' => $product_id,
                'category_id' => $request->category
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $rand = uniqid();
                    $image_name = $rand . "-" . $image->getClientOriginalName();
                    $image->move(public_path('/images/ProductImage/'), $image_name);
                    $image_path = "/images/ProductImage/" . $image_name;
                    ProductImage::query()->insert([
                        'product_id' => $product_id,
                        'image' => $image_path
                    ]);
                }
            }

            ProductConfirm::query()->insert([
                'product_id' => $product_id,
                'admin_id' => 0,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);

            Product::query()->where('id', $product_id)->update([
                'active' => 1
            ]);

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['product_id' => $product_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine(), 'fl' => $throwable->getFile()]);
        }

    }
    public function getUserAdvertSecondHands(){
        try {
            $user = Auth::user();
            $user_id = $user->id;

            $products = Product::query()
                ->leftJoin('product_statuses', 'product_statuses.id', '=', 'products.status_id')
                ->leftJoin('product_usage_statuses', 'product_usage_statuses.id', '=', 'products.usage_status_id')
                ->selectRaw('products.*, product_statuses.name as status_name, product_usage_statuses.name as usage_status_name')
                ->where('products.owner_type', 2)
                ->where('products.owner_id', $user_id)
                ->where('products.active', 1)
                ->get();

            foreach ($products as $product) {
                if ($product->owner_type == 1) {
                    $shop = Shop::query()->where('id', $product->owner_id)->first();
                    $types = ShopType::query()
                        ->leftJoin('types', 'types.id', '=', 'shop_types.type_id')
                        ->selectRaw('shop_types.*, types.name as name')
                        ->where('shop_types.shop_id', $shop->id)
                        ->where('shop_types.active', 1)
                        ->get();
                    $type_words = $types->implode('name', ', ');
                    $shop['types'] = $types;
                    $shop['type_words'] = $type_words;
                    $product['shop'] = $shop;
                } else if ($product->owner_type == 2) {
                    $product['user'] = User::query()->where('id', $product->owner_id)->first();
                }

                $brand = Brand::query()->where('id', $product->brand_id)->first();
                $product['brand'] = $brand;

                $categories = ProductCategory::query()
                    ->leftJoin('categories', 'categories.id', '=', 'product_categories.category_id')
                    ->selectRaw('product_categories.*, categories.name as name')
                    ->where('product_categories.active', 1)
                    ->where('product_categories.product_id', $product->id)
                    ->get();
                $product['categories'] = $categories;


                $price = ProductPrice::query()->where('product_id', $product->id)->orderByDesc('id')->first();
                $product['base_price'] = $price->base_price;
                $product['discounted_price'] = $price->discounted_price;
                $product['discount_rate'] = $price->discount_rate;
                $product['discount_type'] = $price->discount_type;
                $product['currency'] = $price->currency;

                if ($product->has_variation == 1) {
                    $variations = ProductVariation::query()->where('product_id', $product->id)->where('active', 1)->get();
                    foreach ($variations as $variation) {
                        $variation_price = ProductVariationPrice::query()->where('product_id', $product->id)->where('variation_id', $variation->id)->orderByDesc('id')->first();
                        $variation['price'] = $variation_price->price;
                    }
                    $product['variations'] = $variations;
                }

                $fav_count = UserFavorite::query()->where('product_id', $product->id)->count();
                $product['fav_count'] = $fav_count;
            }

            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['products' => $products]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getUserAdvertSecondHand($advert_id){
        try {
            $user = Auth::user();
            $user_id = $user->id;
            $order = Order::query()->where('order_id',$order_id)->first();
            if ($user_id == $order->user_id) {

                $status_name = OrderStatus::query()->where('id', $order->status_id)->first()->name;
                $order['status_name'] = $status_name;
                $product_count = OrderProduct::query()->where('order_id', $order->order_id)->get()->count();
                $order['product_count'] = $product_count;
                $products = OrderProduct::query()->where('order_id', $order->order_id)->get();
                foreach ($products as $product) {
                    $product['status_name'] = OrderStatus::query()->where('id', $product->status_id)->first()->name;
                    $product_info = Product::query()->where('id', $product->product_id)->first();
                    $product['thumbnail'] = $product_info->thumbnail;
                    $product['product_info'] = $product_info;
                }
                $order['products'] = $products;

            }else{
                return response(['message' => 'Yetkisiz işlem.', 'status' => 'auth-006']);
            }


            return response(['message' => 'İşlem Başarılı.', 'status' => 'success', 'object' => ['order' => $order]]);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001']);
        }
    }
    public function getSaledAdvertSecondHand($advert_id)
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $product = Product::query()
                ->where('owner_type', 2)
                ->where('owner_id', $user_id)
                ->where('id', $advert_id)
                ->count();
            if ($product > 0) {
                Product::query()
                    ->where('id', $advert_id)
                    ->update([
                        'is_saled' => 1,
                        'status_id' => 5
                    ]);
                ProductStatusHistory::query()->insert([
                    'product_id' => $advert_id,
                    'status_id' => 5
                ]);
            }


            return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }
    public function getRemoveAdvertSecondHand($advert_id)
    {
        try {

            $user = Auth::user();
            $user_id = $user->id;

            $product = Product::query()
                ->where('owner_type', 2)
                ->where('owner_id', $user_id)
                ->where('id', $advert_id)
                ->count();
            if ($product > 0) {
                Product::query()
                    ->where('id', $advert_id)
                    ->update([
                        'status_id' => 4
                    ]);
                ProductStatusHistory::query()->insert([
                    'product_id' => $advert_id,
                    'status_id' => 4
                ]);
            }


            return response(['message' => 'İşlem başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function addEstate(Request $request)
    {
        try {
//            $request->validate([
//                'title' => 'required',
//                'price' => 'required',
//                'currency' => 'required'
//            ]);
            $user = Auth::user();

            $advert_no = $this->generateUnique12DigitNumber();
            $now = \Illuminate\Support\Carbon::now()->format('Y-m-d');
            $estate_id = Estate::query()->insertGetId([
                'advert_no' => $advert_no,
                'title' => $request->title,
                'description' => $request->description,
                'advert_type' => $request->advert_type,
                'listing_date' => $now,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'neighbourhood_id' => $request->neighbourhood_id,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status_id' => 2,
                'owner_type' => 2,
                'owner_id' => $user->id,
                'active' => 0
            ]);

            return response(['message' => 'başarılı.', 'status' => 'success', 'object' => ['estate_id' => $estate_id]]);

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
                'estate_id' => $estate_id,
                'admin_id' => 0,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);

            Estate::query()->where('id', $estate_id)->update([
                'active' => 1
            ]);

            return response(['message' => 'Ürün ekleme işlemi başarılı.', 'status' => 'success', 'object' => ['estate_id' => $estate_id]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'a' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'er' => $throwable->getMessage(), 'ln' => $throwable->getLine(), 'fl' => $throwable->getFile(), 'cd' => $throwable->getCode()]);
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
            $now = \Illuminate\Support\Carbon::now()->format('Y-m-d');
            $car_id = Car::query()->insertGetId([
                'advert_no' => $advert_no,
                'title' => $request->title,
                'description' => $request->description,
                'listing_date' => $now,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'neighbourhood_id' => $request->neighbourhood_id,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status_id' => 2,
                'owner_type' => 2,
                'owner_id' => $user->id,
                'active' => 0
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
                'car_id' => $car_id,
                'admin_id' => 0,
                'confirmed' => 1,
                'confirmed_at' => Carbon::now()
            ]);

            Car::query()->where('id', $car_id)->update([
                'active' => 1
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
        // Check if the number exists in the estates table
        $existsInEstates = Estate::query()->where('advert_no', $number)->exists();

        // Check if the number exists in the cars table
        $existsInCars = Car::query()->where('advert_no', $number)->exists();

        // Return true if the number exists in either table
        return $existsInEstates || $existsInCars;
    }
}
