<?php

namespace Database\Seeders;

use App\Modules\Gateway\Models\AntitheftCheck;
use App\Modules\Gateway\Models\Event;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GatewaySeeder extends Seeder
{
    // Identyczne z produktami seedowanymi w sklepach (id = product_external_id)
    private const PRODUCTS = [
        1 => ['name' => 'Lody rzemieślnicze pistacjowe 500 ml', 'price' => 2400],
        2 => ['name' => 'Miód wielokwiatowy 0,9 kg, prosto z pasieki', 'price' => 4500],
        3 => ['name' => 'Bukiet kwiatów sezonowych', 'price' => 3900],
        4 => ['name' => 'Powerbank 10 000 mAh na lince zabezpieczającej', 'price' => 8900],
        5 => ['name' => 'Parasol automatyczny STORM', 'price' => 5900],
    ];

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'm@suli.pl'],
            ['name' => 'Michał', 'password' => Hash::make('pay3322')]
        );

        $shops = [
            ['slug' => 'shop1', 'name' => 'Sklep Demo 1 (classic)', 'base_url' => 'https://please-support-me.com', 'payment_mode' => 'classic'],
        ];

        foreach ($shops as $i => $shopData) {
            $shop = Shop::updateOrCreate(
                ['slug' => $shopData['slug']],
                [...$shopData, 'api_key' => Shop::where('slug', $shopData['slug'])->value('api_key') ?? Shop::generateApiKey()]
            );

            $shopNo = $i + 1;
            $tags = [];
            foreach (self::PRODUCTS as $productId => $product) {
                $uid = sprintf('TAG-S%d-%03d', $shopNo, $productId);
                $tags[$productId] = Tag::updateOrCreate(
                    ['tag_uid' => $uid],
                    [
                        'shop_id' => $shop->id,
                        'target_url' => $shop->base_url . '/t/' . $uid,
                        'label' => $product['name'],
                        'active' => true,
                    ]
                );
            }

            $this->seedDemoStats($shop, $tags);

            AntitheftCheck::create([
                'shop_id' => $shop->id,
                'status' => 'ok', // FIKCYJNE — moduł demo, brak realnej detekcji
                'foreign_tags_found' => 0,
                'checked_at' => now()->subMinutes(random_int(5, 45)),
            ]);
        }
    }

    /**
     * Losowe eventy i opłacone transakcje za ostatnie 30 dni,
     * żeby dashboardy i wykresy nie były puste na demo.
     */
    private function seedDemoStats(Shop $shop, array $tags): void
    {
        if ($shop->events()->where('type', 'tag_open')->exists()) {
            return; // już zasiane — nie duplikuj przy ponownym db:seed
        }

        for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
            $day = Carbon::now()->subDays($daysAgo);

            foreach (self::PRODUCTS as $productId => $product) {
                $tag = $tags[$productId];
                $opens = random_int(2, 12);

                for ($o = 0; $o < $opens; $o++) {
                    $at = $day->copy()->setTime(random_int(8, 20), random_int(0, 59), random_int(0, 59));

                    Event::create([
                        'shop_id' => $shop->id,
                        'tag_id' => $tag->id,
                        'type' => 'tag_open',
                        'created_at' => $at,
                    ]);

                    $roll = random_int(1, 100);
                    if ($roll <= 30) { // ~30% otwarć przechodzi do płatności
                        $startedAt = $at->copy()->addSeconds(random_int(20, 120));
                        $paid = $roll <= 24; // z tego ~80% kończy sukcesem

                        $tx = Transaction::create([
                            'id' => (string) Str::uuid(),
                            'shop_id' => $shop->id,
                            'tag_id' => $tag->id,
                            'product_external_id' => (string) $productId,
                            'product_name' => $product['name'],
                            'amount' => $product['price'],
                            'currency' => 'PLN',
                            'status' => $paid ? 'paid' : 'failed',
                            'mode' => $shop->payment_mode,
                            'return_url' => $shop->base_url . '/zwrot/' . Str::uuid(),
                            'notify_url' => $shop->base_url . '/webhooks/gateway',
                            'paid_at' => $paid ? $startedAt->copy()->addSeconds(random_int(15, 90)) : null,
                            'created_at' => $startedAt,
                        ]);

                        Event::create([
                            'shop_id' => $shop->id,
                            'tag_id' => $tag->id,
                            'transaction_id' => $tx->id,
                            'type' => 'payment_started',
                            'created_at' => $startedAt,
                        ]);
                        Event::create([
                            'shop_id' => $shop->id,
                            'tag_id' => $tag->id,
                            'transaction_id' => $tx->id,
                            'type' => $paid ? 'payment_success' : 'payment_failed',
                            'created_at' => $startedAt->copy()->addSeconds(random_int(15, 90)),
                        ]);
                    }
                }
            }
        }
    }
}
