# cms-svc

Backend domenowy "treść i katalog" ekosystemu `pay` — Spring Boot + Java,
Maven, **własna baza Postgres**. Pierwszy krok w kierunku zniknięcia PHP z
projektu (patrz `claude/marcin/03-ekosystem-mikroserwisow.md`, Faza 6).

## Zakres — czym cms-svc JEST

Pięć dziedzin przeniesionych z `app/Modules/Storefront` (Laravel) i
`app/Modules/Gateway` (Lead):

- **Organization** — byt nad kontem (Keycloak `owner_keycloak_sub`, NIE
  numeryczny `user_id` z tabeli Laravela — ta ma docelowo zniknąć).
- **BeneficiaryNode** — węzły podstrony "Wspieramy"/"O nas".
- **JobPosition** + **JobApplication** — sekcja "Praca" (oferty + zgłoszenia).
- **ShopItem** — produkty sklepu donacyjnego (NFC).
- **Lead** — leady ze strony lądowania Gatewaya (płaska tabela, bez właściciela).

## Zakres — czym cms-svc NIE JEST

- **Płatności/transakcje/tagi/anti-theft** — to `Gateway` w Laravelu, ma
  zostać osobnym serwisem (`payments-svc` czy podobny) w kolejnym kroku.
- **Cart/Order/checkout** — dziś `Storefront` w Laravelu, przyszły osobny krok.
- **InitCode** (kody NFC/QR) — to już `core-svc`, nie tu.
- Nic z tego nie jest dziś ruszane — Laravel na produkcji stoi bez zmian.

## Różnica względem `core-svc`

Ten sam stos (Spring Boot 3.4.1, Java 21, Maven, gRPC), ale schemat
zarządzany przez **Liquibase** (`src/main/resources/db/changelog/`), nie
Flyway — świadomy wybór, różne serwisy mogą używać różnych narzędzi
migracji. Hibernate ma `ddl-auto: validate` — Liquibase jest jedynym
właścicielem schematu.

Własność encji: `owner_keycloak_sub` (string), nie FK do żadnej tabeli
innego serwisu — ten sam wzorzec braku-FK-między-serwisami co
`core-svc`/`InitCode` (`organization_id`/`owner_user_id` jako zwykłe longi).

## Uruchomienie lokalnie (bez Dockera)

Wymaga Postgresa na `localhost:5435` — najprościej z `ecosystem/`:

```bash
cd ../../ecosystem && docker compose up -d postgres-cms
cd ../services/cms-svc
mvn spring-boot:run
curl http://localhost:8083/actuator/health
```

## Uruchomienie w Dockerze

Patrz `ecosystem/README.md`.
