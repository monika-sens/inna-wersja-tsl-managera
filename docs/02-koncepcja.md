# Etap 3. Nowa koncepcja: TSL Manager — wydanie redakcyjne

Robocza nazwa motywu: **TSL Manager Editorial** (`tsl-editorial`).
Kierunek: *premium editorial / modern business media* — serwis czytany jak tygodnik
biznesowy i raport strategiczny, a nie przeglądany jak katalog kafelków.

## Idea przewodnia

Obecna wersja (0.10.0) jest **portalem**: granat i miedź, siatki równych kart, ciemne panele
i ramki, ten sam rytm „nagłówek sekcji → siatka kart” na całej stronie.

Nowa wersja jest **wydaniem**: papier i druk, typografia niesie hierarchię, a każda sekcja ma
inną gęstość — jak kolejne strony magazynu. Zamiast kart: okładka, spis działów, lista
z numerami, tabela, kalendarium, okładka raportu.

| | Wersja 0.10.0 (portal) | Nowa wersja (wydanie) |
| --- | --- | --- |
| Paleta | granat #0B1D33, miedź #B8734A, kremowa biel | papier #F2EEE6, czerń drukarska #15171A, jeden akcent: cynober „sygnał” #D23C1F (tekst #B02A12), kamień #E6DFD1 |
| Typografia | Cormorant Garamond (nagłówki) + Inter | **Newsreader** (krój prasowy, nagłówki 600–700 i tekst artykułów) + **Instrument Sans** (interfejs, etykiety kapitalikami) |
| Nagłówek | logo po lewej, filary-przyciski, ikona lupy, CTA, zawsze przyklejony | **winieta** gazetowa: data wydania, duży znak słowny pośrodku, pod nim pasek działów numerowanych 01–06; po przewinięciu winieta chowa się, zostaje cienki pasek działów z małym znakiem |
| Mega menu | biały panel w 4 kolumnach z kartą | panel „spis działu”: duża nazwa działu i obietnica, obszary w dwóch kolumnach z numeracją, polecany tekst ze zdjęciem; czerwona linia otwarcia |
| Menu mobilne | biały pełny ekran, akordeon | czarny pełny ekran z dużymi nazwami działów; dodatkowo **przewijany poziomo pasek działów** pod winietą (szybki dostęp bez otwierania menu) |
| Hero | zdjęcie nad tytułem + ciemny panel RiskBrief z prawej | **okładka**: gigantyczny tytuł na 10 kolumnach, pod nim asymetrycznie zajawka (4 kol.) i duże zdjęcie (8 kol.) dochodzące do krawędzi ekranu |
| RiskBrief na głównej | pionowy panel w hero | **pas sygnałów** na pełną szerokość, czarne tło, kolumny z numerami i słownym poziomem ryzyka; na telefonie przewijany poziomo |
| Filary | 6 równych kafelków | **spis działów** — numerowane wiersze na całą szerokość: numer, nazwa (duży krój szeryfowy), obietnica, obszary |
| Najnowsze | siatka 3×2 identycznych kart | układ o różnej gęstości: jeden duży tekst ze zdjęciem, dwa średnie, lista numerowana |
| Narzędzia | siatka 6 kart | **„Warsztat”** — indeks w formie tabeli: typ, tytuł, opis, akcja; pierwsze narzędzie wyróżnione typograficznie |
| Raport | biała karta | **okładka raportu** (pionowy blok z rokiem i tytułem) obok tekstu |
| Wydarzenia | lista tekstowa | **kalendarium** z dużą datą (dzień, miesiąc) |
| Newsletter | szary prostokąt z formularzem | pas „kamień” z dużym tytułem i polem e-mail w formie linii |
| Artykuł | nagłówek + zdjęcie + kolumna z panelem bocznym (karty) | układ magazynowy: duży tytuł, lead kursywą, pasek autora między liniami, szerokie zdjęcie, tekst szeryfowy 20 px w wąskiej kolumnie, **marginesy** (kontekst, ekspert) po lewej, **następny krok** jako pas po tekście, pasek postępu czytania |
| Listy w działach | karty-wiersze z ramką | „rzeka” tekstów: data w kolumnie, tytuł, zajawka, miniatura; linie zamiast ramek |
| Filtry | „pigułki” (chips) | zakładki tekstowe z podkreśleniem |
| Formularze | pola w ramkach na białych kartach | pola „na linii” (podkreślenie), etykiety kapitalikami, przyciski czarne |
| Stopka | granatowa, 4 kolumny | jasna, indeks linków w kolumnach i **znak słowny na całą szerokość** jako podpis wydania |
| Ikonografia | ikony konturowe w wielu miejscach | prawie bez ikon: strzałki, numeracja, typografia; ikony tylko w przyciskach funkcyjnych (lupa, menu, druk) |
| Grafika zastępcza | droga ku horyzontowi w granacie | typograficzna plansza w kolorze papieru z nazwą działu i numerem |

