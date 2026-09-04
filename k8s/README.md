# k8s/ — cały stack lokalnie na Kubernetesie (Docker Desktop)

Zamiennik dla `docker/` + `ecosystem/` (docker-compose) — **jeden klaster,
jeden namespace (`pay`)**, wszystkie siedem komponentów widzi się nawzajem
po nazwie usługi (DNS klastra), bez `host.docker.internal` — to była
konieczność przy dwóch osobnych projektach docker-compose, na Kubernetesie
znika naturalnie.

## Wymagania

- Docker Desktop z włączonym Kubernetesem (Settings → Kubernetes → Enable
  Kubernetes). Ten sam silnik Dockera co zawsze — obrazy budowane lokalnie
  (`docker build`/`docker compose build`) są od razu widoczne klastrowi,
  bez rejestru.
- `kubectl` (w komplecie z Docker Desktop:
  `C:\Program Files\Docker\Docker\resources\bin\kubectl.exe`).
- Kontekst `docker-desktop` aktywny: `kubectl config use-context docker-desktop`.

## Budowa obrazów (przed pierwszym `kubectl apply`)

```bash
docker build -t pay-laravel:local -f docker/Dockerfile docker/
cd ecosystem && docker compose build core-svc api-gateway
```

Po każdej zmianie kodu Javy/PHP: przebuduj odpowiedni obraz, potem
`kubectl rollout restart deployment/<nazwa> -n pay` (Laravel/PHP nie
wymaga rebuildu obrazu — kod wchodzi przez hostPath, patrz niżej).

## Uruchomienie

```bash
kubectl apply -f k8s/
```

Kolejność w nazwach plików (`00-`, `01-`...) zapewnia, że namespace i
sekrety powstają przed obiektami, które ich używają — `kubectl apply -f
k8s/` stosuje cały katalog za jednym razem, kolejność plików w obrębie
katalogu i tak nie jest gwarantowana przez samo kubectl, ale przy
`Deployment`/`Service` to nieistotne (obiekty tworzą się niezależnie od
kolejności, Kubernetes sam czeka na zależności przy starcie kontenerów —
`initContainers`/readiness tam gdzie to ma znaczenie, patrz niżej).

## Dostęp z hosta

Usługi wcześniej wystawione na porty hosta w docker-compose są tu typu
`LoadBalancer` — to specjalna, wygodna cecha Kubernetesa Docker Desktop:
`LoadBalancer` automatycznie binduje się na `localhost:<port>`, bez
`kubectl port-forward` i bez prawdziwego load balancera w chmurze.

| Usługa | Port hosta | Odpowiednik w docker-compose |
|---|---|---|
| `laravel-app` | `8000` | `docker/docker-compose.yml`, `app` |
| `api-gateway` | `8081` | `ecosystem/docker-compose.yml`, `api-gateway` |
| `core-svc` | `8082` (REST) | `ecosystem/docker-compose.yml`, `core-svc` |
| `keycloak` | `8180` | `ecosystem/docker-compose.yml`, `keycloak` |
| `db` (MySQL) | `13306` | `docker/docker-compose.yml`, `db` |
| `postgres-core` | `5433` | `ecosystem/docker-compose.yml`, `postgres-core` |

`postgres-keycloak` i wewnętrzny port gRPC `core-svc` (`9090`) oraz gRPC
`laravel-app` (`9091`) zostają `ClusterIP` — tylko wewnątrz klastra, tak
jak wcześniej `postgres-keycloak` nie miał w ogóle wpisu `ports:` w
docker-compose.

## Wolumeny

`laravel-app` montuje repo (hostPath) dokładnie tak jak bind-mount
`..:/app` w docker-compose — edycja plików na hoście jest natychmiast
widoczna w podzie. `vendor/` i `storage/` to osobne `PersistentVolumeClaim`
(odpowiednik nazwanych wolumenów Dockera `vendor`/`appstorage` — celowo
NIE na hostPath, ten sam powód co w komentarzu docker-compose: wolny mount
Windows/macOS zamienia każde żądanie w odczyt tysięcy plików).

`postgres-core`, `postgres-keycloak`, `db` (MySQL) też dostały PVC — w
docker-compose nie miały nazwanych wolumenów (dane ginęły przy każdym
`docker compose down`), tutaj to celowa poprawa: restart poda (częstszy w
k8s niż w compose) nie kasuje bazy za każdym razem.

## Samowystarczalny start Laravela — koniec ręcznego `docker exec`

`docker/entrypoint.sh` (Faza 5.5) sam pobiera `rr`/`protoc-gen-php-grpc`,
generuje klasy PHP z `proto/` i odpala `rr serve` w tle — przez całą tę
sesję ten krok trzeba było robić ręcznie po każdym starcie kontenera. Teraz
`kubectl apply -f k8s/` + chwila na start = od razu działający,
kompletny stack, bez żadnej ręcznej interwencji.

## Powrót do docker-compose

`docker/` i `ecosystem/` (docker-compose) nadal działają bez zmian —
`k8s/` to dodatkowa opcja, nie zamiennik. `entrypoint.sh` (samowystarczalny
start `rr serve`) działa identycznie w obu światach.

## Wyłączenie

```bash
kubectl delete -f k8s/
```

(PVC-e przeżywają `kubectl delete -f k8s/`, jeśli nie usuniesz ich jawnie —
`kubectl delete pvc -n pay --all`, żeby też skasować dane baz.)
