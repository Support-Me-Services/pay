# Migracja frontendu na React (Inertia.js + SSR)

Status i plan przeniesienia warstwy widoku z Blade na **React przez Inertia.js**
(z SSR dla SEO). Backend Laravel (routing, kontrolery, auth, **multi‑tenant
`ResolveTenant`**) pozostaje bez zmian — Blade i Inertia współistnieją, migrujemy
strona po stronie.

Gałąź: **`react-inertia`**. Ostatni commit szkieletu/pilotażu: `de22dd7`,
migracja panelu „Sklep": `061240d`.

---

## ✅ Zrobione

### Szkielet (commit `de22dd7`)
- Backend: `inertiajs/inertia-laravel` v3; `App\Http\Middleware\HandleInertiaRequests`
  w grupie `web` (po `ResolveTenant`); root template `resources/views/app.blade.php`;
  `config/inertia.php` (SSR przez `INERTIA_SSR_URL`).
- Frontend: **React 19** + `@inertiajs/react`; **Vite 7** + `@vitejs/plugin-react-swc`
  (jedyny wariant wspierający Vite 7); `resources/js/app.jsx` (hydratacja) + `ssr.jsx` (SSR);
  alias `@` → `resources/js`.
- Pilotaż: trasa `/react-pilot` (dowód łańcucha Laravel→Inertia→React→SSR→hydratacja).

### Panel „Sklep" (commit `061240d`) — pierwszy realny widok
- `Panel/ShopItemController`: `index/create/edit` → `Inertia::render(...)` + serializacja `present()`.
- `resources/js/Layouts/PanelLayout.jsx` (sidebar/flash, **reużywa `theme.css`** — wygląd 1:1).
- `resources/js/Pages/Panel/ShopItems/{Index,Form}.jsx` (`useForm` + upload grafiki, edycja przez spoofing PUT).
- `HandleInertiaRequests` dzieli: `flash`, `auth.user`, `panel` (nav + liczniki, lazy, tylko `panel.*`).
- Usunięte martwe widoki Blade `shop-items/{index,form}.blade.php`.

---

## 🧭 Wzorzec migracji strony (przepis)

1. **Kontroler**: zamień `return view('x', $data)` → `return Inertia::render('Namespace/Page', $props)`.
   Dane przekazuj jako czyste tablice (serializacja `present()`), URL‑e przez `route()`.
2. **Komponent**: `resources/js/Pages/Namespace/Page.jsx`; layout przez
   `Page.layout = (p) => <PanelLayout>{p}</PanelLayout>` (lub layout storefrontu).
3. **Formularze**: `useForm(...)`; pliki → `{ forceFormData: true }`; edycja → `transform(d => ({...d, _method:'put'}))`.
   Błędy walidacji: `form.errors` (Inertia dzieli je automatycznie; kontroler bez zmian).
4. **Redirecty** (`store/update/destroy`): zostają — Inertia je obsłuży (flash przez shared `flash`).
   Endpointy AJAX/JSON (auto-zapis, notatki CRM, upload obrazka w edytorze) zostają — z Reacta
   wołane `fetch`-em z nagłówkiem `X-CSRF-TOKEN` (token w shared prop `csrf_token`, dodany w `HandleInertiaRequests`).
5. **Build + weryfikacja** (patrz niżej). Usuń martwy widok Blade.
6. Style: na razie **reużywamy `theme.css`** (panel) — bez re‑stylingu. Docelowo Tailwind/Vite.

---

## 🛠️ Lokalne środowisko (Docker)

Kod jest bind‑mountowany; Node dostarczany osobnym kontenerem (`node_modules` na wolumenie
`pay_node_modules`, żeby ominąć wolny bind‑mount Windows).

```bash
# BUILD (client + SSR) — po każdej zmianie w React:
docker run --rm -v C:/Users/Marcin/pay:/app -v pay_node_modules:/app/node_modules \
  -w /app node:20 npm run build

# SSR — serwer renderujący (kontener na sieci scratchpad_default):
docker restart pay-ssr            # po rebuildzie
# (pierwsze uruchomienie:)
# docker run -d --name pay-ssr --network scratchpad_default \
#   -v C:/Users/Marcin/pay:/app -v pay_node_modules:/app/node_modules \
#   -w /app node:20 node bootstrap/ssr/ssr.js

# po zmianach w PHP (kontroler/middleware/config):
docker exec scratchpad-app-1 php artisan optimize:clear
```

