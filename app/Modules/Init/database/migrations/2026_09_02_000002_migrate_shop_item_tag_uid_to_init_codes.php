<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migracja danych: każdy ShopItem z ustawionym tag_uid dostaje odpowiadający
 * mu wiersz w init_codes (kind=tag). Świeży UUID — stary tag_uid to surowy
 * UID sprzętowy NFC (np. "04A1B2C3D4"), nie format UUID, i tak czy inaczej
 * nie ma jeszcze żadnych fizycznie wydanych tagów do zachowania (potwierdzone
 * z użytkownikiem) — kolumna tag_uid znika w kolejnej migracji.
 */
return new class extends Migration
{
    public function up(): void
    {
        $items = DB::table('shop_items')->whereNotNull('tag_uid')->get(['id', 'organization_id']);

        $now = now();
        foreach ($items as $item) {
            DB::table('init_codes')->insert([
                'organization_id' => $item->organization_id,
                'uuid' => (string) Str::uuid(),
                'shop_item_id' => $item->id,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Nieodwracalne — patrz create_init_codes (down usuwa całą tabelę).
    }
};
