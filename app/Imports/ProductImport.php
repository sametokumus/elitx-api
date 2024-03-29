<?php

namespace App\Imports;

use App\Models\ImportProduct;
use Maatwebsite\Excel\Concerns\ToModel;


class ProductImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row){
        return new ImportProduct([
            'ana_urun_kod' => $row[0],
            'alt_urun_kod' => $row[1],
            'ana_urun_ad' => $row[2],
            'mikro_urun_ad' => $row[3],
            'mikro_urun_kod' => $row[4],
            'renk' => $row[5],
            'birim' => $row[6],
            'paket_tipi' => $row[7],
            'kmk'=> $row[8],
            'marka' => $row[9],
            'arama_kelimeleri' => $row[10],
            'aciklama' => $row[11],
            'kisa_aciklama' => $row[12],
            'notlar' => $row[13],
            'agirlik' => $row[14],
            'seo_baslik' => $row[15],
            'seo_kelimeler' => $row[16],
            'alt_urun_var' => $row[17],
            'resim' => $row[18],
            'cins' => $row[19]
        ]);
    }
}
