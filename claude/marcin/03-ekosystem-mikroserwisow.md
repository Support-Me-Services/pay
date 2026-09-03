# Ekosystem mikroserwisów — decyzje i pułapki

Pełny opis docelowej architektury, diagram i plan migracji fazowej:
**[Ekosystem mikroserwisów pay](https://claude.ai/code/artifact/4eb3cf82-1002-4a2c-ae1f-fbfb75ac950e)**
— to jest żywy dokument (Claude Artifact), aktualizowany w miejscu przy
każdej fazie. Ten plik to notatki wykonawcze/pułapki, nie zastępuje tamtego
dokumentu.

## Docelowa architektura w skrócie

Sześć komponentów:

1. **`api-gateway`** (Spring Boot/Kotlin) — jedyny punkt wejścia REST dla
   web/mobile, tłumaczy na gRPC do serwisów domenowych. Bez własnej bazy,
   bez logiki biznesowej.
2. **`gateway-svc`** — to jest **obecny Laravel**, przejmuje cały
   dzisiejszy zakres (moduły Gateway + Storefront). Nie przepisujemy go na
   Kotlina.
3. **`core-svc`** (Spring Boot/Kotlin) — nowy backend, celowo pusty,
   własny Postgres. Pierwsza domena jeszcze nie ustalona.
4. **Keycloak** — tożsamość, własny Postgres. **Nie stoi za
   `api-gateway`** — logowanie OIDC to przekierowania przeglądarki
   (z natury REST), więc przeglądarka rozmawia z nim bezpośrednio;
   `api-gateway` tylko waliduje JWT przez JWKS.
5. **`web`** (Next.js, hybrydowo) — SSG/ISR dla stron publicznych, CSR dla
   panelu. Korekta względem pierwotnego pomysłu „statyczny React" — bez
   Next.js stracilibyśmy SSR/SEO bezpowrotnie.
6. **Aplikacja mobilna** (React Native) — konsument tego samego REST-a co
   `web`, logowanie OIDC (Authorization Code + PKCE) bezpośrednio w
   Keycloaku.

**Układ repo: monorepo.** Nowe katalogi w `pay/` (`services/`, `proto/`,
`ecosystem/`, `web/`), nie osobne repozytoria — bo to jednoosobowy zespół.

**gRPC dla Laravela miało plan B (REST), ale PoC się udał** — patrz Faza 1
niżej. `core-svc` zostaje przy gRPC niezależnie od tego, co się dzieje z
Laravelem — to dwa różne środowiska uruchomieniowe.

## Wymaganie: praca na słabym łączu (mobile-first)

Docelowi użytkownicy będą korzystać z tego głównie na telefonach, często
na słabym 3G/4G — **to podstawowy scenariusz, nie brzegowy przypadek**.
Konkretny dowód problemu w dzisiejszym kodzie: `CompanyStoreController::
purchase()` i `CartController::checkout()` robią `redirect()->away(...)` —
twardy, cross-originowy skok z domeny sklepu na domenę bramki, dokładnie w
momencie płatności. Konsekwencja dla `web`: zero takich przekierowań w
checkout, płatność ma iść przez REST do `api-gateway` i renderować się w
tym samym oknie SPA (wzorem istniejącego ekranu `App2App.jsx`, który już
dziś polluje status w miejscu zamiast przekierowywać).

---

## Faza 0 — fundament (zrobione, zweryfikowane)

`proto/health/v1/health.proto` (pierwszy, trywialny kontrakt),
`services/api-gateway`, `services/core-svc` (oba Spring Boot/Kotlin/Maven),
`ecosystem/docker-compose.yml` (Postgres×2, Keycloak, oba serwisy) —
**całkowicie osobno** od `docker/` (który zostaje wyłącznie środowiskiem
Laravela).

### Pułapki napotkane

- **Toolchain: Maven, nie Gradle** (Gradle nie jest zainstalowany lokalnie;
  Maven 3.9.0 tak). Kotlin 2.0.21, Spring Boot 3.4.1.
- **Buduj pod JDK 21, nie domyślnym JDK 25** — Kotlin 2.0.21 nie parsuje
  stringa wersji „25" (`JavaVersion.parse` rzuca
  `IllegalArgumentException: 25`). JDK 21 jest zainstalowany pod
  `C:\Program Files\Java\jdk-21` — trzeba ustawić `JAVA_HOME` przed `mvn`.
  Bytecode i tak celuje w `release 21`.
