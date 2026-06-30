# Parafie rzymskokatolickie w Polsce — dataset OSM

**Łączna liczba obiektów:** 20332

**Data pobrania:** 2026-06-15  
**Źródło:** OpenStreetMap, pobrane przez Overpass API  
**Filtr zapytania:** `amenity=place_of_worship` + `religion=christian`, zawężone do denominacji katolickich (`roman_catholic`, `catholic`, `greek_catholic`, `fsspx`, `old_catholic`, `polish_catholic` i pokrewne; obiekty bez `denomination` zaliczono do katolickich, gdyż w Polsce stanowią w przeważającej większości kościoły rzymskokatolickie).

## Rozbicie per województwo

| Województwo | Liczba parafii |
|---|---:|
| małopolskie | 2025 |
| wielkopolskie | 1948 |
| mazowieckie | 1904 |
| dolnośląskie | 1872 |
| podkarpackie | 1741 |
| śląskie | 1631 |
| zachodniopomorskie | 1358 |
| lubelskie | 1179 |
| warmińsko-mazurskie | 976 |
| łódzkie | 961 |
| pomorskie | 908 |
| opolskie | 901 |
| kujawsko-pomorskie | 877 |
| świętokrzyskie | 719 |
| lubuskie | 678 |
| podlaskie | 654 |
| **RAZEM** | **20332** |

## Metoda

- Pobranie per województwo (16 zapytań), obszar rozwiązywany po tagu `ISO3166-2` (np. `PL-14`) na `admin_level=4` — pewniejszym niż `name`.
- Obsługa rate-limitu: `sleep 35` między województwami, backoff i retry (max 5 prób) przy HTTP 429/504, rotacja mirrorów Overpass (overpass-api.de → kumi.systems → maps.mail.ru).
- Deduplikacja po kluczu `(name + round(lat,4) + round(lon,4))`.
- Pominięto obiekty bez tagu `name`.

## Uwaga o kompletności

OpenStreetMap **nie zawiera wszystkich** parafii rzymskokatolickich w Polsce. Pokrycie jest nierównomierne — zależy od aktywności lokalnych mapowiczów. Liczby należy traktować jako dolne oszacowanie. Dla porównania: w Polsce działa ok. 10 tys. parafii rzymskokatolickich (dane GUS/Kościoła), więc ten zbiór obejmuje znaczną, lecz niepełną ich część (część obiektów to też kościoły filialne, kaplice i klasztory, a nie wyłącznie kościoły parafialne).

## Pliki wyjściowe

- `parishes.csv` — kolumny: name, city, voivodeship, denomination, lat, lon
- `parishes.geojson` — FeatureCollection (Point) z properties: name, city, voivodeship
- `coverage-map.html` — samodzielna mapa Leaflet + markercluster (dane inline, działa bez serwera)
- `SUMMARY.md` — ten plik
