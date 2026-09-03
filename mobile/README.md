# pay — mobile (Faza 4, PoC)

Szkielet aplikacji mobilnej (Expo / React Native) demonstrujący, że `api-gateway`
z [ekosystemu mikroserwisów](../claude/marcin/03-ekosystem-mikroserwisow.md) jest
identycznie użyteczny z telefonu jak z przeglądarki (`web/`) — ten sam REST
kontrakt, to samo logowanie przez Keycloak (Authorization Code + PKCE), inny
tylko klient Keycloaka (`mobile` zamiast `web`).

`core-svc` pozostaje pusty (brak pierwszej funkcji biznesowej) — to świadoma
decyzja z Fazy 4, nie zaległość.

## Wymagania

- Node.js (ten sam co dla `web/`)
- Telefon z aplikacją **Expo Go** (Android/iOS) w tej samej sieci Wi-Fi co
  komputer z uruchomionym `ecosystem/docker-compose.yml`
  — **albo** emulator Androida / symulator iOS, jeśli masz go skonfigurowanego
    lokalnie (w tym środowisku deweloperskim nie było dostępne, więc kod był
    weryfikowany wyłącznie przez `npx expo export --platform android`, bez
    faktycznego uruchomienia na urządzeniu)

## Konfiguracja: `.env`

Skopiuj i uzupełnij swoim adresem IP w sieci lokalnej:

```bash
cp .env.example .env
```

```
EXPO_PUBLIC_API_GATEWAY_URL=http://TWOJE-IP:8081
EXPO_PUBLIC_KEYCLOAK_ISSUER=http://TWOJE-IP:8180/realms/pay
EXPO_PUBLIC_KEYCLOAK_CLIENT_ID=mobile
```

**Dlaczego nie `localhost`?** Na telefonie `localhost` oznacza sam telefon,
nie komputer, na którym stoi `ecosystem/`. Musisz podać prawdziwy adres IP
komputera w sieci Wi-Fi (to samo, którym łączy się telefon).

Jak znaleźć swoje IP:
- Windows: `ipconfig` → szukaj „IPv4 Address" przy adapterze Wi-Fi/Ethernet
- albo po prostu spójrz na adres „Network:" wypisywany przez `npx expo start`
  przy starcie — to jest dokładnie ten sam adres

`.env` jest w `.gitignore` (adres LAN jest inny na każdej maszynie) —
`.env.example` w repo jest szablonem do skopiowania.

## Uruchomienie

Wymaga działającego `ecosystem/` (patrz [03-ekosystem-mikroserwisow.md](../claude/marcin/03-ekosystem-mikroserwisow.md)):

```bash
cd ecosystem && docker compose up -d
cd ../mobile && npm start
```

Zeskanuj kod QR aplikacją Expo Go na telefonie.

## Co jest w `App.js`

1. `GET /api/v1/health` — publiczny health-check `api-gateway`, wołany od razu
   po starcie (bez logowania) — dowód, że telefon w ogóle dogaduje się z
   `api-gateway` po sieci LAN.
2. Przycisk „Zaloguj przez Keycloak" — otwiera systemową przeglądarkę
   (`expo-web-browser`), pełny Authorization Code + PKCE flow
   (`expo-auth-session`), dokładnie ten sam wzorzec co `web/app/panel/page.js`,
   tylko innym `clientId` (`mobile`).
3. Po zalogowaniu: `GET /api/v1/me` z nagłówkiem `Authorization: Bearer
   <token>` — chroniony endpoint `api-gateway`, dowód że JWT z klienta
   `mobile` przechodzi tę samą walidację (issuer + audience) co z klienta
   `web`.

## Klient Keycloaka `mobile`

Utworzony w `ecosystem/keycloak/pay-realm.json` (auto-import przy starcie
Keycloaka), obok istniejącego klienta `web`:

- `publicClient: true`, PKCE (`S256`) — brak client secret, tak jak `web`
  i zgodnie z zaleceniem OAuth dla aplikacji, które nie mogą bezpiecznie
  przechować sekretu (mobile, SPA)
- `redirectUris: ["paymobile://*", "exp://*"]`
  - `paymobile://` — schemat zdefiniowany w `app.json` (`"scheme":
    "paymobile"`), używany gdy aplikacja jest zbudowana jako standalone
    (dev/prod build)
  - `exp://*` — używany przez Expo Go podczas developmentu (Expo Go samo
    zarządza swoim tymczasowym schematem `exp://`); bez tego wpisu logowanie
    w Expo Go zwróci błąd `invalid_redirect_uri`
- ten sam audience mapper co `web` (`aud: api-gateway`) — dzięki temu
  `SecurityConfig` w `api-gateway` (patrz Faza 3) nie musi wiedzieć nic o
  różnicy między klientami, tylko o audience

## Ograniczenia tego PoC

- Token trzymany wyłącznie w pamięci komponentu (`useState`) — znika po
  zamknięciu aplikacji. Produkcyjnie: `expo-secure-store`.
- Brak refresh-token flow — po wygaśnięciu access tokena trzeba się zalogować
  ponownie ręcznie.
- Brak obsługi słabego/niestabilnego połączenia (retry, offline cache) — to
  było wymaganiem zgłoszonym wcześniej w tej samej sesji dla web/gateway, ale
  nie zostało jeszcze przeniesione na mobile.