- Wygenerowany kod gRPC-Java odwołuje się do starej adnotacji
  `javax.annotation.Generated` (usuniętej z JDK 11+) — potrzebna zależność
  `javax.annotation:javax.annotation-api:1.3.2` (jakarta's `Generated` to
  inny pakiet, nie zaspokaja tej referencji).
- Kotlin klasy są domyślnie `final`, a Spring wymaga `open` na
  `@Configuration`/`@Bean` (CGLIB proxy) — rozwiązane pluginem kompilatora
  `kotlin-spring` (allopen), nie ręcznym `open` przy każdej klasie.
- `proto/` leży w korzeniu repo; `pom.xml` każdego serwisu wskazuje
  `protoSourceRoot` na `../../proto`. Build w Dockerze musi mieć
  `build.context: ..` (korzeń repo) i kopiować `proto` + `services/<nazwa>`
  z zachowaniem tej samej relatywnej ścieżki.
- Porty lokalne: `api-gateway` 8081, `core-svc` 8082 (REST/Actuator) + 9090
  (gRPC), `postgres-core` 5433, Keycloak 8180 — dobrane, żeby nie
  kolidować z Laravelem/Vite (8000, 5173) i MySQL Dockerem (13306).

---

## Faza 1 — PoC gRPC dla Laravela (zrobione, zweryfikowane)

**Wynik: PoC się udał.** `gateway-svc` (Laravel) hostuje dziś prawdziwy
serwer gRPC pod **RoadRunnerem**, obok istniejącego `php artisan serve`
(dual-run, zero zmian w dzisiejszych trasach). `api-gateway` woła go
dokładnie tak samo jak `core-svc` — zweryfikowane realnym wywołaniem
sieciowym, nie atrapą.

### Toolchain i pułapki

- Composer: `spiral/roadrunner-grpc`, `spiral/roadrunner-http`, i
  `--dev spiral/roadrunner-cli` (daje `vendor/bin/rr` — pobiera binarkę
  `rr` i plugin `protoc-gen-php-grpc`: `php vendor/bin/rr get` oraz
  `php vendor/bin/rr download-protoc-binary`).
- Wymaga `ext-sockets` w obrazie PHP (dodane do `docker/Dockerfile`) — bez
  tego composer w ogóle nie rozwiąże zależności.
- `protoc` (sam kompilator, nie plugin) doszedł przez `apt install
  protobuf-compiler` — nie jest częścią tooling'u composer/rr.
