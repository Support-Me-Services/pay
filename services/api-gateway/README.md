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
- **Auth (Faza 3): waliduje JWT wydany przez Keycloak** — Spring Security
  OAuth2 Resource Server (`SecurityConfig.kt`). `/api/v1/health` i
  `/actuator/**` publiczne, wszystko inne wymaga ważnego tokenu (podpis
  przez JWKS + `iss` + `aud` zawiera `api-gateway`). `/api/v1/me` to demo
  chronionego endpointu (`MeController.kt`) — zwraca claimy z tokenu.
  `jwk-set-uri` i `issuer` są rozdzielone w configu celowo — patrz
  komentarz w `SecurityConfig.kt` i `application.yml`.

## Uruchomienie lokalnie (bez Dockera)

Wymaga działającego `core-svc` na `localhost:9090` (gRPC) i Keycloaka na
`localhost:8180` (realm `pay`) — patrz `services/core-svc/README.md` i
`ecosystem/README.md`.

```bash
mvn spring-boot:run
curl http://localhost:8081/api/v1/health   # publiczny, 200
curl http://localhost:8081/api/v1/me       # chroniony, 401 bez tokenu
```

## Uruchomienie w Dockerze

Patrz `ecosystem/README.md` — buduje się razem z `core-svc`, Postgresem i
Keycloakiem jednym `docker compose up`.
