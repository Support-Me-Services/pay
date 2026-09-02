# proto/ — współdzielone kontrakty gRPC

Jedno źródło prawdy dla kontraktów między `services/api-gateway` i
`services/core-svc` (a docelowo — jeśli PoC się powiedzie, patrz dokument
architektury — także `gateway-svc`/Laravel). Nie kopiujemy `.proto` ręcznie
między serwisami: każdy z nich generuje kod z tego samego katalogu przez
swój build (`protobuf-maven-plugin`, `protoSourceRoot` wskazujący tutaj).

## Konwencja

- Jeden katalog na domenę: `proto/<domena>/v1/<domena>.proto`.
- Pakiet proto zawsze `pay.<domena>.v1` — wersja w nazwie pakietu, nie w
  nazwie pliku, żeby breaking change dało się wprowadzić jako `v2` obok `v1`
  bez psucia klientów w trakcie migracji.
- `java_package` = nazwa pakietu proto 1:1 — zero niespodzianek przy
  generowaniu dla Kotlin/JVM.

## Co tu dziś jest

- `health/v1/health.proto` — pierwszy, celowo trywialny kontrakt
  (`HealthCheckService`). Demonstruje zasadę z dokumentu architektury:
  REST na brzegu (`api-gateway`), gRPC w środku. Realna domena (transakcje,
  sklepy...) dostanie własny katalog dopiero gdy będzie miała konkretnego
  właściciela (`gateway-svc` albo `core-svc`) — patrz `docs/` i dokument
  architektury co do kolejności faz.

## Generowanie kodu

Dziś: tylko strona JVM (Kotlin konsumuje wygenerowane klasy Java z
`protoc-gen-grpc-java` — prościej i stabilniej w Maven niż osobny generator
`grpc-kotlin`, do rozważenia jako later upgrade). Strona PHP (Laravel) nie
generuje jeszcze nic stąd — to zależy od wyniku PoC gRPC-w-PHP opisanego w
dokumencie architektury; do czasu jego rozstrzygnięcia `gateway-svc` nie jest
konsumentem tego katalogu.
