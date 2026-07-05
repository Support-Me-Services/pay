# Uruchomienie lokalne (Docker)

Aplikacja startuje w kontenerach (**PHP 8.2 + MySQL**) — bez instalowania PHP ani
MySQL na komputerze. Wymagany tylko **Docker Desktop**.

## Szybki start

```bash
cd docker
docker compose up -d
```

Pierwszy start pobiera obrazy, instaluje zależności (composer), tworzy `.env`,
generuje klucz aplikacji i migruje schemat sklepu — potrwa to 1–3 min.
Podgląd postępu:

```bash
docker compose logs -f app
```

Gdy w logu pojawi się `gotowe -> http://localhost:8000`, otwórz w przeglądarce:

**http://localhost:8000**

## Co dzieje się automatycznie (entrypoint)

- `.env` tworzony z `.env.docker` (jeśli nie istnieje),
- `composer install` do wolumenu `vendor` (jednorazowo),
- `php artisan key:generate` (jeśli klucz pusty),
- migracje modułu storefront → baza `nfc_shop1` (produkty sklepu są zaseedowane),
- serwer `php artisan serve` na porcie 8000.

`localhost` jest zmapowany na sklep w `config/tenants.php`, więc wchodzisz od razu
na sklep. Płatności działają w trybie **bypass** (klik „Kupuję i płacę" → ekran
podziękowania, bez realnej bramki PayU).

## Przydatne komendy

```bash
docker compose logs -f app          # logi aplikacji
docker compose exec app php artisan migrate --path=app/Modules/Storefront/database/migrations --force
docker compose exec app php artisan tinker
docker compose exec db mysql -uroot -proot nfc_shop1     # konsola bazy
docker compose down                 # zatrzymaj
docker compose down -v              # zatrzymaj + usuń bazę i wolumeny (twardy reset)
```

## Uwagi

- **Wydajność (Windows/macOS):** `vendor/` i `storage/` celowo są na wolumenach
  Dockera, nie na bind-mount. Inaczej każde żądanie czyta tysiące plików z wolnego
  mostu plików i trwa kilkadziesiąt sekund/minut.
- **Migracje:** migrowana jest tylko ścieżka `storefront`. Moduły `gateway`
  i `storefront` definiują osobno tabelę `events`, więc pełne `php artisan migrate`
  na jednej bazie skończy się kolizją. W produkcji schemat budowany jest **Liquibase**
  (`db/liquibase/`), nie artisanem — patrz `DEPLOYMENT.md`.
- Konfiguracja jest w `docker/` (`Dockerfile`, `docker-compose.yml`, `entrypoint.sh`,
  `init.sql`) oraz w szablonie `.env.docker`.
