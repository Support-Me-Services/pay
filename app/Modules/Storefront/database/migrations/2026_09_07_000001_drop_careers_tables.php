<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 4 migracji Organization -> org-svc: JobPosition/JobApplication
 * przeniesione całkowicie (dane + CRUD) do org-svc. Dane zweryfikowane 1:1
 * w org-svc PRZED tym dropem. job_applications najpierw (FK do job_positions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_positions');
    }

    public function down(): void
    {
        // Nieodwracalne — dane żyją teraz wyłącznie w org-svc.
    }
};
