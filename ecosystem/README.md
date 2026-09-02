# ecosystem/ — Faza 0 nowej architektury mikroserwisowej

Osobny stack Dockera od `docker/` (który zostaje wyłącznie środowiskiem
lokalnym Laravela — patrz `LOCAL.md`, nic tam nie zmieniamy). Można odpalić
oba naraz, bez konfliktów portów.

**Stan: nic tu jeszcze nie jest podłączone do żywej aplikacji.** To dowód, że
szkielet (api-gateway, core-svc, ich kontrakt gRPC, Keycloak) w ogóle działa —
zanim popłynie przez niego jakakolwiek prawdziwa domena albo prawdziwe
logowanie. Kontekst i uzasadnienie każdej decyzji: dokument architektury
ekosystemu `pay` (link w `docs/` / historii rozmowy) oraz `proto/README.md`.

## Uruchomienie

```bash
cd ecosystem
docker compose up -d --build
```

Pierwszy build kompiluje oba serwisy Maven wewnątrz obrazu (kilka minut).

## Co sprawdzić

```bash
# api-gateway odpowiada i faktycznie odpytał core-svc po gRPC (nie atrapa):
curl http://localhost:8081/api/v1/health

# core-svc sam w sobie + jego połączenie do Postgresa:
curl http://localhost:8082/actuator/health

# Keycloak wstał i ma gotową konfigurację OIDC:
curl http://localhost:8180/realms/master/.well-known/openid-configuration
```

Poprawna odpowiedź z `/api/v1/health` wygląda tak (dwa niezależne serwisy,
połączone realnym gRPC, nie hardcode):

```json
{
  "apiGateway": "UP",
  "coreSvc": {
    "status": "SERVING",
    "serviceName": "core-svc",
    "message": "core-svc odpowiada na wywołanie od: api-gateway"
  }
}
```

## Porty

| Usługa | Port hosta | Rola |
|---|---|---|
| `api-gateway` | `8081` | REST na zewnątrz |
| `core-svc` | `8082` | REST wewnętrzny (Actuator) |
| `core-svc` | `9090` | gRPC (konsument: api-gateway) |
| `postgres-core` | `5433` | baza `core-svc` (dziś: żadnych tabel domenowych) |
| `keycloak` | `8180` | konsola/OIDC (login: `admin`/`admin`, tylko dev) |

## Czego tu celowo nie ma

- Żadnego wpięcia do `panel/login` Laravela — auth tam działa dokładnie tak
  jak dziś, Keycloak stoi obok.
- Żadnej domeny biznesowej w `core-svc` — patrz `services/core-svc/README.md`.
- gRPC po stronie Laravela — to osobny PoC (ryzyko opisane w dokumencie
  architektury), nie część tej fazy.
