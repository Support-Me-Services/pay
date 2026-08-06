<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\BeneficiaryNode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Dane demo funkcji „Wspieramy" — węzły podstrony /beneficiaries (i sekcji
 * „Kogo wspieramy?" na stronie głównej, renderowanej z tych samych węzłów):
 * 5 parafii pobranych na żywo z publicznych stron produkcyjnych /p/{slug}
 * (nazwa, opis, grafika 1:1).
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

    public function run(): void
    {
        $this->seedNodes();
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

    private function match(string $pattern, string $subject): string
    {
        return preg_match($pattern, $subject, $m) ? $m[1] : '';
    }
}
