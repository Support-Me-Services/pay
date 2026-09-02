# core-svc

Nowy backend domenowy ekosystemu `pay`. Spring Boot + Kotlin, Maven, własna
baza Postgres.

## Stan: celowo pusty

Zero encji, zero tabel domenowych. To miejsce na funkcje budowane **od
teraz** — nie na przepisanie Laravela (ten zostaje `gateway-svc` i przejmuje
cały dzisiejszy zakres, patrz dokument architektury). Zanim tu trafi
pierwsza prawdziwa domena, warto świadomie zdecydować jaka — inaczej powstaje
drugi monolit pod inną nazwą.

Jedyne co dziś robi naprawdę: serwer gRPC implementujący współdzielony
kontrakt `proto/health/v1` (`HealthGrpcService.kt`) — konsumowany przez
`api-gateway` — oraz połączenie do własnej bazy Postgres, widoczne w
`/actuator/health` jako `db: UP`.

## Uruchomienie lokalnie (bez Dockera)

Wymaga Postgresa na `localhost:5433` — najprościej z `ecosystem/`:

```bash
cd ../../ecosystem && docker compose up -d postgres-core
cd ../services/core-svc
mvn spring-boot:run
curl http://localhost:8082/actuator/health
```

## Uruchomienie w Dockerze

Patrz `ecosystem/README.md`.
