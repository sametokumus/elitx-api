<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PriceImports;
use App\Models\Brand;
use App\Models\ImportPrice;
use App\Models\ImportProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductRule;
use App\Models\ProductSeo;
use App\Models\ProductType;
use App\Models\ProductVariation;
use App\Models\ProductVariationGroup;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;


class ImportController extends Controller
{
    public function productExcelImport(Request $request)
    {
        Excel::import(new ProductImport, $request->file('file'));
        return response(['mesaj' => 'başarılı']);
    }

    public function addAllProduct()
    {
        $import_products = ImportProduct::all();

        foreach ($import_products as $import_product) {

            $hasProduct = Product::query()->where('sku', $import_product->ana_urun_kod)->first();

            if (!isset($hasProduct)) {

                if ($import_product->marka == '3_2') {

                    $product_id = Product::query()->insertGetId([
                        'sku' => $import_product->ana_urun_kod,
                        'name' => $import_product->ana_urun_ad,
                        'brand_id' => 3,
                        'type_id' => $import_product->cins,
                        'description' => $import_product->aciklama,
                        'short_description' => $import_product->kisa_aciklama,
                        'notes' => $import_product->notlar
                    ]);
                    ProductSeo::query()->insert([
                        'product_id' => $product_id,
                        'title' => $import_product->seo_baslik,
                        'keywords' => $import_product->seo_kelimeler,
                        'search_keywords' => $import_product->arama_kelimeleri
                    ]);
                    $variation_group_id = ProductVariationGroup::query()->insertGetId([
                        'group_type_id' => 1,
                        'product_id' => $product_id,
                        'order' => 1
                    ]);
                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);

                    $product_id = Product::query()->insertGetId([
                        'sku' => $import_product->ana_urun_kod,
                        'name' => $import_product->ana_urun_ad,
                        'brand_id' => 2,
                        'type_id' => $import_product->cins,
                        'description' => $import_product->aciklama,
                        'short_description' => $import_product->kisa_aciklama,
                        'notes' => $import_product->notlar
                    ]);
                    ProductSeo::query()->insert([
                        'product_id' => $product_id,
                        'title' => $import_product->seo_baslik,
                        'keywords' => $import_product->seo_kelimeler,
                        'search_keywords' => $import_product->arama_kelimeleri
                    ]);
                    $variation_group_id = ProductVariationGroup::query()->insertGetId([
                        'group_type_id' => 1,
                        'product_id' => $product_id,
                        'order' => 1
                    ]);

                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);


                } else {

                    $product_id = Product::query()->insertGetId([
                        'sku' => $import_product->ana_urun_kod,
                        'name' => $import_product->ana_urun_ad,
                        'brand_id' => $import_product->marka,
                        'type_id' => $import_product->cins,
                        'description' => $import_product->aciklama,
                        'short_description' => $import_product->kisa_aciklama,
                        'notes' => $import_product->notlar
                    ]);
                    ProductSeo::query()->insert([
                        'product_id' => $product_id,
                        'title' => $import_product->seo_baslik,
                        'keywords' => $import_product->seo_kelimeler,
                        'search_keywords' => $import_product->arama_kelimeleri
                    ]);
                    $variation_group_id = ProductVariationGroup::query()->insertGetId([
                        'group_type_id' => 1,
                        'product_id' => $product_id,
                        'order' => 1
                    ]);

                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);

                }


            } else {

                if ($import_product->marka == '3_2') {

                    $variation_group_id = ProductVariationGroup::query()->where('product_id', $hasProduct->id)->first()->id;

                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);

                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id + 1,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);

                } else {

                    $variation_group_id = ProductVariationGroup::query()->where('product_id', $hasProduct->id)->first()->id;

                    $variation_id = ProductVariation::query()->insertGetId([
                        'variation_group_id' => $variation_group_id,
                        'sku' => $import_product->alt_urun_kod,
                        'name' => $import_product->renk,
                        'description' => ''
                    ]);
                    ProductRule::query()->insert([
                        'package_type_id' => $import_product->kmk,
                        'variation_id' => $variation_id,
                        'micro_name' => $import_product->mikro_urun_ad,
                        'micro_sku' => $import_product->mikro_urun_kod,
                        'dimensions' => $import_product->birim,
                        'weight' => $import_product->agirlik
                    ]);
                    ProductImage::query()->insert([
                        'variation_id' => $variation_id,
                        'image' => $import_product->resim,
                        'order' => 1
                    ]);

                }

            }


        }
    }

    public function productVariationUpdate()
    {
        $products = Product::query()->get();
        foreach ($products as $product) {
            $group = ProductVariationGroup::query()->where('product_id', $product->id)->first();
            $variation_id = ProductVariation::query()->where('variation_group_id', $group->id)->first()->id;
            Product::query()->where('id', $product->id)->update([
                'featured_variation' => $variation_id
            ]);
        }
        return response(['mesaj' => 'başarılı']);
    }

    public function setProductCategory()
    {

        $products = Product::query()->get();
        foreach ($products as $product) {
            $type = ProductType::query()->where('id', $product->type_id)->first();
            ProductCategory::query()->insert([
                'product_id' => $product->id,
                'category_id' => $type->cid
            ]);
        }

        return response(['mesaj' => 'başarılı']);
    }


    public function priceExcelImport(Request $request)
    {
        Excel::import(new PriceImports(), $request->file('file'));
    }

    public function addProductPrice()
    {
        $import_prices = ImportPrice::all();
        foreach ($import_prices as $import_price) {
            $products = Product::query()->where('sku', $import_price->web_servis_kodu)->get();
            foreach ($products as $product){
                $product_variation_group = ProductVariationGroup::query()->where('product_id', $product->id)->first()->id;
                $variations = ProductVariation::query()->where('variation_group_id',$product_variation_group)->get();
                $brand = Brand::query()->where('id',$product->brand_id)->first();
                foreach ($variations as $variation){
                    if($brand->dis == 0){
                        $regular_tax = $import_price->fiyati / 100 * $import_price->kdv;
                        ProductRule::query()->where('variation_id',$variation->id)->update([
                            'discount_rate' => null,
                            'tax_rate' => $import_price->kdv,
                            'regular_price' => $import_price->fiyati,
                            'regular_tax' => $regular_tax,
                            'discounted_price' => null,
                            'discounted_tax' => null,
                            'currency' => $import_price->currency
                        ]);
                    }else{
                        $regular_tax = $import_price->fiyati / 100 * $import_price->kdv;
                        $discounted_price = $import_price->fiyati - ($import_price->fiyati / 100 * $brand->dis);
                        $discounted_tax = $discounted_price * $import_price->kdv;
                        ProductRule::query()->where('variation_id',$variation->id)->update([
                            'discount_rate' => $brand->dis,
                            'tax_rate' => $import_price->kdv,
                            'regular_price' => $import_price->fiyati,
                            'regular_tax' => $regular_tax,
                            'discounted_price' => $discounted_price,
                            'discounted_tax' => $discounted_tax,
                            'currency' => $import_price->currency
                        ]);
                    }



                    if ($import_price->yeni_urun_mu == "" || $import_price->indirimli_goster == ""  || $import_price->tanitimli_goster == ""){
                        Product::query()->where('sku',$import_price->web_servis_kodu)->update([
                            'is_new' => 0,
                            'is_discounted' => $import_price->indirimli_goster,
                            'is_featured' => $import_price->tanitimli_goster,
                            'order' => $import_price->sira_no
                        ]);
                    }else{
                        Product::query()->where('sku',$import_price->web_servis_kodu)->update([
                            'is_new' => $import_price->yeni_urun_mu,
                            'is_discounted' => $import_price->indirimli_goster,
                            'is_featured' => $import_price->tanitimli_goster,
                            'order' => $import_price->sira_no
                        ]);
                    }
                }
            }
        }
    }
}
