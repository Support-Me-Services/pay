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

## Faza 7 — efemeryczne środowisko testowe na GCP (per feature-branch)

**Decyzja: `pay-ephemeral` jest i ZOSTAJE WYŁĄCZNIE klastrem pod środowiska
testowe — jeśli produkcja kiedyś przeniesie się na Kubernetes, dostaje
WŁASNY, osobny klaster (`pay-prod` czy jak go tam nazwiemy), nigdy ten
sam.** Rozważaliśmy jeden wspólny klaster (testy + produkcja jako osobne
namespace'y) — oszczędzałby drugą opłatę za zarządzanie klastra (~$73/mies.,
bo darmowy kredyt GCP pokrywa tylko jeden klaster w projekcie). Świadomie
z tego zrezygnowaliśmy: to system przetwarzający płatności/darowizny —
stabilność i bezpieczeństwo produkcji są tu ważniejsze niż ta oszczędność.
Współdzielony klaster oznaczałby wspólne okna konserwacji/aktualizacji
węzłów, wspólny limit zasobów i większą powierzchnię ataku (m.in.
`housekeeper`, który ma uprawnienie do KASOWANIA namespace'ów w całym
klastrze — nie chcemy, żeby cokolwiek z tą możliwością współdzieliło
klaster z produkcją, nawet jeśli dziś celuje tylko w namespace'y z etykietą
`pay/ephemeral=true`). Nie warto tej izolacji poświęcać dla ~$73/mies.

Push na branchu `feature/**` automatycznie stawia cały stos na dedykowanym
klastrze GKE w OSOBNYM projekcie GCP (`please-support-me-test`, osobny
billing account niż produkcja — patrz sekcja wyżej o izolacji),
w osobnym namespace per branch, i kasuje go samoczynnie po godzinie
bezczynności. Szczegóły
architektury i uzasadnienie decyzji: plan Fazy 7 (`claude/marcin/
03-ekosystem-mikroserwisow.md`, sekcja "Faza 7"). W skrócie:

- `k8s/base/` — Kustomize referujący istniejące `k8s/*.yaml` bez zmian
  (lokalny `kubectl apply -f k8s/` dalej działa identycznie, nic tu nie rusza).
- `k8s/overlays/ephemeral/` — nakładka na chmurę: obrazy z Artifact Registry
  zamiast lokalnych tagów, `ClusterIP` zamiast `LoadBalancer` dla usług
  czysto wewnętrznych (bazy, `core-svc`), realm Keycloaka jako `ConfigMap`
  zamiast `hostPath` (fizycznie nie istnieje na GKE), obraz Laravela z
  zapieczonym kodem (`docker/Dockerfile.ci`) zamiast `hostPath` na repo.
- `k8s/housekeeper/` — `CronJob` **wdrażany ręcznie raz** (nie przez CI per
  branch) do namespace'u `pay-system`; co 5 minut kasuje namespace'y
  oznaczone `pay/ephemeral=true` starsze niż godzinę.

**Świadome ograniczenie**: efemeryczne środowisko testuje wyłącznie tenant
Storefront (nieznany host mapuje się dziś na `localhost` tylko w
`environment('local')`, patrz `ResolveTenant::applyTenant()`) — tenant
Gateway (`pay.please-support-me.com`) wymagałby drugiego hosta/adresu,
odłożone poza v1.

### Jednorazowy setup (raz na projekt, przed pierwszym pushem na feature-branch)

**Krok 0 (ręcznie, w konsoli GCP — tego nie da się zrobić przez gcloud/CLI
bez interakcji z formularzem płatności): załóż NOWY, osobny billing
account** dla `please-support-me-test` — https://console.cloud.google.com/billing/create
— z własną metodą płatności, całkowicie oddzielny od billing accountu
produkcji (`please-support-me-499509`). Zanotuj jego ID
(`gcloud billing accounts list` po założeniu pokaże `ACCOUNT_ID`).

```bash
# 1. Nowy projekt, podpięty pod nowy (nie produkcyjny!) billing account
gcloud projects create please-support-me-test --name="Pay — środowiska testowe"
gcloud billing projects link please-support-me-test --billing-account=<ACCOUNT_ID z kroku 0>

# 2. Włącz wymagane API (świeży projekt nie ma włączonego niczego)
gcloud services enable container.googleapis.com artifactregistry.googleapis.com \
  iam.googleapis.com iamcredentials.googleapis.com cloudresourcemanager.googleapis.com \
  --project=please-support-me-test

# 3. Klaster (Autopilot — bez zarządzania węzłami, płatność per zużyty zasób)
gcloud container clusters create-auto pay-ephemeral \
  --project=please-support-me-test --region=europe-central2

# 4. Rejestr obrazów
gcloud artifacts repositories create pay --repository-format=docker \
  --location=europe-central2 --project=please-support-me-test

# 5. Service account CI z uprawnieniami OGRANICZONYMI do tego, co potrzebne
gcloud iam service-accounts create github-ci --project=please-support-me-test
gcloud projects add-iam-policy-binding please-support-me-test \
  --member="serviceAccount:github-ci@please-support-me-test.iam.gserviceaccount.com" \
  --role=roles/container.developer
gcloud projects add-iam-policy-binding please-support-me-test \
  --member="serviceAccount:github-ci@please-support-me-test.iam.gserviceaccount.com" \
  --role=roles/artifactregistry.writer

# 6. Workload Identity Federation — GitHub Actions loguje się bez klucza JSON
#    (dokładne komendy: https://github.com/google-github-actions/auth#setting-up-workload-identity-federation)
#    Rezultat: sekrety repo GitHub GCP_WORKLOAD_IDENTITY_PROVIDER, GCP_SERVICE_ACCOUNT
#    (referowane w .github/workflows/ephemeral-env.yml).

# 7. Housekeeper (raz, nie per-branch)
kubectl apply -f k8s/housekeeper/
```

**Uwaga o kosztach**: osobny billing account oznacza, że darmowy kredyt
GCP na opłatę za zarządzanie klastra ($74.40/mies., patrz rozmowa o
cenach) przysługuje TU OSOBNO, niezależnie od tego, czy/kiedy produkcja
dostanie własny klaster na swoim billing accouncie — każdy z dwóch
billing accountów ma własny, pełny darmowy kredyt. Efektywnie: rozdzielenie
na 2 konta nie tylko izoluje bezpieczeństwo, ale też nie kosztuje nas
dodatkowo tej opłaty (w przeciwieństwie do 2 klastrów na JEDNYM koncie).

### Ręczne wyłączenie środowiska brancha (bez czekania na housekeeper)

```bash
kubectl delete namespace pay-eph-<slug-brancha>
```

(albo po prostu zamknij/zmerguj PR — drugi trigger w workflow robi to samo
automatycznie).