Podgląd: `http://localhost:8000` (app), panel: `/panel/login` (`admin@local` / `admin123`).
Weryfikacja strony za auth: logowanie curl‑em **wewnątrz kontenera** (cookie jar w `/tmp`;
curl Windows nie rozumie ścieżek `/c/...`).

**TODO dev‑experience (opcjonalne, przyspiesza):** podpiąć `npm run dev` (Vite HMR) +
serwis `node` w `docker/docker-compose.yml`, zamiast ręcznego rebuildu.

---

## 📋 Do zrobienia — pozostałe strony

Legenda ryzyka: 🟢 niskie · 🟡 średnie · 🔴 wysokie (żywe płatności / SEO).

### Faza 1 — Panel storefront (`resources/views/storefront/common/panel/`) 🟢
Wzorzec CRUD jak `shop-items` (część już przeanalizowana — kontrolery niemal identyczne).

- [x] `dashboard` (statystyki + wykres, Chart.js) 🟡 — **zrobione**
- [x] `login` (formularz logowania) 🟢 — **zrobione** (strona standalone, bez chromu panelu)
- [x] `categories` (index + form, drzewo + reorder) 🟡 — **zrobione**
- [x] `salespeople` (index + form) 🟢 — **zrobione**
- [x] `positions` (index + form + toggle, Quill) 🟢 — **zrobione**
- [x] `products` (index + form + **stats**; CRM parafii) 🟡 — **zrobione** (Quill+upload, galeria, pipeline statusów, notatki CRM AJAX, lejek+BarChart)
- [x] `potential-parishes` (index) 🟢 — **zrobione** (filtry GET + auto-zapis inline)
- [x] `applications` (index + show, filtry + zmiana statusu + CV) 🟢 — **zrobione**
- [x] `messages` (index + show) 🟢 — **zrobione**
- [x] `beneficiaries` (index — **edytor węzłów: Quill + drag&drop**) 🔴 — **zrobione** (natywny HTML5 DnD zamiast SortableJS, modal-kreator z kadrowaniem + live preview)
- [x] `coverage/map` (**interaktywna mapa**, dane AJAX) 🔴 — **zrobione** (Leaflet+markercluster z CDN, dynamiczny import w useEffect, popupy z auto-zapisem)

### Faza 1b — Panel bramki (`resources/views/gateway/panel/`) 🟢 — **ZROBIONE**
- [x] `login`, `dashboard`, `stats`, `antitheft`, `leads`
- [x] `shops` (index + form), `tags` (index + form)
- [x] layout: `gateway/layouts/panel.blade.php` → komponent `GatewayPanelLayout.jsx`
- ⚠️ `HandleInertiaRequests` jest teraz **świadomy roli**: `panel` buduje nawigację
  storefrontu **lub** bramki wg `config('platform.role')` — na hoście bramki trasy
  storefrontu nie istnieją, więc nie wolno ich tam wołać (i odwrotnie).
- Weryfikacja bramki lokalnie: host `pay.please-support-me.com` (baza `nfc_pay`) —
  migracja przez `--path` (framework + `Modules/Gateway/database/migrations`),
  bo pełny `migrate` koliduje ze storefrontem na tabeli `events`.

### Faza 2 — Publiczny storefront (`resources/views/storefront/church/shop/`) 🟡 SEO — **UKOŃCZONE**
Wymaga SSR + weryfikacji OG/meta i zgodności PayU po każdej stronie.
- [x] layouty: **`StorefrontLayout.jsx`** (landing) + **`StorefrontPublicLayout.jsx`** (public, tryb `bare`).
  OG/meta w `<Head>` z SSR; współdzielone `seo`+`routes` w `HandleInertiaRequests` (rola storefront).
- [x] statyczne marketingowe: `inwestorzy`, `dziekujemy`, `parafie`, `mecenasi-lokalny-rolnik`,
  `fundacje`, `szkoly` (Route::view → Inertia; page-scoped CSS w `<style>`/prop `css` z hashem serwerowym).
