<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zgłoszenia rekrutacyjne (aplikacje na oferty pracy) wraz z CV.
        // job_position_id = NULL oznacza aplikację ogólną / spontaniczną.
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_position_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();          // list motywacyjny
            $table->string('cv_path')->nullable();         // ścieżka pliku CV (prywatny dysk)
            $table->string('cv_original_name')->nullable(); // oryginalna nazwa pliku CV
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('job_position_id')
                ->references('id')->on('job_positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
