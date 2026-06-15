<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Kategorie wsparcia (sekcja „Kogo wspieramy?"). Idempotentnie po slug —
 * te same 6 pozycji co dawniej w StorefrontController::CATEGORIES.
 *
 * Uruchom dla bazy church:
 *   TENANT=shop1.redai.pl php artisan db:seed --class=Database\\Seeders\\CategoriesSeeder
 */
class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'organizacje-spoleczne',          'label' => 'Organizacje społeczne',          'label_html' => 'Organizacje społeczne',                     'label_text' => 'Organizacje społeczne',                 'intro' => 'Inicjatywy, które realnie zmieniają lokalne społeczności.',                 'source' => 'none',     'position' => 0],
            ['slug' => 'fundacje-i-stowarzyszenia',       'label' => 'Fundacje i stowarzyszenia',      'label_html' => 'Fundacje <br>i stowarzyszenia',             'label_text' => 'Fundacje i stowarzyszenia',             'intro' => 'Organizacje pożytku publicznego działające na rzecz innych.',              'source' => 'none',     'position' => 1],
            ['slug' => 'wspolnoty-lokalne',               'label' => 'Wspólnoty lokalne',              'label_html' => 'Wspólnoty lokalne',                         'label_text' => 'Wspólnoty lokalne',                     'intro' => 'Sąsiedzkie i lokalne inicjatywy blisko Ciebie.',                            'source' => 'none',     'position' => 2],
            ['slug' => 'miejsca-kultu',                   'label' => 'Miejsca kultu i organizacje religijne', 'label_html' => 'Miejsca kultu <br>i organizacje religijne', 'label_text' => 'Miejsca kultu i organizacje religijne', 'intro' => 'Parafie i wspólnoty religijne, które wspierasz jednym zbliżeniem telefonu.', 'source' => 'parishes', 'position' => 3],
            ['slug' => 'inicjatywy-charytatywne',         'label' => 'Inicjatywy charytatywne',        'label_html' => 'Inicjatywy charytatywne',                   'label_text' => 'Inicjatywy charytatywne',               'intro' => 'Zbiórki i akcje pomocowe wymagające szybkiego wsparcia.',                  'source' => 'none',     'position' => 4],
            ['slug' => 'projekty-spoleczne-i-edukacyjne', 'label' => 'Projekty społeczne i edukacyjne', 'label_html' => 'Projekty społeczne <br>i edukacyjne',      'label_text' => 'Projekty społeczne i edukacyjne',       'intro' => 'Edukacja, kultura i projekty rozwijające społeczeństwo.',                  'source' => 'none',     'position' => 5],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'parent_id' => null,
                    'label' => $data['label'],
                    'label_html' => $data['label_html'],
                    'label_text' => $data['label_text'],
                    'intro' => $data['intro'],
                    'source' => $data['source'],
                    'position' => $data['position'],
                    'active' => true,
                ]
            );
        }
    }
}
