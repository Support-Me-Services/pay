<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\BeneficiaryNode;
use App\Modules\Storefront\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Dane demo funkcji „Wspieramy":
 *  1) węzły podstrony /beneficiaries — 5 parafii pobranych na żywo z publicznych
 *     stron produkcyjnych /p/{slug} (nazwa, opis, grafika 1:1),
 *  2) kafelki sekcji „Kogo wspieramy?" na stronie głównej — 6 kategorii, każda ze
 *     źródłem `beneficiaries` (każdy kafelek prowadzi na /beneficiaries), z ikonami z prod.
 *
 * Uruchomienie: TENANT=please-support-me.com php artisan db:seed --class="Database\Seeders\BeneficiariesSeeder"
 */
class BeneficiariesSeeder extends Seeder
{
    private const BASE = 'https://please-support-me.com';

    private const SLUGS = [
        'bazylika-mariacka-krakow',
        'archikatedra-sw-jana-chrzciciela-warszawa',
        'kosciol-pokoju-swidnica',
        'bazylika-sw-elzbiety-wroclaw',
        'sanktuarium-matki-bozej-lichen-stary',
    ];

    /** 6 kategorii „Kogo wspieramy?" (jak CategoriesSeeder) — tu wszystkie -> /beneficiaries. */
    private const CATEGORIES = [
        ['slug' => 'organizacje-spoleczne',           'label' => 'Organizacje społeczne',                 'label_html' => 'Organizacje społeczne',                     'intro' => 'Inicjatywy, które realnie zmieniają lokalne społeczności.'],
        ['slug' => 'fundacje-i-stowarzyszenia',       'label' => 'Fundacje i stowarzyszenia',             'label_html' => 'Fundacje <br>i stowarzyszenia',             'intro' => 'Organizacje pożytku publicznego działające na rzecz innych.'],
        ['slug' => 'wspolnoty-lokalne',               'label' => 'Wspólnoty lokalne',                     'label_html' => 'Wspólnoty lokalne',                         'intro' => 'Sąsiedzkie i lokalne inicjatywy blisko Ciebie.'],
        ['slug' => 'miejsca-kultu',                   'label' => 'Miejsca kultu i organizacje religijne', 'label_html' => 'Miejsca kultu <br>i organizacje religijne', 'intro' => 'Parafie i wspólnoty religijne, które wspierasz jednym zbliżeniem telefonu.'],
        ['slug' => 'inicjatywy-charytatywne',         'label' => 'Inicjatywy charytatywne',               'label_html' => 'Inicjatywy charytatywne',                   'intro' => 'Zbiórki i akcje pomocowe wymagające szybkiego wsparcia.'],
        ['slug' => 'projekty-spoleczne-i-edukacyjne', 'label' => 'Projekty społeczne i edukacyjne',       'label_html' => 'Projekty społeczne <br>i edukacyjne',       'intro' => 'Edukacja, kultura i projekty rozwijające społeczeństwo.'],
    ];

    public function run(): void
    {
        $this->seedNodes();
        $this->seedCategories();
    }

    /** Węzły podstrony /beneficiaries — 5 parafii z prod. */
    private function seedNodes(): void
    {
        BeneficiaryNode::truncate();

        foreach (self::SLUGS as $i => $slug) {
            $html = Http::get(self::BASE . '/p/' . $slug)->body();

            $heading = trim(html_entity_decode(strip_tags($this->match('/sp-give__title">(.*?)<\/h2>/s', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $body = trim($this->match('/<div class="sp-give__lead">(.*?)<\/div>/s', $html));
            $imgUrl = $this->match('/sp-circle sp-circle--lg"><img src="([^"]+)"/', $html);

            if ($heading === '' || $imgUrl === '') {
                $this->command?->warn("Pominięto {$slug} — nie udało się odczytać danych.");
                continue;
            }

            $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $path = sprintf('beneficiaries/parafia-%d.%s', $i + 1, $ext);
            Storage::disk('public')->put($path, Http::get($imgUrl)->body());

            BeneficiaryNode::create([
                'heading' => $heading,
                'image' => $path,
                'image_side' => $i % 2 === 0 ? 'left' : 'right',
                'image_scale' => 100,
                'image_x' => 0,
                'image_y' => 0,
                'text_align' => 'left',
                'body_html' => $body,
                'position' => $i,
                'active' => true,
            ]);

            $this->command?->info("Węzeł: {$heading}");
        }
    }

    /** Kafelki „Kogo wspieramy?" — 6 kategorii, wszystkie prowadzą na /beneficiaries. */
    private function seedCategories(): void
    {
        Category::where('slug', 'parafie')->delete(); // sprzątanie po ewentualnej testowej

        foreach (self::CATEGORIES as $i => $c) {
            $attrs = [
                'parent_id' => null,
                'label' => $c['label'],
                'label_html' => $c['label_html'],
                'label_text' => $c['label'],
                'intro' => $c['intro'],
                'source' => 'beneficiaries', // każdy kafelek -> podstrona „Wspieramy"
                'position' => $i,
                'active' => true,
            ];

            $resp = Http::get(self::BASE . '/storage/category-icons/' . $c['slug'] . '.jpg');
            if ($resp->ok()) {
                $iconPath = 'category-icons/' . $c['slug'] . '.jpg';
                Storage::disk('public')->put($iconPath, $resp->body());
                $attrs['icon'] = $iconPath;
            }

            Category::updateOrCreate(['slug' => $c['slug']], $attrs);
            $this->command?->info("Kafelek: {$c['label']}");
        }
    }

    private function match(string $pattern, string $subject): string
    {
        return preg_match($pattern, $subject, $m) ? $m[1] : '';
    }
}
