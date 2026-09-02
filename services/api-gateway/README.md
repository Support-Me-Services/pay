# api-gateway

Jedyny punkt wejścia REST dla web/mobile w docelowej architekturze
ekosystemu `pay`. Spring Boot + Kotlin, Maven.

## Rola (i czego świadomie brakuje)

- REST na zewnątrz, gRPC do serwisów domenowych wewnątrz — to jest w tym
  serwisie zademonstrowane na jednym, celowo trywialnym kontrakcie
  (`proto/health/v1`, patrz `HealthController.kt`), zanim popłynie przez
  niego prawdziwa domena.
- **Bez własnej bazy danych i bez logiki biznesowej** — jeśli kiedyś zacznie
  trzymać stan, przestaje pełnić rolę bramki.
- Auth: docelowo waliduje JWT wydany przez Keycloak (JWKS) — **jeszcze nie
  wpięte** w tej fazie.

## Uruchomienie lokalnie (bez Dockera)

Wymaga działającego `core-svc` na `localhost:9090` (gRPC) — patrz
`services/core-svc/README.md`.

```bash
mvn spring-boot:run
curl http://localhost:8081/api/v1/health
```

## Uruchomienie w Dockerze

Patrz `ecosystem/README.md` — buduje się razem z `core-svc`, Postgresem i
Keycloakiem jednym `docker compose up`.
