#!/bin/sh
# Produkcyjny entrypoint — w odróżnieniu od docker/entrypoint.sh (lokalny dev/
# ephemeral): BEZ auto-migracji (schemat zarządzany przez Liquibase, patrz
# .claude/plans/fluffy-frolicking-galaxy.md) i BEZ seedowania demo danych
# (Shop::updateOrCreate) — to by dotykało prawdziwej bazy produkcyjnej.
# Konfiguracja WYŁĄCZNIE przez zmienne środowiskowe kontenera (bez .env —
# Laravel Dotenv::safeLoad() nie wymaga pliku, gdy zmienne już są w env procesu).
set -e
cd /app

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

[ -L public/storage ] || php artisan storage:link

# rr/protoc-gen-php-grpc i wygenerowane klasy PHP z proto/ są już wypieczone
# w obrazie (docker/Dockerfile.prod) — nic do zrobienia tutaj.

echo "[entrypoint.prod] startuję serwer gRPC (rr serve) w tle na :9091..."
./rr serve -c .rr.yaml > storage/logs/rr.log 2>&1 &

echo "[entrypoint.prod] gotowe -> :8000"
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
