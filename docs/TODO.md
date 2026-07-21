# TODO — backlog operacyjny/techniczny

Lista rzeczy do zrobienia wychwyconych przy okazji innych prac. Format:
`- [ ] opis — kontekst/dlaczego (data dodania)`. Zrobione przenieś do „Zrobione".

## Otwarte

- [ ] **Zbadać puchnięcie `/var/log/syslog`** — na prod urósł do ~2,9 GB (sprzątane
  2026-07-21 przez `truncate`). Znaleźć, co loguje intensywnie (podejrzenie:
  `google-cloud-ops-agent` albo powtarzający się błąd). Rozważyć limity journald
  (`SystemMaxUse`) i logrotate, żeby nie zapełniało dysku (dysk / ma tylko ~10 GB).
- [ ] **Posprzątać artefakty rollbacku po React cutover** — po potwierdzeniu
  stabilności prod. Instrukcja: `docs/POST-CUTOVER-CLEANUP.md`.
- [ ] **Drobiazgi parytetu React (NISKI, świadomie pominięte przy audycie)** — do
  ewentualnego dokończenia, jeśli będą przeszkadzać:
  - `Product.jsx` — podświetlenie presetu przy „Innej kwocie".
  - `PotentialParishes/Index.jsx` — auto-zapis statusu `onChange` zamiast `onBlur`
    (wymaga refaktoru `save`, by nie wysyłał nieaktualnej wartości).
  - `Panel/Auth/Login.jsx` (i inne) — cache-busting `?v=<hash>` na `/css/*.css`.
  - `Gateway/Leads.jsx` — pełna paginacja z numerami stron zamiast prev/next.
- [ ] **Monitoring `pay-ssr`** — rozważyć alert, gdy usługa SSR pada (dziś
  `Restart=always`, ale brak powiadomienia; Inertia renderuje wtedy klient-side).

## Zrobione

- [x] Cutover prod Blade → React/Inertia + SSR (2026-07-21).
- [x] Audyt parytetu Blade→React (~60 par stron; naprawione: stopka, favicon,
  aria-current, swipe fundacji, walidacja galerii, komentarz /people).
