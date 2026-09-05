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
klastrze GKE w OSOBNYM projekcie GCP (`please-support-me-test1`, osobny
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
account** dla `please-support-me-test1` — https://console.cloud.google.com/billing/create
— z własną metodą płatności, całkowicie oddzielny od billing accountu
produkcji (`please-support-me-499509`). Zanotuj jego ID
(`gcloud billing accounts list` po założeniu pokaże `ACCOUNT_ID`).

```bash
# 1. Nowy projekt, podpięty pod nowy (nie produkcyjny!) billing account
gcloud projects create please-support-me-test1 --name="Pay — środowiska testowe"
gcloud billing projects link please-support-me-test1 --billing-account=<ACCOUNT_ID z kroku 0>

# 2. Włącz wymagane API (świeży projekt nie ma włączonego niczego)
gcloud services enable container.googleapis.com artifactregistry.googleapis.com \
  iam.googleapis.com iamcredentials.googleapis.com cloudresourcemanager.googleapis.com \
  --project=please-support-me-test1

# 3. Klaster (Autopilot — bez zarządzania węzłami, płatność per zużyty zasób)
gcloud container clusters create-auto pay-ephemeral \
  --project=please-support-me-test1 --region=europe-central2

# 4. Rejestr obrazów
gcloud artifacts repositories create pay --repository-format=docker \
  --location=europe-central2 --project=please-support-me-test1

# 5. Service account CI z uprawnieniami OGRANICZONYMI do tego, co potrzebne
gcloud iam service-accounts create github-ci --project=please-support-me-test1
gcloud projects add-iam-policy-binding please-support-me-test1 \
  --member="serviceAccount:github-ci@please-support-me-test1.iam.gserviceaccount.com" \
  --role=roles/container.developer
gcloud projects add-iam-policy-binding please-support-me-test1 \
  --member="serviceAccount:github-ci@please-support-me-test1.iam.gserviceaccount.com" \
  --role=roles/artifactregistry.writer

# 5b. Węzły klastra (Compute Engine default SA, NIE github-ci — inna
#     tożsamość) potrzebują PRAWA ODCZYTU z rejestru, żeby w ogóle ściągnąć
#     obrazy — bez tego wszystkie pody wiszą w ErrImagePull/403 Forbidden
#     (złapane w pierwszym realnym teście Fazy 7, patrz notatka poniżej).
#     <PROJECT_NUMBER>: `gcloud projects describe please-support-me-test1 --format="value(projectNumber)"`.
gcloud projects add-iam-policy-binding please-support-me-test1 \
  --member="serviceAccount:<PROJECT_NUMBER>-compute@developer.gserviceaccount.com" \
  --role=roles/artifactregistry.reader

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

### Wyczyszczenie WSZYSTKIEGO jedną komendą

Cała infrastruktura testowa (klaster, rejestr obrazów, service account,
Workload Identity Federation, wszystkie namespace'y branchy) żyje w JEDNYM,
dedykowanym projekcie GCP (`please-support-me-test1`) na WŁASNYM billing
accouncie — nic z tego nie dotyka produkcji. Dlatego wyczyszczenie
wszystkiego to dosłownie jedna komenda:

```bash
gcloud projects delete please-support-me-test1
```

Kasuje projekt i WSZYSTKO w nim (klaster GKE, obrazy w Artifact Registry,
service account, WIF, wszystkie namespace'y) nieodwracalnie — po tym trzeba
by przejść cały "Jednorazowy setup" od nowa (z nowym ID projektu, ten
konkretny jest już zużyty raz i zostanie zarezerwowany przez Google nawet
po usunięciu). Sam billing account (`Moje konto rozliczeniowe`,
`marcin.lula@`) PRZEŻYWA usunięcie projektu — jeśli chcesz też jego się
pozbyć, rób to osobno w konsoli GCP (Billing → zamknij konto), nie jest to
konieczne (pusty billing account bez podpiętych projektów nic nie kosztuje).

## Faza 8 — produkcja na GKE (`pay-prod`, osobny projekt od `pay-ephemeral`)

**Decyzja (patrz Faza 7 wyżej): produkcja dostaje WŁASNY, osobny klaster —
nigdy `pay-ephemeral`.** Pełny plan (architektura sieci, retrofit Liquibase,
blue-green, konteneryzacja Laravela, CI/CD) w
`.claude/plans/fluffy-frolicking-galaxy.md` (ta sesja) — tu tylko runbook
jednorazowego setupu GCP dla Fazy 8.0.

**Kluczowa różnica względem setupu `please-support-me-test1` wyżej**: nowy
projekt produkcyjny wisi pod TYM SAMYM billing accountem co dzisiejszy
`please-support-me-499509` (właściciel: `founder@please-support-me.com`) —
**nie zakładamy nowego billing accountu**. Dane `nfc_pay`/`nfc_shop1` Cloud
SQL **zostają** w `please-support-me-499509` — nowy projekt/klaster łączy się
do nich przez sieć (VPC peering), nic się nie migruje.

**Kto wykonuje**: `marcin.lula@please-support-me.com` (ta sesja) nie ma
uprawnień IAM na `please-support-me-499509` — każdy krok oznaczony niżej
**[founder@]** musi wykonać osobiście `founder@please-support-me.com`. Kroki
oznaczone **[marcin.lula@]** można wykonać z tej sesji/konta na nowym
projekcie, gdy tylko projekt istnieje i jest podpięty pod billing.

### Jednorazowy setup — Faza 8.0 (fundament, zero ryzyka dla produkcji)

```bash
# --- Zmienne pomocnicze (ustaw raz na początku) ---
NEW_PROJECT=support-me-prod           # do potwierdzenia dostępności globalnej ID
OLD_PROJECT=please-support-me-499509
REGION=europe-central2                # SPRAWDŹ region istniejącej instancji Cloud SQL/VM
                                       # (gcloud sql instances describe / gcloud compute
                                       # instances list --project=$OLD_PROJECT) — użyj TEGO
                                       # SAMEGO regionu, żeby uniknąć cross-region latency/opłat

# 1. [founder@] Znajdź billing account ID istniejącego projektu produkcyjnego
gcloud billing projects describe $OLD_PROJECT --format="value(billingAccountName)"
# -> zwraca coś jak billingAccounts/XXXXXX-XXXXXX-XXXXXX, zanotuj jako BILLING_ACCOUNT_ID

# 2. [founder@] Nowy projekt, podpięty pod ISTNIEJĄCY (produkcyjny) billing account
gcloud projects create $NEW_PROJECT --name="Pay — produkcja (ekosystem)"
gcloud billing projects link $NEW_PROJECT --billing-account=<BILLING_ACCOUNT_ID z kroku 1>

# 3. [founder@ albo marcin.lula@, jeśli dostanie rolę Editor na nowym projekcie] Włącz API
gcloud services enable container.googleapis.com artifactregistry.googleapis.com \
  iam.googleapis.com iamcredentials.googleapis.com cloudresourcemanager.googleapis.com \
  sts.googleapis.com sqladmin.googleapis.com servicenetworking.googleapis.com \
  secretmanager.googleapis.com compute.googleapis.com \
  --project=$NEW_PROJECT

# 4. [marcin.lula@ na nowym projekcie] Klaster Autopolot REGIONALNY (nie zonalny —
#    HA control-plane, priorytet stabilności dla systemu płatności), private nodes + Cloud NAT
gcloud container clusters create-auto pay-prod \
  --project=$NEW_PROJECT --region=$REGION \
  --enable-private-nodes
# Cloud NAT: Autopilot regionalny z --enable-private-nodes wymaga Cloud Router + NAT dla
# ruchu wychodzącego węzłów (patrz pułapka #10, Faza 7, ecosystem notes) — dopisać jeśli
# `create-auto` sam tego nie skonfiguruje.

# 4b. [founder@ na starym projekcie, PRZED realnym cutoverem] Podnieś proaktywnie kwotę
#     CPUS-ALL-REGIONS-per-project na nowym projekcie — Faza 7 utknęła na limicie 12
#     reaktywnie (pułapka #11); tu robimy to z wyprzedzeniem, budżet 2x steady-state
#     (dwie generacje pod naraz podczas blue-green). Dokładna wartość: DO USTALENIA
#     wspólnie po oszacowaniu realnego obciążenia wszystkich serwisów.
gcloud alpha quotas info list --project=$NEW_PROJECT --service=compute.googleapis.com \
  --filter="quota_id:CPUS-ALL-REGIONS-per-project"

# 5. [marcin.lula@] Rejestr obrazów
gcloud artifacts repositories create pay --repository-format=docker \
  --location=$REGION --project=$NEW_PROJECT

# 6. [marcin.lula@] Service account CI (analogicznie do ephemeral, ograniczone uprawnienia)
gcloud iam service-accounts create github-ci --project=$NEW_PROJECT
gcloud projects add-iam-policy-binding $NEW_PROJECT \
  --member="serviceAccount:github-ci@${NEW_PROJECT}.iam.gserviceaccount.com" \
  --role=roles/container.developer
gcloud projects add-iam-policy-binding $NEW_PROJECT \
  --member="serviceAccount:github-ci@${NEW_PROJECT}.iam.gserviceaccount.com" \
  --role=roles/artifactregistry.writer

# 6b. Węzły klastra (Compute Engine default SA) potrzebują odczytu z rejestru —
#     sam ten sam gotcha co w Fazie 7 (ErrImagePull/403 bez tego)
gcloud projects add-iam-policy-binding $NEW_PROJECT \
  --member="serviceAccount:$(gcloud projects describe $NEW_PROJECT --format='value(projectNumber)')-compute@developer.gserviceaccount.com" \
  --role=roles/artifactregistry.reader

# 7. Workload Identity Federation dla GitHub Actions (jak w Fazie 7, ale scoped do
#    NOWEGO projektu i osobnego workflow production-deploy.yml)
#    https://github.com/google-github-actions/auth#setting-up-workload-identity-federation
#    -> sekrety repo: GCP_WORKLOAD_IDENTITY_PROVIDER_PROD, GCP_SERVICE_ACCOUNT_PROD
#    (osobne nazwy sekretów od ephemeral, żeby oba workflowy współistniały bez kolizji)

# --- Sieć: piaskownica VPC peering (Faza 8.0 cel — DOWIEŚĆ, że to działa, ZANIM
#     cokolwiek na tym zbudujemy) ---

# 8. [founder@ na starym projekcie] Sprawdź nazwę i CIDR istniejącego VPC
gcloud compute networks list --project=$OLD_PROJECT
gcloud compute networks subnets list --project=$OLD_PROJECT --filter="region:$REGION"
# Zanotuj CIDR — nowy VPC (krok 9) NIE MOŻE się z nim nakładać.

# 9. [marcin.lula@] Nowy VPC w nowym projekcie (custom-mode, CIDR bez nakładania z krokiem 8)
gcloud compute networks create pay-prod-vpc --project=$NEW_PROJECT --subnet-mode=custom
gcloud compute networks subnets create pay-prod-subnet \
  --project=$NEW_PROJECT --network=pay-prod-vpc --region=$REGION \
  --range=<CIDR niekolidujący z krokiem 8, np. 10.90.0.0/20>

# 10. [founder@ + marcin.lula@ razem — peering jest dwustronny] Classic VPC peering
#     między nowym a starym VPC
gcloud compute networks peerings create pay-prod-to-old \
  --project=$NEW_PROJECT --network=pay-prod-vpc \
  --peer-project=$OLD_PROJECT --peer-network=<nazwa starego VPC z kroku 8> \
  --export-custom-routes --import-custom-routes
gcloud compute networks peerings create old-to-pay-prod \
  --project=$OLD_PROJECT --network=<nazwa starego VPC z kroku 8> \
  --peer-project=$NEW_PROJECT --peer-network=pay-prod-vpc \
  --export-custom-routes --import-custom-routes

# 11. [founder@ na starym projekcie] Włącz export/import custom routes też na
#     ISTNIEJĄCYM peeringu starego VPC do sieci producenta Cloud SQL (Private
#     Services Access) — bez tego trasa do 10.60.96.3 nie "przeleci" dalej
#     przez peering z kroku 10 (VPC peering jest nietranzytywny)
gcloud services vpc-peerings update --service=servicenetworking.googleapis.com \
  --network=<nazwa starego VPC> --project=$OLD_PROJECT \
  --export-custom-routes --import-custom-routes

# 12. [founder@ na starym projekcie] Cross-project IAM: pozwól service accountowi
#     Cloud SQL Auth Proxy (nowy projekt) łączyć się z instancjami w starym projekcie
gcloud projects add-iam-policy-binding $OLD_PROJECT \
  --member="serviceAccount:github-ci@${NEW_PROJECT}.iam.gserviceaccount.com" \
  --role=roles/cloudsql.client
# (docelowy runtime service account podów, nie tylko CI — dopisać drugi binding
#  dla Workload Identity SA laravel-web/laravel-grpc w Fazie 8.2, gdy powstanie)

# --- Weryfikacja Fazy 8.0 (cel: DOWÓD, że sieć działa, zanim cokolwiek na tym zbudujemy) ---
# Postaw jednorazowy testowy pod w pay-prod z Cloud SQL Auth Proxy (--private-ip)
# celujący w 10.60.96.3 (albo w tymczasową testową instancję w starym projekcie,
# jeśli wolimy nie dotykać żywej instancji nawet read-only na tym etapie — DO USTALENIA),
# potwierdź że `psql` przez proxy faktycznie się łączy. Dopiero to jest zielone światło
# na Fazę 8.1.
```

**Sprawdzenie, czy backupy `nfc_pay`/`nfc_shop1` są już włączone** (potrzebne
niezależnie od powyższego, do decyzji czy Faza 8.0/8.1 musi je dopiero włączyć):

```bash
# [founder@]
gcloud sql instances describe <nazwa-instancji> --project=$OLD_PROJECT \
  --format="value(settings.backupConfiguration.enabled,settings.backupConfiguration.pointInTimeRecoveryEnabled)"
```

**Otwarte do ustalenia przed/w trakcie Fazy 8.0** (patrz plan, sekcja "Otwarte
pytania"): dokładna nazwa/ID projektu jeśli `support-me-prod` zajęte globalnie,
docelowa kwota CPU, czy test sieciowy w kroku "Weryfikacja" celuje w żywą
instancję (read-only) czy w tymczasową kopię.
