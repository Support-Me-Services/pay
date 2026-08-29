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
na sklep.

- Koszyk (`/user/{handle}/koszyk`) i darowizna na `/` działają w trybie **bypass**
  (`PAYMENT_BYPASS=true`) — klik od razu kończy się ekranem podziękowania, bez
  przechodzenia przez bramkę.
- „Wesprzyj" na stronie parafii/produktu (`/p/{slug}`) NIE ma tego bypassu —
  idzie przez prawdziwy moduł Gateway (`MockProvider`), więc pokazuje pełny
  symulator PayU pod `/mockpay/{uuid}`: „Zapłać" (dowolny 6-cyfrowy kod → sukces
  po 2 s) albo „Symuluj odmowę" (→ ekran `ReturnFailure`). Umożliwia to
  `entrypoint.sh`, który przy starcie migruje schemat Gateway (baza `nfc_pay`) i
  zakłada sklep testowy (`Shop` z kluczem z `GATEWAY_API_KEY_CHURCH`, patrz
  `.env.docker`).
- **Uwaga:** ten flow robi wewnętrzne wywołanie HTTP sklepu do własnego API
  bramki (self-call). `php artisan serve` domyślnie obsługuje jedno połączenie
  naraz i taki self-call blokowałby się na 10 s (timeout `GatewayClient`) —
  dlatego `entrypoint.sh` uruchamia serwer z `--no-reload` i
  `PHP_CLI_SERVER_WORKERS=4` (wymaga rozszerzenia `pcntl` w obrazie).

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
