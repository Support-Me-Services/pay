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

Istniejące bazy mają już tabele — NIE uruchamiaj `migrate` na produkcji bez
potrzeby.

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
composer install --no-dev --optimize-autoloader   # tylko gdy zmieniły się zależności
sudo -u www-data php8.2 artisan config:clear && sudo -u www-data php8.2 artisan view:clear && sudo -u www-data php8.2 artisan route:clear
# config:cache / route:cache są bezpieczne — ResolveTenant nadpisuje config()
# w runtime, a TENANT przy buforowaniu trafia do domyślnego tenanta CLI.
# (opcjonalnie) restart php-fpm / kolejek
```

Uwaga CLI: jeśli używasz `config:cache`, pamiętaj, że `TENANT` jest wówczas
„zamrożony" jako domyślny tenant. Dla seedów/migracji konkretnej bazy podawaj
`TENANT=...` jawnie i czyść cache, lub trzymaj `TENANT` w środowisku procesu CLI.
