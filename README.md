# TSL Manager — inna wersja (TSL Manager Editorial)

Druga, pełnoprawna wersja serwisu **TSL Manager**: te same funkcje co wersja 0.10.0
(`wordpress-playground (7).zip`), zupełnie inna koncepcja wizualna — *premium editorial /
modern business media*: winieta zamiast paska z logo, okładka zamiast siatki kart, pas
sygnałów RiskBrief, spis działów, kalendarium, typografia Newsreader + Instrument Sans.

Projekt jest niezależny od repozytorium `tsl-manager-` — tamten serwis nie jest tu zmieniany.

## Co jest w repozytorium

| Katalog | Co zawiera | Rodzaj |
| --- | --- | --- |
| `wordpress/wp-content/themes/tsl-editorial/` | **Nowy motyw** (wersja WordPress, docelowa) | źródło |
| `wordpress/wp-content/plugins/tsl-core/` | Wtyczka „TSL Manager — model treści” 0.10.0 — **bez zmian**, ta sama co w obecnym serwisie | źródło (kopia) |
| `demo/` | **Lekkie demo** do otwierania z dysku (`demo/index.html`) | wygenerowane |
| `tools/demo/` | Generator demo (`build.py`), strona startowa, skrypt i styl paska demo | źródło |
| `tools/dev/setup-local.sh` | Lokalny WordPress na SQLite (bez Dockera i MySQL) | narzędzie |
| `tools/package.sh` | Paczki ZIP motywu i wtyczki → `dist/` | narzędzie |
| `docs/` | Audyt i mapa funkcji, koncepcja, audyt końcowy, wdrożenie | dokumentacja |

## A. Wersja WordPress

Nowa wersja = **nowy motyw `tsl-editorial` na tej samej wtyczce `tsl-core`**. Treści,
ustawienia, adresy URL, formularze, newsletter, analityka i SEO pozostają we wtyczce,
więc motyw można włączyć na istniejącej instalacji TSL Manager i w każdej chwili wrócić
do starego motywu — bez utraty danych.

Szybko:

```bash
tools/package.sh            # dist/tsl-editorial-1.0.0.zip, dist/tsl-core-0.10.0.zip
```

1. Wtyczki → Dodaj nową → Wyślij: `tsl-core-0.10.0.zip` (jeśli serwis jej jeszcze nie ma) → Włącz.
2. Wygląd → Motywy → Dodaj nowy → Wyślij: `tsl-editorial-1.0.0.zip` → Włącz.
3. Ustawienia → Bezpośrednie odnośniki → Zapisz.

Pełna instrukcja (także test na kopii serwisu i powrót do starego motywu): [docs/WDROZENIE.md](docs/WDROZENIE.md).

### Test lokalny (bez Dockera)

Wymaga PHP 8.1+ z `pdo_sqlite`, `curl`, `unzip`.

```bash
tools/dev/setup-local.sh ~/tsl-local "wordpress-playground (7).zip"
cd ~/tsl-local && PHP_CLI_SERVER_WORKERS=4 php -S localhost:8090 -t wordpress router.php
# http://localhost:8090  (bez pliku ZIP: czysta instalacja, login admin / admin)
```

Z plikiem z WordPress Playground serwis startuje z tymi samymi treściami (36 artykułów,
materiały demonstracyjne, ilustracje). Motyw i wtyczka są dowiązane z repozytorium.

## B. Lekkie demo

Otwórz **`demo/index.html`** (dwuklik) → „Otwórz serwis”. Nie wymaga serwera ani
internetu; działa w Chrome, Edge, Firefox i Safari, także na telefonie. Opcjonalnie:

```bash
cd demo && python3 -m http.server 8000     # http://localhost:8000
```

Demo to zapis 170 stron serwisu (12,9 MB, ilustracje w WebP): wszystkie działy i obszary,
36 artykułów, RiskBrief, narzędzia, raport, wydarzenia, eksperci, partnerzy, newsletter,
kontakt, strony projektu. Działają: panele działów, menu na telefonie, wyszukiwarka
(dowolne hasło + filtry), filtry kolekcji i formatów, checklisty; formularze sprawdzają
pola i pokazują komunikat wersji demonstracyjnej (nic nie wysyłają). Szczegóły:
[demo/CZYTAJ-MNIE.txt](demo/CZYTAJ-MNIE.txt).

Odświeżenie demo po zmianach w motywie (serwis lokalny musi działać):

```bash
python3 tools/demo/build.py http://localhost:8090 demo     # wymaga Pillow
```

## Dokumentacja

1. [Audyt obecnego serwisu i mapa funkcji](docs/01-audyt-i-mapa-funkcji.md) — checklista F-01…F-63
2. [Nowa koncepcja](docs/02-koncepcja.md) — porównanie 0.10.0 ↔ nowa wersja, zasady kompozycji, responsywność
3. [Audyt końcowy](docs/03-audyt-koncowy.md) — funkcja po funkcji, testy, wydajność
4. [Wdrożenie](docs/WDROZENIE.md)
