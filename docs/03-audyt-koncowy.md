# Etapy 5–8. Test funkcjonalny, responsywność, poprawki, optymalizacja — audyt końcowy

Porównanie: **TSL Manager 0.10.0** (motyw `tsl-manager`, plik `wordpress-playground (7).zip`)
↔ **TSL Manager Editorial 1.0.0** (motyw `tsl-editorial`). Obie wersje testowane na tej samej
bazie i tej samej wtyczce `tsl-core` 0.10.0 (WordPress 7.1.2, PHP 8.3, SQLite, język polski,
tryb demonstracyjny), Chromium/Playwright.

Oznaczenia: **Z** — zachowana · **ZI** — zachowana, przedstawiona inaczej · **U** — ulepszona ·
**!** — wymaga uwagi.

## A. Struktura i nawigacja

| ID | Funkcja | Ocena | Jak jest w nowej wersji |
| --- | --- | --- | --- |
| F-01 | Pasek informacyjny | ZI | „Pasek wydania”: data wydania + te same linki; „Nowy” przy RiskBrief z 7 dni. Na telefonie data i RiskBrief, reszta w menu |
| F-02 | Logo | ZI | Typograficzny znak słowny (tekst, nie grafika); logo nadal robocze |
| F-03 | Nawigacja: 6 filarów + Narzędzia | ZI | Pasek działów z numerami 01–06; krótkie nazwy poniżej 1380 px |
| F-04 | Mega menu | ZI | Panel działu: obietnica, obszary (numerowane, 2 kolumny), Transport/Spedycja lub „Więcej obszarów”, polecany materiał ze zdjęciem — ta sama logika i dane |
| F-05 | Zamykanie paneli | Z | Esc + powrót fokusu, klik poza, wyjście fokusem, jeden panel naraz (test automatyczny) |
| F-06 | Wyszukiwarka w nagłówku | U | Ten sam panel z podpowiedziami; dodatkowo dostępna po zwinięciu winiety |
| F-07 | CTA „RiskBrief na e-mail” | Z | W winiecie (≥1080 px), z `data-track` |
| F-08 | Menu mobilne | U | Pełny ekran (ciemny), te same elementy, pułapka fokusu, `inert`, Esc; **dodatkowo** przewijany poziomo pasek działów pod winietą — dział jednym dotknięciem |
| F-09 | Nagłówek sticky | U | Szeroki ekran: winieta zwija się, pasek działów zostaje (z małym znakiem i lupą). Telefon: nagłówek chowa się przy czytaniu w dół, wraca przy przewinięciu w górę |
| F-10 | Skip link | Z | |
| F-11 | Breadcrumbs | Z | Ta sama logika; długi tytuł skracany wielokropkiem |
| F-12 | Stopka | ZI | Jasna, indeks: Działy (numerowane), Korzystaj, Projekt; znak słowny na całą szerokość; dolna belka z dokumentami i „Ustawienia prywatności” |
| F-13 | Lokalizacje menu | Z | `footer-project`, `footer-legal` |

## B. Strona główna

| ID | Moduł | Ocena | Nowa forma |
| --- | --- | --- | --- |
| F-14 | Pilny sygnał | ZI | Czerwony pas pod paskiem działów |
| F-15 | Temat tygodnia | ZI | **Okładka**: tytuł na 11 kolumnach, lead + meta + CTA obok zdjęcia dochodzącego do krawędzi; jeden H1; wariant bez tematu (H1 = nazwa serwisu) zachowany |
| F-16 | RiskBrief | ZI | **Pas sygnałów** na pełną szerokość, numerowane kolumny; telefon — przewijanie poziome |
| F-17 | Sześć filarów | ZI | **Spis działów** — wiersze: numer, nazwa, obietnica, obszary |
| F-18 | Najnowsze analizy | ZI | Różna gęstość: 1 duży + 2 średnie + lista numerowana; bez powtórzeń |
| F-19 | Decyzja pod lupą | ZI | **Dossier** na tle „kamień”: przyklejony nagłówek, materiał flagowy, 2 numerowane |
| F-20 | Narzędzia | ZI | **Indeks narzędzi** (tabela: typ, tytuł, opis, akcja), pierwsze wyróżnione |
| F-21 | Ile to kosztuje + kalkulator | ZI | Typograficzny znak „zł/km”, materiał, ciemna karta kalkulatora |
| F-22 | Transport i operacje (zakładki) | Z / ! | Kod i zachowanie ARIA zachowane; moduł nie wyświetla się, bo artykuły nie mają rodzaju transportu — **jak w 0.10.0** (do uzupełnienia przez redakcję) |
| F-23 | Pierwsze 24 h | ZI | Ciemna sekcja z procedurą |
| F-24 | Newsletter | ZI | Pas z dużym tytułem i polem „na linii” |
| F-25 | Raporty + Wydarzenia | ZI | **Okładka raportu** (rok, tytuł) + **kalendarium** z dużymi datami |
| F-26 | Eksperci | ZI | „Głosy”: inicjały/zdjęcie, rola |
| F-27 | Partnerzy | Z | Rola jawna, logotypy w skali szarości |
| F-28 | Wygląd → Strona główna | Z | Ta sama opcja w bazie — ustawienia przechodzą między motywami |
| F-29 | `data-track-module` | Z | Te same nazwy modułów |

