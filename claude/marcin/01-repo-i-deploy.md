# Repo i deploy

## Czym jest to repo

`pay` — brama płatności NFC (`pay.please-support-me.com`), GitHub
`git@github.com:Support-Me-Services/pay.git`. Laravel backend +
React/Inertia frontend budowany Vite, SSR przez Node
(`bootstrap/ssr/ssr.js`).

- Landing page, panel bramki (dashboard/sklepy/tagi/statystyki/leady),
  REST API dla sklepów (`/api/v1`), integracja PayU + `MockProvider` do
  testów.
- Backlog/notatki operacyjne: `docs/TODO.md`. Instrukcje sprzątania po
  cutoverze: `docs/POST-CUTOVER-CLEANUP.md`.

**Katalog przeniesiony 2026-09-02** z `C:\Users\marci\git` bezpośrednio do
`C:\Users\marci\git\pay` — jeśli gdzieś w starych notatkach/skryptach
widzisz ścieżkę bez `\pay`, jest nieaktualna.

## Frontend = React/Inertia + SSR (Vite)

Zbudowane assety (`public/build`) są w `.gitignore` — sam `git pull` **NIE
wystarczy**, build musi się wykonać, a proces SSR musi zostać
zrestartowany, inaczej serwuje stary bundle.

**Zasada: po każdej zmianie we frontendzie zawsze przebuduj zgodnie z
`bin/deploy.sh`** — nie zostawiaj samej zmiany w kodzie JSX/CSS jako
„gotowe". Pełny proces (build klienta + SSR + restart SSR + czyszczenie
cache) jest zamknięty w tym skrypcie.

Przepływ dla zmiany frontendowej:

1. Commit i push (patrz niżej — tożsamość gita).
2. Na serwerze (stage/prod): `sudo bash bin/deploy.sh` (pull + npm build +
   restart SSR + czyszczenie cache). `--migrate` tylko gdy pull przyniósł
   nowe migracje. `--no-build` tylko gdy zmiana jest wyłącznie w PHP.

## Git — tożsamość i push

W tym repo (na dowolnym nowym klonie) lokalna tożsamość gita **nie jest
ustawiona domyślnie** — trzeba ją skonfigurować lokalnie (nie globalnie):

```bash
git config user.name "Marcin Polak"
git config user.email "marcin.lula@please-support-me.com"
```

SSH do GitHuba zwykle działa od razu (bez specjalnego klucza) —
uwierzytelnia jako `marcin-lula-sm`. Jeśli git poprosi „please tell me who
you are", to zawsze jest brak lokalnej tożsamości, nie problem z SSH.

**Ważne (uwaga z 2026-09-02):** po przeniesieniu katalogu repo (np. na
nowy komputer, inny path) lokalna tożsamość gita **nie przenosi się** —
mimo że to ten sam `.git`. Trzeba ją ustawić ponownie od zera na nowym
miejscu/komputerze.
