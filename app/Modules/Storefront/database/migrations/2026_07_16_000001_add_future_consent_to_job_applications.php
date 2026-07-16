<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // Nieobowiązkowa zgoda na przetwarzanie danych na potrzeby PRZYSZŁYCH
            // procesów rekrutacyjnych (ważna 24 miesiące od dnia udzielenia).
            // future_recruitment_consent_at = data udzielenia zgody (NULL = brak zgody).
            $table->boolean('future_recruitment_consent')->default(false)->after('status');
            $table->timestamp('future_recruitment_consent_at')->nullable()->after('future_recruitment_consent');
            $table->index('future_recruitment_consent');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // dropIndex (nie dropUnique) — zgodność z PostgreSQL.
            $table->dropIndex(['future_recruitment_consent']);
            $table->dropColumn(['future_recruitment_consent', 'future_recruitment_consent_at']);
        });
    }
};
