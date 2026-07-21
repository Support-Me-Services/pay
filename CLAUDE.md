# pay / SupportME — instrukcje projektowe

## Frontend = React/Inertia + SSR (Vite)

Frontend (`resources/js/**`, komponenty `.jsx`, style) jest budowany przez Vite.
Zbudowane assety (`public/build`) są w `.gitignore` — sam `git pull` NIE wystarczy,
build MUSI się wykonać, a proces SSR (Node, `bootstrap/ssr/ssr.js`) musi zostać
zrestartowany, bo inaczej serwuje stary bundle.

### ZASADA: po każdej zmianie we frontendzie ZAWSZE przebuduj zgodnie z `bin/deploy.sh`

Nie zostawiaj samej zmiany w kodzie JSX/CSS jako „gotowe". Zmiana wchodzi na
środowisko dopiero po buildzie. Pełny proces (build klienta + SSR + restart SSR +
czyszczenie cache) jest zamknięty w `bin/deploy.sh` — używaj go, nie odtwarzaj
kroków ręcznie.

Przepływ dla zmiany frontendowej:

1. Zacommituj i wypchnij zmianę (patrz pamięć: pay-repo-git-push — push jako
   Marcin Polak przez klucz `.ssh/gcp_vps`).
2. Na serwerze (stage/prod) uruchom deploy, który zrobi `git pull` + `npm run build`
   (klient + SSR) + restart `pay-ssr` + czyszczenie cache:

   ```bash
   sudo bash bin/deploy.sh
   ```

   - `bin/deploy.sh` uruchamia się NA serwerze, z katalogu repo `/var/www/support-me`
     (lub `DEPLOY_DIR=...`). Szczegóły serwerów: pamięć stage-deploy / prod-deploy.
   - NIE używaj `--no-build` przy zmianie frontendu — build jest wtedy potrzebny.
   - `--migrate` tylko gdy pull przyniósł nowe migracje (jednorazowo, po backupie).

Jeśli zmiana jest wyłącznie w PHP (bez frontendu), można pominąć build:
`sudo bash bin/deploy.sh --no-build`.