- Wygenerowane klasy PHP z proto lądują poza konwencją `App\` Laravela
  (pakiet proto `pay.health.v1` → namespace PHP `Pay\Health\V1`) —
  potrzebne jawne wpisy PSR-4 w `composer.json` (`"Pay\\": "..."`,
  `"GPBMetadata\\": "..."`), inaczej autoloader ich nie widzi.
- Worker (`grpc-worker.php` w korzeniu repo) bootuje pełną aplikację
  Laravel przez `$app->make(Illuminate\Contracts\Console\Kernel::class)
  ->bootstrap()` — te same bootstrappery co `artisan` — więc handlery
  gRPC mają dostęp do kontenera/Eloquent jak zwykły kontroler HTTP.
- Konfiguracja: `.rr.yaml` w korzeniu repo, gRPC nasłuchuje na `:9091`
  (`:9090` zajęte przez `core-svc`). `docker/docker-compose.yml` publikuje
  też `9091`. `api-gateway` dociera do tego z wnętrza `ecosystem/` przez
  `host.docker.internal:9091` (to OSOBNY projekt docker-compose, nie
  sąsiedni kontener w tej samej sieci) — ustawione przez zmienne
  `PAY_GATEWAY_SVC_GRPC_HOST`/`_PORT` w `ecosystem/docker-compose.yml`.

### Nie jest jeszcze produkcyjne

Proces `rr serve` uruchamiany **ręcznie** (nie przez supervisor/systemd).
To długożyjący proces (jak Octane/Swoole) — stan między wywołaniami NIE
resetuje się automatycznie jak przy `php-fpm`. Pierwsza realna domena w
gRPC (nie sam health-check) będzie wymagać audytu singletonów.

---

## Faza 2 — szkielet `web/` w Next.js (zrobione, zweryfikowane — tylko szkielet)

Świadomie ograniczony zakres (wybrany przez użytkownika, nie moje
założenie) — analogicznie do Fazy 0. Dwie strony demo, obie wołające
`api-gateway` (`GET /api/v1/health`):

- **`/`** — SSG/ISR: `'use cache'` + `cacheLife` (Cache Components).
- **`/live`** — CSR: fetch z przeglądarki po zamontowaniu.

Zweryfikowane realnym testem w przeglądarce (treść strony, nie tylko
curl) — obie strony renderują dane poprawnie, nawigacja między nimi bez
przeładowania dokumentu.

### Pułapki napotkane

- Scaffold: `create-next-app@latest --js --no-tailwind --eslint --app
  --no-src-dir` — JS nie TS, bez Tailwind, zgodnie z istniejącymi
  konwencjami frontendu w tym repo (zwykły CSS, zero TS).
- **Next.js 16 ma `cacheComponents: true` jako opt-in, nie domyślnie** —
  włączone świadomie w `next.config.mjs`, bo to aktualny, zalecany model
  cache'owania (dyrektywa `'use cache'` + `cacheLife()` z `next/cache`),
  nie starszy wzorzec `export const revalidate`. Własny `AGENTS.md`
  wygenerowany przez Next 16 ostrzega, że API mogły się zmienić względem
  danych treningowych modelu — to ostrzeżenie było trafne.
- **Pułapka monorepo**: Turbopack myli katalog główny, gdy w drzewie jest
  więcej niż jeden `package-lock.json` (korzeń repo ma Laravelowy, `web/`
  ma własny) — fix to `turbopack: { root: <katalog web> }` w
  `next.config.mjs`.
- **CORS wymagany** dla strony CSR (przeglądarka woła `api-gateway`
  bezpośrednio, inny origin niż dev server Next.js) — dodany
  `WebMvcConfigurer` (`WebConfig.kt`) w `api-gateway`, dopuszczający
  `http://localhost:3000`. Fetch po stronie serwera (strona SSG/ISR) tego
  NIE potrzebuje — to wywołanie dzieje się w procesie Node Next.js, nie w
  przeglądarce.
- **Pułapka Kotlina**: Kotlin obsługuje **zagnieżdżone** komentarze
  blokowe — literalne `/*` wewnątrz prozy w komentarzu `/** ... */`
  (np. opisując wzorzec URL typu `/api/**`) otwiera drugi, zagnieżdżony
  komentarz i psuje kompilację błędem „Unclosed comment". Unikaj
  dosłownej sekwencji `/*` w treści komentarzy Kotlina.

### Świadomie NIE zrobione

Pełne przepisanie wszystkich stron Storefront/Gateway na ten szkielet, i
eliminacja cross-originowego przekierowania w checkout — to osobna,
dedykowana sesja, nie coś do robienia kawałkami przy okazji.

---

## Faza 3 — auth przez Keycloak (zrobione, zweryfikowane — PoC)

Pełny przepływ OIDC (Authorization Code + PKCE) zweryfikowany END-TO-END w
realnej przeglądarce: logowanie w Keycloaku → powrót do `web` z sesją →
wywołanie **chronionego** endpointu `api-gateway` (`GET /api/v1/me`) z
tokenem Bearer → poprawna odpowiedź z realnymi claimami (subject, email,
issuer, audience) → wylogowanie. Bez tokenu `/api/v1/me` zwraca 401.

### Co powstało

