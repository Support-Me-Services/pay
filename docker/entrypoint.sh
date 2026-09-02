#!/bin/sh
# Samowystarczalny start lokalny: .env, zależności, klucz, schemat, serwer.
set -e
cd /app

# .env z szablonu, jeśli brak
[ -f .env ] || { echo "[entrypoint] tworzę .env z .env.docker"; cp .env.docker .env; }

# zależności do wolumenu vendor (jednorazowo — potem pomijane)
[ -f vendor/autoload.php ] || { echo "[entrypoint] composer install..."; composer install --no-interaction --prefer-dist --no-progress; }

# klucz aplikacji, jeśli pusty
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

# katalogi runtime (na wolumenie storage)
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# symlink public/storage -> storage/app/public (bez niego grafiki wgrane
# przez panel — produkty, węzły "O nas" — nie są publicznie dostępne, 404)
[ -L public/storage ] || php artisan storage:link

# moduł Init (tagi NFC / kody QR) — PRZED storefront: migracja danych Init
# kopiuje shop_items.tag_uid do init_codes, a migracja storefront dopiero
# potem tę kolumnę usuwa.
php artisan migrate --path=app/Modules/Init/database/migrations --force || true

# schemat sklepu (storefront -> baza nfc_shop1 wg TENANT z .env).
# Migrujemy tylko ścieżkę storefront: w prod schemat budowany jest Liquibase,
# nie artisanem — patrz LOCAL.md.
php artisan migrate --path=app/Modules/Storefront/database/migrations --force || true

# schemat bazowy (tabela users — handle, is_admin, enabled_sections). Osobna
# ścieżka, bo NIE leży w module Storefront (patrz DEPLOYMENT.md: prod migruje
# ją też osobnym poleceniem, --path=database/migrations).
php artisan migrate --path=database/migrations --force || true

# schemat bramki (Gateway -> baza nfc_pay) + sklep "shops" z kluczem API —
# potrzebne do pełnego mock-flow płatności lokalnie (Wesprzyj -> ekran testowy
# PayU zamiast 401). TENANT tylko na czas migracji (przełącza domyślną bazę);
# model Shop i tak zawsze czyta/pisze przez połączenie 'gateway' (nfc_pay).
TENANT=pay.please-support-me.com php artisan migrate --path=app/Modules/Gateway/database/migrations --force || true
php artisan tinker --execute="
\App\Modules\Gateway\Models\Shop::updateOrCreate(
    ['slug' => 'local-church'],
    ['name' => 'Parafia Żbików (lokalnie)', 'base_url' => 'http://localhost:8000', 'api_key' => env('GATEWAY_API_KEY_CHURCH'), 'payment_mode' => 'classic']
);
" || true

echo "[entrypoint] gotowe -> http://localhost:8000"
# --no-reload + PHP_CLI_SERVER_WORKERS (patrz .env.docker): bez tego serwer
# obsluguje jedno polaczenie naraz i self-call sklepu do wlasnego API bramki
# (flow "Wesprzyj") blokuje sie na 10s (timeout GatewayClient) zamiast pokazac mock.
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