## C. Typy stron

| ID | Strona | Ocena | Uwagi |
| --- | --- | --- | --- |
| F-30 | Filar | ZI | Otwarcie „Dział 0X” z bardzo dużym tytułem, spis obszarów, wyróżnienie, lista, margines z narzędziami i sygnałami (gdy pusty — lista na pełną szerokość) |
| F-31 | Podkategoria | ZI | Filtr formatów jako zakładki tekstowe; puste stany jak w 0.10.0 |
| F-32 | Artykuł | U | Układ magazynowy: lead kursywą, inicjał, szeroka ilustracja, tekst szeryfowy 20 px, margines (następny krok, kontekst, ekspert); **pasek postępu czytania**; tabele przewijane z podpowiedzią (101 tabel w artykułach) |
| F-33 | RiskBrief | ZI | Te same sekcje; poziom ryzyka — znak + słowo (kwadrat/trójkąt/koło/obwódka, nie tylko kolor) |
| F-34 | Narzędzie | Z | Wersja, data, „Jak korzystać”, pobranie / formularz po e-mailu, zastrzeżenie, powiązane |
| F-35 | Checklista | Z | Odhaczanie, licznik (duże cyfry), pasek, punkty krytyczne + ostrzeżenie, uwagi, localStorage, wyczyść, druk samej checklisty (test automatyczny) |
| F-36 | e-TOLL | Z | Widżet wtyczki ostylowany w nowym języku |
| F-37 | Kalkulator kosztu km | U | W 0.10.0 bez Cost Calculator Builder kalkulator nie wyświetla się wcale. Motyw rysuje go z tej samej definicji (pola, limity, wzory) — wyniki identyczne (4,94 / 6,39 zł; przebieg 0 → „—”). Z CCB działa kalkulator wtyczki. **Decyzja właścicielki (6.10.2026): kalkulator nie jest publikowany** — narzędzie zostaje „w przygotowaniu”, treść w bazie bez zmian; mechanizm w motywie jest nieaktywny, dopóki redakcja nie wstawi kodu `[tsl_kalkulator]` |
| F-38 | Raport | Z | Wnioski, pobranie, metodologia, autorzy, powiązane |
| F-39 | Wydarzenie | ZI | Fakty w pasku metadanych, agenda, prelegenci, rejestracja / nagranie / materiały; obsługa TEC zachowana |
| F-40 | Ekspert | Z | |
| F-41 | Partner | Z | `rel="sponsored"` zachowane |
| F-42 | Wydanie newslettera | Z | Sekcje automatyczne, zachęta, archiwum |
| F-43 | Archiwa kolekcji | ZI | Każda kolekcja ma własną formę: dziennik sygnałów, indeks narzędzi, kalendarium, półka raportów, lista osób, lista partnerów; filtry i statusy jak w 0.10.0 |
| F-44 | Wyszukiwarka | U | Duże pole „na linii”, te same filtry i podpowiedzi; poprawna polska odmiana („3 materiały”, „19 materiałów”) |
| F-45 | Kontakt | Z | `?temat=` wybiera temat (sprawdzone) |
| F-46 | Newsletter | Z | `?nl=potwierdzono|wypisano|blad`, potwierdzenie wypisania (sprawdzone) |
| F-47 | Strony informacyjne | ZI | |
| F-48 | 404 | ZI | Wyszukiwarka + numerowany spis działów |
| F-49 | Widoki TEC | Z | Szablon zachowany (TEC nieaktywny w bazie testowej — nie testowano na żywo) |

## D. Formularze i interakcje

| ID | Funkcja | Ocena | Uwagi |
| --- | --- | --- | --- |
| F-50 | Formularze | ZI | Pola „na linii”; te same nazwy pól, atrybuty, walidacja, komunikaty, REST (test: błędy przy polach, komunikat trybu demo) |
| F-51 | Newsletter | Z | Walidacja e-mail i zgody, REST (test) |
| F-52 | Analityka | Z | Atrybuty `data-track*`, `data-search-*`, `.card-title` w tytułach, baner zgody ostylowany |
| F-53 | SEO | Z | Z wtyczki; motyw: `title-tag` |
| F-54 | Oznaczenia | Z | Materiał partnerski na listach i w artykule; ryzyko tekstem |
| F-55 | Puste stany | Z | |
| F-56 | Grafika zastępcza | ZI | Plansza z numerem i nazwą działu |

## E. Jakość techniczna

