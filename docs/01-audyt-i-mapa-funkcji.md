# Etap 1–2. Audyt obecnego serwisu i mapa funkcji

Punkt odniesienia funkcjonalnego: **TSL Manager 0.10.0** z pliku `wordpress-playground (7).zip`
(Dysk Google, folder projektu; plik z 6.10.2026). Zawartość ZIP-a:

| Element | Wersja / stan |
| --- | --- |
| Motyw `tsl-manager` | 0.10.0 (identyczny z gałęzią `poprawki-demo-inwestorskie-0.10.0` repozytorium `tsl-manager-`) |
| Wtyczka `tsl-core` („TSL Manager — model treści”) | 0.10.0 (identyczna z tą gałęzią) |
| Wtyczka The Events Calendar | w paczce, **nieaktywna** — wydarzenia obsługuje wbudowany typ `wydarzenie` z `tsl-core` |
| Cost Calculator Builder | brak; kalkulator kosztu kilometra opisany jako „w przygotowaniu” |
| Baza (SQLite, WordPress 7.1, `db_version` 61833) | tryb demonstracyjny włączony, 36 opublikowanych artykułów z ilustracjami, materiały przykładowe (RiskBrief ×4, narzędzia ×7, raport, wydarzenia ×2, eksperci ×2, partnerzy ×2, wydanie newslettera), 6 opublikowanych stron informacyjnych, 6 stron prawnych w szkicach |
| Ilustracje | 37 obrazów PNG (+ miniatury WordPressa), ok. 224 MB |

Audyt wykonano na działającej kopii serwisu (WordPress 7.1.2 + SQLite + serwer PHP 8.3) —
przejrzano kod motywu i wtyczki, przeszukano wszystkie linki serwisu (765 adresów: 760 × 200,
5 × 404 — linki do szkiców stron prawnych, zachowanie zamierzone w trybie demo)
i obejrzano strony w przeglądarce.

## Podział odpowiedzialności w obecnym kodzie

- **`tsl-core` (wtyczka)** — cała logika: typy treści, taksonomie, drzewo 6 filarów (72 kategorie),
  adresy URL, pola, powiązania, formularze (REST), newsletter (podwójna zgoda, wysyłka, archiwum),
  analityka i baner zgody, SEO, tryb demo/produkcja, importy artykułów i .docx, shortcode'y
  `[tsl_etoll]` i `[tsl_kalkulator]`, WP-CLI.
- **`tsl-manager` (motyw)** — wyłącznie prezentacja + niewielka logika widoków
  (zapytania modułów strony głównej, „następny krok”, checklisty, ekran *Wygląd → Strona główna*).

Wniosek dla nowej wersji: **wtyczka zostaje bez zmian**, nowa wersja = nowy motyw
korzystający z tego samego API wtyczki. Treści, ustawienia i adresy URL są wspólne —
nowy motyw można włączyć na tej samej bazie i wrócić do starego bez utraty danych.

## Mapa funkcji (checklista)

Identyfikatory (F-xx) są używane w audycie końcowym (`docs/03-audyt-koncowy.md`).

### A. Struktura i nawigacja