- [x] `regulamin` (treść prawna/PayU — „czas realizacji", administrator danych — zachowane 1:1).
- [x] **okołopłatnicze**: `home` (/main), `storefront` (`/` paywin), `user-shop` (/people/{handle}),
  `category`, `product`, `koszyk`, `kontakt`, `praca`+`oferta`+`aplikuj` (careers), `beneficiaries`,
  `return-{success,pending,failure}`, `tag-not-found` (404 zachowane). Legacy `index.blade` usunięty.
  - ⚠️ **PayU**: formularze płatności (paywin `/`, `/p/{slug}` kup, koszyk „Kupuję i płacę") to
    **NATYWNY POST** (pełne przeładowanie → `redirect()->away(payment_url)` PayU działa), NIE router
    Inertia. CSRF przez ukryte `_token` (shared `csrf_token`). Akcje wewnętrzne (koszyk: add/update/
    remove/dostawa) idą przez `router.post` (redirect wewnętrzny + flash).
- [x] **DUŻE strony statyczne**: `docs` (~1431 linii) i `samouczek` (~1115) — **zrobione**.
  Czysta treść, zerowa interaktywność, ale mnóstwo `<pre>`/`<code>` z nawiasami `{}` i `${VAR}`
  (setki wystąpień) — ręczna transkrypcja na drzewo JSX byłaby skrajnie błędogenna. Dlatego:
  treść trzymana **verbatim** w `resources/inertia-content/{docs,samouczek}.html`, renderowana
  przez `Storefront/{Docs,Samouczek}.jsx` jednym `dangerouslySetInnerHTML` (treść zaufana, statyczna,
  SSR działa → SEO/OG w `<Head>` przez `StorefrontLayout`). Figury (screeny) **rozstrzygane
  serwerowo** w trasie (`$docHtml`): `<figure data-fig="slug">` z placeholderem `__FIG:slug__` →
  jeśli `public/img/docs/{slug}.{png,jpg,webp}` istnieje, wstawiamy `<img src>`, inaczej całą figurę
  usuwamy (obraz „pojawia się automatycznie" po wrzuceniu pliku — zachowane zachowanie Blade).
  Placeholdery `__DATE__` (data aktualizacji) i `__ROUTE_DOCS__` (`route('docs')`) też serwerowo.
  Konwersja Blade→verbatim była jednorazowym skryptem; TOC rozwinięty statycznie z `$toc`.
  **Weryfikacja**: znormalizowany diff starego body Blade vs nowy `html`-prop = identyczny 1:1
  (jedyna różnica: nieszkodliwy atrybut `data-fig` na obecnych figurach; liczba renderowanych
  figur zgodna: docs 10/15, samouczek 15/18 — brakujące pliki obrazków pominięte jak wcześniej).
  Legacy `docs.blade.php` i `samouczek.blade.php` usunięte.

### Faza 3 — Bramka publiczna + płatności 🔴 NAJOSTROŻNIEJ (żywe płatności PayU)
- [ ] `gateway/landing`
- [ ] `gateway/payment/{app2app,blik,error,transition}`
- [ ] `gateway/mockpay/{app2app,classic}`
- [ ] layout `gateway/layouts/public.blade.php`
- ⚠️ Ruch płatniczy — migrować pojedynczo, z testem pełnego flow (mockpay) przed produkcją.

### Zostaje w Blade (NIE migrować)
- [x] `emails/job-application.blade.php` — szablon maila (renderowany serwerowo do HTML).

---

## ⚠️ Zasady bezpieczeństwa migracji
- Wszystko na gałęzi `react-inertia`; **produkcji nie dotykamy** aż do świadomego wdrożenia
  (uwaga: prod = Postgres `nfc_shop1`, deploy wg `DEPLOYMENT.md` — build assetów + restart SSR
  będą częścią wdrożenia frontu).
- Multi‑tenant: `ResolveTenant` działa server‑side — Inertia tego nie zmienia.
- Każdą stronę weryfikować (SSR render + akcje) i commitować osobno/partiami.
- Faza 3 (płatności) dopiero po pełnym przejściu faz 1–2 i teście flow.

## 🚀 Przyspieszenie (opcja)
Fazy 1 i 2 to w większości niezależne strony o powtarzalnym wzorcu — idealne pod
**wielo‑agentowy workflow** (równoległa migracja, każdy agent 1 strona, zbiór + build + weryfikacja
po stronie prowadzącego). Wymaga świadomej zgody (więcej agentów = większy koszt tokenów).
Faza 3 pozostaje ręczna.
