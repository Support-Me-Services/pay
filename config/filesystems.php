<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // 'serve' WYŁĄCZONE: framework auto-rejestruje trasę
            // `GET storage/{path}` do serwowania TEGO dysku, kolidującą
            // 1:1 z naszą własną `routes/web.php` (proxy dysku 'public' do
            // GCS pod tym samym URL-em, patrz komentarz tam) — złapane na
            // żywo: nasza trasa nigdy nie była osiągalna, framework
            // przechwytywał żądanie pierwszy. Dysk 'local' (prywatne CV) i
            // tak nigdy nie był serwowany tą generyczną trasą — pobieranie
            // idzie przez dedykowaną, kontrolowaną logikę w
            // ApplicationController (autoryzacja, nie goły URL).
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            // Faza 8 — produkcja na Kubernetes ma wiele podów bez trwałego
            // dysku (i docelowo wiele generacji naraz przy blue-green) —
            // pliki wgrywane przez panel (zdjęcia produktów, "O nas") MUSZĄ
            // żyć poza podem. `FILESYSTEM_PUBLIC_DRIVER=gcs` w produkcji,
            // `local` (bez zmian) wszędzie indziej — sam kod (Storage::disk(
            // 'public')) nigdzie się nie zmienia, tylko backend tego dysku.
            'driver' => env('FILESYSTEM_PUBLIC_DRIVER', 'local'),
            // 'root' TYLKO dla drivera 'local' — pakiet spatie/laravel-google-
            // cloud-storage używa TEGO SAMEGO klucza 'root' jako prefiksu
            // ścieżki w buckecie, jeśli jest ustawiony (patrz jego
            // GoogleCloudStorageServiceProvider::prepareConfig() — 'root' ma
            // pierwszeństwo nad 'path_prefix', ustawianym tylko gdy 'root'
            // BRAK). Zostawienie tu lokalnej ścieżki dyskowej cicho
            // przekierowywało prefiks GCS na bezsensowne "/app/storage/app/
            // public" zamiast "public" — złapane na żywo: exists()/allFiles()
            // zawsze zwracały pusto, bez żadnego wyjątku (throw:false to
            // maskowało). Klucz istnieje TYLKO gdy driver to 'local'.
            ...(env('FILESYSTEM_PUBLIC_DRIVER', 'local') === 'local' ? ['root' => storage_path('app/public')] : []),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
            // Klucze specyficzne dla drivera 'gcs' (spatie/laravel-google-
            // cloud-storage) — ignorowane przez driver 'local'. Autoryzacja
            // przez Workload Identity (Application Default Credentials w
            // podzie), NIE plik key.json — zero statycznego sekretu do
            // wycieku/rotacji.
            'project_id' => env('GCS_PROJECT_ID'),
            'bucket' => env('GCS_BUCKET'),
            'path_prefix' => env('GCS_PATH_PREFIX', 'public'),
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