| ID | Funkcja | Gdzie / jak działa w 0.10.0 |
| --- | --- | --- |
| F-01 | Pasek informacyjny nad nagłówkiem | RiskBrief TSL (kropka + etykieta „Nowy”, gdy RiskBrief z ostatnich 7 dni), Raporty i dane, Newsletter / Wydarzenia, Partnerzy, O projekcie, Kontakt |
| F-02 | Logo z linkiem do strony głównej | SVG robocze, unikalne id tytułu (nagłówek/stopka) |
| F-03 | Nawigacja główna: 6 filarów + Narzędzia | budowana z drzewa filarów (panel Filary), krótkie etykiety na węższych ekranach |
| F-04 | Mega menu filaru | otwierane kliknięciem (bez hover): obietnica filaru, „Zobacz wszystko”, najważniejsze obszary (flaga „mega”), w Transporcie — rodzaje transportu i spedycji, w pozostałych — „Więcej obszarów”, wyróżniony materiał (przypięty lub najnowszy, cache 1 h) |
| F-05 | Zamykanie paneli | Esc (fokus wraca na przycisk), klik poza nagłówkiem, wyjście fokusem z panelu; najwyżej jeden panel naraz |
| F-06 | Wyszukiwarka w nagłówku | przycisk → panel z polem (autofokus) i podpowiedziami: fałszywy przewoźnik, CMR, płynność, TMS |
| F-07 | CTA „RiskBrief na e-mail” w nagłówku | link do /newsletter/, `data-track` |
| F-08 | Menu mobilne (≤1080 px) | pełny ekran: wyszukiwarka, 4 skróty (RiskBrief, Narzędzia, Wydarzenia, Newsletter), 6 filarów w akordeonie („Wszystkie treści” + podkategorie), linki dodatkowe (+ Współpraca partnerska); pułapka fokusu, `inert` na reszcie strony, Esc zamyka, zamknięcie po poszerzeniu okna |
| F-09 | Nagłówek przyklejony (sticky) | `position: sticky` przy przewijaniu |
| F-10 | Skip link „Przejdź do treści” | do `#main` |
| F-11 | Breadcrumbs | pełne nazwy filarów; typy własne, typ narzędzia, podkategorie z przodkami |
| F-12 | Stopka | marka + opis + CTA newslettera; Filary; Korzystaj (Narzędzia, RiskBrief, Raporty, Wydarzenia, Newsletter, Eksperci, Partnerzy); Projekt (menu `footer-project` lub domyślne 5 linków); dolna belka: © rok, menu `footer-legal` lub 5 dokumentów; przycisk „Ustawienia prywatności” (gdy analityka wymaga zgody) |
| F-13 | Lokalizacje menu | `footer-project`, `footer-legal` |

### B. Strona główna (15 modułów, włączanych w *Wygląd → Strona główna*)

| ID | Moduł | Logika |
| --- | --- | --- |
| F-14 | Pasek pilnego sygnału | krytyczny RiskBrief z ostatnich 7 dni |
| F-15 | Temat tygodnia (hero) | przypięty artykuł lub najnowszy; obraz, rodzaj, tytuł = jedyny H1, zajawka, metadane, „Czytaj analizę”, „Narzędzia dla menedżera”; gdy wyłączony — H1 = nazwa serwisu |
| F-16 | RiskBrief TSL | 5 najnowszych sygnałów z poziomem ryzyka (kolor + tekst), datą, link do archiwum |
| F-17 | Sześć filarów | numer, nazwa, obietnica, 3 podkategorie |
| F-18 | Najnowsze analizy | po 1 najnowszym materiale z każdego filaru, bez powtórzeń; link „Wszystkie materiały” (/?s=) |
| F-19 | Decyzja pod lupą | 3 materiały formatu, flagowy + 2; „Więcej porównań” (/?s=&format=decyzja-pod-lupa) |
| F-20 | Narzędzia dla menedżera | 6 narzędzi, typ, zajawka, „Użyj narzędzia” |
| F-21 | Ile to kosztuje + kalkulator | materiał formatu „Ile to naprawdę kosztuje?” + wejście do kalkulatora |
| F-22 | Transport i operacje (zakładki ARIA) | rodzaje transportu ze strzałkami/Home/End; link Spedycja (moduł ukrywa się bez treści) |
| F-23 | Szkoda pod lupą / Pierwsze 24 h | materiał + procedura (moduł wyłączony w demo) |
| F-24 | Newsletter | formularz zapisu (REST, zgoda, pole-pułapka) |
| F-25 | Raporty i dane + Wydarzenia | najnowszy raport; 3 wydarzenia (nadchodzące, uzupełnione zakończonymi) |
| F-26 | Eksperci | 4 profile |
| F-27 | Partnerzy | z jawną rolą (poziom partnerstwa) |
| F-28 | Ekran *Wygląd → Strona główna* | opcja `tsl_home_modules_off`; moduł bez treści się nie wyświetla |
| F-29 | Śledzenie modułów | `data-track-module` (ekspozycja i kliknięcia) |

### C. Typy stron

