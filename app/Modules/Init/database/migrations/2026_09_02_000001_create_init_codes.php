<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kody inicjalizacji kontaktu (tag NFC / kod QR) — refaktor dawnego
 * ShopItem::tag_uid + /t/{tag_uid} do osobnego modułu Init.
 *
 * WAŻNE: tag i QR to ten sam identyfikator/ten sam byt — /init/tag/{uuid} i
 * /init/qr/{uuid} to dwa RÓWNOLEGŁE adresy do TEGO SAMEGO kodu (kanał
 * fizyczny, tylko informacyjnie dla analityki), nie dwa różne typy rekordu.
 *
 * Cel przekierowania jest ZAWSZE dynamiczny — to sedno systemu: ten sam
 * fizyczny tag/QR (ten sam uuid) może w dowolnym momencie zacząć prowadzić
 * gdzie indziej, bo zmienia się tylko wartość w tej tabeli, odczytywana na
 * świeżo przy każdym skanie.
 *
 * Właściciel kodu to DOKŁADNIE JEDNO z dwóch (pilnowane w kontrolerach, nie
 * DB constraintem — brak w tym repo precedensu na CHECK):
 *  - organization_id: kod należący do organizacji, zarządzany w jej panelu,
 *    celem jest konkretny produkt (shop_item_id).
 *  - owner_user_id: kod osobisty użytkownika (konta), zarządzany przez niego
 *    w osobnej sekcji "Moje tagi", celem jest cała lista zbiórek jednej z
 *    JEGO organizacji (target_organization_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('init_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('label')->nullable();
            // Cel — dokładnie jedno z dwóch, zależnie od właściciela (patrz wyżej).
            $table->foreignId('shop_item_id')->nullable()->constrained('shop_items')->nullOnDelete();
            $table->foreignId('target_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('organization_id');
            $table->index('owner_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('init_codes');
    }
};
