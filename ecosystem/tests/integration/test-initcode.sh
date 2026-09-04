#!/usr/bin/env bash
# Faza 5 — automatyczny test integracyjny InitCode (tagi NFC / kody QR)
# przez api-gateway -> core-svc -> gateway-svc. Wymaga uruchomionego
# `ecosystem/` (docker compose up -d) ORAZ `docker/` (Laravel) z odpalonym
# `rr serve` (patrz claude/marcin/03-ekosystem-mikroserwisow.md, sekcja
# Faza 5). Czarna skrzynka — woła wyłącznie po HTTP, nie zależy od
# konkretnego frameworka testowego żadnego z trzech serwisów.
#
# Użycie: bash ecosystem/tests/integration/test-initcode.sh
# Zmienne środowiskowe (opcjonalnie): API_GATEWAY_URL, INTERNAL_API_KEY,
# SHOP_ITEM_ID, SHOP_ITEM_SLUG — nadpisują wartości domyślne dopasowane do
# seeda lokalnego dev (produkt "serduszko", id=1).

set -u

API_GATEWAY_URL="${API_GATEWAY_URL:-http://localhost:8081}"
INTERNAL_API_KEY="${INTERNAL_API_KEY:-local-dev-only-change-me}"
SHOP_ITEM_ID="${SHOP_ITEM_ID:-1}"
SHOP_ITEM_SLUG="${SHOP_ITEM_SLUG:-serduszko}"

PASS=0
FAIL=0

pass() { PASS=$((PASS + 1)); echo "  OK: $1"; }
fail() { FAIL=$((FAIL + 1)); echo "  BLAD: $1"; }

extract_json_string() { grep -o "\"$2\":\"[^\"]*\"" <<<"$1" | head -1 | cut -d'"' -f4; }
extract_json_number() { grep -o "\"$2\":[0-9]*" <<<"$1" | head -1 | grep -o '[0-9]*$'; }

echo "== Faza 5: test InitCode (NFC/QR) =="
echo "api-gateway: $API_GATEWAY_URL"
echo

echo "1) Health check api-gateway/core-svc/gateway-svc"
health=$(curl -s "$API_GATEWAY_URL/api/v1/health")
if grep -q '"apiGateway":"UP"' <<<"$health" && grep -qc '"status":"SERVING"' <<<"$health"; then
  pass "wszystkie trzy komponenty odpowiadają"
else
  fail "health check nie zwrocil oczekiwanego statusu: $health"
  echo
  echo "Ecosystem nie odpowiada poprawnie — sprawdz 'docker compose ps' w ecosystem/ i docker/ zanim pojdziesz dalej."
  echo "PODSUMOWANIE: $PASS OK, $FAIL BLEDOW"
  exit 1
fi

echo
echo "2) Tworzenie kodu bez klucza -> oczekiwane 403"
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$API_GATEWAY_URL/internal/v1/init-codes" \
  -H 'Content-Type: application/json' -d '{"owner":{"organizationId":1},"label":"x","shopItemId":1}')
[ "$code" = "403" ] && pass "brak klucza odrzucony (403)" || fail "oczekiwano 403, dostano $code"

echo
echo "3) Tworzenie kodu z poprawnym kluczem -> oczekiwane 201"
create_response=$(curl -s -w '\n%{http_code}' -X POST "$API_GATEWAY_URL/internal/v1/init-codes" \
  -H 'Content-Type: application/json' -H "X-Internal-Api-Key: $INTERNAL_API_KEY" \
  -d "{\"owner\":{\"organizationId\":1},\"label\":\"test-automatyczny\",\"shopItemId\":$SHOP_ITEM_ID}")
create_code=$(tail -1 <<<"$create_response")
create_body=$(sed '$d' <<<"$create_response")
uuid=$(extract_json_string "$create_body" uuid)
id=$(extract_json_number "$create_body" id)

if [ "$create_code" = "201" ] && [ -n "$uuid" ]; then
  pass "kod utworzony (uuid=$uuid)"
else
  fail "oczekiwano 201 z uuid, dostano $create_code: $create_body"
  echo
  echo "PODSUMOWANIE: $PASS OK, $FAIL BLEDOW"
  exit 1
fi

echo
echo "4) Skan utworzonego kodu -> oczekiwane 302 na produkt '$SHOP_ITEM_SLUG'"
scan_headers=$(curl -s -i "$API_GATEWAY_URL/init/tag/$uuid")
scan_code=$(head -1 <<<"$scan_headers" | grep -o '[0-9][0-9][0-9]')
location=$(grep -i '^Location:' <<<"$scan_headers" | tr -d '\r')

if [ "$scan_code" = "302" ] && grep -q "produkt=$SHOP_ITEM_SLUG" <<<"$location"; then
  pass "przekierowanie poprawne ($location)"
else
  fail "oczekiwano 302 z Location zawierajacym produkt=$SHOP_ITEM_SLUG, dostano $scan_code / $location"
fi

echo
echo "5) Skan przez /init/qr/ (ten sam kod, drugi kanal) -> oczekiwane 302"
qr_code=$(curl -s -o /dev/null -w '%{http_code}' "$API_GATEWAY_URL/init/qr/$uuid")
[ "$qr_code" = "302" ] && pass "kanal qr dziala tak samo (302)" || fail "oczekiwano 302, dostano $qr_code"

echo
echo "6) Skan nieistniejacego uuid -> oczekiwane bezpieczne 404"
notfound_code=$(curl -s -o /dev/null -w '%{http_code}' "$API_GATEWAY_URL/init/tag/00000000-0000-0000-0000-000000000000")
[ "$notfound_code" = "404" ] && pass "nieistniejacy kod -> 404" || fail "oczekiwano 404, dostano $notfound_code"

echo
echo "7) Podszyty naglowek Host -> oczekiwane odrzucenie (nie redirect na obcy host)"
evil_headers=$(curl -s -i -H "Host: evil.example.com" "$API_GATEWAY_URL/init/tag/$uuid")
evil_code=$(head -1 <<<"$evil_headers" | grep -o '[0-9][0-9][0-9]')
evil_location=$(grep -i '^Location:' <<<"$evil_headers")
if [ "$evil_code" != "302" ] || ! grep -qi "evil.example.com" <<<"$evil_location"; then
  pass "obcy Host odrzucony (kod $evil_code, brak przekierowania na evil.example.com)"
else
  fail "MOZLIWY OPEN REDIRECT: $evil_location"
fi

echo
echo "8) Sprzatanie — usuniecie testowego kodu"
if [ -n "$id" ]; then
  del_code=$(curl -s -o /dev/null -w '%{http_code}' -X DELETE "$API_GATEWAY_URL/internal/v1/init-codes/$id" \
    -H 'Content-Type: application/json' -H "X-Internal-Api-Key: $INTERNAL_API_KEY" \
    -d '{"owner":{"organizationId":1}}')
  [ "$del_code" = "204" ] && pass "testowy kod usuniety" || fail "sprzatanie nie powiodlo sie (kod $del_code)"
fi

echo
echo "== PODSUMOWANIE: $PASS OK, $FAIL BLEDOW =="
[ "$FAIL" -eq 0 ]