| ID | Strona | Zawartość |
| --- | --- | --- |
| F-30 | Filar | intro z obietnicą, podkategorie, materiał wyróżniony, „Najnowsze w filarze”, narzędzia w obszarze (3), RiskBrief z filaru, newsletter, paginacja |
| F-31 | Podkategoria | opis zakresu, podkategorie potomne, filtr formatów (`?format=`), lista, „Sekcja w przygotowaniu” + zakres / „Brak materiałów w wybranym formacie”, narzędzie (1), RiskBrief z filaru |
| F-32 | Artykuł | breadcrumbs, rodzaj, filar, oznaczenie partnerskie, H1, zajawka, autor/data/aktualizacja/czas czytania, ilustracja, treść (bloki: tabele, listy, ramki `tsl-box` decision/info/summary/formula, `tsl-note`), „Co zrobić w poniedziałek”, ramka CTA biznesowe (opcja), źródła, panel: następny krok, kontekst (Dla kogo, Transport, Problem, Typ firmy, Region, Sektor, Także w filarach), ekspert; powiązane (3); newsletter |
| F-33 | RiskBrief | marka, poziom, status; „Kogo dotyczy”, „Możliwy skutek”, „Co zrobić teraz”, ważność / ponowna weryfikacja; pełna analiza, podobne sygnały, archiwum |
| F-34 | Narzędzie | wersja + data; checklista interaktywna; „Jak korzystać”; pobranie (bezpośrednie lub po e-mailu → formularz `pobranie`); zastrzeżenie; „Przeczytaj przed użyciem”, powiązane wydarzenie; właściciel merytoryczny |
| F-35 | Checklista | odhaczanie, licznik i pasek postępu, punkty krytyczne + ostrzeżenie, „Wszystkie punkty sprawdzone”, uwagi, zapis w `localStorage`, „Wyczyść”, druk/PDF tylko checklisty (nagłówek z polami do wypełnienia, stopka ze źródłem) |
| F-36 | Kalkulator e-TOLL | shortcode `[tsl_etoll]` z wtyczki: karta z linkiem do oficjalnego kalkulatora (opcjonalnie iframe) |
| F-37 | Kalkulator kosztu kilometra | `[tsl_kalkulator]` (Cost Calculator Builder); w 0.10.0 opisany jako „w przygotowaniu” |
| F-38 | Raport | kluczowe wnioski, pobranie (po e-mailu), metodologia + zakres danych, autorzy, wydarzenie premierowe, artykuły pochodne |
| F-39 | Wydarzenie | termin, forma, miejsce, status (nadchodzące/zakończone), rejestracja (link zewnętrzny lub formularz `wydarzenie`), nagranie po terminie, agenda (godzina — punkt), prelegenci, materiały po wydarzeniu, partnerzy, raport premierowy; wsparcie The Events Calendar (Google Calendar, iCal) |
| F-40 | Ekspert | zdjęcie lub inicjały, rola, kompetencje, LinkedIn; publikacje, narzędzia, wydarzenia |
| F-41 | Partner | poziom, obszar, strona (`rel="sponsored"`), wspólne projekty |
| F-42 | Wydanie newslettera | treść + sekcje automatyczne (RiskBrief, artykuły, narzędzia); zachęta do zapisu; archiwum wydań |
| F-43 | Archiwa kolekcji | Narzędzia (+ typy narzędzi jako taksonomia z adresem), RiskBrief, Raporty, Wydarzenia, Eksperci, Partnerzy, Wydania; filtry: typ narzędzia / poziom ryzyka / typ wydarzenia / typ raportu / poziom partnerstwa; status sygnału; status wydarzenia (nadchodzące/zakończone); brak nadchodzących wydarzeń → komunikat + ostatnie; paginacja |
| F-44 | Wyszukiwarka | pole + filtry: filar, format, rodzaj transportu; liczba wyników; brak wyników → linki do Narzędzi, RiskBrief, filarów i „Zgłoś temat redakcji”; `data-search-term/count` dla analityki; paginacja |
| F-45 | Kontakt | formularz `kontakt` z tematem z adresu (`?temat=partnerstwo`), treść strony (gdy nie „Do uzupełnienia”), „Zanim napiszesz” |
| F-46 | Newsletter | komunikaty `?nl=potwierdzono|wypisano|blad`, potwierdzenie wypisania (`?nl=wypisz&token=`, POST), formularz, „Co dostaniesz”, ostatnie wydania + archiwum |
| F-47 | Strony informacyjne | O projekcie, Redakcja, Zasady redakcyjne, Współpraca partnerska (+ prawne w szkicach) |
| F-48 | 404 | wyszukiwarka + 6 filarów |
| F-49 | Widoki kalendarza TEC | szablon `tribe/events/v2/default-template.php` (gdy TEC aktywny) |

