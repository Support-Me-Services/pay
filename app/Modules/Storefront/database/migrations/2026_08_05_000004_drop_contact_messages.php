<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usunięcie formularza kontaktowego (/kontakt) i panelu „Wiadomości" —
     * cały moduł ContactMessage/ContactController/MessageController.
     */
    public function up(): void
    {
        Schema::dropIfExists('contact_messages');
    }

    public function down(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
