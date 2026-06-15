<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\Event;
use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'm@suli.pl'],
            ['name' => 'Michał', 'password' => Hash::make('pay3322')]
        );

        foreach ($this->parishes() as $i => $data) {
            $svg = $this->stainedGlassSvg($data['palette']);
            $path = sprintf('parishes/parafia-%d.svg', $i + 1);
            Storage::disk('public')->put($path, $svg);

            $product = Product::updateOrCreate(
                ['tag_uid' => $data['tag_uid']],
                [
                    'name' => $data['name'],
                    'city' => $data['city'],
                    'purpose' => $data['purpose'],
                    'slug' => Str::slug($data['name'] . ' ' . $data['city']),
                    'description_html' => $data['description_html'],
                    'pickup_instruction' => null,
                    'price' => 2000, // sugerowane 20 zł (domyślnie zaznaczone)
                    'main_image' => $path,
                    'active' => true,
                ]
            );

            $this->seedDemoStats($product);
        }
    }

    /**
     * 5 znanych polskich kościołów — reużywają tagi NFC TAG-S1-001..005.
     */
    private function parishes(): array
    {
        return [
            [
                'tag_uid' => 'TAG-S1-001',
                'name' => 'Bazylika Mariacka',
                'city' => 'Kraków',
                'purpose' => 'Renowacja ołtarza Wita Stwosza',
                'palette' => ['#7a1f2b', '#c8a24c', '#1e3a5f'], // czerwień · złoto · błękit
                'description_html' => '<p>Kościół Wniebowzięcia Najświętszej Maryi Panny — serce krakowskiego Rynku, z którego wieży co godzinę rozbrzmiewa hejnał. Wewnątrz znajduje się największy gotycki ołtarz w Europie, dzieło Wita Stwosza z XV wieku.</p><p>Zbieramy środki na bieżącą konserwację polichromii i złoceń ołtarza. Każda złożona taca pomaga zachować to dziedzictwo dla kolejnych pokoleń.</p>',
            ],
            [
                'tag_uid' => 'TAG-S1-002',
                'name' => 'Archikatedra św. Jana Chrzciciela',
                'city' => 'Warszawa',
                'purpose' => 'Konserwacja organów',
                'palette' => ['#1e3a5f', '#c8a24c', '#3f6e5a'], // granat · złoto · zieleń
                'description_html' => '<p>Najstarszy kościół Starego Miasta w Warszawie, miejsce koronacji i pochówków książąt mazowieckich. Odbudowana po zniszczeniach wojennych archikatedra jest wpisana na Listę UNESCO.</p><p>Wsparcie przeznaczamy na konserwację zabytkowych organów oraz utrzymanie świątyni otwartej dla wiernych i pielgrzymów.</p>',
            ],
            [
                'tag_uid' => 'TAG-S1-003',
                'name' => 'Kościół Pokoju',
                'city' => 'Świdnica',
                'purpose' => 'Ratujemy zabytkowy strop',
                'palette' => ['#3f6e5a', '#c8a24c', '#7a4a1f'], // zieleń · złoto · brąz
                'description_html' => '<p>Ewangelicki Kościół Pokoju pw. Trójcy Świętej — największa drewniana świątynia barokowa w Europie, wzniesiona z drewna, gliny i słomy. Obiekt wpisany na Listę światowego dziedzictwa UNESCO.</p><p>Drewniana konstrukcja wymaga nieustannej opieki. Twoja taca wspiera prace ratunkowe przy zabytkowym stropie i bogato zdobionych emporach.</p>',
            ],
            [
                'tag_uid' => 'TAG-S1-004',
                'name' => 'Bazylika św. Elżbiety',
                'city' => 'Wrocław',
                'purpose' => 'Odnowa wieży widokowej',
                'palette' => ['#5a2d6e', '#c8a24c', '#1e3a5f'], // fiolet · złoto · granat
                'description_html' => '<p>Gotycka bazylika mniejsza w sercu Wrocławia, z jedną z najwyższych kościelnych wież w Polsce (91,5 m) i tarasem widokowym otwartym dla zwiedzających.</p><p>Środki przeznaczamy na renowację konstrukcji wieży i bezpieczeństwo tarasu widokowego, z którego rozciąga się panorama całego miasta.</p>',
            ],
            [
                'tag_uid' => 'TAG-S1-005',
                'name' => 'Sanktuarium Matki Bożej',
                'city' => 'Licheń Stary',
                'purpose' => 'Utrzymanie świątyni',
                'palette' => ['#1f5a6e', '#c8a24c', '#7a1f2b'], // turkus · złoto · czerwień
                'description_html' => '<p>Bazylika Najświętszej Maryi Panny Licheńskiej — jeden z największych kościołów w Polsce i Europie, cel licznych pielgrzymek. Imponująca świątynia otoczona parkiem i kalwarią.</p><p>Utrzymanie tak ogromnego obiektu to codzienne wyzwanie. Złożona przez Ciebie taca pomaga pokryć koszty ogrzewania, sprzątania i opieki nad pielgrzymami.</p>',
            ],
        ];
    }

    /**
     * Procedualny witraż w łuku gotyckim — bez pobierania z internetu.
     * $palette = [główny, złoto/akcent, drugorzędny].
     */
    private function stainedGlassSvg(array $palette): string
    {
        [$c1, $gold, $c2] = $palette;
        $w = 1000;
        $h = 680;

        // Siatka "szkła" w obrębie łuku — naprzemienne tony palety.
        $tones = [$c1, $c2, $gold, $this->tint($c1, 28), $this->tint($c2, 22)];
        $cells = '';
        $cols = 5;
        $rows = 6;
        $gw = 520;            // szerokość okna
        $gx = ($w - $gw) / 2; // 240
        $cellW = $gw / $cols;
        $top = 150;
        $cellH = 70;
        for ($r = 0; $r < $rows; $r++) {
            for ($col = 0; $col < $cols; $col++) {
                $idx = ($r * 7 + $col * 3) % count($tones);
                $fill = $tones[$idx];
                $x = $gx + $col * $cellW;
                $y = $top + $r * $cellH;
                $cells .= sprintf(
                    '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s" stroke="#0d1015" stroke-width="3" rx="2"/>',
                    $x, $y, $cellW, $cellH, $fill
                );
            }
        }

        $cx = $w / 2;          // 500
        $archTopY = 70;        // wierzchołek łuku
        $shoulderY = 150;      // początek pionowych boków
        $leftX = $gx;          // 240
        $rightX = $gx + $gw;   // 760

        // Łuk ostrowy (clip dla siatki + obrys mosiężny)
        $archPath = sprintf(
            'M%1$.0f %2$.0f L%1$.0f %3$.0f Q%1$.0f %4$.0f %5$.0f %4$.0f Q%6$.0f %4$.0f %6$.0f %3$.0f L%6$.0f %2$.0f',
            $leftX, $top + $rows * $cellH, $shoulderY, $archTopY, $cx, $rightX
        );

        // Rozeta (rose window) w wierzchołku
        $rose = '';
        $rcx = $cx; $rcy = 122; $rr = 46;
        $rose .= sprintf('<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="#0d1015" stroke-width="4"/>', $rcx, $rcy, $rr, $this->tint($gold, 10));
        for ($p = 0; $p < 8; $p++) {
            $ang = $p * M_PI / 4;
            $px = $rcx + cos($ang) * 24;
            $py = $rcy + sin($ang) * 24;
            $petalFill = $p % 2 ? $c1 : $c2;
            $rose .= sprintf('<circle cx="%.1f" cy="%.1f" r="13" fill="%s" stroke="#0d1015" stroke-width="2.5"/>', $px, $py, $petalFill);
        }
        $rose .= sprintf('<circle cx="%d" cy="%d" r="10" fill="%s"/>', $rcx, $rcy, $this->tint($gold, 30));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="stone" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#2b3242"/>
      <stop offset="1" stop-color="#11151e"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="42%" r="55%">
      <stop offset="0" stop-color="{$gold}" stop-opacity="0.45"/>
      <stop offset="0.6" stop-color="{$gold}" stop-opacity="0.08"/>
      <stop offset="1" stop-color="{$gold}" stop-opacity="0"/>
    </radialGradient>
    <clipPath id="arch"><path d="{$archPath} Z"/></clipPath>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#stone)"/>
  <rect width="{$w}" height="{$h}" fill="url(#glow)"/>
  <g clip-path="url(#arch)">{$cells}</g>
  <path d="{$archPath}" fill="none" stroke="{$gold}" stroke-width="7"/>
  {$rose}
</svg>
SVG;
    }

    /** Rozjaśnia kolor hex o podany procent (0–100). */
    private function tint(string $hex, int $pct): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $mix = fn ($c) => (int) round($c + (255 - $c) * ($pct / 100));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /**
     * Losowe eventy i opłacone tace za 30 dni — dashboard nie może być pusty na demo.
     */
    private function seedDemoStats(Product $product): void
    {
        if ($product->events()->where('type', 'tag_open')->exists()) {
            return; // już zasiane
        }

        $amounts = [1000, 2000, 2000, 2000, 5000, 10000]; // realny rozrzut tac (grosze)

        for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
            $day = Carbon::now()->subDays($daysAgo);
            $opens = random_int(2, 12);

            for ($o = 0; $o < $opens; $o++) {
                $at = $day->copy()->setTime(random_int(8, 20), random_int(0, 59), random_int(0, 59));

                Event::create(['product_id' => $product->id, 'type' => 'tag_open', 'created_at' => $at]);
                Event::create(['product_id' => $product->id, 'type' => 'page_view', 'created_at' => $at->copy()->addSeconds(2)]);

                $roll = random_int(1, 100);
                if ($roll <= 38) {
                    Event::create(['product_id' => $product->id, 'type' => 'buy_click', 'created_at' => $at->copy()->addSeconds(random_int(15, 70))]);

                    if ($roll <= 30) { // wpłata zakończona sukcesem
                        $paidAt = $at->copy()->addSeconds(random_int(40, 140));
                        Order::create([
                            'product_id' => $product->id,
                            'transaction_id' => (string) Str::uuid(),
                            'amount' => $amounts[array_rand($amounts)],
                            'status' => 'paid',
                            'paid_at' => $paidAt,
                            'created_at' => $at->copy()->addSeconds(random_int(25, 55)),
                        ]);
                        Event::create(['product_id' => $product->id, 'type' => 'purchase', 'created_at' => $paidAt]);
                    }
                }
            }
        }
    }
}
