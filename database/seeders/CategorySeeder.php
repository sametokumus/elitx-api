<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Kadın Giyim',
                'slug' => 'kadin-giyim',
                'image_url' => 'kadin_giyim.jpg',
                'children' => [
                    ['name' => 'Elbiseler', 'slug' => 'elbise', 'image_url' => 'elbise.jpg'],
                    ['name' => 'Bluzlar ve Gömlekler', 'slug' => 'bluz-gomlek', 'image_url' => 'bluz_gomlek.jpg'],
                    ['name' => 'Etekler', 'slug' => 'etek', 'image_url' => 'etek.jpg'],
                    ['name' => 'Pantolonlar', 'slug' => 'pantolon', 'image_url' => 'pantolon.jpg'],
                    ['name' => 'Ceketler ve Kabanlar', 'slug' => 'ceket-kaban', 'image_url' => 'ceket_kaban.jpg'],
                    ['name' => 'İç Giyim', 'slug' => 'ic-giyim', 'image_url' => 'ic_giyim.jpg'],
                    ['name' => 'Spor Giyim', 'slug' => 'spor-giyim', 'image_url' => 'spor_giyim.jpg'],
                    ['name' => 'Abiye Giyim', 'slug' => 'abiye-giyim', 'image_url' => 'abiye_giyim.jpg'],
                    ['name' => 'Hamile Giyim', 'slug' => 'hamile-giyim', 'image_url' => 'hamile_giyim.jpg'],
                    ['name' => 'Büyük Beden Giyim', 'slug' => 'buyuk-beden-giyim', 'image_url' => 'buyuk_beden_giyim.jpg'],
                    ['name' => 'Plaj Giyim', 'slug' => 'plaj-giyim', 'image_url' => 'plaj_giyim.jpg'],
                ]
            ],
            [
                'name' => 'Erkek Giyim',
                'slug' => 'erkek-giyim',
                'image_url' => 'erkek_giyim.jpg',
                'children' => [
                    ['name' => 'Tişörtler ve Polo Yaka Tişörtler', 'slug' => 'tisort-polo', 'image_url' => 'tisort_polo.jpg'],
                    ['name' => 'Gömlekler', 'slug' => 'gomlek', 'image_url' => 'gomlek.jpg'],
                    ['name' => 'Pantolonlar', 'slug' => 'pantolon', 'image_url' => 'pantolon.jpg'],
                    ['name' => 'Takımlar ve Ceketler', 'slug' => 'takim-ceket', 'image_url' => 'takim_ceket.jpg'],
                    ['name' => 'Kabanlar ve Montlar', 'slug' => 'kaban-mont', 'image_url' => 'kaban_mont.jpg'],
                    ['name' => 'İç Giyim', 'slug' => 'ic-giyim', 'image_url' => 'ic_giyim.jpg'],
                    ['name' => 'Spor Giyim', 'slug' => 'spor-giyim', 'image_url' => 'spor_giyim.jpg'],
                    ['name' => 'Kravat ve Papyonlar', 'slug' => 'kravat-papyon', 'image_url' => 'kravat_papyon.jpg'],
                    ['name' => 'Şortlar', 'slug' => 'sort', 'image_url' => 'sort.jpg'],
                ]
            ],
            [
                'name' => 'Çocuk Giyim',
                'slug' => 'cocuk-giyim',
                'image_url' => 'cocuk_giyim.jpg',
                'children' => [
                    [
                        'name' => 'Kız Çocuk Giyim',
                        'slug' => 'kiz-cocuk-giyim',
                        'image_url' => 'kiz_cocuk_giyim.jpg',
                        'children' => [
                            ['name' => 'Elbiseler', 'slug' => 'elbise', 'image_url' => 'elbise.jpg'],
                            ['name' => 'Etekler', 'slug' => 'etek', 'image_url' => 'etek.jpg'],
                            ['name' => 'Bluzlar', 'slug' => 'bluz', 'image_url' => 'bluz.jpg'],
                            ['name' => 'Pantolonlar', 'slug' => 'pantolon', 'image_url' => 'pantolon.jpg'],
                            ['name' => 'Ceketler', 'slug' => 'ceket', 'image_url' => 'ceket.jpg'],
                            ['name' => 'Spor Giyim', 'slug' => 'spor-giyim', 'image_url' => 'spor_giyim.jpg'],
                        ]
                    ],
                    [
                        'name' => 'Erkek Çocuk Giyim',
                        'slug' => 'erkek-cocuk-giyim',
                        'image_url' => 'erkek_cocuk_giyim.jpg',
                        'children' => [
                            ['name' => 'Tişörtler', 'slug' => 'tisort', 'image_url' => 'tisort.jpg'],
                            ['name' => 'Gömlekler', 'slug' => 'gomlek', 'image_url' => 'gomlek.jpg'],
                            ['name' => 'Pantolonlar', 'slug' => 'pantolon', 'image_url' => 'pantolon.jpg'],
                            ['name' => 'Ceketler', 'slug' => 'ceket', 'image_url' => 'ceket.jpg'],
                            ['name' => 'Spor Giyim', 'slug' => 'spor-giyim', 'image_url' => 'spor_giyim.jpg'],
                        ]
                    ],
                    [
                        'name' => 'Bebek Giyim',
                        'slug' => 'bebek-giyim',
                        'image_url' => 'bebek_giyim.jpg',
                        'children' => [
                            ['name' => 'Zıbınlar', 'slug' => 'zibin', 'image_url' => 'zibin.jpg'],
                            ['name' => 'Tulumlar', 'slug' => 'tulum', 'image_url' => 'tulum.jpg'],
                            ['name' => 'Şortlar', 'slug' => 'sort', 'image_url' => 'sort.jpg'],
                            ['name' => 'Body\'ler', 'slug' => 'body', 'image_url' => 'body.jpg'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Aksesuarlar',
                'slug' => 'aksesuarlar',
                'image_url' => 'aksesuarlar.jpg',
                'children' => [
                    ['name' => 'Şapkalar', 'slug' => 'sapka', 'image_url' => 'sapka.jpg'],
                    ['name' => 'Çantalar', 'slug' => 'canta', 'image_url' => 'canta.jpg'],
                    ['name' => 'Ayakkabılar', 'slug' => 'ayakkabi', 'image_url' => 'ayakkabi.jpg'],
                    ['name' => 'Takılar', 'slug' => 'taki', 'image_url' => 'taki.jpg'],
                    ['name' => 'Kemerler', 'slug' => 'kemer', 'image_url' => 'kemer.jpg'],
                    ['name' => 'Eldivenler', 'slug' => 'eldiven', 'image_url' => 'eldiven.jpg'],
                    ['name' => 'Eşarplar ve Şallar', 'slug' => 'esarp-sal', 'image_url' => 'esarp_sal.jpg'],
                    ['name' => 'Güneş Gözlükleri', 'slug' => 'gunes-gozlugu', 'image_url' => 'gunes_gozlugu.jpg'],
                    ['name' => 'Saatler', 'slug' => 'saat', 'image_url' => 'saat.jpg'],
                ]
            ],
            [
                'name' => 'Spor Giyim',
                'slug' => 'spor-giyim',
                'image_url' => 'spor_giyim.jpg',
                'children' => [
                    ['name' => 'Koşu Kıyafetleri', 'slug' => 'kosu-kiyafetleri', 'image_url' => 'kosu_kiyafetleri.jpg'],
                    ['name' => 'Yoga Kıyafetleri', 'slug' => 'yoga-kiyafetleri', 'image_url' => 'yoga_kiyafetleri.jpg'],
                    ['name' => 'Spor Sütyenleri', 'slug' => 'spor-sutyenleri', 'image_url' => 'spor_sutyenleri.jpg'],
                    ['name' => 'Spor Taytları', 'slug' => 'spor-taytlari', 'image_url' => 'spor_taytlari.jpg'],
                    ['name' => 'Spor Ayakkabıları', 'slug' => 'spor-ayakkabilari', 'image_url' => 'spor_ayakkabilari.jpg'],
                    ['name' => 'Eşofman Takımları', 'slug' => 'esofman-takimlari', 'image_url' => 'esofman_takimlari.jpg'],
                    ['name' => 'Spor Çantaları', 'slug' => 'spor-cantalari', 'image_url' => 'spor_cantalari.jpg'],
                ]
            ],
            [
                'name' => 'İç Giyim',
                'slug' => 'ic-giyim',
                'image_url' => 'ic_giyim.jpg',
                'children' => [
                    ['name' => 'Sütyenler', 'slug' => 'sutyen', 'image_url' => 'sutyen.jpg'],
                    ['name' => 'Külotlar', 'slug' => 'kulot', 'image_url' => 'kulot.jpg'],
                    ['name' => 'Atletler', 'slug' => 'atlet', 'image_url' => 'atlet.jpg'],
                    ['name' => 'Çoraplar', 'slug' => 'corap', 'image_url' => 'corap.jpg'],
                    ['name' => 'Termal İç Giyim', 'slug' => 'termal-ic-giyim', 'image_url' => 'termal_ic_giyim.jpg'],
                    ['name' => 'Gecelikler ve Sabahlıklar', 'slug' => 'gecelik-sabahlik', 'image_url' => 'gecelik_sabahlik.jpg'],
                ]
            ],
            [
                'name' => 'Dış Giyim',
                'slug' => 'dis-giyim',
                'image_url' => 'dis_giyim.jpg',
                'children' => [
                    ['name' => 'Montlar', 'slug' => 'mont', 'image_url' => 'mont.jpg'],
                    ['name' => 'Kabanlar', 'slug' => 'kaban', 'image_url' => 'kaban.jpg'],
                    ['name' => 'Yağmurluklar', 'slug' => 'yagmurluk', 'image_url' => 'yagmurluk.jpg'],
                    ['name' => 'Trençkotlar', 'slug' => 'trenckot', 'image_url' => 'trenckot.jpg'],
                    ['name' => 'Yelekler', 'slug' => 'yelek', 'image_url' => 'yelek.jpg'],
                ]
            ],
            [
                'name' => 'Ev Giyim',
                'slug' => 'ev-giyim',
                'image_url' => 'ev_giyim.jpg',
                'children' => [
                    ['name' => 'Pijamalar', 'slug' => 'pijama', 'image_url' => 'pijama.jpg'],
                    ['name' => 'Ev Terlikleri', 'slug' => 'ev-terligi', 'image_url' => 'ev_terligi.jpg'],
                    ['name' => 'Sabahlıklar', 'slug' => 'sabahlik', 'image_url' => 'sabahlik.jpg'],
                    ['name' => 'Ev Etekleri', 'slug' => 'ev-etegi', 'image_url' => 'ev_etegi.jpg'],
                ]
            ],
        ];

        $this->insertCategories($categories);
    }

    private function insertCategories($categories, $parentId = null)
    {
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create(array_merge($categoryData, ['parent_id' => $parentId]));

            if (!empty($children)) {
                $this->insertCategories($children, $category->id);
            }
        }
    }
}
