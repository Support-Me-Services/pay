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

    protected $description = 'Wzbogaca potential_parishes o telefon, miasto i adres z OSM (Overpass) / Nominatim / źródeł teleadresowych (deon.pl, schematyzmy, Wikipedia) — dopasowanie po name + zaokrąglone lat/lon, a gdy to nie trafi: geo-fallback (najbliższa parafia <60 m, haversine). UPDATE tylko pustych pól (COALESCE). Nie tworzy duplikatów, nie rusza statusu/notatki/handlowca.';

    public function handle(): int
    {
        $rows = $this->loadRows();
        if ($rows === null) {
            return self::FAILURE;
        }

        $this->info('DB: ' . config('database.connections.mysql.database') . ' — rekordów wejściowych: ' . count($rows));

        $dry = (bool) $this->option('dry-run');

        $matched = 0;
        $geoMatched = 0;
        $phoneFilled = 0;
        $cityFilled = 0;
        $addressFilled = 0;
        $noMatch = 0;
        $processed = 0;

        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $latRaw = $r['lat'] ?? null;
            $lonRaw = $r['lon'] ?? null;
            $phone = $this->nullableStr($r['phone'] ?? null);
            $city = $this->nullableStr($r['city'] ?? null);
            $address = $this->nullableStr($r['address'] ?? null);

            $processed++;

            // Bez nazwy/współrzędnych nie dopasujemy. Bez phone, city i address — nic do zrobienia.
            if ($name === '' || ! is_numeric($latRaw) || ! is_numeric($lonRaw)) {
                continue;
            }
            if ($phone === null && $city === null && $address === null) {
                continue;
            }

            $lat = round((float) $latRaw, 7);
            $lon = round((float) $lonRaw, 7);

            // Klucz dopasowania jak w ImportParishes: name + ROUND(lat,5)+ROUND(lon,5).
            $existing = PotentialParish::where('name', $name)
                ->whereRaw('ROUND(lat, 5) = ?', [round($lat, 5)])
                ->whereRaw('ROUND(lon, 5) = ?', [round($lon, 5)])
                ->first();

            // Geo-fallback: nazwy/współrzędne potrafią się minimalnie różnić.
            // Gdy klucz dokładny nie trafi, bierzemy NAJBLIŻSZĄ parafię w promieniu
            // <60 m (haversine). Bounding-box prefiltr (~78 m) ogranicza zapytanie.
            if (! $existing) {
                $existing = $this->geoFallback($lat, $lon);
                if ($existing) {
                    $geoMatched++;
                }
            }

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
            // address: COALESCE — nie nadpisuj istniejącego niepustego adresu.
            if ($address !== null && ($existing->address === null || trim((string) $existing->address) === '')) {
                $update['address'] = $address;
                $addressFilled++;
            }

            if ($update && ! $dry) {
                // Bezpośredni UPDATE — NIE dotykamy status/salesperson_id/note/called_at.
                DB::table('potential_parishes')->where('id', $existing->id)->update($update);
            }

            if ($processed % 2000 === 0) {
                $this->info("Przetworzono {$processed}… (dopasowane: {$matched}, geo: {$geoMatched}, phone: {$phoneFilled}, city: {$cityFilled}, address: {$addressFilled})");
            }
        }

        $tag = $dry ? ' [DRY-RUN — nic nie zapisano]' : '';
        $this->info("Gotowe{$tag}. Wejście: {$processed}, dopasowane do bazy: {$matched} (w tym geo-fallback <60 m: {$geoMatched}), bez dopasowania: {$noMatch}.");
        $this->info("Uzupełniono telefonów: {$phoneFilled}, miast: {$cityFilled}, adresów: {$addressFilled}.");

        $totalPhone = PotentialParish::whereNotNull('phone')->where('phone', '!=', '')->count();
        $totalCity = PotentialParish::whereNotNull('city')->where('city', '!=', '')->count();
        $totalAddress = PotentialParish::whereNotNull('address')->where('address', '!=', '')->count();
        $this->info("Stan tabeli — z telefonem: {$totalPhone}, z miastem: {$totalCity}, z adresem: {$totalAddress}, łącznie: " . PotentialParish::count() . '.');

        return self::SUCCESS;
    }

    /**
     * Geo-fallback: najbliższa parafia w promieniu <60 m (haversine), bez wymogu zgodności nazwy.
     * Bounding-box prefiltr (~78 m / 0.0007°) ogranicza skan, potem dokładny haversine.
     */
    private function geoFallback(float $lat, float $lon): ?PotentialParish
    {
        $dLat = 0.0007;                                  // ~78 m N-S
        $dLon = 0.0007 / max(cos(deg2rad($lat)), 0.01);  // ~78 m E-W na danej szerokości

        $candidates = PotentialParish::whereBetween('lat', [$lat - $dLat, $lat + $dLat])
            ->whereBetween('lon', [$lon - $dLon, $lon + $dLon])
            ->get(['id', 'name', 'city', 'address', 'phone', 'lat', 'lon']);

        $best = null;
        $bestDist = 60.0; // metry
        foreach ($candidates as $c) {
            $d = $this->haversine($lat, $lon, (float) $c->lat, (float) $c->lon);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $c;
            }
        }

        return $best;
    }

    /** Odległość haversine w metrach. */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6_371_000.0;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $dp = deg2rad($lat2 - $lat1);
        $dl = deg2rad($lon2 - $lon1);
        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
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
                'address'     => isset($cols['address']) ? ($row[$cols['address']] ?? null) : null,
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
