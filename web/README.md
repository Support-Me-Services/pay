# web — Next.js (Faza 2 + 3, szkielet)

Dowód modelu hybrydowego z dokumentu architektury: **jeden origin, dwa
sposoby renderowania, jeden kontrakt REST do `api-gateway`**, plus
prawdziwe logowanie OIDC. Nie jest to przepisanie całego frontendu
Storefront/Gateway — to szkielet, analogiczny do `services/api-gateway` i
`services/core-svc` z Fazy 0.

## Trzy strony demo

- `/` — **SSG/ISR**. Serwerowy komponent z `'use cache'` + `cacheLife`
  (Cache Components, Next 16) — odpowiedź `api-gateway` trafia do
  wygenerowanego HTML, odświeżana w tle co ok. minutę, nie przy każdym
  żądaniu.
- `/live` — **CSR**. Kliencki komponent, dane ładowane w przeglądarce po
  zamontowaniu — dokładnie tak jak działa panel za logowaniem (nic do
  indeksowania, SEO bez znaczenia).
- `/panel` — **logowanie przez Keycloak** (Faza 3). Przycisk „Zaloguj"
  przekierowuje bezpośrednio do Keycloaka (Authorization Code + PKCE, nie
  przez `api-gateway`); po powrocie strona woła **chroniony** endpoint
  `api-gateway` (`GET /api/v1/me`) z tokenem z sesji. W pełni dynamiczna
  (`export const instant = false`) — patrz „Uwagi" niżej, dlaczego nie
  Suspense.

`/` i `/live` wołają `GET /api/v1/health` (publiczny). Nawigacja między
stronami (linki u góry) to przejście po stronie klienta — bez
przeładowania dokumentu. To jest odpowiedź na wymaganie „załaduj raz, potem
wszystko w przeglądarce" z dokumentu architektury.

## Uruchomienie lokalnie

Wymaga działającego `api-gateway` + Keycloaka (patrz `ecosystem/README.md`)
i testowego użytkownika w Keycloaku (realm `pay` odtwarza się sam z
importu, ale użytkownicy nie — patrz `claude/marcin/03-ekosystem-...md`):

```bash
cd ecosystem && docker compose up -d
cd ../web && npm install && npm run dev
```

Otwórz `http://localhost:3000`. Skopiuj zmienne poniżej do `.env.local`
(nieśledzony przez git):

```
NEXT_PUBLIC_API_GATEWAY_URL=http://localhost:8081
AUTH_SECRET=<wygeneruj: node -e "console.log(require('crypto').randomBytes(32).toString('base64'))">
AUTH_URL=http://localhost:3000
KEYCLOAK_ISSUER=http://localhost:8180/realms/pay
KEYCLOAK_CLIENT_ID=web
```

## Uwagi

- **Cache Components włączone świadomie** (`cacheComponents: true` w
  `next.config.mjs`) — to aktualny, zalecany model cache'owania w Next 16,
  nie stary `export const revalidate`. Patrz komentarze w `app/page.js`.
- **CORS**: `/live` woła `api-gateway` bezpośrednio z przeglądarki (inny
  origin niż `:3000`) — wymaga `WebConfig.kt` po stronie `api-gateway`
  (dziś: tylko `http://localhost:3000` na sztywno, PoC).
- **`/panel` jest celowo BEZ `<Suspense>`** (`export const instant =
  false` zamiast tego). Napotkany, realny bug Next.js 16.3.4: strona z
  Suspense wokół async komponentu czytającego `cookies()` renderowała się
  poprawnie po stronie serwera, ale treść zostawała uwięziona w
  `<div hidden>` po stronie klienta i nigdy nie była aktywowana — strona
  wisiała na fallbacku mimo gotowych danych. `instant = false` (oficjalna
  ścieżka „block" z komunikatu błędu builda) całkowicie omija ten problem.
  Szczegóły: `claude/marcin/03-ekosystem-mikroserwisow.md`, sekcja Faza 3.
- Auth: `next-auth@beta` (Auth.js v5) z providerem Keycloak jako klient
  publiczny (`clientSecret: undefined`, `checks: ["pkce"]`) — mimo że
  oficjalna dokumentacja providera pokazuje tylko wariant z sekretem.
- To NIE jest jeszcze podłączone do żadnej realnej domeny (transakcje,
  produkty) ani do Storefront/Gateway z istniejącego Laravela, ani do
  realnych kont użytkowników (dual-auth) — pełny zakres Fazy 2 i 3 to
  osobna, dedykowana sesja.
