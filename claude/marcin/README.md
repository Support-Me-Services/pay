# claude/marcin — ustalenia z sesji Claude Code

Ten katalog to przeniesiona „pamięć" z pracy nad refaktoringiem `pay` na
ekosystem mikroserwisów — żeby nie zaczynać od zera na innym komputerze.
Kod w tym repo jest źródłem prawdy (i tak trzeba by go przeczytać), ale te
notatki oszczędzają ponownego odkrywania tych samych pułapek.

## Spis treści

1. [`01-repo-i-deploy.md`](01-repo-i-deploy.md) — czym jest to repo, jak
   pushować, jak wygląda deploy frontendu.
2. [`02-baza-danych.md`](02-baza-danych.md) — produkcja jest już na
   Postgresie (nie MySQL) — ważne, żeby nie zaproponować zbędnej migracji.
3. [`03-ekosystem-mikroserwisow.md`](03-ekosystem-mikroserwisow.md) —
   decyzje architektoniczne i wszystkie pułapki napotkane przy budowie
   Fazy 0/1/2 (Maven/JDK, RoadRunner+gRPC w PHP, Next.js 16).

## Punkt startowy dla nowej sesji Claude

Poproś Claude o przeczytanie tego katalogu na początku, oraz otwórz
dokument architektury (link w `03-ekosystem-mikroserwisow.md`) — tam jest
pełny, aktualny opis docelowej topologii i planu migracji z diagramem.

## Stan na 2026-09-02 (koniec sesji)

- Faza 0 (fundament: `proto/`, `services/api-gateway`, `services/core-svc`,
  `ecosystem/`) — zrobione, zweryfikowane.
- Faza 1 (PoC gRPC dla Laravela pod RoadRunner) — zrobione, zweryfikowane.
- Faza 2 (szkielet `web/`, Next.js — SSG/ISR + CSR przez api-gateway) —
  zrobione, zweryfikowane. Pełne przepisanie frontendu — **nie zaczęte**,
  świadomie odłożone na osobną sesję.
- Wszystko zacommitowane i wypchnięte na `main`.

## Uwaga o środowisku lokalnym

Nic z tego nie jest jeszcze uruchamiane automatycznie (bez supervisor/
systemd) — patrz `03-ekosystem-mikroserwisow.md`, sekcja „Jak odpalić od
zera". Na nowym komputerze trzeba: Docker Desktop, JDK 21 (obok
domyślnego), Maven, Node — dokładne kroki i pułapki są tam opisane.