- **Realm `pay`** w Keycloaku (nie `master`), klient **`web`** (publiczny,
  PKCE, bez client secret), mapper audience (`aud: api-gateway`). Wyeksportowany
  do `ecosystem/keycloak/pay-realm.json` i **auto-importowany** przy starcie
  kontenera (`command: start-dev --import-realm` + zamontowany plik) —
  zweryfikowane na całkiem świeżym środowisku (usunięty kontener + wolumen
  Postgresa), realm i klient odtworzyły się same.
- **`api-gateway`**: Spring Security OAuth2 Resource Server. Chroni
  wszystko poza `/api/v1/health` i `/actuator/**`. Waliduje: podpis (JWKS),
  `iss`, i `aud` zawiera `api-gateway` (jawny `DelegatingOAuth2TokenValidator`
  w `SecurityConfig.kt`, nie sam auto-config `issuer-uri`).
- **`web`**: logowanie przez `next-auth@beta` (Auth.js v5) z providerem
  Keycloak. Strona `/panel` (server component) — link do wywołania
  `/api/v1/me` z tokenem z sesji, przyciski logowania/wylogowania jako
  Server Actions.

### Pułapki napotkane

- **`jwk-set-uri` ≠ `issuer` w kontenerze.** Standardowy Spring auto-config
  (`issuer-uri`) zakłada, że token ma taki sam adres, pod jakim serwis
  faktycznie dociąga JWKS — nieprawda tutaj: przeglądarka (i token) widzi
  Keycloaka jako `localhost:8180`, ale `api-gateway` wewnątrz sieci Dockera
  musi dociągać klucze przez `keycloak:8080`. Rozwiązanie: rozdzielić
  `jwk-set-uri` (per-środowisko, nadpisywane w `ecosystem/docker-compose.yml`)
  od `issuer` (stały string, taki sam wszędzie — to jest w tokenie).
- **Keycloak REST Admin API przez curl na Windows/Git Bash: JSON body
  ZAWSZE plikiem** (`--data-binary "@plik.json"`), nigdy inline `-d
  '{...}'` z heredokiem/wieloliniowym stringiem — kończy się `"unable to
  read contents from stream"` (400) przez mangling nowych linii/cudzysłowów
  w tym shellu.
- **Token admina Keycloaka wygasa szybko** (domyślnie krótki
  `accessTokenLifespan` na realm `master`) — jeśli kolejne wywołanie
  Admin API dostanie 401, po prostu pobierz nowy token, nie diagnozuj dłużej.
- **`partial-export` Keycloaka NIE eksportuje użytkowników** — tylko
  realm/klienci/role/grupy. Testowy user (`marcin` / `test1234`, profil
  wymaga `firstName`+`lastName` inaczej Keycloak zażąda `VERIFY_PROFILE`
  przy pierwszym logowaniu) trzeba odtworzyć ręcznie po każdym `docker
  compose down` bez zachowania wolumenu — komenda w `ecosystem/README.md`.
- **Auth.js v5, klient publiczny (PKCE, bez sekretu):**
  `clientSecret: undefined` + `checks: ["pkce"]` w konfiguracji providera
  Keycloak — mimo że oficjalna dokumentacja providera pokazuje tylko
  wariant z sekretem ("confidential"), publiczny klient też działa.