### D. Formularze i interakcje

| ID | Funkcja | Szczegóły |
| --- | --- | --- |
| F-50 | Formularze kontakt / wydarzenie / pobranie | pola z definicji wtyczki (`tsl_form_definitions`), walidacja w przeglądarce i na serwerze, komunikaty przy polach (`aria-invalid`, `aria-describedby`), zgoda RODO + link do polityki, opcjonalny zapis do newslettera, pole-pułapka, wysyłka REST `tsl/v1/form/{typ}`, w trybie demo komunikat zamiast wysyłki |
| F-51 | Zapis do newslettera | REST `tsl/v1/subscribe`, walidacja e-maila i zgody, komunikaty statusu, podwójna zgoda |
| F-52 | Analityka | `data-track`, `data-track-module`, `window.tslTrack`, baner zgody (GA4/Matomo), „Ustawienia prywatności” |
| F-53 | SEO | opis, canonical, Open Graph, noindex filtrów — z wtyczki (motyw: `title-tag`) |
| F-54 | Oznaczenia | materiał partnerski/sponsorowany na kartach i w artykule; poziomy ryzyka zawsze tekstem; „Demo” w panelu |
| F-55 | Puste stany | „Sekcja w przygotowaniu” + zakres; brak w formacie; brak wyników |
| F-56 | Grafika zastępcza | gdy materiał nie ma ilustracji — moduły ze zdjęciem nie są puste |

### E. Jakość techniczna

| ID | Wymóg | Stan w 0.10.0 |
| --- | --- | --- |
| F-57 | Jeden H1 na stronę, brak skoków nagłówków, ukryte H2 przed listami | spełnione |
| F-58 | Dostępność klawiatury | widoczny fokus, skip link, pułapka fokusu, ARIA expanded/controls, `prefers-reduced-motion` |
| F-59 | Responsywność | 360–1920 px bez poziomego przewijania; przełom menu 1080 px |
| F-60 | Wydajność | 1 plik CSS (57 kB) + 1 JS (14 kB, `defer`), fonty lokalne (Inter + Cormorant, preload), obrazy `lazy`, hero `fetchpriority=high`, brak jQuery w motywie |
| F-61 | Druk | style druku checklisty |
| F-62 | Edytor | `editor.css`, paleta i typografia w `theme.json` |
| F-63 | Brak wtyczki `tsl-core` | komunikat w panelu i 503 na stronie zamiast błędów PHP |

## Zależności między funkcjami (najważniejsze)

- Nawigacja, stopka, breadcrumbs, filtry i „następny krok” zależą od drzewa filarów i funkcji
  wtyczki (`tsl_get_pillars`, `tsl_get_subcategories`, `tsl_term_promise`, `tsl_related`, `tsl_field*`).
- Formularze i newsletter: motyw rysuje HTML, wtyczka waliduje i wysyła; JS motywu łączy jedno
  z drugim (REST). Atrybuty `data-tsl-form`, `data-tsl-newsletter`, `data-endpoint`, nazwy pól
  i klasy `form-status`, `field-error`, `hp-field` są kontraktem — nowy motyw musi je zachować.
- Analityka (skrypt wtyczki) szuka `data-track*`, `data-search-*`, `data-consent`,
  `data-tsl-privacy` i tytułów w `.card-title`/`.tool-title` — kontrakt do zachowania.
- Wtyczka sama wypisuje HTML z klasami `btn btn-primary|btn-ghost|btn-accent`, `tsl-widget*`,
  `tsl-etoll*`, `tsl-calc`, `tsl-demo-note`, `tsl-consent` — nowy motyw musi je ostylować.
- Treść artykułów używa klas `tsl-box`, `tsl-box-{decision,info,summary,formula}`,
  `tsl-box-title`, `tsl-note` oraz 101 tabel (`wp-block-table`) — muszą być czytelne także na telefonie.
- Ustawienie `tsl_home_modules_off` (moduły strony głównej) jest zapisane w bazie — nowy motyw
  korzysta z tej samej opcji, więc decyzje redakcji przechodzą na nowy wygląd.