## Zasady kompozycji

- **Siatka 12 kolumn**, szerokość treści do 1320 px; świadome przełamania: zdjęcie okładki
  wychodzi do krawędzi okna, pas RiskBrief i pas newslettera na pełną szerokość.
- **Rytm redakcyjny**: okładka (gęsto, duża skala) → pas sygnałów (ciemny, poziomy) → spis
  działów (jasny, liniowy) → najnowsze (mieszany) → dossier „Decyzja pod lupą” (kamień) →
  warsztat (tabela) → koszt (typograficzny) → newsletter → raport i kalendarium → głosy i partnerzy.
- **Otwarcia sekcji**: numer sekcji + tytuł 40–56 px + krótki opis; link „więcej” jako tekst ze strzałką.
- **Linie zamiast ramek**: separatory 1 px w kolorze #CFC7B8, grube linie 3 px (czarne
  lub czerwone) otwierają ważne bloki. Zaokrąglenia: 0.
- **Akcent oszczędnie**: czerwony tylko dla sygnałów ryzyka, liczb porządkowych i głównych CTA.
- **Zdjęcia rzadziej i większe**: okładka, główny tekst w „Najnowszych”, dossier, artykuł,
  wyróżnienie działu. Listy są typograficzne.

## Mikrointerakcje

- podkreślenie linków rośnie przy najechaniu / fokusie;
- strzałki przesuwają się o 4 px;
- winieta zwija się płynnie do paska działów (bez skoków układu — wysokość zarezerwowana);
- pasek postępu czytania w artykule (2 px, czerwony);
- tabele w artykułach: przewijanie poziome z cieniem-wskazówką, gdy tabela jest szersza niż ekran;
- wszystkie animacje wyłączone przy `prefers-reduced-motion`.

## Responsywność (projektowana osobno)

| Szerokość | Rozwiązanie |
| --- | --- |
| ≥ 1200 px | pełna winieta, pasek działów z pełnymi nazwami, okładka 10/4/8 kolumn |
| 900–1199 px | pasek działów z krótkimi nazwami, okładka 12/5/7 |
| 600–899 px (tablet) | winieta kompaktowa, pasek działów przewijany poziomo, przycisk „Menu”; sekcje w 2 kolumnach |
| < 600 px (telefon) | znak słowny po lewej, przyciski Szukaj/Menu; tytuł okładki 40–44 px; pas RiskBrief i najnowsze przewijane poziomo (scroll-snap) lub jako lista; tabele narzędzi jako bloki; formularze w jednej kolumnie; przyciski min. 44 px |

## Architektura techniczna

- Nowy motyw `tsl-editorial` (bez frameworków, bez jQuery): 1 CSS, 1 JS (`defer`),
  fonty lokalne woff2 z `unicode-range` (bez Google Fonts — RODO).
- Wtyczka `tsl-core` 0.10.0 **bez zmian** — ten sam model treści, formularze, newsletter,
  analityka, SEO, tryb demo, importy.
- Zachowane kontrakty z wtyczką (klasy, atrybuty `data-*`, nazwy pól).
- Zachowana opcja modułów strony głównej (`tsl_home_modules_off`) i ekran *Wygląd → Strona główna*.
- Ulepszenia bez zmiany danych: kalkulator `[tsl_kalkulator]` działa także **bez** Cost Calculator
  Builder (motyw rysuje go z tej samej definicji `ccb-kalkulatory.json`), pasek postępu czytania,
  podpowiedź przewijania tabel, przewijany pasek działów na telefonie.
