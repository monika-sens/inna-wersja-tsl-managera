# Wdrożenie TSL Manager Editorial (motyw 1.0.0)

Motyw `tsl-editorial` jest drugą warstwą prezentacji serwisu TSL Manager. Cała logika
(typy treści, filary, adresy, formularze, newsletter, analityka, SEO, tryb demo,
importy) jest we wtyczce **tsl-core 0.10.0**, identycznej z wersją w obecnym serwisie.
Pełna instrukcja wtyczki (SMTP, cron, import artykułów, tryb demo/produkcja) pozostaje
w dokumentacji projektu TSL Manager 0.10.0 (`docs/WDROZENIE-0.10.0.md` w repozytorium
`tsl-manager-`) — poniżej tylko to, co dotyczy nowego motywu.

## Wymagania

| Element | Wymaganie |
| --- | --- |
| WordPress | 6.9+ (testowane na 7.1.2), język polski |
| PHP | 8.1+ (testowane na 8.3) |
| Wtyczka | TSL Manager — model treści (`tsl-core`) 0.10.0 — **obowiązkowa** |
| Opcjonalnie | The Events Calendar (kalendarz), Cost Calculator Builder (kalkulator; bez niego motyw rysuje kalkulator sam) |

Motyw nie ładuje nic z zewnętrznych serwerów (fonty lokalne, bez Google Fonts,
bez skryptu emoji WordPressa).

## Paczki

```bash
tools/package.sh
# dist/tsl-editorial-1.0.0.zip   motyw
# dist/tsl-core-0.10.0.zip       wtyczka (ta sama wersja co w serwisie 0.10.0)
```

## Wariant 1: nowa instalacja

1. Zainstaluj WordPress po polsku, włącz SSL.
2. Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer → `tsl-core-0.10.0.zip` → Włącz
   (wtyczka wczyta drzewo filarów, słowniki, strony informacyjne).
3. Wygląd → Motywy → Dodaj nowy → Wyślij motyw na serwer → `tsl-editorial-1.0.0.zip` → Włącz.
4. Ustawienia → Bezpośrednie odnośniki → Zapisz.
5. Treści: Narzędzia → TSL Manager → „Wczytaj artykuły redakcji” (i w trybie demo
   „Wczytaj materiały przykładowe”), jak w 0.10.0.

## Wariant 2: istniejący serwis TSL Manager 0.10.0 (zmiana wyglądu)

1. Zrób kopię zapasową (pliki + baza) albo przetestuj najpierw na kopii (staging).
2. Wyślij i **włącz** motyw `tsl-editorial` (Wygląd → Motywy). Wtyczki nie trzeba ruszać.
3. Ustawienia → Bezpośrednie odnośniki → Zapisz.
4. Sprawdź listę odbioru (niżej).

Powrót do poprzedniego wyglądu: Wygląd → Motywy → włącz „TSL Manager”. Treści,
ustawienia modułów strony głównej i menu stopki są wspólne dla obu motywów.

## Co przechodzi bez zmian

- **Wygląd → Strona główna** — te same 15 modułów i ta sama opcja w bazie
  (`tsl_home_modules_off`): moduły wyłączone w starym motywie są wyłączone w nowym.
- Menu `Stopka — Projekt` i `Stopka — dolna belka`.
- Rozmiary obrazków `tsl-card` (720×405) i `tsl-hero` (1280×720) — istniejące
  miniatury działają bez regeneracji.
- Formularze, newsletter, baner zgody, analityka (atrybuty `data-track*`), SEO, tryb demo.

## Test na lokalnej kopii (bez Dockera)

```bash
tools/dev/setup-local.sh ~/tsl-local "wordpress-playground (7).zip"
cd ~/tsl-local && PHP_CLI_SERVER_WORKERS=4 php -S localhost:8090 -t wordpress router.php
```

## Lista odbioru

- [ ] Strona główna: okładka (temat tygodnia), pas RiskBrief, spis działów, pozostałe moduły
- [ ] Panele działów (klik, Esc, klik poza), wyszukiwarka w nagłówku i po zwinięciu winiety
- [ ] Telefon: przewijany pasek działów, menu pełnoekranowe, akordeony
- [ ] Dział, obszar z filtrem formatów, artykuł, RiskBrief, narzędzie (checklista: zaznaczanie, druk)
- [ ] Wydarzenie (rejestracja), raport (pobranie po e-mailu), ekspert, partner, wydanie newslettera
- [ ] Kontakt (temat z adresu `?temat=`), newsletter (`?nl=potwierdzono|wypisano|blad`, wypisanie)
- [ ] Wyszukiwarka z filtrami, 404
- [ ] Formularze w trybie produkcyjnym z SMTP: wiadomość dochodzi
