<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Definiowalna treść podziękowania per produkt (sekcja „Zbiórki") — pokazywana
 * w modalu na /main zamiast domyślnego, sztywnego tekstu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->string('thank_you_heading')->nullable()->after('description');
            $table->text('thank_you_body')->nullable()->after('thank_you_heading');
            $table->string('thank_you_image')->nullable()->after('thank_you_body');
            $table->string('mecenas_name')->nullable()->after('thank_you_image');
            $table->string('mecenas_url')->nullable()->after('mecenas_name');
            $table->string('mecenas_logo')->nullable()->after('mecenas_url');
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn(['thank_you_heading', 'thank_you_body', 'thank_you_image', 'mecenas_name', 'mecenas_url', 'mecenas_logo']);
        });
    }
};
