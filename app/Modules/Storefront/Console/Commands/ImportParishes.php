<?php

namespace App\Modules\Storefront\Console\Commands;

use App\Modules\Storefront\Models\PotentialParish;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportParishes extends Command
{
    protected $signature = 'parishes:import
        {--file= : Ścieżka do pliku CSV (domyślnie storage/app/parishes/parishes.csv)}';

    protected $description = 'Importuje dataset parafii (OSM) do tabeli potential_parishes — idempotentnie (name + lat/lon)';

    public function handle(): int
    {
        $path = $this->option('file') ?: storage_path('app/parishes/parishes.csv');

        if (! is_file($path)) {
            $this->error("Nie znaleziono pliku CSV: {$path}");

            return self::FAILURE;
        }

        $this->info("DB: " . config('database.connections.mysql.database') . " — plik: {$path}");

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Nie można otworzyć pliku: {$path}");

            return self::FAILURE;
        }

        // Nagłówek -> mapa kolumna => indeks (odporna na kolejność kolumn).
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('Pusty plik CSV.');

            return self::FAILURE;
        }
        $header = array_map(fn ($h) => trim((string) $h), $header);
        $cols = array_flip($header);

        foreach (['name', 'lat', 'lon'] as $required) {
            if (! isset($cols[$required])) {
                fclose($handle);
                $this->error("Brak wymaganej kolumny '{$required}' w nagłówku CSV.");

                return self::FAILURE;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim((string) ($row[$cols['name']] ?? ''));
            $latRaw = $row[$cols['lat']] ?? null;
            $lonRaw = $row[$cols['lon']] ?? null;

            // Pomijamy wiersze bez nazwy lub bez współrzędnych (klucz idempotencji).
            if ($name === '' || ! is_numeric($latRaw) || ! is_numeric($lonRaw)) {
                $skipped++;
                continue;
            }

            $lat = round((float) $latRaw, 7);
            $lon = round((float) $lonRaw, 7);

            // Klucz idempotencji: name + lat/lon zaokrąglone do 5 miejsc (~1 m),
            // żeby drobne różnice formatu nie tworzyły duplikatów.
            $existing = PotentialParish::where('name', $name)
                ->whereRaw('ROUND(lat, 5) = ?', [round($lat, 5)])
                ->whereRaw('ROUND(lon, 5) = ?', [round($lon, 5)])
                ->first();

            $payload = [
                'name'         => $name,
                'city'         => $this->nullableStr($row[$cols['city']] ?? null),
                'voivodeship'  => $this->nullableStr($row[$cols['voivodeship']] ?? null),
                'denomination' => $this->nullableStr($row[$cols['denomination']] ?? null),
                'lat'          => $lat,
                'lon'          => $lon,
            ];

            if ($existing) {
                // Aktualizujemy tylko dane referencyjne — NIE nadpisujemy stanu CRM
                // (status, salesperson_id, note, called_at), żeby import nie kasował pracy handlowców.
                $existing->fill($payload)->save();
                $updated++;
            } else {
                PotentialParish::create($payload);
                $created++;
            }

            $processed++;
            if ($processed % 2000 === 0) {
                $this->info("Przetworzono {$processed} wierszy… (nowe: {$created}, zaktualizowane: {$updated})");
            }
        }

        fclose($handle);

        $total = PotentialParish::count();

        $this->info("Gotowe. Przetworzono: {$processed}, nowe: {$created}, zaktualizowane: {$updated}, pominięte: {$skipped}.");
        $this->info("Łącznie w potential_parishes: {$total}.");

        return self::SUCCESS;
    }

    private function nullableStr(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }
}
