#!/usr/bin/env bash
#
# Deploy pay/SupportME — multi-tenant (host), frontend React/Inertia + SSR.
# Uruchamiaj NA serwerze (prod/stage), z katalogu repo lub podając DEPLOY_DIR.
#
#   sudo bash bin/deploy.sh                       # rutynowy deploy: pull + build + SSR + cache
#   sudo bash bin/deploy.sh --migrate             # dodatkowo migracje schematu (patrz niżej)
#   sudo bash bin/deploy.sh --no-build            # pomiń build frontendu (zmiana tylko w PHP)
#   SSR_RESTART_CMD='systemctl restart pay-ssr' sudo -E bash bin/deploy.sh
#
# MIGRACJE SĄ JEDNORAZOWE — odpalaj --migrate WYŁĄCZNIE gdy pull przyniósł nowe
# pliki migracji. Bez flagi skrypt tylko OSTRZEŻE, że wykrył nowe migracje (nie
# uruchomi ich). Przed --migrate zrób backup bazy — patrz DEPLOYMENT.md.
#
# Frontend: main to React/Inertia (Vite). Assety (public/build) są w .gitignore,
# więc MUSZĄ być zbudowane na serwerze — sam `git pull` nie wystarczy. SSR to
# osobny proces Node (bootstrap/ssr/ssr.js @ 127.0.0.1:13714) i po buildzie MUSI
# być zrestartowany, inaczej serwuje stary bundle.

set -euo pipefail

# ---------------------------------------------------------------------------
# Konfiguracja (można nadpisać zmienną środowiskową, np. DEPLOY_DIR=... )
# ---------------------------------------------------------------------------
DEPLOY_DIR="${DEPLOY_DIR:-/var/www/support-me}"
BRANCH="${BRANCH:-main}"
PHP="${PHP:-php8.2}"
WEB_USER="${WEB_USER:-www-data}"
# Host tenanta storefront (baza nfc_shop1) — do migracji modułu sklepu.
TENANT_STOREFRONT="${TENANT_STOREFRONT:-please-support-me.com}"
# Komenda restartu procesu SSR. Domyślnie usługa systemd `pay-ssr`
# (patrz bin/pay-ssr.service). Nadpisz zmienną, jeśli używasz supervisor/pm2.
SSR_RESTART_CMD="${SSR_RESTART_CMD:-systemctl restart pay-ssr}"

DO_MIGRATE=0
DO_BUILD=1
for arg in "$@"; do
  case "$arg" in
    --migrate)  DO_MIGRATE=1 ;;
    --no-build) DO_BUILD=0 ;;
    *) echo "Nieznany argument: $arg" >&2; exit 2 ;;
  esac
done

run_www() { sudo -u "$WEB_USER" "$@"; }

cd "$DEPLOY_DIR"
echo "==> Deploy: $DEPLOY_DIR (branch $BRANCH)"

# Zawsze przywróć aplikację z trybu serwisowego przy wyjściu (także po błędzie).
trap 'run_www "$PHP" artisan up >/dev/null 2>&1 || true' EXIT

BEFORE="$(git rev-parse HEAD)"

echo "==> Tryb serwisowy (artisan down)"
run_www "$PHP" artisan down --render="errors::503" >/dev/null 2>&1 || run_www "$PHP" artisan down || true

echo "==> git pull --ff-only origin $BRANCH"
sudo git pull --ff-only origin "$BRANCH"
AFTER="$(git rev-parse HEAD)"

if [ "$BEFORE" = "$AFTER" ]; then
  echo "    (brak nowych commitów — kontynuuję build/cache dla pewności)"
  CHANGED=""
else
  CHANGED="$(git diff --name-only "$BEFORE" "$AFTER")"
fi

# composer tylko gdy zmienił się lock
if printf '%s\n' "$CHANGED" | grep -qx 'composer.lock'; then
  echo "==> composer install (zmienił się composer.lock)"
  composer install --no-dev --optimize-autoloader --no-interaction
fi

if [ "$DO_BUILD" = 1 ]; then
  # npm ci gdy zmienił się package-lock lub brak node_modules; build zawsze.
  if printf '%s\n' "$CHANGED" | grep -qx 'package-lock.json' || [ ! -d node_modules ]; then
    echo "==> npm ci"
    npm ci
  fi
  echo "==> npm run build (klient + SSR)"
  npm run build
else
  echo "==> Pomijam build frontendu (--no-build)"
fi

# Wykrycie nowych migracji w pulla
NEW_MIGRATIONS="$(printf '%s\n' "$CHANGED" | grep -E '(^|/)database/migrations/.*\.php$' || true)"
if [ "$DO_MIGRATE" = 1 ]; then
  echo "==> Migracje schematu (tenant storefront: $TENANT_STOREFRONT)"
  echo "    !!! Upewnij się, że masz BACKUP bazy (pg_dump) — patrz DEPLOYMENT.md"
  # --path OGRANICZONY do modułu, by nie odpalić gateway create_shop_tables (kolizja).
  # Init PRZED Storefront: migracja danych Init kopiuje shop_items.tag_uid do
  # init_codes, a migracja Storefront dopiero potem tę kolumnę usuwa.
  run_www env TENANT="$TENANT_STOREFRONT" "$PHP" artisan migrate --force \
    --path=app/Modules/Init/database/migrations
  run_www env TENANT="$TENANT_STOREFRONT" "$PHP" artisan migrate --force \
    --path=app/Modules/Storefront/database/migrations
  run_www env TENANT="$TENANT_STOREFRONT" "$PHP" artisan migrate --force \
    --path=database/migrations
elif [ -n "$NEW_MIGRATIONS" ]; then
  echo "!!! UWAGA: pull przyniósł nowe migracje, a NIE podano --migrate:"
  printf '%s\n' "$NEW_MIGRATIONS" | sed 's/^/      /'
  echo "    Po backupie uruchom świadomie: sudo bash bin/deploy.sh --migrate"
fi

echo "==> Czyszczenie cache (config/view/route) + odświeżenie manifestu pakietów"
run_www "$PHP" artisan config:clear
run_www "$PHP" artisan view:clear
run_www "$PHP" artisan route:clear
# package:discover regeneruje bootstrap/cache/packages.php — KONIECZNE, jeśli vendor
# był aktualizowany bez skryptów composera (np. rsync). Bez tego provider Inertii
# bywa niezarejestrowany → „Target [Inertia\Ssr\Gateway] is not instantiable" (500).
run_www "$PHP" artisan package:discover

echo "==> Restart SSR"
if [ -n "$SSR_RESTART_CMD" ]; then
  eval "sudo $SSR_RESTART_CMD"
else
  echo "!!! SSR_RESTART_CMD nie ustawione — proces SSR NIE został zrestartowany."
  echo "    SSR serwuje STARY bundle do czasu restartu. Ustaw komendę i ponów, np.:"
  echo "    SSR_RESTART_CMD='systemctl restart pay-ssr' sudo -E bash bin/deploy.sh --no-build"
fi

echo "==> Deploy zakończony: $BEFORE -> $AFTER"
