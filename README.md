# pay — bramka płatności NFC Pay (pay.please-support-me.com)

Landing page „Dołącz do nas", panel bramki, REST API dla sklepów, integracja PayU + symulator (MockProvider).

## Instalacja

```bash
composer install
cp .env.example .env && php artisan key:generate
# skonfiguruj DB_* (MySQL, baza nfc_pay) i sekcję PAYU_* (patrz niżej)
php artisan migrate --seed
```

Seeder tworzy: użytkownika `m@suli.pl` / `pay3322`, sklep demo `shop1` (classic) z kluczem API, 5 tagów (`TAG-S1-001…005`) oraz dane statystyczne demo za 30 dni.

Klucz API sklepu (do `.env` instancji sklepu): `SELECT slug, api_key FROM shops;`

## .env — płatności

```
PAYMENT_PROVIDER=mock        # mock | payu
PAYU_ENV=production          # sandbox | production (sandbox wymaga osobnego konta snd)
PAYU_MERCHANT_ID=4373028
PAYU_POS_ID=                 # z panelu PayU: Moje sklepy → Punkty płatności
PAYU_CLIENT_ID=              # zwykle = POS ID
PAYU_CLIENT_SECRET=          # Klucze API
PAYU_SECOND_KEY=             # drugi klucz (MD5) — do weryfikacji webhooków
```

W panelu PayU ustaw adres notyfikacji: `https://pay.please-support-me.com/webhooks/payu` i aktywuj BLIK na POS-ie.

## Architektura płatności

- `App\Payments\PaymentProviderInterface` — `createTransaction()`, `getRedirectUrl()`, `handleWebhook()`.
- `PayUProvider` — REST v2.1: OAuth client_credentials (token cache ~12 h), `POST /api/v2_1/orders`
  (dla trybu `app2app` z `payMethods: {payMethod: {type: PBL, value: blik}}` — klient omija wybór metody),
  webhook z weryfikacją `OpenPayu-Signature` (MD5/SHA-256, `hash_equals`).
- `MockProvider` — hostowane strony `/mockpay/{uuid}`: classic = pole na 6-cyfrowy kod (sukces po 2 s),
  app2app = animowany ekran „aplikacji banku" (sukces po 3 s), przycisk „Symuluj odmowę".
- Nowy operator = jedna klasa providera + `PAYMENT_PROVIDER` w `.env` (binding w `AppServiceProvider`).

## API dla sklepów (`/api/v1`, nagłówek `X-Api-Key`)

| Endpoint | Opis |
|---|---|
| `POST /api/v1/transactions` | tworzy transakcję → `{uuid, payment_url}` |
| `GET /api/v1/transactions/{uuid}` | status (`created/pending/paid/failed/abandoned`) |
| `POST /api/v1/events` | przyjmuje `tag_open` ze sklepów |

Webhook wychodzący do sklepu: `POST {notify_url}` z `{uuid, status, paid_at}`, nagłówek
`X-Signature` = HMAC-SHA256(body, api_key sklepu).

## Panel (`/panel`)

Dashboard (globalnie + per sklep), Sklepy (dodawanie generuje api_key pokazywany raz),
Tagi per sklep, Statystyki (filtrowanie sklep/tag, wykres 30 dni), Leady (eksport CSV),
AntiTheft (moduł FIKCYJNY — wyłącznie UI demo, brak realnej detekcji).
