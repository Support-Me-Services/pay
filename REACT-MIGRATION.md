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

- [ ] `dashboard` (wykresy — Chart.js → react‑chartjs‑2) 🟡
- [ ] `login` (formularz logowania) 🟢
- [ ] `categories` (index + form, **drzewo `parent_id` + reorder**) 🟡
- [ ] `salespeople` (index + form) 🟢
- [ ] `positions` (index + form + toggle) 🟢
- [ ] `products` (index + form + **stats**; CRM parafii — większy) 🟡
- [ ] `potential-parishes` (index) 🟢
- [ ] `applications` (index + show) 🟢
- [ ] `messages` (index + show) 🟢
- [ ] `beneficiaries` (index — **edytor węzłów: Quill + SortableJS**) 🔴 złożony
- [ ] `coverage/map` (**interaktywna mapa**, dane AJAX) 🔴 złożony

### Faza 1b — Panel bramki (`resources/views/gateway/panel/`) 🟢
- [ ] `login`, `dashboard`, `stats`, `antitheft`, `leads`
- [ ] `shops` (index + form), `tags` (index + form)
- [ ] layout: `gateway/layouts/panel.blade.php` → komponent `GatewayPanelLayout.jsx`

### Faza 2 — Publiczny storefront (`resources/views/storefront/church/shop/`) 🟡 SEO
Wymaga SSR + weryfikacji OG/meta i zgodności PayU po każdej stronie.
- [ ] layouty: `church/layouts/{landing,public}.blade.php` → `StorefrontLayout.jsx`
- [ ] `home`, `storefront` (`/` darowizna — paywin), `index`
- [ ] `user-shop` (`/people/{handle}`), `koszyk`, `category`, `product`
- [ ] `beneficiaries` (`/beneficiaries`), `oferta`, `fundacje`, `parafie`, `szkoly`, `mecenasi-lokalny-rolnik`
- [ ] `regulamin`, `dziekujemy`, `praca`, `aplikuj`, `kontakt`, `docs`, `samouczek`, `inwestorzy`
- [ ] `tag-not-found`, `return-{success,pending,failure}`
- ⚠️ **PayU (potwierdzone na prod, do zachowania):** czas realizacji w `regulamin`,
  administrator danych w polityce (PDF), produkty z opisem/ceną/koszykiem w `user-shop`.
  OG/meta obecnie w `landing.blade` — przenieść do `<Head>` (Inertia) bez utraty podglądów linków.

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
