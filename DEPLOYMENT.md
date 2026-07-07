# Wdrożenie — NFC Pay unified (multi-tenant po hoście)

Jedna instancja Laravela obsługuje wszystkie domeny jednocześnie. Moduł
(bramka/sklep) oraz baza danych wybierane są na
podstawie hosta żądania — patrz `config/tenants.php` i
`app/Http/Middleware/ResolveTenant.php`.

## Mapa hostów (config/tenants.php)

| Host                        | Moduł      | Motyw  | Baza      |
|-----------------------------|------------|--------|-----------|
| pay.please-support-me.com   | gateway    | —      | nfc_pay   |
| please-support-me.com       | storefront | church | nfc_shop1 |

Obie bazy są dostępne przez tego samego użytkownika MySQL `nfc_pay`.

## .env (jeden plik, bez wariantów per-rola)

```bash
cp .env.example .env
# ustaw APP_KEY (php artisan key:generate), DB_PASSWORD (patrz /var/www/pay/pay/.env),
# PAYU_* oraz GATEWAY_URL=https://pay.please-support-me.com
php artisan config:clear
```

`APP_ROLE` / `SHOP_KIND` nie są już używane. `DB_DATABASE` to tylko wartość
startowa — `ResolveTenant` nadpisuje ją per żądanie. `TENANT` jest fallbackiem
wyłącznie dla CLI (artisan, kolejki, harmonogram), np.:

```bash
TENANT=please-support-me.com php artisan db:seed   # seeduje bazę nfc_shop1
# bez TENANT => domyślnie pay.please-support-me.com => baza nfc_pay
```

## Migracje / zmiany schematu (WAŻNE — patrz incydent 2026-07-07)

**Jedno źródło prawdy schematu = migracje Laravel (`artisan migrate`).** Liquibase
w `db/liquibase/` to WYŁĄCZNIE referencja do budowy bazy od zera — **nie uruchamiaj
`liquibase update` na żywych bazach** (są już wyprzedzone przez Laravel migrate;
`databasechangelog` nie zna delt Laravela → konflikt).

Topologia produkcji (łatwo pomylić):
- Żywe bazy = **PostgreSQL** (Cloud SQL `10.60.96.3`): storefront `nfc_shop1`,
  bramka `nfc_pay`. **MariaDB na VM (`sudo mysql ...`) to martwe legacy** — nie mylić.
- `ResolveTenant` podmienia tylko NAZWĘ bazy na domyślnym połączeniu (`pgsql`);
  w CLI wybór bazy przez `TENANT=<host>`.

Deploy zawierający nowe migracje — procedura (krótkie okno):

```bash
# 1. BACKUP właściwej bazy (creds z .env):
pg_dump -h 10.60.96.3 -U nfc_pay nfc_shop1 | gzip > ~/backup_nfc_shop1_$(date +%F-%H%M).sql.gz
# 2. okno serwisowe + pull + migracja TEJ bazy tenanta:
sudo -u www-data php artisan down
sudo git pull --ff-only origin main
#    --path OGRANICZ do modułu! goły `migrate` odpali gateway `create_shop_tables`,
#    które tworzy events/shops/tags i KOLIDUJE z istniejącymi tabelami storefrontu:
sudo -u www-data env TENANT=please-support-me.com php artisan migrate --force \
  --path=app/Modules/Storefront/database/migrations
sudo -u www-data env TENANT=please-support-me.com php artisan migrate --force \
  --path=database/migrations            # bazowe (np. add_handle_to_users)
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan up
```

Migracje MUSZĄ być **PG-safe**: unik zbudowany w PostgreSQL to INDEKS, więc w
migracjach używaj `$table->dropIndex('nazwa_unique')`, **nie** `dropUnique([...])`
(to drugie generuje `DROP CONSTRAINT` → `SQLSTATE 42704` → migracja wpół-zastosowana
→ nowy kod rzuca 500). Rollback kodu przy 500: `sudo git reset --hard <poprzedni>`
+ `optimize:clear`.

## nginx — jeden server block

```nginx
server {
    listen 443 ssl http2;
    server_name pay.please-support-me.com please-support-me.com www.please-support-me.com;

    root /var/www/support-me/public;
    index index.php;

    # ssl_certificate / ssl_certificate_key — wg posiadanych certyfikatów
    # (SAN dla please-support-me.com i pay.please-support-me.com).

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

PHP-FPM przekazuje nagłówek Host, więc `request()->getHost()` zwraca właściwą
domenę i `ResolveTenant` dobiera moduł/motyw/bazę. Jeden docroot, jeden deploy,
jeden `.env`.

## Deploy w skrócie

```bash
cd /var/www/support-me            # prod to checkout git (repo jest własnością root → sudo)
sudo git pull --ff-only origin main
# UWAGA: jeśli pull przyniósł nowe pliki migracji → NIE wystarczy ten skrót;
#        wykonaj procedurę z sekcji „Migracje / zmiany schematu" (backup + scoped migrate).
composer install --no-dev --optimize-autoloader   # tylko gdy zmieniły się zależności
sudo -u www-data php8.2 artisan config:clear && sudo -u www-data php8.2 artisan view:clear && sudo -u www-data php8.2 artisan route:clear
# config:cache / route:cache są bezpieczne — ResolveTenant nadpisuje config()
# w runtime, a TENANT przy buforowaniu trafia do domyślnego tenanta CLI.
# (opcjonalnie) restart php-fpm / kolejek
```

Uwaga CLI: jeśli używasz `config:cache`, pamiętaj, że `TENANT` jest wówczas
„zamrożony" jako domyślny tenant. Dla seedów/migracji konkretnej bazy podawaj
`TENANT=...` jawnie i czyść cache, lub trzymaj `TENANT` w środowisku procesu CLI.
