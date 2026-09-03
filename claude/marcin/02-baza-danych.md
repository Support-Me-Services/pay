# Baza danych — produkcja to już PostgreSQL

**Nie MySQL.** Produkcja działa na PostgreSQL (Google Cloud SQL, prywatny
IP `10.60.96.3`), zarówno `nfc_pay` (bramka), jak i `nfc_shop1` (sklep) —
cutover z MariaDB nastąpił 2026-06-27. Źródło: `DEPLOYMENT.md`.

## Dlaczego to ważne

Wcześniejsza wersja dokumentu architektury ekosystemu mikroserwisów
zakładała, że migracja MySQL → Postgres jest jeszcze do zrobienia dla
przyszłego `gateway-svc`. **To nieprawda — już się stało.** Poprawione w
opublikowanym dokumencie 2026-09-02. Nie proponuj tej migracji ponownie.

## Fakty

- Źródło prawdy schematu = **migracje Laravela** (`artisan migrate`), NIE
  Liquibase. `db/liquibase/` (`mariadb-raw/`, `postgres/`) to wyłącznie
  referencja — użyta raz, do zbudowania schematu Postgresa od zera przy
  cutoverze. **Nigdy nie uruchamiaj `liquibase update` na żywej
  produkcji** — jego tracking changelogów nie wie o migracjach
  zastosowanych od tamtej pory i spróbuje odtworzyć istniejące obiekty
  (udokumentowany incydent 2026-07-07).
- Lokalny Docker (`docker/docker-compose.yml`) wciąż używa **MySQL 8** —
  to tylko lokalna zaległość, nie odzwierciedla produkcji. Nie zakładaj,
  że oba środowiska mają ten sam silnik bazy.
- Migracje muszą być bezpieczne pod Postgresa: używaj
  `$table->dropIndex('x_unique')`, NIE `dropUnique([...])` — to drugie
  generuje `DROP CONSTRAINT`, co pada na Postgresie (unik zbudowany tam to
  indeks, nie constraint) → `SQLSTATE 42704`, migracja wpół-zastosowana,
  kolejne żądanie kończy się 500.

## Jak stosować

Pisząc nowe migracje Laravela — zawsze zakładaj Postgresa jako docelowy
silnik (nawet jeśli lokalnie testujesz na MySQL), i używaj wzorca
`dropIndex` opisanego wyżej.
