# Testy przeglądarkowe (Playwright)

Wymagają Node.js i pakietu `playwright` (z przeglądarką Chromium).

```bash
# Interakcje w WordPressie: panele działów, wyszukiwarka, winieta, newsletter,
# pasek postępu, checklista, formularz, menu mobilne. Zrzuty → <katalog>.
node tools/tests/interakcje.js http://localhost:8090/ /tmp/zrzuty \
  http://localhost:8090/zarzadzanie-finanse/strategia-decyzje/wejscie-na-nowy-rynek-7-decyzji/ \
  http://localhost:8090/narzedzia/checklisty/weryfikacja-przewoznika/ \
  http://localhost:8090/kontakt/

# Brak przewijania w bok i jeden H1 na 11 szerokościach (360–1920 px).
node tools/tests/szerokosci.js http://localhost:8090 / /riskbrief/ /kontakt/

# Demo otwierane z dysku (file://): wyszukiwarka, filtry, formularze, brak zapytań do sieci.
node tools/tests/demo.js "$PWD/demo"
```
