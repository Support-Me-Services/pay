# Liquibase — schemat baz (MariaDB → PostgreSQL)

> **⚠️ PRECEDENCJA (od 2026-07-07): jedno źródło prawdy schematu = migracje Laravel
> (`artisan migrate`).** Liquibase to narzędzie do **budowy bazy OD ZERA** (użyte przy
> cutoverze na PG 2026-06-27). **NIE uruchamiaj `liquibase update` na żywych bazach
> produkcji** — są wyprzedzone przez Laravel migrate (kolejne zmiany schematu idą tam),
> a `databasechangelog` tego nie zna → `liquibase update` spróbowałby dodać istniejące
> obiekty i padnie. Te changelogi trzymamy jako referencję/rebuild i dopisujemy do nich
> delty równolegle do migracji Laravel, ale **wykonuje je tylko `artisan migrate`**.

Changelogi Liquibase odwzorowujące **schemat produkcji** i gotowe do
wykonania na **świeżej** bazie **PostgreSQL** (Cloud SQL `nfc-postgres-prod`).

## Struktura

| Katalog | Zawartość |
|---|---|
| `mariadb-raw/` | Surowy `generateChangeLog` z żywej prod MariaDB (introspekcja). **Referencja** — nie wykonuje się 1:1 na PG. |
| `postgres/`    | Wersja przetłumaczona na PostgreSQL — **to się uruchamia**, by utworzyć schemat. |

Dwie bazy = dwa osobne changelogi (każdy wykonywany na swojej bazie):
`nfc_pay`, `nfc_shop1`.

## Tłumaczenie typów MariaDB → PostgreSQL (zastosowane w `postgres/`)

| MariaDB | PostgreSQL |
|---|---|
| `BIGINT UNSIGNED` / `INT UNSIGNED` | `BIGINT` / `INTEGER` (PG nie ma `UNSIGNED`) |
| `TINYINT(3) UNSIGNED` | `SMALLINT` |
| `TINYINT(1)` (+ default 1/0) | `BOOLEAN` (+ default true/false) |
| `ENUM(...)` | `VARCHAR(32)` + `CHECK (col IN (...))` — zachowuje dozwolone wartości |
| `LONGTEXT` / `MEDIUMTEXT` | `TEXT` |
| `current_timestamp()` (default) | `CURRENT_TIMESTAMP` |
| `timestamp(0)`, `CHAR(36)`, `DECIMAL`, `VARCHAR`, `INT`, `TEXT` | bez zmian |

Tylko **schemat (DDL)** — migracja danych to osobny krok.

## Jak wykonać (tworzy schemat na PG)

Wymaga Java + Liquibase + sterownika JDBC PostgreSQL w `lib/`. Połączenie do Cloud SQL
po **prywatnym IP** (`10.60.96.3`) — z hosta w VPC albo przez tunel SSH.

```bash
# dla każdej z baz osobno:
liquibase \
  --search-path=db/liquibase/postgres \
  --changelog-file=nfc_pay.xml \
  --url="jdbc:postgresql://10.60.96.3:5432/nfc_pay" \
  --username=nfc_pay --password=<haslo> \
  update
# ... i analogicznie nfc_shop1.xml -> /nfc_shop1
```

## Weryfikacja

Changelogi z `postgres/` zostały zastosowane na lokalnym PostgreSQL 16 (Docker) i tworzą
schemat czysto: liczby tabel zgodne z prod (15 / 21), `BOOLEAN` i ograniczenia `CHECK`
z enumów poprawne.
