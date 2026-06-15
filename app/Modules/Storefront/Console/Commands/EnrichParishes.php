<?php

namespace App\Modules\Storefront\Console\Commands;

use App\Modules\Storefront\Models\PotentialParish;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrichParishes extends Command
{
    protected $signature = 'parishes:enrich
        {--file= : Ścieżka do enriched CSV (domyślnie storage/app/parishes/parishes-enriched.csv)}
        {--json= : Alternatywnie: ścieżka do enriched JSON (np. /tmp/parishes2/enriched.json)}
        {--dry-run : Tylko policz dopasowania, nie zapisuj}';

    protected $description = 'Wzbogaca potential_parishes o telefon i miasto z OSM (Overpass) — dopasowanie po name + zaokrąglone lat/lon, UPDATE tylko pustych pól (COALESCE). Nie tworzy duplikatów, nie rusza statusu/notatki/handlowca.';

    public function handle(): int
    {
        $rows = $this->loadRows();
        if ($rows === null) {
            return self::FAILURE;
        }

        $this->info('DB: ' . config('database.connections.mysql.database') . ' — rekordów wejściowych: ' . count($rows));

        $dry = (bool) $this->option('dry-run');

        $matched = 0;
        $phoneFilled = 0;
        $cityFilled = 0;
        $noMatch = 0;
        $processed = 0;

        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $latRaw = $r['lat'] ?? null;
            $lonRaw = $r['lon'] ?? null;
            $phone = $this->nullableStr($r['phone'] ?? null);
            $city = $this->nullableStr($r['city'] ?? null);

            $processed++;

            // Bez nazwy/współrzędnych nie dopasujemy. Bez phone i bez city — nic do zrobienia.
            if ($name === '' || ! is_numeric($latRaw) || ! is_numeric($lonRaw)) {
                continue;
            }
            if ($phone === null && $city === null) {
                continue;
            }

            $lat = round((float) $latRaw, 7);
            $lon = round((float) $lonRaw, 7);

            // Klucz dopasowania jak w ImportParishes: name + ROUND(lat,5)+ROUND(lon,5).
            $existing = PotentialParish::where('name', $name)
                ->whereRaw('ROUND(lat, 5) = ?', [round($lat, 5)])
                ->whereRaw('ROUND(lon, 5) = ?', [round($lon, 5)])
                ->first();

            if (! $existing) {
                $noMatch++;
                continue;
            }

            $matched++;
            $update = [];

            // phone: uzupełnij tylko gdy w bazie puste.
            if ($phone !== null && ($existing->phone === null || trim((string) $existing->phone) === '')) {
                $update['phone'] = $phone;
                $phoneFilled++;
            }
            // city: COALESCE — nie nadpisuj istniejącego niepustego miasta.
            if ($city !== null && ($existing->city === null || trim((string) $existing->city) === '')) {
                $update['city'] = $city;
                $cityFilled++;
            }

            if ($update && ! $dry) {
                // Bezpośredni UPDATE — NIE dotykamy status/salesperson_id/note/called_at.
                DB::table('potential_parishes')->where('id', $existing->id)->update($update);
            }

            if ($processed % 2000 === 0) {
                $this->info("Przetworzono {$processed}… (dopasowane: {$matched}, phone: {$phoneFilled}, city: {$cityFilled})");
            }
        }

        $tag = $dry ? ' [DRY-RUN — nic nie zapisano]' : '';
        $this->info("Gotowe{$tag}. Wejście: {$processed}, dopasowane do bazy: {$matched}, bez dopasowania: {$noMatch}.");
        $this->info("Uzupełniono telefonów: {$phoneFilled}, miast: {$cityFilled}.");

        $totalPhone = PotentialParish::whereNotNull('phone')->where('phone', '!=', '')->count();
        $totalCity = PotentialParish::whereNotNull('city')->where('city', '!=', '')->count();
        $this->info("Stan tabeli — z telefonem: {$totalPhone}, z miastem: {$totalCity}, łącznie: " . PotentialParish::count() . '.');

        return self::SUCCESS;
    }

    /** Wczytuje rekordy z JSON (--json) albo CSV (--file). Zwraca tablicę asocjacyjną lub null. */
    private function loadRows(): ?array
    {
        $jsonPath = $this->option('json');
        if ($jsonPath) {
            if (! is_file($jsonPath)) {
                $this->error("Nie znaleziono pliku JSON: {$jsonPath}");

                return null;
            }
            $data = json_decode((string) file_get_contents($jsonPath), true);
            if (! is_array($data)) {
                $this->error('Nieprawidłowy JSON.');

                return null;
            }

            return $data;
        }

        $path = $this->option('file') ?: storage_path('app/parishes/parishes-enriched.csv');
        if (! is_file($path)) {
            $this->error("Nie znaleziono pliku CSV: {$path} (podaj --file lub --json).");

            return null;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Nie można otworzyć pliku: {$path}");

            return null;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('Pusty plik CSV.');

            return null;
        }
        $header = array_map(fn ($h) => trim((string) $h), $header);
        $cols = array_flip($header);

        foreach (['name', 'lat', 'lon'] as $req) {
            if (! isset($cols[$req])) {
                fclose($handle);
                $this->error("Brak wymaganej kolumny '{$req}' w nagłówku CSV.");

                return null;
            }
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = [
                'name'        => $row[$cols['name']] ?? null,
                'city'        => isset($cols['city']) ? ($row[$cols['city']] ?? null) : null,
                'voivodeship' => isset($cols['voivodeship']) ? ($row[$cols['voivodeship']] ?? null) : null,
                'phone'       => isset($cols['phone']) ? ($row[$cols['phone']] ?? null) : null,
                'lat'         => $row[$cols['lat']] ?? null,
                'lon'         => $row[$cols['lon']] ?? null,
            ];
        }
        fclose($handle);

        return $rows;
    }

    private function nullableStr(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }
}