- **Duży, realny bug Next.js 16.3.4 (Cache Components):** strona z
  `<Suspense>` wokół async server component czytającego `cookies()`
  (przez `auth()`) renderowała się poprawnie po stronie serwera (HTML +
  strumień RSC oba miały właściwą treść — zweryfikowane surowym curlem),
  ale po stronie klienta treść zostawała uwięziona w `<div hidden>` na
  końcu `<body>` i NIGDY nie była przenoszona na miejsce fallbacku
  (`offsetParent: null`, zerowy `getBoundingClientRect()`) — więc strona
  wisiała na fallbacku w nieskończoność, mimo że dane były już dawno
  gotowe. Reprodukowalne i w `next dev`, i w `next start` (build
  produkcyjny) — to nie problem trybu dev. Diagnoza zajęła sporo czasu,
  bo standardowe narzędzia do inspekcji DOM (accessibility tree) też
  mylące pokazywały element jako "istniejący" bez jasnego sygnału o
  `hidden`/zerowym rozmiarze — dopiero bezpośredni `getBoundingClientRect()`
  + sprawdzenie łańcucha rodziców przez `javascript_tool` ujawniło
  prawdziwą przyczynę. **Fix**: strona, która i tak nie potrzebuje
  częściowego prerenderowania (panel prywatny, zero SEO), powinna być w
  pełni blokująca — `export const instant = false;` (oficjalna ścieżka
  `["block"]` z komunikatu błędu builda), NIE Suspense, i NIE stary
  `export const dynamic = "force-dynamic"` (ten drugi jest **niekompatybilny**
  z `cacheComponents: true` i wywala build). Warto spróbować tego fixu
  najpierw, zanim zacznie się debugować Suspense/streaming w tej wersji
  Next.js.

### Dane testowe (tylko lokalnie, nieprzenoszone do repo)

- Konsola admina Keycloaka: `http://localhost:8180` — `admin`/`admin`.
- Testowy użytkownik realm `pay`: `marcin`/`test1234`.

### Świadomie NIE zrobione

Provisioning/migracja realnych kont z dzisiejszych tabel `users` Laravela
(bramka + sklep) do Keycloaka, i okres dual-auth (stare sesje Laravela +
tokeny Keycloak równolegle) — to jest właściwy zakres „Fazy 3" z dokumentu
architektury; to, co tu zrobione, to fundament pod to (mechanizm działa,
zweryfikowany), nie sama migracja kont.

---

## Jak odpalić od zera (nowy komputer)

1. **Laravel** (`docker/`) — patrz `LOCAL.md` w korzeniu repo.
2. **Ekosystem** (`ecosystem/`) — `cd ecosystem && docker compose up -d
   --build`. Wymaga Docker Desktop. Patrz `ecosystem/README.md`.
3. **PoC gRPC Laravela** — wymaga ręcznego doinstalowania (nie jest
   zbudowane w obraz na stałe): `ext-sockets` już jest w
   `docker/Dockerfile` (przebuduj obraz `docker compose build app`),
   `protoc` przez `apt install protobuf-compiler` w kontenerze,
   `composer install` doinstaluje pakiety RoadRunner z `composer.json`.
   Potem w kontenerze: `php vendor/bin/rr get` +
   `php vendor/bin/rr download-protoc-binary`, wygeneruj klasy z
   `proto/health/v1/health.proto` (komenda `protoc` — patrz historia tej
   sesji albo po prostu wygeneruj ponownie), i odpal `./rr serve -c
   .rr.yaml` w tle.
4. **`web/`** — `cd web && npm install && npm run dev`. Wymaga JDK 21
   (obok domyślnego) do budowania `services/*` i Node do `web/`. Skopiuj
   `.env.local` (nieśledzony przez git — zmienne wypisane w
   `web/README.md`) i wygeneruj własny `AUTH_SECRET`.
5. Wymaga też Maven (do `services/api-gateway`, `services/core-svc`) —
   `JAVA_HOME` na JDK 21 przed `mvn`.
6. **Keycloak — testowy użytkownik** (realm `pay` odtwarza się sam z
   `ecosystem/keycloak/pay-realm.json`, ale użytkownicy nie są w eksporcie):
   zaloguj się do konsoli admina (`http://localhost:8180`, `admin`/`admin`),
   realm `pay` → Users → Add user, ustaw `firstName`+`lastName` (inaczej
   Keycloak zażąda uzupełnienia profilu przy pierwszym logowaniu) i hasło
   przez zakładkę Credentials (nie „temporary"). Albo przez Admin REST API
   — patrz `claude/marcin/03-ekosystem-mikroserwisow.md`, sekcja Faza 3,
   dla przykładu payloadu.

Wszystkie porty i szczegóły — patrz README w każdym katalogu
(`services/*/README.md`, `ecosystem/README.md`, `web/README.md`) i sekcje
„Pułapki napotkane" wyżej.
