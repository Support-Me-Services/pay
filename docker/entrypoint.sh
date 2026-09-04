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

# schemat bazowy (tabela users — handle, is_admin, enabled_sections) MUSI
# wejść PRZED storefront: storefront ma migrację (add_user_id_to_shop_items)
# odpytującą tabelę users. Osobna ścieżka, bo nie leży w module Storefront
# (patrz DEPLOYMENT.md: prod migruje ją też osobnym poleceniem).
php artisan migrate --path=database/migrations --force || true

# storefront + init (tagi NFC/kody QR) RAZEM, jedno wywołanie z dwoma
# --path — migracje obu modułów są chronologicznie POPRZEPLATANE (Init ma
# FK do organizations ze storefrontu; storefront dopiero PÓŹNIEJ usuwa
# shop_items.tag_uid, który wcześniejsza migracja Init kopiuje do
# init_codes) — osobne wywołania per moduł migrowały je w złej kolejności
# względem siebie (zadziałało tylko na bazie z historią z wcześniejszych
# sesji, wywaliło się na 100% świeżej — patrz Faza 5.5). Wiele flag --path
# w jednym `migrate` scala i sortuje pliki chronologicznie, tak jak trzeba.
# W prod schemat budowany jest Liquibase, nie artisanem — patrz LOCAL.md.
php artisan migrate \
    --path=app/Modules/Storefront/database/migrations \
    --path=app/Modules/Init/database/migrations \
    --force || true

# schemat bazowy MUSI wejść też do nfc_pay (Gateway) — dotąd wchodził
# WYŁĄCZNIE do domyślnego TENANT-a ze .env (nfc_shop1/Storefront), bo
# powyższe wywołanie nie ma TENANT= i nfc_pay nigdy nie dostawało tabeli
# users/sessions/cache/jobs z tej ścieżki (Faza 6 to wykryła: keycloak_sub
# nie miał gdzie wejść w bazie bramki). Gateway ma dziś WŁASNY panel/login
# z tabelą users, więc to nie jest nowa potrzeba, tylko wcześniej ukryta luka.
TENANT=pay.please-support-me.com php artisan migrate --path=database/migrations --force || true

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

# PoC Fazy 1 — serwer gRPC gateway-svc (RoadRunner). Dotąd uruchamiany
# ręcznie po każdym starcie kontenera (docker exec, patrz claude/marcin) —
# od Kubernetesa (Faza 5.5) samowystarczalny, żeby pod nie wymagał ręcznej
# interwencji za każdym razem. protoc jest już w obrazie (Dockerfile);
# rr/protoc-gen-php-grpc i wygenerowane klasy PHP lądują na wolumenie vendor/
# repo (bind-mount) — jednorazowo, potem pomijane jak reszta setupu wyżej.
[ -f rr ] || { echo "[entrypoint] pobieram binarkę rr..."; php vendor/bin/rr get; }
[ -f protoc-gen-php-grpc ] || { echo "[entrypoint] pobieram protoc-gen-php-grpc..."; php vendor/bin/rr download-protoc-binary; }

if [ ! -f app/Modules/Gateway/Grpc/Generated/Pay/Health/V1/HealthCheckServiceInterface.php ] \
    || [ ! -f app/Modules/Gateway/Grpc/Generated/Pay/Storefront/V1/StorefrontServiceInterface.php ]; then
    echo "[entrypoint] generuję klasy PHP z proto/..."
    mkdir -p app/Modules/Gateway/Grpc/Generated
    protoc --proto_path=proto --php_out=app/Modules/Gateway/Grpc/Generated \
        --php-grpc_out=app/Modules/Gateway/Grpc/Generated \
        --plugin=protoc-gen-php-grpc=./protoc-gen-php-grpc \
        proto/health/v1/health.proto proto/storefront/v1/storefront.proto
    composer dump-autoload -o
fi

echo "[entrypoint] startuję serwer gRPC (rr serve) w tle na :9091..."
./rr serve -c .rr.yaml > storage/logs/rr.log 2>&1 &

echo "[entrypoint] gotowe -> http://localhost:8000"
# --no-reload + PHP_CLI_SERVER_WORKERS (patrz .env.docker): bez tego serwer
# obsluguje jedno polaczenie naraz i self-call sklepu do wlasnego API bramki
# (flow "Wesprzyj") blokuje sie na 10s (timeout GatewayClient) zamiast pokazac mock.
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
