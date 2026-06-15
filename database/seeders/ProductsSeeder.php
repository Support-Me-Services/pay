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

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'm@suli.pl'],
            ['name' => 'Michał', 'password' => Hash::make('pay3322')]
        );

        $shopNo = config('shop.id') === 'shop2' ? 2 : 1;

        foreach ($this->products($shopNo) as $i => $data) {
            $svg = $this->placeholderSvg($data['name'], $data['emoji'], $data['color']);
            $path = sprintf('products/seed/produkt-%d.svg', $i + 1);
            Storage::disk('public')->put($path, $svg);

            $product = Product::updateOrCreate(
                ['tag_uid' => $data['tag_uid']],
                [
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'description_html' => $data['description_html'],
                    'pickup_instruction' => $data['pickup_instruction'],
                    'price' => $data['price'],
                    'main_image' => $path,
                    'active' => true,
                ]
            );

            $this->seedDemoStats($product);
        }
    }

    private function products(int $shopNo): array
    {
        $uid = fn (int $n) => sprintf('TAG-S%d-%03d', $shopNo, $n);

        return [
            [
                'tag_uid' => $uid(1),
                'name' => 'Lody rzemieślnicze pistacjowe 500 ml',
                'price' => 2400,
                'emoji' => '🍦',
                'color' => '#93C572',
                'description_html' => '<p>Prawdziwe lody rzemieślnicze na bazie sycylijskich pistacji, wytwarzane w małych partiach z mleka od lokalnego dostawcy. Bez sztucznych barwników, aromatów i utwardzanych tłuszczów — tylko pistacje, mleko, śmietanka i cukier.</p><p>Opakowanie 500 ml wystarcza dla 3–4 osób. Lody przechowywane są w temperaturze -18°C w zamrażarce samoobsługowej tuż obok tego taga.</p><p>Po opłaceniu po prostu otwórz zamrażarkę i poczęstuj się — paragon znajdziesz na ekranie potwierdzenia.</p>',
                'pickup_instruction' => 'Otwórz zamrażarkę i wyjmij jedno opakowanie z półki oznaczonej PISTACJA.',
            ],
            [
                'tag_uid' => $uid(2),
                'name' => 'Miód wielokwiatowy 0,9 kg, prosto z pasieki',
                'price' => 4500,
                'emoji' => '🍯',
                'color' => '#E8A33D',
                'description_html' => '<p>Miód wielokwiatowy z pasieki położonej na skraju Puszczy Kampinoskiej. Pszczoły zbierają nektar z łąk kwietnych, rzepaku i lip — miód ma głęboki, lekko korzenny smak i naturalnie krystalizuje po kilku tygodniach.</p><p>Słoik 0,9 kg, zakręcany, z datą rozlewu na etykiecie. Miód nie był podgrzewany powyżej 40°C, więc zachowuje wszystkie enzymy i właściwości odżywcze.</p><p>Kupujesz bezpośrednio od pszczelarza — bez pośredników, bez marży sieci handlowych.</p>',
                'pickup_instruction' => 'Zabierz jeden słoik z regału. Dziękujemy!',
            ],
            [
                'tag_uid' => $uid(3),
                'name' => 'Bukiet kwiatów sezonowych',
                'price' => 3900,
                'emoji' => '💐',
                'color' => '#D672A8',
                'description_html' => '<p>Ręcznie wiązany bukiet z kwiatów sezonowych — skład zmienia się w zależności od dostaw z lokalnych upraw: latem piwonie i lwie paszcze, jesienią dalie i astry. Każdy bukiet jest inny.</p><p>Kwiaty cięte tego samego dnia rano, stoją w wiaderkach z wodą w stojaku obok. Bukiet utrzymuje świeżość 5–7 dni przy regularnej wymianie wody.</p><p>Idealny na ostatnią chwilę — prezent, przeprosiny albo po prostu dla siebie.</p>',
                'pickup_instruction' => 'Wybierz jeden bukiet z wiaderka i strząśnij wodę z łodyg.',
            ],
            [
                'tag_uid' => $uid(4),
                'name' => 'Powerbank 10 000 mAh na lince zabezpieczającej',
                'price' => 8900,
                'emoji' => '🔋',
                'color' => '#4A6FA5',
                'description_html' => '<p>Powerbank 10 000 mAh z szybkim ładowaniem 22,5 W (USB-C Power Delivery + USB-A). Naładuje smartfon dwukrotnie, a wbudowane kable Lightning i USB-C oznaczają, że nie potrzebujesz nic więcej.</p><p>Urządzenie jest zabezpieczone linką z elektrozaczepem. Po opłaceniu zakupu blokada zwalnia się automatycznie — usłyszysz ciche kliknięcie.</p><p>W zestawie: powerbank, instrukcja, 24 miesiące gwarancji producenta. Paragon elektroniczny na ekranie potwierdzenia.</p>',
                'pickup_instruction' => 'Linka zabezpieczająca została zwolniona. Pociągnij delikatnie powerbank do siebie i zabierz go ze stojaka.',
            ],
            [
                'tag_uid' => $uid(5),
                'name' => 'Parasol automatyczny STORM',
                'price' => 5900,
                'emoji' => '☂️',
                'color' => '#37474F',
                'description_html' => '<p>Parasol automatyczny STORM z włóknem szklanym w konstrukcji — wytrzymuje porywy wiatru do 80 km/h bez wywinięcia czaszy. Otwieranie i zamykanie jednym przyciskiem.</p><p>Czasza 105 cm z powłoką teflonową schnie w kilka minut. Rączka pokryta miękką gumą, pokrowiec w zestawie.</p><p>Stojak z parasolami znajduje się przy wejściu — po opłaceniu blokada linki zwalnia się, a ekran wskaże numer Twojego stanowiska.</p>',
                'pickup_instruction' => 'Blokada zwolniona — wyjmij parasol ze stojaka nr wskazanego na ekranie.',
            ],
        ];
    }

    /**
     * Prosty placeholder SVG z nazwą produktu — bez pobierania z internetu.
     */
    private function placeholderSvg(string $name, string $emoji, string $color): string
    {
        // Neutralny, jasny placeholder (à la zdjęcie packshot) — bez kolorowych teł.
        $label = htmlspecialchars(mb_strlen($name) > 34 ? mb_substr($name, 0, 33) . '…' : $name, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <rect width="800" height="800" fill="#F6F6F6"/>
  <text x="400" y="420" font-size="230" text-anchor="middle">{$emoji}</text>
  <text x="400" y="600" font-family="Inter, Arial, sans-serif" font-size="30" font-weight="600"
        fill="#8a8a8a" text-anchor="middle">{$label}</text>
</svg>
SVG;
    }

    /**
     * Losowe eventy i opłacone zamówienia za 30 dni — dashboard nie może być
     * pusty na demo.
     */
    private function seedDemoStats(Product $product): void
    {
        if ($product->events()->where('type', 'tag_open')->exists()) {
            return; // już zasiane
        }

        for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
            $day = Carbon::now()->subDays($daysAgo);
            $opens = random_int(2, 12);

            for ($o = 0; $o < $opens; $o++) {
                $at = $day->copy()->setTime(random_int(8, 20), random_int(0, 59), random_int(0, 59));

                Event::create(['product_id' => $product->id, 'type' => 'tag_open', 'created_at' => $at]);
                Event::create(['product_id' => $product->id, 'type' => 'page_view', 'created_at' => $at->copy()->addSeconds(2)]);

                $roll = random_int(1, 100);
                if ($roll <= 32) {
                    Event::create(['product_id' => $product->id, 'type' => 'buy_click', 'created_at' => $at->copy()->addSeconds(random_int(20, 90))]);

                    if ($roll <= 24) { // zakup zakończony sukcesem
                        $paidAt = $at->copy()->addSeconds(random_int(60, 180));
                        Order::create([
                            'product_id' => $product->id,
                            'transaction_id' => (string) Str::uuid(),
                            'amount' => $product->price,
                            'status' => 'paid',
                            'paid_at' => $paidAt,
                            'created_at' => $at->copy()->addSeconds(random_int(30, 60)),
                        ]);
                        Event::create(['product_id' => $product->id, 'type' => 'purchase', 'created_at' => $paidAt]);
                    }
                }
            }
        }
    }
}
