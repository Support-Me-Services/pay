# ecosystem/ — nowa architektura mikroserwisowa (Fazy 0–3)

Osobny stack Dockera od `docker/` (który zostaje wyłącznie środowiskiem
lokalnym Laravela — patrz `LOCAL.md`, nic tam nie zmieniamy). Można odpalić
oba naraz, bez konfliktów portów.

Kontekst i uzasadnienie każdej decyzji: dokument architektury ekosystemu
`pay` (link w `claude/marcin/03-ekosystem-mikroserwisow.md`) oraz
`proto/README.md`.

## Uruchomienie

```bash
cd ecosystem
docker compose up -d --build
```

Pierwszy build kompiluje oba serwisy Maven wewnątrz obrazu i ściąga
Keycloaka (kilka minut). Realm `pay` + klient `web` (publiczny, PKCE)
odtwarzają się automatycznie z `keycloak/pay-realm.json`
(`--import-realm`) — **ale użytkownicy nie są w tym imporcie**, trzeba ich
dodać ręcznie po każdym starcie od zera (patrz niżej).

## Co sprawdzić

```bash
# api-gateway odpowiada, odpytał core-svc I gateway-svc (Laravel/RoadRunner) po gRPC:
curl http://localhost:8081/api/v1/health

# core-svc sam w sobie + jego połączenie do Postgresa:
curl http://localhost:8082/actuator/health

# Keycloak, realm "pay" (nie "master"):
curl http://localhost:8180/realms/pay/.well-known/openid-configuration

# api-gateway: chroniony endpoint bez tokenu -> 401
curl -o /dev/null -w '%{http_code}\n' http://localhost:8081/api/v1/me
```

Pełny test logowania (Faza 3) wymaga przeglądarki — patrz `web/README.md`,
strona `/panel`.

## Odtworzenie testowego użytkownika Keycloaka

`partial-export` Keycloaka (skąd pochodzi `keycloak/pay-realm.json`) NIE
eksportuje użytkowników. Po świeżym starcie (albo `docker compose down` bez
zachowania wolumenu `postgres-keycloak`) trzeba dodać użytkownika ręcznie:

- Konsola: `http://localhost:8180` → login `admin`/`admin` → realm `pay` →
  Users → Add user. Ustaw `firstName` + `lastName` (inaczej Keycloak zażąda
  uzupełnienia profilu przy pierwszym logowaniu), potem zakładka
  Credentials → ustaw hasło (**nie** „temporary").
- Albo przez Admin REST API (przykład payloadu i pełen przepływ curl —
  `claude/marcin/03-ekosystem-mikroserwisow.md`, sekcja Faza 3).

## Porty

| Usługa | Port hosta | Rola |
|---|---|---|
| `api-gateway` | `8081` | REST na zewnątrz |
| `core-svc` | `8082` | REST wewnętrzny (Actuator) |
| `core-svc` | `9090` | gRPC (konsument: api-gateway) |
| `postgres-core` | `5433` | baza `core-svc` (dziś: żadnych tabel domenowych) |
| `keycloak` | `8180` | konsola/OIDC, realm `pay` (login admina: `admin`/`admin`, tylko dev) |

`gateway-svc` (Laravel/RoadRunner, PoC Fazy 1) żyje w **osobnym** stacku
(`docker/`), port `9091` — `api-gateway` dociera do niego przez
`host.docker.internal`, nie nazwę serwisu (to inny projekt docker-compose).

## Czego tu celowo nie ma

- Żadnego wpięcia do `panel/login` Laravela — auth tam działa dokładnie tak
  jak dziś. Keycloak i `api-gateway` stoją obok, gotowe do użycia, ale
  realne konta z dzisiejszych tabel `users` (bramka + sklep) nie są
  migrowane/provisionowane do Keycloaka — to właściwy zakres Fazy 3, nie
  zrobiony jeszcze.
- Żadnej domeny biznesowej w `core-svc` — patrz `services/core-svc/README.md`.
