# web — Next.js (Faza 2, szkielet)

Dowód modelu hybrydowego z dokumentu architektury: **jeden origin, dwa
sposoby renderowania, jeden kontrakt REST do `api-gateway`**. Nie jest to
przepisanie całego frontendu Storefront/Gateway — to szkielet, analogiczny
do `services/api-gateway` i `services/core-svc` z Fazy 0.

## Dwie strony demo

- `/` — **SSG/ISR**. Serwerowy komponent z `'use cache'` + `cacheLife`
  (Cache Components, Next 16) — odpowiedź `api-gateway` trafia do
  wygenerowanego HTML, odświeżana w tle co ok. minutę, nie przy każdym
  żądaniu.
- `/live` — **CSR**. Kliencki komponent, dane ładowane w przeglądarce po
  zamontowaniu — dokładnie tak jak będzie działał docelowo panel za
  logowaniem (nic do indeksowania, SEO bez znaczenia).

Obie strony wołają ten sam endpoint: `GET /api/v1/health` na `api-gateway`.
Nawigacja między nimi (link u góry) to przejście po stronie klienta — bez
przeładowania dokumentu. To jest odpowiedź na wymaganie „załaduj raz, potem
wszystko w przeglądarce" z dokumentu architektury.

## Uruchomienie lokalnie

Wymaga działającego `api-gateway` (patrz `ecosystem/README.md`):

```bash
cd ecosystem && docker compose up -d
cd ../web && npm install && npm run dev
```

Otwórz `http://localhost:3000`. Zmienna `NEXT_PUBLIC_API_GATEWAY_URL`
(w `.env.local`, nieśledzonym przez git) domyślnie wskazuje na
`http://localhost:8081`.

## Uwagi

- **Cache Components włączone świadomie** (`cacheComponents: true` w
  `next.config.mjs`) — to aktualny, zalecany model cache'owania w Next 16,
  nie stary `export const revalidate`. Patrz komentarze w `app/page.js`.
- **CORS**: `/live` woła `api-gateway` bezpośrednio z przeglądarki (inny
  origin niż `:3000`) — wymaga `WebConfig.kt` po stronie `api-gateway`
  (dziś: tylko `http://localhost:3000` na sztywno, PoC).
- To NIE jest jeszcze podłączone do żadnej realnej domeny (transakcje,
  produkty) ani do Storefront/Gateway z istniejącego Laravela — Faza 2
  w pełnym zakresie (przepisanie wszystkich stron, eliminacja
  cross-originowego redirectu w checkout) to osobna, dedykowana sesja.
