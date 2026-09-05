# Ekosystem mikroserwisów — decyzje i pułapki

Pełny opis docelowej architektury, diagram i plan migracji fazowej:
**[Ekosystem mikroserwisów pay](https://claude.ai/code/artifact/4eb3cf82-1002-4a2c-ae1f-fbfb75ac950e)**
— to jest żywy dokument (Claude Artifact), aktualizowany w miejscu przy
każdej fazie. Ten plik to notatki wykonawcze/pułapki, nie zastępuje tamtego
dokumentu.

## Docelowa architektura w skrócie

Sześć komponentów:

1. **`api-gateway`** (Spring Boot/Java — patrz niżej, przepisane z Kotlina)
   — jedyny punkt wejścia REST dla web/mobile, tłumaczy na gRPC do
   serwisów domenowych. Bez własnej bazy, bez logiki biznesowej.
2. **`gateway-svc`** — to jest **obecny Laravel**, przejmuje cały
   dzisiejszy zakres (moduły Gateway + Storefront). Nie przepisujemy go.
3. **`core-svc`** (Spring Boot/Java) — nowy backend, pierwsza domena:
   InitCode (Faza 5), własny Postgres.
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

## Faza 4 — szkielet `mobile/` w Expo/React Native (zrobione, zweryfikowane — bez urządzenia)

Dowód, że `api-gateway` jest identycznie użyteczny z telefonu jak z
przeglądarki: ten sam REST kontrakt (`/api/v1/health`, `/api/v1/me`), to
samo logowanie Keycloak (Authorization Code + PKCE), inny tylko klient
Keycloaka (`mobile` zamiast `web`). `core-svc` pozostaje pusty — świadoma
decyzja, nie zaległość (pierwsza funkcja biznesowa jeszcze nieustalona).

W tym środowisku deweloperskim nie ma emulatora Androida/iOS ani fizycznego
telefonu, więc weryfikacja ograniczona jest do tego, co da się sprawdzić bez
urządzenia — patrz „Zakres weryfikacji" niżej. Realny test na telefonie
(skan kodu QR w Expo Go) zostaje po stronie użytkownika.

### Co powstało

- **`mobile/`** — `create-expo-app` (szablon `blank`), Expo SDK 57 / React
  Native 0.86 / React 19.
- **`mobile/App.js`** — pojedynczy ekran: health-check publiczny przy
  starcie, przycisk logowania Keycloak (`expo-auth-session` +
  `expo-web-browser`, system browser + PKCE), po zalogowaniu wywołanie
  chronionego `/api/v1/me` z tokenem Bearer. Wzorzec 1:1 z
  `web/app/panel/page.js`, tylko przeniesiony z Server Components/Server
  Actions na hooki (`useAuthRequest`, `useEffect`) — bo tu nie ma serwera,
  całość działa po stronie klienta na telefonie.
- **Klient Keycloaka `mobile`** — dodany do `ecosystem/keycloak/pay-realm.json`
  obok `web` (publiczny, PKCE, `redirectUris: ["paymobile://*", "exp://*"]`,
  ten sam audience mapper `aud: api-gateway`). `exp://*` jest wymagany dla
  Expo Go w developmencie (Expo Go zarządza własnym tymczasowym schematem);
  `paymobile://*` (z `app.json` → `scheme`) dla buildów standalone.
- **`mobile/.env.example`** + `mobile/README.md` — LAN IP zamiast
  `localhost` (na telefonie `localhost` to sam telefon), z instrukcją jak
  znaleźć własne IP. `.env` (realne IP, różne na każdej maszynie) jest
  w `.gitignore`, tak samo jak `web/.env.local`.

### Pułapki napotkane

- **Dwa równoległe `npm install` w tym samym `node_modules/` psują stan
  katalogu.** `create-expo-app` sam odpala `npm install` na końcu
  scaffoldingu — jeśli w tym czasie odpali się kolejny `npm install`/`npx
  expo install` w tym samym katalogu (np. bo poprzednie polecenie w tle
  wydawało się już skończone, a w rzeczywistości dalej działało), efekt to
  częściowy `node_modules/` bez `node_modules/.bin` w ogóle — `npx expo
  ...` zaczyna zgłaszać `'expo' is not recognized`, mimo że `expo` jest
  w `package.json`. Diagnoza: sprawdzić czy proces `npm install` faktycznie
  się zakończył (np. `ls node_modules/.bin | wc -l` — powinno być
  kilkadziesiąt+ wpisów) zanim odpali się kolejna komenda npm w tym samym
  katalogu; nie ufać samemu statusowi „running"/timeout polecenia w tle bez
  potwierdzenia.
- **`create-expo-app` na Windows/npm potrafi trwać kilkadziesiąt minut**
  (w tej sesji: ~27 min na `npm install` samego scaffoldu) — prawdopodobnie
  antywirus skanujący każdy plik `node_modules` w locie. Nie traktować
  długiego czasu jako zawieszenia; sprawdzić realny postęp (rosnący
  `node_modules/`) zanim się to przerwie.
- **`npx expo export --platform web` wymaga `react-native-web`**, którego
  tu celowo nie ma (apka nie celuje w web) — błąd jasno mówi, co doinstalować,
  ale dla weryfikacji bez urządzenia właściwe polecenie to `npx expo export
  --platform android` (samo bundlowanie przez Metro, nie wymaga emulatora
  ani zainstalowanego Android SDK) — to jedyny wiarygodny „czy się w ogóle
  kompiluje" test dostępny w tym środowisku.

### Zakres weryfikacji (bez urządzenia)

- `npx expo export --platform android` — bundluje `App.js` przez Metro
  (619 modułów, bez błędów) → dowód, że kod się kompiluje i wszystkie
  importy (`expo-auth-session`, `expo-web-browser`, `expo-crypto`) się
  rozwiązują.
- **NIE zweryfikowano** (wymaga telefonu/emulatora): faktyczny redirect do
  Keycloaka z Expo Go, wymiana kodu na token, poprawność `redirectUri` w
  praktyce, zachowanie na słabym łączu.

### Świadomie NIE zrobione

- Trwałe przechowywanie tokenu (`expo-secure-store`) — token żyje tylko
  w pamięci komponentu, znika po zamknięciu apki. To PoC logowania, nie
  gotowy do użycia ekran.
- Refresh-token flow — po wygaśnięciu access tokena trzeba zalogować się
  ponownie ręcznie.
- Obsługa słabego/niestabilnego połączenia (retry, cache offline) — mimo że
  to był jawnie zgłoszony wymóg wcześniej w tej samej sesji, nie został
  jeszcze przeniesiony na `mobile/` (ani zresztą w pełni na `web/`).

---

## Faza 5 — pierwsza domena w core-svc: InitCode / NFC / QR (zrobione, zweryfikowane lokalnie end-to-end)

Pełny plan (kontekst, uzasadnienie architektury, decyzje) zapisany w
`C:\Users\marci\.claude\plans\mellow-floating-babbage.md` — tu tylko wynik
i pułapki. **Kluczowa korekta odkryta w trakcie planowania**: `api-gateway`/
`core-svc`/Keycloak nie mają dziś ŻADNEJ ścieżki wdrożenia na stage/prod
(produkcja to nginx+PHP-FPM, `git pull`+build, zero Dockera/JVM) — więc mimo
że użytkownik pierwotnie prosił o cutover ruchu "teraz", faktyczny zakres tej
fazy jest **wyłącznie dodatkowy**: core-svc dostaje pierwszą prawdziwą
domenę, w pełni zweryfikowaną w `ecosystem/`, ale **dzisiejsze trasy
Laravela (`/init/tag`, `/init/qr`, panel CRUD) zostają nietknięte** — realny
cutover to osobny, przyszły temat (wymaga najpierw wdrożenia JVM na
stage/prod, czego dziś nie ma).

### Co powstało

- **`proto/initcode/v1/initcode.proto`** — CRUD + `Resolve(uuid)` (czysta
  identyfikacja celu, bez wiedzy o slugach/URL-ach).
- **`proto/storefront/v1/storefront.proto`** — `ResolveRedirectTarget`,
  implementowany przez Laravel — jedyny właściciel wiedzy o slugach/
  handle'ach/aktywności.
- **`core-svc`**: pierwsza prawdziwa persystencja — `spring-boot-starter-data-jpa`
  + Flyway (`V1__create_init_codes.sql`, bigint PK + osobna unikalna
  kolumna `uuid`, `CHECK` wymuszający dokładnie jedno z
  `organization_id`/`owner_user_id` — w Laravelu ten niezmiennik był tylko
  w kontrolerze, tu wreszcie na poziomie bazy). gRPC service z pełnym
  egzekwowaniem scope przy mutacjach (`ownedEntityOrError`).
- **`gateway-svc` (Laravel)**: nowy `app/Modules/Storefront/Grpc/StorefrontGrpcHandler.php`,
  zarejestrowany w `grpc-worker.php` OBOK istniejącego health handlera —
  wyłącznie dodanie, `app/Modules/Init/**` bez zmian.
- **`api-gateway`**: `PublicInitController` (`GET /init/tag/{uuid}`,
  `/init/qr/{uuid}`, publiczne, z allowlistą hosta przeciw open-redirect) +
  `InternalInitCodeController` (`/internal/v1/init-codes/**`, CRUD, chronione
  nagłówkiem `X-Internal-Api-Key` porównywanym stałoczasowo, osobny
  `SecurityFilterChain` z `@Order(1)` obok łańcucha JWT). Deadline'y na
  wszystkich blocking-stubach (w tym doklejone do istniejącego
  `HealthController`, który wcześniej ich nie miał).

### Pułapki napotkane

- **Ten sam bug zagnieżdżonego komentarza Kotlina co w Fazie 2, trzy nowe
  miejsca**: literalny fragment `/internal/**` wewnątrz bloku `/** ... */`
  (KDoc) otwiera zagnieżdżony komentarz i psuje kompilację
  ("Unclosed comment") — Kotlin, w przeciwieństwie do PHP, WSPIERA
  zagnieżdżone `/* */`. Znowu w treści prozy opisującej ścieżkę URL z `**`
  (glob). Fix: unikać dosłownego `/**` w treści komentarza, pisać
  "prefiks `/internal/`" zamiast `/internal/**`. **Warto to sprawdzać z
  automatu (grep po `/\*\*[^ ]` w plikach `.kt`) zanim się buduje**, bo to
  już drugi raz w tej samej sesji.
- **`UsernamePasswordAuthenticationFilter` jest w `org.springframework.security.web.authentication`,
  NIE w `org.springframework.security.authentication`** — łatwa pomyłka przy
  pisaniu z pamięci, kompilator od razu łapie (`Unresolved reference`).
- **`.rr.yaml` ma jawną allowlistę `grpc.proto` — nowy plik `.proto` trzeba
  tam DOPISAĆ RĘCZNIE**, inaczej serwer RoadRunner startuje bez błędu, ale
  każde wywołanie nowej usługi kończy się `UNIMPLEMENTED: unknown service`
  mimo że handler jest poprawnie zaimplementowany i zarejestrowany w
  `grpc-worker.php`. Najmylący objaw w tej fazie — długo wyglądało jak
  problem z autoloaderem (patrz niżej), a to był oddzielny, drugi błąd.
- **`composer.json` ma `optimize-autoloader: true`** — po `protoc` wygenerował
  nowe klasy PHP (`Pay\Storefront\V1\*`), trzeba **ręcznie odpalić
  `composer dump-autoload -o`** w kontenerze, inaczej autoloader (zamrożony
  classmap z poprzedniego builda) ich nie widzi, mimo że pliki fizycznie
  istnieją i PSR-4 mapping jest poprawny. Ten sam mechanizm, który już był
  problemem przy generowaniu klas health.proto w Fazie 1, tylko tam nikt
  wtedy nie musiał dopisywać NOWEGO serwisu do już działającego workera.
- **Worker RoadRunner (`rr serve`) NIE przeładowuje kodu PHP automatycznie**
  — po każdej zmianie w `StorefrontGrpcHandler.php`, `.rr.yaml`, albo po
  `composer dump-autoload`, trzeba ubić stary proces i odpalić `./rr serve`
  na nowo (najprościej: `docker compose restart app` — czyści WSZYSTKIE
  osierocone procesy naraz, dużo bezpieczniejsze niż ręczne `kill` po PID
  wewnątrz kontenera).
- **Ręczne zabijanie procesów w kontenerze przez `docker exec sh -c "for p
  in /proc/... grep -q 'rr' ..."` ryzykuje samobójstwo powłoki** — jeśli
  wzorzec grepa (np. literalne `'rr'` w kodzie skryptu) pasuje do WŁASNEGO
  `cmdline` tej powłoki (bo `sh -c "<cały skrypt>"` zawiera tekst skryptu
  jako swój argument), proces zabija sam siebie (i całe drzewo) — exit 137
  bez żadnego innego komunikatu. Bezpieczniej: listować procesy do pliku
  najpierw, grepować LOKALNIE (poza kontenerem) po dokładnym prefiksie
  (`./rr serve`, nie samo `rr`), zabijać po konkretnym PID w osobnym kroku.
- **`MSYS_NO_PATHCONV=1` wciąż konieczne na Windows/Git Bash** dla KAŻDEGO
  `docker exec` z argumentem zaczynającym się od `/` (np. `/app/...`) —
  bez tego Git Bash cichcem podmienia go na ścieżkę Windows
  (`C:/Program Files/Git/app`) i komenda w kontenerze dostaje śmieci. Znany
  problem z wcześniejszej fazy, wraca przy każdej nowej sesji `docker exec`.
- **Duży, nieoczywisty bug środowiskowy — wywołanie HTTP Guzzle
  DO SAMEGO SIEBIE, z WEWNĄTRZ długo żyjącego workera gRPC RoadRunner, jest
  wyraźnie wolniejsze niż z normalnego, świeżego procesu CLI**:
  `GatewayClient::sendEvent()` (synchroniczny `Http::timeout(3)->post(...)`
  do `http://localhost:8000/api/v1/events`) z `php artisan tinker` (świeży
  proces) wykonuje się w ~900ms; DOKŁADNIE TO SAMO wywołanie, z tym samym
  URL-em, z wewnątrz `grpc-worker.php` (długo żyjący worker) zajmowało
  konsekwentnie ~2.4-2.9s (blisko własnego limitu `timeout(3)`) — za każdym
  razem, nie sporadycznie. Surowy `curl` z tego samego kontenera do tego
  samego URL-a był szybki (<1s). Przyczyna NIE w pełni zdiagnozowana —
  podejrzenie: coś w sposobie, w jaki proces workera RoadRunner (komunikacja
  z binarką `rr` przez Goridge po stdin/stdout) współdzieli/blokuje
  deskryptory plików albo pętlę zdarzeń z curl/Guzzle, ale nie potwierdzone.
  **To dokładnie ten rodzaj problemu, przed którym ostrzegał już komentarz w
  `grpc-worker.php` z Fazy 1** ("przy pierwszej realnej domenie trzeba będzie
  pilnować singletonów trzymających stan per-request") — to jest ta pierwsza
  realna domena, i faktycznie ujawniła coś nieoczywistego. **Obejście
  zastosowane tutaj**: podniesiony deadline (4s zamiast 2s) tylko na hopie
  `api-gateway` → `gateway-svc` (ten, który wywołuje `sendEvent`), z
  wyjaśniającym komentarzem w kodzie. **Świadomie NIE zdiagnozowane do
  końca** — to należy do tej samej kategorii co "RoadRunner bez
  supervisora" na liście ryzyk dokumentu architektury: prawdziwe
  utwardzenie długo żyjącego procesu PHP to osobna, przyszła praca, nie coś
  do rozwiązania przy okazji pierwszej domeny.

### Zweryfikowane end-to-end (lokalnie, `ecosystem/` + `docker/`)

Zautomatyzowane jako test integracyjny: `bash
ecosystem/tests/integration/test-initcode.sh` (czarna skrzynka po HTTP,
sam sprząta po sobie) — powtarza dokładnie poniższe punkty 1-4.

1. `POST /internal/v1/init-codes` bez nagłówka klucza → `403`; z poprawnym
   kluczem → `201` z realnym `uuid`.
2. `GET /init/tag/{uuid}` dla świeżo utworzonego kodu → `302`, poprawny
   `Location` (`http://localhost/?produkt=serduszko`) zbudowany z prawdziwego
   slugu produktu pobranego przez `gateway-svc` — dowód, że łańcuch
   `api-gateway → core-svc.Resolve → gateway-svc.ResolveRedirectTarget →
   redirect` faktycznie działa, nie atrapa.
3. Nieistniejący `uuid` → bezpieczne `404`, nie `500`, nie zawieszony request.
4. Nagłówek `Host: evil.example.com` → odrzucone (allowlist działa, brak
   open-redirect).
5. **Kontrola regresji, zweryfikowana wprost**: nowy `InitCode` utworzony
   natywnie w Laravelu (Eloquent, `app/Modules/Init/Models/InitCode.php`) i
   zeskanowany przez STARĄ trasę (`http://localhost:8000/init/tag/{uuid}`,
   `InitController::show()`, niezmieniona) — działa dokładnie jak przed tą
   fazą (`302` na `http://localhost:8000?produkt=serduszko`). Dwa
   niezależne systemy (Laravelowy `init_codes` i core-svc'owy `init_codes`)
   współistnieją bez kolizji — dokładnie zgodnie z planem.

### Świadomie NIE zrobione

- Cutover ruchu produkcyjnego (usunięcie starych tras Laravela, przepięcie
  panelu na `/internal/v1/**`, reguła nginx) — wymaga najpierw realnej
  ścieżki wdrożenia `api-gateway`/`core-svc` na stage/prod, której dziś nie
  ma. Osobny, przyszły temat.
- Migracja istniejących wierszy `init_codes` z Laravela do core-svc — brak
  fizycznie wydanych tagów w produkcji, więc nic realnego do przenoszenia
  teraz; skrypt nie został napisany (niepotrzebny bez realnego cutover).
- Pełna diagnoza przyczyny wolnego Guzzle-do-siebie w workerze gRPC (patrz
  pułapki wyżej) — obejście (dłuższy deadline) wystarcza na tym etapie.

## Przepisanie `services/api-gateway` i `services/core-svc` z Kotlina na Javę (2026-09-04)

Na wyraźną prośbę użytkownika: **cały kod Kotlina w obu serwisach przepisany
1:1 na Javę** (17 plików `.kt` → Java, `pom.xml` obu modułów oczyszczone z
wtyczki Kotlina). Zero zmian funkcjonalnych — potwierdzone tym samym testem
integracyjnym z Fazy 5 (`ecosystem/tests/integration/test-initcode.sh`),
przechodzącym 8/8 po przebudowie obrazów Dockera.

### Co się zmieniło technicznie

- `pom.xml` (oba moduły): usunięte `kotlin.version`, `kotlin-reflect`,
  `kotlin-stdlib`, cały blok `kotlin-maven-plugin` (w tym `allopen`/`noarg`
  compiler pluginy) i ręczne nadpisania faz `maven-compiler-plugin`
  (`default-compile`→`none` itd., potrzebne tylko żeby uniknąć konfliktu z
  kompilacją Kotlina). Zamiast tego: zwykły `java.version` w
  `<properties>` — `spring-boot-starter-parent` sam poprawnie konfiguruje
  `maven-compiler-plugin` z tej właściwości, żadnej ręcznej konfiguracji nie
  trzeba. `protobuf-maven-plugin` bez zmian — sam dodaje wygenerowane
  katalogi Javy do compile source roots, więc `javac` (domyślne wiązanie
  `compile`/`testCompile`) widzi je automatycznie.
- Encja JPA `InitCode`: z konstruktorowego stylu Kotlina (`class InitCode(val
  id: Long = 0, val uuid: String, var label: String, ...)`) na jawne pola +
  gettery/settery + bezargumentowy konstruktor `protected` (wymagany przez
  Hibernate) + konstruktor z parametrami. Bez Lombok (nie było go wcześniej,
  nie dodawaliśmy nowej zależności tylko po to).
- Kotlinowe `data class` (DTO w api-gateway) → Java `record` (Java 21,
  natywnie serializowalne przez Jackson bez dodatkowej konfiguracji) —
  **jeden plik na klasę**, nie jeden plik z pięcioma top-level klasami jak w
  oryginalnym `InitCodeDtos.kt` (Kotlin na to pozwala, Java nie — i to
  bardziej idiomatyczny podział i tak).
- `Pair<Long?, Long?>` (Kotlin, `scopeOf()` w `InitCodeGrpcService`) → mały
  prywatny `record OwnerIdentity(Long organizationId, Long ownerUserId)`
  (Java 21) — czytelniejsze niż `Pair.first`/`Pair.second` czy tablica.
- DSL Spring Security Kotlina (`http { csrf { disable() }; ... }`) → Java
  lambda-based config (`http.csrf(AbstractHttpConfigurer::disable)...`) —
  Spring Security 6 wspiera oba style, to bezpośredni, ekwiwalentny
  odpowiednik, nie obejście.
- `when` (Kotlin) → `switch` **wyrażeniowy** Javy 21 (`->`, nie stary
  `case: break`) wszędzie, gdzie Kotlin używał `when` na enumach z proto
  (`TargetType`, `ScopeCase`, kody błędów gRPC) — ta sama zwięzłość,
  ten sam wymóg wyczerpania (`default` zamiast `else`).
- `ResponseEntity<Any>` (Kotlin, jeden generyczny typ na wszystkie
  odpowiedzi kontrolera) → `ResponseEntity<?>` (Java) w
  `InternalInitCodeController` — z `Object` zamiast `?` trzeba by rzutować
  ręcznie przy każdym `.body(x)` (niezgodność wariancji generyków Javy),
  z wildcardem `<?>` nie, bo `ResponseEntity<Konkret>` jest naturalnym
  podtypem `ResponseEntity<?>`.

### Świadomie NIE ruszone

`app/Modules/Storefront/Grpc/StorefrontGrpcHandler.php` (Laravel/PHP) —
prośba dotyczyła wyłącznie Kotlina, PHP zostaje PHP.

## Faza 5.5 — cały stack lokalnie na Kubernetesie (2026-09-04)

Na wyraźną prośbę użytkownika: **cały stack (`docker/` + `ecosystem/`,
siedem komponentów) odtworzony jako manifesty Kubernetes w `k8s/`**,
uruchamiany na wbudowanym Kubernetesie Docker Desktop. `docker/` i
`ecosystem/` (docker-compose) **nadal działają bez zmian** — `k8s/` to
dodatkowa opcja, nie zamiennik. Pełny opis: `k8s/README.md`.

### Drugi realny bug znaleziony przy testowaniu z przeglądarki: brak portu w Location

Przekierowanie skanu (`PublicInitController`) budowało `Location` z gołego
nagłówka `Host` żądania (`localhost`), bez portu — działało w testach
curlem, bo test tylko grepował obecność `produkt=` w nagłówku, nigdy
faktycznie nie podążał za przekierowaniem. W przeglądarce ujawniło się
natychmiast: `Location: http://localhost/...` (domyślny port 80, tam nic
nie odpowiada) zamiast `http://localhost:8000/...` (prawdziwy port
Laravela). Przyczyna: port przychodzącego żądania to port **api-gateway**
(8081 lokalnie), nie storefrontu (8000) — nie da się go po prostu
przepisać. **Fix**: nowy config `pay.storefront.port` (domyślnie `8000`
lokalnie, celowo PUSTY jako named-default dla przyszłego prod — tam
storefront i api-gateway mają docelowo stać za tym samym portem 443/80,
port w URL byłby zbędny) doklejany jawnie przy budowie `Location`. Test
integracyjny (`test-initcode.sh`) wzmocniony: teraz sprawdza DOKŁADNY
`Location` (nie tylko obecność parametru) i **faktycznie podąża za
przekierowaniem**, sprawdzając że storefront pod tym adresem odpowiada
200 — złapałby ten bug automatycznie, nie tylko przy ręcznym klikaniu.

### Kluczowa różnica względem docker-compose: jeden namespace

Wcześniej `docker/` i `ecosystem/` to dwa OSOBNE projekty docker-compose —
stąd `host.docker.internal` jako sposób, żeby `api-gateway` dobił się do
gRPC Laravela (`PAY_GATEWAY_SVC_GRPC_HOST=host.docker.internal`). Na
Kubernetesie wszystko jest w jednym namespace `pay` — Laravel to zwykła
usługa klastra (`laravel-app`), więc `PAY_GATEWAY_SVC_GRPC_HOST=laravel-app`
i żadnej sztuczki nie trzeba. To nie obejście, to naturalne uproszczenie,
którego docker-compose (dwa osobne projekty z historycznych powodów) nie
dawał.

### Realny, wcześniej nieodkryty bug znaleziony przy tej okazji

**Kolejność migracji w `docker/entrypoint.sh` była błędna dla w pełni
świeżej bazy** — nigdy się to nie ujawniło w zwykłym `docker/`, bo
kontener `db` (MySQL) nigdy nie był tam naprawdę pusty (dane z wcześniejszych
sesji zostawały, migracje po prostu pomijały już zastosowane wpisy).
Kubernetes z nowym PVC dla MySQL uruchomił migracje na 100% czystej bazie
po raz pierwszy — i się wysypało:

- Migracje modułu `Init` (tagi NFC/QR) mają FK do `organizations`
  (Storefront) — uruchamiane PRZED Storefrontem, jak było, walą się od razu
  (`Failed to open the referenced table 'organizations'`).
- Migracje Storefrontu mają zapytanie do `users` (baza) — uruchamiane
  PRZED bazowymi migracjami, jak było, walą się (`Table 'nfc_shop1.users'
  doesn't exist`).
- Do tego Init i Storefront są chronologicznie POPRZEPLATANE (Init kopiuje
  `shop_items.tag_uid` do `init_codes`, Storefront DOPIERO PÓŹNIEJ tę
  kolumnę usuwa) — nie da się ich po prostu ustawić jedno-po-drugim jako
  całe bloki, trzeba migrować RAZEM, jednym wywołaniem.

**Fix**: `database/migrations` (bazowe) osobno i NAJPIERW, potem
`app/Modules/Storefront/database/migrations` + `app/Modules/Init/database/migrations`
**w jednym wywołaniu `migrate` z dwoma flagami `--path`** — Laravel scala
pliki z obu ścieżek i sortuje je chronologicznie automatycznie, dokładnie
rozwiązując przeplot. `app/Modules/Gateway/database/migrations` zostaje
osobno, ostatnie (inna baza, `TENANT=pay.please-support-me.com`).

To dotyczy KAŻDEGO środowiska migrowanego od zera (nowy komputer,
`docker/` po `docker compose down` bez wolumenu, nie tylko k8s) — poprawka
w `entrypoint.sh` jest ogólna, nie specyficzna dla Kubernetesa.

### Samowystarczalny start — koniec ręcznego `docker exec`

Przy okazji: `docker/entrypoint.sh` sam pobiera teraz `rr`/
`protoc-gen-php-grpc`, generuje klasy PHP z `proto/` i odpala `rr serve` w
tle (`protobuf-compiler` doszedł na stałe do `docker/Dockerfile`). Przez
całą tę sesję ten krok trzeba było robić ręcznie po każdym starcie
kontenera (`docker exec ... protoc ...`, `docker exec -d ... rr serve`) —
teraz dzieje się sam, w obu światach (`docker/` i `k8s/`).

### Co powstało

- `k8s/00-namespace.yaml` … `k8s/31-laravel-app.yaml` — po jednym pliku na
  komponent (Deployment + Service, gdzie trzeba + PVC/ConfigMap).
  `type: LoadBalancer` wszędzie, gdzie docker-compose miał `ports:` na
  hosta — to specjalna cecha Kubernetesa Docker Desktop: bindują się na
  `localhost:<port>` automatycznie, bez `kubectl port-forward`.
- PVC dla `postgres-core`, `postgres-keycloak`, `db` (MySQL) — w
  docker-compose te trzy NIE miały nazwanych wolumenów (dane ginęły przy
  każdym `down`); tu świadoma poprawa, bo restart poda jest w k8s
  częstszy niż w compose.
- `laravel-app`: hostPath na całe repo (odpowiednik bind-mount `..:/app`)
  + osobne PVC dla `vendor/`i `storage/` (jak nazwane wolumeny w compose —
  ten sam powód: wolny mount Windows zamienia każde żądanie w odczyt
  tysięcy plików).

### Pułapki napotkane

- **`subPath` na wolumenie `hostPath` typu `File` nie działa**
  (`CreateContainerConfigError: failed to prepare subPath for volumeMount`)
  — gdy `hostPath` już wskazuje wprost na plik (nie katalog), `subPath` jest
  zbędny i wręcz psuje montowanie. Fix: `mountPath` bez `subPath`, gdy
  źródłowy `hostPath` to już ten konkretny plik.
- **Docker Desktop miał `memoryMiB: 2048`** (2GB na cały silnik Dockera) —
  stanowczo za mało na kontrolplane Kubernetesa + siedem komponentów
  (2× JVM, Keycloak, 3× baza, PHP-FPM). Podniesione do 6144 (host ma 16GB
  RAM) w `C:\Users\<user>\AppData\Roaming\Docker\settings.json` przed
  włączeniem Kubernetesa.
- Konwencja hostPath dla Windows w Kubernetesie Docker Desktop:
  `C:\Users\marci\git\pay` → `/run/desktop/mnt/host/c/Users/marci/git/pay`
  (małą literą dysk, `/run/desktop/mnt/host/<litera>/...`).
- Kubernetes nie ma odpowiednika `depends_on: condition: service_healthy`
  z compose — zastąpione `initContainers` z prostą pętlą `nc -z <usługa>
  <port>` (np. `keycloak` czeka na `postgres-keycloak`, `core-svc` na
  `postgres-core`, `laravel-app` na `db`).

## Faza 6 — Laravel bez własnego uwierzytelniania, wszystko przez Keycloak (2026-09-04)

Na wyraźną prośbę użytkownika: **panel Gateway I Storefront logują się
WYŁĄCZNIE przez Keycloak** — Laravel nie sprawdza już żadnego hasła
samodzielnie (`Auth::attempt()`/`Hash::check` usunięte całkowicie). Sesja
Laravela ZOSTAJE jako nośnik stanu (CSRF/Inertia/`$request->user()` bez
zmian) — Keycloak zastępuje wyłącznie weryfikację hasła na wejściu. Pełny
plan projektowy (decyzje i uzasadnienia) zachowany w
`C:\Users\marci\.claude\plans\mellow-floating-babbage.md`. **Wyłącznie
lokalnie** — zero zmian w prawdziwym/produkcyjnym Laravelu.

### Kluczowe decyzje (skrót — pełne uzasadnienia w pliku planu)

- **Dwa klienty Keycloaka** (`laravel-panel-gateway`, `laravel-panel-storefront`,
  oba poufne/`publicClient:false`) — Gateway i Storefront to dziś
  kompletnie osobne tożsamości (osobne bazy, osobne `users`), jeden
  wspólny klient osłabiłby izolację rotacji sekretu.
- **Dopasowanie konta WYŁĄCZNIE po nowej kolumnie `keycloak_sub`, NIGDY po
  e-mailu** — realm ma `verifyEmail:false`, auto-link po e-mailu byłby
  furtką na przejęcie konta. Storefront: brak dopasowania → nowe konto.
  Gateway: brak dopasowania → 403, zero auto-rejestracji (jak dziś).
- **Prawdziwe single-logout** — `id_token` w sesji, użyty jako
  `id_token_hint` przy end-session Keycloaka, żeby SSO-sesja w Keycloaku
  faktycznie się kończyła (nie tylko lokalna sesja Laravela).
- `app/Http/Controllers/Auth/KeycloakController.php` — jeden wspólny
  kontroler dla obu modułów (wcześniej dwie kopie `LoginController` z
  własnym `Auth::attempt()`). Który klient Keycloaka jest użyty ustala
  `ResolveTenant::applyKeycloakClient()` per host (ten sam mechanizm co już
  istniejący `gateway_api_key` per-host).
- Usunięte całkowicie: oba `LoginController`y (stary wariant),
  `Storefront/RegisterController`, oba `PasswordController`y (routes +
  kontrolery + strony Inertia) — zmiana hasła to teraz konto Keycloaka
  (link do `{issuer}/account`, nowa karta).
- Migracja `users.keycloak_sub` (nullable, unique) + `users.password`
  teraz `nullable()` — jedna migracja w `database/migrations`, dotyczy OBU
  baz (`nfc_shop1`, `nfc_pay`).

### Realne błędy znalezione przy weryfikacji (nie tylko test artefakty)

Ta faza miała wyjątkowo dużo prawdziwych bugów złapanych na etapie
weryfikacji — zapisane tu, bo żaden nie był oczywisty z samego kodu:

1. **Brak przycisku wylogowania w `PanelLayout.jsx` (Storefront)** —
   `logoutUrl` był już poprawnie współdzielony jako prop z
   `HandleInertiaRequests`, ale layout nigdy go nie renderował (sprawdzone
   przez `git diff` — luka ISTNIAŁA już PRZED Fazą 6, nie coś co ta faza
   zepsuła). `GatewayPanelLayout.jsx` miał link "Wyloguj" cały czas.
   Fix: dodany analogiczny link do `PanelLayout.jsx`.
2. **`router.post()` Inertii nie może wylogować przez Keycloak** — po
   dodaniu linku "Wyloguj" (wzorem Gateway: `router.post(panel.logoutUrl)`,
   `onClick` + `e.preventDefault()`) wylogowanie w przeglądarce kończyło
   się cichym `net::ERR_FAILED` w konsoli (złapane przez
   `read_network_requests`, nie widoczne na oko). Przyczyna: `router.post()`
   idzie przez `fetch`/XHR; `KeycloakController::logout()` odpowiada
   `redirect()->away($keycloakEndSessionUrl)` — **przekierowaniem
   cross-origin** (Keycloak na innym porcie/hoście). Przeglądarka NIE
   pozwala scriptowemu `fetch`/XHR podążyć za takim przekierowaniem i
   odczytać efektu (CORS) — to działa tylko dla PRAWDZIWEJ nawigacji
   (link, pełny submit formularza). Dotyczyło OBU layoutów (Gateway też
   używał `router.post`, wcześniej nieszkodliwie, bo stare
   Gateway-logout przekierowywało tylko wewnątrz appki, same-origin).
   **Fix**: `logout()` w obu layoutach buduje i submituje PRAWDZIWY
   `<form method="POST">` (z tokenem CSRF) przez `document.body.appendChild`
   + `form.submit()` — pełna nawigacja, nie fetch, bez ograniczenia CORS.
3. **Keycloak odrzucał `post_logout_redirect_uri`** ("Invalid redirect
   uri") — atrybut klienta `post.logout.redirect.uris` domyślnie `"+"`
   (= ta sama lista co `redirectUris`, czyli tylko `/panel/auth/callback`),
   a `KeycloakController::logout()` woła end-session z
   `post_logout_redirect_uri=/panel/login`. **Fix**: ustawiony jawnie na
   URL logowania każdego klienta przez Admin API (`PUT
   /admin/realms/pay/clients/{id}`, pole `attributes["post.logout.redirect.uris"]`).
   Bez tego end-session był odrzucany PRZED wylogowaniem — SSO-sesja
   przeżywała, drugie logowanie cicho przechodziło bez ekranu Keycloaka
   (dowód: dopiero PO tym fixie drugie logowanie faktycznie pokazało
   prawdziwy formularz logowania Keycloaka).
4. **`users.email` jest unique → 500 zamiast czystej odmowy** — test
   regresyjny na decyzję "brak auto-linku po e-mailu" (plan zakładał: e-mail
   pasujący do istniejącego konta, ale INNY/brak `keycloak_sub` → tworzy
   OSOBNE konto) okazał się niewykonalny w praktyce: `email` w `users` MA
   unikalny indeks (potwierdzone: `DESCRIBE` → `email varchar(255) NO UNI`),
   więc `User::create()` z kolidującym e-mailem rzuca nieobsłużony
   `UniqueConstraintViolationException` → surowe 500, nie "osobne konto".
   Sama własność bezpieczeństwa trzyma się (NIE loguje w cudze konto), ale
   UX jest zły (500 zamiast czytelnego komunikatu). **Fix**: jawny check
   `User::where('email', ...)->exists()` przed `create()`, odmowa 409 z
   komunikatem, tym samym wzorcem co odmowa Gateway (`abort()`). Test
   przemianowany na `..._is_rejected_not_auto_linked` (dokładniej opisuje,
   co faktycznie się dzieje).
5. **`ResolveTenant`'owe przełączanie bazy per-tenant psuje testy sqlite**
   — technika "jeden użytkownik MySQL, wiele nazwanych baz" (`purge()` +
   `config(['database.connections.mysql.database' => ...])`) jest z natury
   specyficzna dla MySQL; uruchomiona bezwarunkowo próbowała nadpisać
   `database.connections.sqlite.database` (testy: `DB_CONNECTION=sqlite`,
   `:memory:`) literalną nazwą tenanta (np. `"nfc_shop1"`), co sqlite
   traktuje jak ścieżkę do PLIKU — łamiąc KAŻDY test dotykający bazy
   (potwierdzone: nawet domyślny szkieletowy `ExampleTest` już to
   robił, tylko nikt wcześniej nie napisał testu, który by to ujawnił —
   dziś zero pokrycia testowego bazy w tym repo). **Fix**: przełączanie
   bazy w `ResolveTenant::applyTenant()` ograniczone do `$conn === 'mysql'`
   — w produkcji (MySQL) zero zmiany zachowania, w testach (sqlite) po
   prostu nie próbuje przełączać.
6. **Migracje modułów Gateway i Storefront kolidują na współdzielonej
   bazie testowej** — oba moduły mają NIEZALEŻNIE OD SIEBIE tabelę
   `events` (różne przeznaczenie, różne kolumny — w produkcji nigdy nie
   koliduje, bo żyją w osobnych bazach MySQL), ale `RefreshDatabase` w
   testach scala migracje ZE WSZYSTKICH modułów w jeden przebieg na
   JEDNEJ sqlite `:memory:` — `"table events already exists"`. To
   pre-istniejąca luka w testowalności repo (nikt nigdy nie napisał
   testu dotykającego bazy przed Fazą 6), nie coś, co ta faza zepsuła —
   i naprawa właściwa (osobne połączenia testowe per tenant, mirror
   produkcyjnego układu) to osobny, większy temat. **Obejście w
   `KeycloakLoginTest`**: bez `RefreshDatabase`, ręczne
   `Schema::create('users', ...)` w `setUp()` (dokładny odpowiednik
   dzisiejszego schematu) — wystarczające do przetestowania logiki
   logowania w izolacji, bez dotykania pozostałych tabel/modułów.

### Weryfikacja — wszystko potwierdzone realnie (przeglądarka + curl + testy)

- **Storefront (localhost, przeglądarka)**: nowa tożsamość → nowe konto
  (`keycloak_sub` wypełnione, `password` NULL), zalogowanie na dashboard.
- **Gateway (`pay.please-support-me.com`, curl z jednym cookie jarem przez
  CAŁY flow — Browser tool odmawia nawigacji do niestandardowego hosta)**:
  brak dopasowania → czyste 403, **zero utworzonego wiersza** w
  `nfc_pay.users` (sprawdzone bezpośrednio w bazie). Pierwsza próba dała
  mylące 500 — to był artefakt WŁASNEGO skryptu testowego (osobne cookie
  jary dla `/panel/auth/redirect` i `/panel/auth/callback` gubiły sesję
  Laravela, więc Socialite's `state`-CSRF-check rzucał
  `InvalidStateException` ZANIM kod aplikacji w ogóle się wykonał) — po
  poprawce (jeden cookie jar na cały flow) prawidłowe 403 potwierdzone.
- **Prawdziwe single-logout**: wylogowanie → drugie `/panel/auth/redirect`
  pokazuje PRAWDZIWY formularz logowania Keycloaka (nie ciche
  re-auth) — potwierdzone w przeglądarce, dopiero po fixie #3 wyżej.
- **Regresja: scoping organizacji + bramka `is_admin`** — przetestowane z
  PRAWDZIWYM kontem (nie tylko świeżo utworzonym, pustym): seedowe konto
  `id=1` (`admin@local`) ręcznie powiązane z nową tożsamością Keycloaka
  (`keycloak_sub` ustawiony wprost w bazie — jednorazowa, lokalna czynność
  testowa, NIE realny cutover), `is_admin=1`, przypisana organizacja
  testowa. Po zalogowaniu: aktywna organizacja widoczna, panel „Wszystkie
  organizacje" (gate `abort_unless($user->is_admin, ...)` w
  `UsersController`) dostępny i działający — identycznie jak przed Fazą 6.
- **`tests/Feature/Auth/KeycloakLoginTest.php`** (nowy, Socialite
  fake'owany przez `shouldReceive`) — 5 testów, wszystkie zielone: nowa
  tożsamość (Storefront), ta sama `keycloak_sub` bez duplikatu, e-mail
  bez dopasowania odrzucony (nie auto-linkowany), Gateway odrzuca
  niedopasowaną tożsamość, Gateway akceptuje dopasowaną.

### Świadomie NIE zrobione (jak w planie)

Realny cutover produkcyjny (prawdziwe sekrety, migracja istniejących kont),
migracja `is_admin` na rolę Keycloaka, zmiany w `web/`/`mobile/` poza
akceptacją efektu ubocznego `registrationAllowed:true` — wszystko odłożone,
osobny przyszły temat.

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
   `proto/health/v1/health.proto` **i** `proto/storefront/v1/storefront.proto`
   (ten drugi doszedł w Fazie 5 — komenda `protoc --proto_path=proto
   --php_out=app/Modules/Gateway/Grpc/Generated
   --php-grpc_out=app/Modules/Gateway/Grpc/Generated
   --plugin=protoc-gen-php-grpc=./protoc-gen-php-grpc proto/<plik>.proto`,
   patrz historia tej sesji). Po KAŻDYM `protoc` (nowe/zmienione klasy):
   `composer dump-autoload -o` — `optimize-autoloader: true` w
   `composer.json` zamraża classmap, nowe klasy bez tego są niewidoczne
   mimo poprawnego PSR-4. Każdy nowy plik `.proto` trzeba też DOPISAĆ do
   `grpc.proto` w `.rr.yaml` (jawna allowlista, nie auto-discovery) —
   inaczej `unknown service` mimo poprawnie zarejestrowanego handlera.
   Na koniec odpal `./rr serve -c .rr.yaml` w tle (po każdej zmianie w
   PHP/`.rr.yaml`/autoloaderze: ubij stary proces i odpal ponownie — nie
   przeładowuje się sam; najprościej `docker compose restart app`).
4. **`web/`** — `cd web && npm install && npm run dev`. Wymaga JDK 21
   (obok domyślnego) do budowania `services/*` i Node do `web/`. Skopiuj
   `.env.local` (nieśledzony przez git — zmienne wypisane w
   `web/README.md`) i wygeneruj własny `AUTH_SECRET`.
5. Wymaga też Maven (do `services/api-gateway`, `services/core-svc`) —
   `JAVA_HOME` na JDK 21 przed `mvn`.
6. **`mobile/`** — `cd mobile && npm install`. Skopiuj `.env.example` do
   `.env`, uzupełnij własnym LAN IP (patrz `mobile/README.md`). Bez
   telefonu/emulatora da się zweryfikować co najwyżej `npx expo export
   --platform android` (bundlowanie bez błędów) — realny test wymaga Expo
   Go na telefonie w tej samej sieci Wi-Fi.
7. **Keycloak — testowy użytkownik** (realm `pay` odtwarza się sam z
   `ecosystem/keycloak/pay-realm.json`, ale użytkownicy nie są w eksporcie):
   zaloguj się do konsoli admina (`http://localhost:8180`, `admin`/`admin`),
   realm `pay` → Users → Add user, ustaw `firstName`+`lastName` (inaczej
   Keycloak zażąda uzupełnienia profilu przy pierwszym logowaniu) i hasło
   przez zakładkę Credentials (nie „temporary"). Albo przez Admin REST API
   — patrz `claude/marcin/03-ekosystem-mikroserwisow.md`, sekcja Faza 3,
   dla przykładu payloadu.
8. **Panel Laravela (Faza 6) — sekrety klientów Keycloaka**: `pay-realm.json`
   odtwarza klienty `laravel-panel-gateway`/`laravel-panel-storefront`, ale
   partial-export Keycloaka MASKUJE sekrety (fresh import generuje nowe) —
   po każdym świeżym imporcie realmu ustaw realne wartości w `.env` (NIE
   `.env.docker`): konsola admina → Clients → `laravel-panel-*` →
   Credentials → Client secret. Bez tego callback Laravela dostanie 401
   przy wymianie kodu na token. Ten sam problem/wzorzec co odtwarzanie
   testowego użytkownika (punkt wyżej) — świeży import realmu zawsze
   wymaga tego ręcznego kroku.

Wszystkie porty i szczegóły — patrz README w każdym katalogu
(`services/*/README.md`, `ecosystem/README.md`, `web/README.md`) i sekcje
„Pułapki napotkane" wyżej.

## Faza 7 — efemeryczne środowisko testowe na GCP per feature-branch

Użytkownik: push na `feature/**` ma automatycznie postawić cały stos na
GCP, gotowy do testowania, i zniknąć samoczynnie po godzinie — żeby nie
generować kosztów. Pełny plan (kontekst, uzasadnienie każdej decyzji):
`.claude/plans/mellow-floating-babbage.md` w tej sesji — tu tylko skrót +
pułapki napotkane przy implementacji.

**Architektura w skrócie**: jeden stały, współdzielony klaster GKE
Autopilot (`pay-ephemeral`), branch = namespace (`pay-eph-<slug>`) w tym
klastrze, nie osobny klaster (tworzenie klastra trwa minuty — nie do
zaakceptowania w pętli push→środowisko). Bazy i `core-svc` jako
`ClusterIP` (nie `LoadBalancer` — koszt + zbędna ekspozycja bazy danych w
internecie), `laravel-app`/`api-gateway`/`keycloak` jako `LoadBalancer` z
adresami `sslip.io` (magiczny DNS z IP w nazwie, zero konfiguracji DNS —
świadomie bez HTTPS/Ingressa, to godzinne testy wewnętrzne). Sprzątanie:
`CronJob` **wewnątrz klastra** (`k8s/housekeeper/`), nie harmonogram w
GitHub Actions — działa nawet przy awarii Actions, zero uprawnień GCP
(samo `kubectl delete namespace` zwalnia adresy IP LoadBalancerów).

**Kluczowy problem projektowy i jego rozwiązanie — "jajko i kura"**:
Keycloak musi znać z góry dozwolone redirect URI Laravela, ale zewnętrzny
adres Laravela poznaje się dopiero PO wystawieniu jego `Service`
(LoadBalancer dostaje IP asynchronicznie). Rozwiązane dzięki temu, że
`ResolveTenant::applyKeycloakClient()` (Faza 6) już buduje `redirect`
dynamicznie z `$request->getSchemeAndHttpHost()`, nie z `config('app.url')`
— Laravel nie musi z góry znać własnego adresu. Jedyne, co Keycloak musi
poznać z wyprzedzeniem, to allowlist redirect URI, a to da się dopisać PO
fakcie przez Admin API (ta sama technika co ręczne zmiany w Fazie 6).
Sekwencja w CI: bazy → Keycloak (czekaj na IP) → Admin API: pobierz
prawdziwe sekrety klientów (świeży import maskuje je, ten sam problem co
punkt 8 wyżej) → core-svc/api-gateway (JWKS URI zawsze wewnątrzklastrowy,
bez zmian) → laravel-app z realnym `KEYCLOAK_BASE_URL`+sekretami (czekaj
na WŁASNY IP) → Admin API: dopisz redirect URI Laravela do obu klientów.
Żaden krok nie wymaga ręcznego restartu — `kubectl set env` na
Deploymencie i tak wymusza rolling restart z nowym env.

### Pułapki napotkane

1. **Kustomize `LoadRestrictionsRootOnly` blokuje odwołania do plików
   POZA katalogiem danej kustomizacji** — `k8s/base/kustomization.yaml`
   celowo referuje istniejące `k8s/*.yaml` (`../00-namespace.yaml` itd.,
   zero duplikacji manifestów), ale to domyślnie zablokowane jako
   "security" ryzyko ("file ... is not in or below ..."). `kubectl apply
   -k` NIE ma flagi, żeby to poluzować (nie eksponuje
   `--load-restrictor`) — wymaga **samodzielnej** binarki `kustomize`
   (`kustomize build --load-restrictor=LoadRestrictionsNone | kubectl
   apply -f -`), zainstalowanej w CI przez `imranismail/setup-kustomize`.
   Dotyczy to WYŁĄCZNIE ścieżki CI (chmura) — lokalny `kubectl apply -f
   k8s/` nigdy nie używał Kustomize, więc nie ma tego ograniczenia i
   pozostaje bez zmian.
2. **Obraz Laravela lokalnie (`docker/Dockerfile`) w ogóle nie zawiera
   kodu aplikacji** — kod wchodzi przez `hostPath` na dysk Windows, co
   fizycznie nie istnieje na GKE. Nowy `docker/Dockerfile.ci` (wielo-
   etapowy: `composer install --no-scripts` bez kodu aplikacji jeszcze
   niekopiowanego, `npm run build:client` w osobnym etapie, finalny obraz
   PHP z zapieczonym kodem/vendor/`public/build`) — `docker/entrypoint.sh`
   zostaje bez zmian (już idempotentny, composer install no-opuje się gdy
   `vendor/` już jest).
3. **`composer install --no-scripts` (konieczne, bo kod app jeszcze nie
   skopiowany na tamtym etapie) zostawia `bootstrap/cache/packages.php`
   nieaktualny** — bez ręcznego `php artisan package:discover` w
   finalnym etapie obrazu, provider Inertii nie jest zarejestrowany
   ("Target [Inertia\Ssr\Gateway] is not instantiable", 500) — dokładnie
   ten sam, już wcześniej udokumentowany błąd co w `bin/deploy.sh`
   (komentarz przy `package:discover` tamże). Dodane jawnie po `COPY`
   pełnego kodu w `Dockerfile.ci`.
4. **`gcloud` na tej maszynie**: plain `gcloud` (bez rozszerzenia, z PATH)
   zgłasza błąd "nie znaleziono Python" przy realnych komendach (nie przy
   `--version`) — użyj pełnej ścieżki do `gcloud.cmd`
   (`C:\Program Files (x86)\Google\Cloud SDK\google-cloud-sdk\bin\
   gcloud.cmd`), ten sam plik co w pamięci `gcp-server-access`. Osobno:
   token auth wygasa i wymaga interaktywnego `gcloud auth login` — nie da
   się tego zrobić nieinteraktywnie z sesji Claude, użytkownik musi to
   zrobić sam przed jednorazowym setupem GCP.

### Świadomie NIE zrobione (v1)

Testowanie tenantu Gateway w efemerycznym środowisku (tylko Storefront —
nieznany host mapuje się na `localhost` wyłącznie w `environment('local')`),
HTTPS/własna domena, cache warstw Dockera między buildami, reguły dla
PR-ów z forków (repo prywatne, nieistotne). Pełna lista w planie.

### Decyzja (po fakcie, zmieniła pierwotny plan): 2 osobne projekty/billing accounty, nie 1 wspólny klaster

Pierwotny plan zakładał JEDEN wspólny klaster GKE (`pay-shared`) na testy
i (kiedyś) produkcję, żeby nie dzielić się darmowym kredytem GCP
($74.40/mies. na opłatę za zarządzanie klastra — pokrywa tylko jeden
klaster PER BILLING ACCOUNT). Użytkownik świadomie to odrzucił: to system
przetwarzający płatności/darowizny — stabilność i bezpieczeństwo produkcji
(brak wspólnych okien konserwacji węzłów, brak `housekeeper`-a z prawem
kasowania namespace'ów w tym samym klastrze co produkcja) są ważniejsze
niż ta oszczędność. Ostateczna decyzja, silniejsza niż same osobne
klastry: **2 CAŁKOWICIE OSOBNE billing accounty** (nie tylko 2 projekty na
jednym) — dodatkowa korzyść odkryta po drodze: kredyt $74.40/mies. liczy
się PER BILLING ACCOUNT, więc 2 billing accounty = 2 pełne darmowe
kredyty, zero kompromisu kosztowego względem 1 wspólnego klastra.

Przydział kont Google (dwa istniejące na tej maszynie):
- `founder@please-support-me.com` → **produkcja**, bez zmian (już dziś
  właściciel `please-support-me-499509` — potwierdzone pamięcią
  `gcp-server-access`). Konto rolowe/firmowe, nie osobiste — celowo, żeby
  krytyczna infrastruktura przetrwała zmiany kadrowe.
- `marcin.lula@please-support-me.com` (osobiste konto Marcina) → **nowy
  billing account pod środowiska testowe**, dopiero co założony przez
  użytkownika w konsoli GCP (ja nie mogę zakładać billing accountów —
  wymaga formularza płatności).

Nowy projekt GCP pod testy: **`please-support-me-test1`** (jeszcze NIE
utworzony). Klaster w nim: **`pay-ephemeral`** (nazwa już użyta we
wszystkich plikach — `.github/workflows/ephemeral-env.yml`,
`k8s/README.md`, `k8s/overlays/ephemeral/kustomization.yaml`).

### Stan: jednorazowy setup GCP WYKONANY (2026-09-05)

Projekt `please-support-me-test` był już zajęty globalnie (ID projektu
jest unikalne w całym GCP, nie tylko w tej organizacji) — użyty
**`please-support-me-test1`** zamiast, wszystkie pliki zaktualizowane
konsekwentnie (workflow, kustomization, README, ta notatka).

Wykonane realnie na GCP (kontem `marcin.lula@please-support-me.com`):
1. ✅ Projekt `please-support-me-test1` utworzony.
2. ✅ Billing account `017076-18B8FF-57CB09` ("Moje konto rozliczeniowe")
   podpięty.
3. ✅ API włączone: `container`, `artifactregistry`, `iam`,
   `iamcredentials`, `cloudresourcemanager`, `sts`.
4. ✅ Artifact Registry `pay` (region `europe-central2`).
5. ✅ Service account `github-ci@please-support-me-test1.iam.gserviceaccount.com`
   z rolami `container.developer` + `artifactregistry.writer` (potwierdzone
   `get-iam-policy`, NIE Owner/Editor).
6. ✅ Workload Identity Federation: pula `github` + provider
   `github-actions` (`issuer-uri=token.actions.githubusercontent.com`),
   **attribute-condition ograniczony do dokładnie jednego repo**
   (`assertion.repository=='Support-Me-Services/pay'` — bez tego
   DOWOLNY publiczny/prywatny workflow na GitHub mógłby impersonować to
   konto serwisowe). Binding `roles/iam.workloadIdentityUser` na
   `github-ci` ograniczony do tego samego repo przez `principalSet://...
   /attribute.repository/Support-Me-Services/pay`.
7. ✅ Klaster Autopilot `pay-ephemeral` (`europe-central2`, 3 węzły,
   `RUNNING`). Po drodze: brakujący `gke-gcloud-auth-plugin` wymagał
   uprawnień administratora do zainstalowania (SDK w `Program Files`) —
   `gcloud components install gke-gcloud-auth-plugin --quiet` odmawia w
   trybie nieinteraktywnym ("Cannot use bundled Python... non-interactive
   mode"), zadziałało BEZ `--quiet`, ręcznie, w oknie administratora.
8. ✅ `kubectl apply -f k8s/housekeeper/` — namespace `pay-system`,
   ServiceAccount/ClusterRole/ClusterRoleBinding, CronJob zweryfikowany
   (`kubectl get cronjob -n pay-system` → schedule `*/5 * * * *`).
9. ⬜ Sekrety repo GitHub `GCP_WORKLOAD_IDENTITY_PROVIDER` (wartość:
   `projects/770107734741/locations/global/workloadIdentityPools/github/
   providers/github-actions`) i `GCP_SERVICE_ACCOUNT` (wartość:
   `github-ci@please-support-me-test1.iam.gserviceaccount.com`) — NIE
   udało się ustawić automatycznie (`gh` CLI nie jest zainstalowane na tej
   maszynie) — użytkownik dodaje ręcznie w
   https://github.com/Support-Me-Services/pay/settings/secrets/actions/new.
   DO POTWIERDZENIA czy już zrobione, zanim pierwszy push zadziała.
10. ⬜ Pierwszy realny test: branch `feature/...`, push, sprawdzić Actions
    → podsumowanie z URL-ami → zalogować się przez przeglądarkę.

**Jednorazowy setup GCP jest w całości wykonany (punkty 1-8).** Zostaje
tylko potwierdzenie sekretów GitHub (użytkownik, ręcznie) i pierwszy test.

### ✅ Zweryfikowane end-to-end (2026-09-05, 12. próba workflow)

Po 11 nieudanych przebiegach (patrz pułapki #6-#12 niżej — każdy odkrywał
kolejny, prawdziwy, wcześniej nieznany problem) dwunasty przebieg przeszedł
w całości. Potwierdzone bezpośrednim curl na żywym środowisku:
- `http://<laravel-ip>.sslip.io:8000/` → HTTP 200.
- `http://<api-gateway-ip>.sslip.io:8081/api/v1/health` → 200, cały
  łańcuch odpowiada (`api-gateway` → `core-svc` przez gRPC → Laravel/
  RoadRunner przez gRPC).
- `http://<keycloak-ip>.sslip.io:8180/realms/pay/.well-known/openid-configuration`
  → 200, poprawny `issuer` wskazujący na adres sslip.io.

Namespace kasowany ręcznie po każdej nieudanej próbie (`kubectl delete
namespace ... `) — bez tego kolejne przebiegi startowałyby z
nawarstwionymi, częściowo martwymi generacjami podów z poprzednich prób.

### Pułapki napotkane przy pierwszym realnym teście (dodatkowe, #6-#12)

6. **`Dockerfile.ci`, etap `composer-deps` na obrazie `composer:2` (bez
   rozszerzeń PHP)** — `composer install` odmówił: `spiral/goridge`,
   `roadrunner-worker`, `roadrunner-grpc` wymagają `ext-sockets` do
   weryfikacji zgodności platformy. Naprawa: wspólny etap `php-base`
   (z `docker-php-ext-install ... sockets`), z którego korzysta zarówno
   `composer-deps`, jak i finalny obraz.
7. **`.dockerignore` wykluczał `services/`** — złamało to obrazy
   `core-svc`/`api-gateway`, które budują się z TEGO SAMEGO kontekstu
   repo-root i kopiują `services/core-svc`/`services/api-gateway`.
   `.dockerignore` obowiązuje globalnie dla KAŻDEGO builda z danym
   kontekstem, nie per-Dockerfile.
8. **Węzły klastra bez prawa odczytu z Artifact Registry** — `github-ci`
   dostał tylko `artifactregistry.writer` (do pushowania z CI), ale to
   INNA tożsamość niż ta, której węzły GKE używają do ściągania obrazów
   (domyślne konto `<project-number>-compute@developer.gserviceaccount.com`).
   Bez `artifactregistry.reader` na TYM koncie: wszystkie pody wiszą w
   `ErrImagePull`/403.
9. **`postgres` (oficjalny obraz) odmawia initdb, gdy katalog danych
   niepusty** — dyski PD w GKE mają świeżo sformatowany ext4 z
   automatycznym `lost+found` w korzeniu. Lokalnie (hostpath-provisioner
   Docker Desktop) nieszkodliwe, więc nie ujawniło się wcześniej mimo że
   manifesty są te same od Fazy 5.5. Naprawa: `PGDATA=/var/lib/postgresql/
   data/pgdata` (podkatalog, nie korzeń wolumenu) w OBU bazach Postgresa
   — w bazowych manifestach `k8s/10-*`/`k8s/11-*`, nie w nakładce
   ephemeral (dotyczyłoby każdego realnego wdrożenia w chmurze).
10. **Węzły z publicznym IP wyczerpywały kwotę `IN_USE_ADDRESSES` (limit 4
    na świeżym projekcie)** razem z 3 LoadBalancerami — blokowało
    skalowanie (`GCE quota exceeded`). Naprawa: klaster odtworzony z
    `--enable-private-nodes` + Cloud NAT (Cloud Router + NAT gateway,
    wymagane dla ruchu wychodzącego węzłów prywatnych — GKE tego nie
    stawia automatycznie).
11. **Nawet po prywatnych węzłach: globalna kwota `CPUS-ALL-REGIONS-per-project`
    (limit 12, nie regionalna — osobna, dużo bardziej restrykcyjna)**
    blokowała drugi węzeł 8-vCPU (pierwszy już 95% pełny samym narzutem
    systemowym GKE + moimi podami). Świeże projekty NIE mogą jej
    samoobsługowo podnieść (`ineligibilityReason: NOT_ENOUGH_USAGE_HISTORY`,
    potwierdzone przez `gcloud beta quotas info list` I ręcznie w konsoli
    — pole akceptuje tylko wartości ≤ obecnego limitu). Naprawa: jawne,
    małe `resources.requests` na WSZYSTKICH kontenerach w nakładce
    ephemeral (wcześniej 3 bazy nie miały żadnych — Autopilot dokładał
    własne 500m/2Gi każda) — zmieściło wszystko na jednym węźle.
12. **Realm `master` (wbudowany w Keycloaka, NIE ma go w `pay-realm.json`
    w przeciwieństwie do realmu `pay`, który ma `sslRequired: none`)
    wymusza HTTPS dla ruchu z adresów ZEWNĘTRZNYCH** — login admina przez
    zewnętrzny LoadBalancer IP dostawał 403 "HTTPS required". Naprawa:
    Admin API (logowanie + operacje na kliencie) przez `kubectl
    port-forward` (localhost) zamiast zewnętrznego IP — Keycloak widzi
    taki ruch jako lokalny/wewnętrzny, wymóg go nie dotyczy. Realnych
    użytkowników w przeglądarce to NIE dotyczy (logują się do realmu
    `pay`, nie `master`). Dodatkowo: `port-forward` w tle w kroku CI
    wymaga jawnego czekania na linię "Forwarding from" w jego logu przed
    pierwszym użyciem — ślepy `sleep 3` czasem nie wystarczał (tunel
    jeszcze nie gotowy → pierwsze próby curl dostają "connection
    refused", nieodróżnialne bez logowania od "Keycloak jeszcze nie
    gotowy", co maskowało prawdziwą przyczynę przez kilka kolejnych
    nieudanych przebiegów).

**Pułapka #5 (nowa)**: `gcloud.cmd` wywoływany z tej powłoki (Git Bash na
Windows) **wywala się z myląco niepowiązanym błędem `'C:\Program' is not
recognized...`, gdy KTÓRYKOLWIEK argument zawiera SPACJĘ** wewnątrz
cudzysłowu (np. `--attribute-condition="assertion.repository == '...'"`
ze spacjami wokół `==`, albo wieloliniowa komenda z `\` na końcu linii z
`--name="Coś z spacjami"`) — nawet gdy cała komenda jest poprawnie
zacytowana w bash. Naprawia to WYŁĄCZNIE usunięcie spacji z wartości
argumentu (np. `assertion.repository=='...'` bez spacji wokół `==`,
`--name=bez-spacji`), nie sposób zacytowania. Dotyczy tylko tej jednej
sesji/maszyny — najpewniej kwirk przekazywania argumentów przez warstwę
Git Bash → `cmd.exe` przy pliku `.cmd` (nie native `.exe`).

Kolejne kroki po gotowości klastra: `k8s/README.md`, sekcja "Jednorazowy
setup", punkty 7-10 (Artifact Registry i service account/WIF już zrobione
— zostaje `kubectl apply` housekeepera i pierwszy test).
