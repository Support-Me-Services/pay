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

# schemat sklepu (storefront -> baza nfc_shop1 wg TENANT z .env).
# Migrujemy tylko ścieżkę storefront: moduł gateway ma osobną bazę (nfc_pay)
# i w prod schemat budowany jest Liquibase, nie artisanem — patrz LOCAL.md.
php artisan migrate --path=app/Modules/Storefront/database/migrations --force || true

echo "[entrypoint] gotowe -> http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
