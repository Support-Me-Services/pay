# org-svc

Backend domenowy ekosystemu `pay` dla organizacji. Ten sam stack co
`core-svc`: Spring Boot + Maven, własna baza Postgres, gRPC-first
(konsument: `api-gateway`).

## Domena: organizacje

Pierwsza (i na razie jedyna) domena przenoszona tu z Laravela — patrz
`app/Modules/Storefront/Models/Organization.php` i
`OrganizationsController`. Zakres startowy: CRUD podstawowy (nazwa,
unikalny handle, widoczność 5 sekcji self-service). Relacje do beneficiary
nodes/shop items/positions/applications **zostają w Laravelu** — migrują
dopiero gdy dostaną własny właściciel/domenę, nie razem z tą encją.

Kontrakt: `proto/organization/v1/organization.proto`. Implementacja:
`OrganizationGrpcService` (`organization/` — encja + repozytorium +
serwis gRPC), plus wspólny `HealthGrpcService` (`grpc/`), tak samo jak w
core-svc.

## Uruchomienie lokalnie (bez Dockera)

Wymaga Postgresa na `localhost:5434` — najprościej z `ecosystem/`:

```bash
cd ../../ecosystem && docker compose up -d postgres-org
cd ../services/org-svc
mvn spring-boot:run
curl http://localhost:8083/actuator/health
```

## Uruchomienie w Dockerze

Patrz `ecosystem/README.md`.