| ID | Wymóg | Ocena | Wynik |
| --- | --- | --- | --- |
| F-57 | Nagłówki | Z | Dokładnie jeden H1 na 23 typach stron × 11 szerokości; brak skoków poziomów, brak powtórzonych id (15 stron) |
| F-58 | Klawiatura / dostępność | Z | Fokus widoczny, skip link, pułapka fokusu, ARIA; nagłówek mobilny nie chowa się przy fokusie z klawiatury; `prefers-reduced-motion` |
| F-59 | Responsywność | U | 360, 390, 600, 768, 820, 1024, 1180, 1280, 1366, 1440, 1920 px × 23 strony: bez poziomego przewijania. Osobne rozwiązania na telefon (pasek działów, chowany nagłówek, przewijane sygnały, tabele) |
| F-60 | Wydajność | Z / ! | CSS 17,5 kB gzip (0.10.0: 11,3 kB), JS 6,2 kB gzip (4,3 kB), bez jQuery i bibliotek; fonty 136 kB na stronę (+104 kB kursywy, gdy użyta; 0.10.0: ok. 180 kB); **mniej zapytań** — usunięty skrypt emoji WordPressa (zewnętrzny CDN). Ilustracje w Mediach to PNG 0,3–2 MB — **zalecana konwersja do WebP/JPG** (w demo: 2,8 MB zamiast ~220 MB) |
| F-61 | Druk | Z | Druk checklisty + ogólny druk artykułu bez nawigacji |
| F-62 | Edytor | ZI | `editor.css` i `theme.json` z nową paletą i krojami |
| F-63 | Brak tsl-core | Z | Komunikat w panelu i 503 |

## Testy automatyczne (Playwright)

- Interakcje WordPress — 26/26: panele działów (klik, jeden naraz, Esc, fokus), wyszukiwarka
  (także po zwinięciu winiety), zwijanie winiety, newsletter (walidacja, odpowiedź REST),
  pasek postępu, checklista (licznik, zapis, ostrzeżenie, czyszczenie), formularz kontaktu
  (błędy, wysyłka → komunikat trybu demo), mobile (brak przewijania w bok, pasek działów,
  menu, inert, akordeon, Esc, chowanie i powrót nagłówka). Błędy JS: brak.
- Kalkulator motywu: wartości domyślne, przebieg 0, puste przebiegi 0%.
- Demo z dysku (`file://`) — 27/27: strona startowa, czcionki, panel działu, wyszukiwarka
  (hasło, polskie znaki, filtr formatu — ten sam wynik co WordPress, brak wyników), filtr
  formatów w obszarze, formularze (walidacja + komunikat demo), checklista, strony poza demo,
  pasek demo, 6 stron bez przewijania w bok na 375 px, menu. **Zapytania do sieci: brak.**
- Składnia: PHP (`php -l`, wszystkie pliki motywu), JS (`node --check`), Python, JSON.

## Poprawki wprowadzone w trakcie testów (etap 7)

- tabele i długie breadcrumbs rozpychały kolumnę na telefonie → `min-width: 0` w siatkach;
- pasek działów z pełnymi nazwami nie mieścił się przy 1280 px → krótkie nazwy poniżej 1380 px;
- po zwinięciu winiety pasek działów zasłaniał lupę → krótkie nazwy w stanie zwiniętym;
- nagłówek mobilny nie chował się po zamknięciu menu (fokus w nagłówku) → warunek tylko dla otwartych paneli + wyjątek dla fokusu z klawiatury;
- atrybut `hidden` przegrywał z `display: grid` (filtry w demo) → reguła globalna;
- inicjał (drop cap) trafiał na notę „Materiał demonstracyjny” → tylko pierwszy zwykły akapit artykułu;
- podwójne linie nad pustym stanem i prelegentami; lista „Co dostaniesz” łamała się w siatce;
- kolizje nazw plików ilustracji w eksporcie demo → skrót w nazwie.

## Wymaga uwagi (decyzje poza kodem)

1. **Kalkulator kosztu kilometra** — zdecydowano: **nie publikować** (zostaje „w przygotowaniu”, jak w 0.10.0). Mechanizm w motywie czeka nieaktywny (F-37).
2. **Moduł „Transport i operacje”** — pojawi się po przypisaniu rodzaju transportu w artykułach (jak w 0.10.0).
3. **Ilustracje** — duże pliki PNG w Mediach; warto je skonwertować przed startem (wydajność).
4. **Logo** — znak słowny jest roboczy, jak logo w 0.10.0; po wyborze finalnego znaku podmiana w `tsl_logo()`.
5. **Język witryny** — baza z Playground ma język angielski (daty po angielsku w 0.10.0); testy i demo
   wykonano po polsku, zgodnie z instrukcją wdrożenia (WordPress po polsku).
6. Nie testowano: Safari i Firefox (dostępny był Chromium), czytnik ekranu, The Events Calendar
   i Cost Calculator Builder na żywo, wysyłka poczty SMTP, hosting docelowy.
