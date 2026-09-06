<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Trasy roli (gateway / storefront) ładowane są w odpowiednim
// ServiceProviderze modułu zależnie od APP_ROLE (config/platform.php).
// Ten plik celowo pozostaje minimalny.

// Faza 8 — dysk 'public' w produkcji to GCS (prywatny, zgodnie z polityką
// organizacji GCP blokującą allUsers — patrz .claude/plans/
// fluffy-frolicking-galaxy.md), nie lokalny symlink `public/storage`. Cała
// aplikacja generuje URL-e obrazków przez `asset('storage/'.$path)` w
// kilkunastu miejscach (nie przez Storage::url()) — zamiast zmieniać każde
// wywołanie, ta trasa PRZEJMUJE dokładnie tę samą ścieżkę URL i strumieniuje
// plik z aktywnego dysku 'public', niezależnie od jego backendu (local/gcs).
Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('storage.proxy');
