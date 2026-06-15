# Wdrożenie — NFC Pay unified (multi-tenant po hoście)

Jedna instancja Laravela obsługuje wszystkie domeny jednocześnie. Moduł
(bramka/sklep), motyw (products/church) oraz baza danych wybierane są na
podstawie hosta żądania — patrz `config/tenants.php` i
`app/Http/Middleware/ResolveTenant.php`.

## Mapa hostów (config/tenants.php)

| Host                    | Moduł      | Motyw    | Baza       |
|-------------------------|------------|----------|------------|
| pay.redai.pl            | gateway    | —        | nfc_pay    |
| shop2.redai.pl          | storefront | products | nfc_shop2  |
| shop1.redai.pl          | storefront | church   | nfc_shop1  |
| please-support-me.com   | storefront | church   | nfc_shop1  |

Wszystkie trzy bazy są dostępne przez tego samego użytkownika MySQL `nfc_pay`.

## .env (jeden plik, bez wariantów per-rola)

```bash
cp .env.example .env
# ustaw APP_KEY (php artisan key:generate), DB_PASSWORD (patrz /var/www/pay/pay/.env),
# PAYU_* oraz GATEWAY_URL=https://pay.redai.pl
php artisan config:clear
```

`APP_ROLE` / `SHOP_KIND` nie są już używane. `DB_DATABASE` to tylko wartość
startowa — `ResolveTenant` nadpisuje ją per żądanie. `TENANT` jest fallbackiem
wyłącznie dla CLI (artisan, kolejki, harmonogram), np.:

```bash
TENANT=shop1.redai.pl php artisan db:seed   # seeduje bazę nfc_shop1
TENANT=shop2.redai.pl php artisan migrate    # migruje bazę nfc_shop2
# bez TENANT => domyślnie pay.redai.pl => baza nfc_pay
```

Istniejące bazy mają już tabele — NIE uruchamiaj `migrate` na produkcji bez
potrzeby.

## nginx — jeden server block

```nginx
server {
    listen 443 ssl http2;
    server_name pay.redai.pl shop1.redai.pl shop2.redai.pl please-support-me.com;

    root /var/www/pay/unified/public;
    index index.php;

    # ssl_certificate / ssl_certificate_key — wg posiadanych certyfikatów
    # (osobne/SAN dla redai.pl i please-support-me.com).

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
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
cd /var/www/pay/unified
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan view:clear
# config:cache / route:cache są bezpieczne — ResolveTenant nadpisuje config()
# w runtime, a TENANT przy buforowaniu trafia do domyślnego tenanta CLI.
# (opcjonalnie) restart php-fpm / kolejek
```

Uwaga CLI: jeśli używasz `config:cache`, pamiętaj, że `TENANT` jest wówczas
„zamrożony" jako domyślny tenant. Dla seedów/migracji konkretnej bazy podawaj
`TENANT=...` jawnie i czyść cache, lub trzymaj `TENANT` w środowisku procesu CLI.
