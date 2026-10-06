<?php
/**
 * Drzewo sześciu filarów i podkategorii (rozdz. 6 i Załącznik A mapy serwisu).
 *
 * Każdy węzeł:
 *  - name        pełna nazwa (strony kategorii, breadcrumbs, metadane)
 *  - slug        część adresu URL
 *  - short       skrócona etykieta do nagłówka desktopowego (tylko filary)
 *  - promise     jednozdaniowa obietnica filaru (mega menu, strona filaru)
 *  - description zakres tematyczny (opis kategorii)
 *  - mega        true = podkategoria widoczna w mega menu (maks. 5–6 na filar)
 *  - children    podkategorie kolejnego poziomu
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'        => 'Zarządzanie i finanse',
		'slug'        => 'zarzadzanie-finanse',
		'short'       => 'Zarządzanie',
		'promise'     => 'Rentowność, płynność i decyzje właścicielskie, które budują wynik firmy TSL.',
		'description' => 'Strategia, controlling, koszty, płynność, finansowanie, inwestycje, sprzedaż i zarządzanie kryzysowe.',
		'children'    => array(
			array( 'name' => 'Strategia i decyzje właścicielskie', 'slug' => 'strategia-decyzje', 'mega' => true, 'description' => 'Rozwój, skalowanie, sukcesja, model biznesowy, M&A.' ),
			array( 'name' => 'Rentowność i controlling', 'slug' => 'rentownosc-controlling', 'mega' => true, 'description' => 'Marża, rentowność klienta i zlecenia, budżet, KPI zarządcze.' ),
			array( 'name' => 'Koszty i wycena usług', 'slug' => 'koszty-wycena', 'mega' => true, 'description' => 'Koszt/km, paliwo, myto, przestoje, puste przebiegi, indeksacja cen.' ),
			array( 'name' => 'Płynność, należności i płatności', 'slug' => 'plynnosc-naleznosci', 'mega' => true, 'description' => 'DSO, zatory, windykacja, koncentracja klientów, rezerwy gotówkowe.' ),
			array( 'name' => 'Finansowanie', 'slug' => 'finansowanie', 'mega' => true, 'description' => 'Kredyt, faktoring, leasing, wynajem, finansowanie obrotowe.' ),
			array( 'name' => 'Inwestycje i aktywa', 'slug' => 'inwestycje-aktywa', 'description' => 'Flota, magazyn, technologia, infrastruktura, energia.' ),
			array( 'name' => 'Sprzedaż, klienci i kontrakty', 'slug' => 'sprzedaz-klienci-kontrakty', 'mega' => true, 'description' => 'Oferty, przetargi, portfel klientów, ryzyko handlowe.' ),
			array( 'name' => 'Restrukturyzacja i zarządzanie kryzysowe', 'slug' => 'restrukturyzacja', 'description' => 'Wczesne sygnały, plan naprawczy, redukcja kosztów, ciągłość finansowa.' ),
		),
	),
	array(
		'name'        => 'Transport i operacje',
		'slug'        => 'transport-operacje',
		'short'       => 'Transport',
		'promise'     => 'Transport, spedycja, flota i magazyn — operacje, które działają także pod presją.',
		'description' => 'Wszystkie gałęzie transportu i spedycji, flota, magazyn, planowanie, podwykonawcy i ciągłość operacji.',
		'children'    => array(
			array(
				'name'        => 'Transport',
				'slug'        => 'transport',
				'mega'        => true,
				'description' => 'Transport drogowy, morski, kolejowy, lotniczy oraz intermodalny i multimodalny.',
				'children'    => array(
					array( 'name' => 'Drogowy', 'slug' => 'drogowy', 'description' => 'Flota, trasy, myto, paliwo, kierowcy, przepustowość.' ),
					array( 'name' => 'Morski', 'slug' => 'morski', 'description' => 'Kontenery, porty, armatorzy, demurrage, detention.' ),
					array( 'name' => 'Kolejowy', 'slug' => 'kolejowy', 'description' => 'Terminale, rozkłady, wagony, intermodal, szlaki.' ),
					array( 'name' => 'Lotniczy', 'slug' => 'lotniczy', 'description' => 'Cargo lotnicze, towary pilne i wartościowe, handling.' ),
					array( 'name' => 'Intermodalny i multimodalny', 'slug' => 'intermodalny', 'description' => 'Zmiana gałęzi, terminale, dokumenty i odpowiedzialność na styku.' ),
				),
			),
			array(
				'name'        => 'Spedycja',
				'slug'        => 'spedycja',
				'mega'        => true,
				'description' => 'Spedycja drogowa, morska, kolejowa i lotnicza.',
				'children'    => array(
					array( 'name' => 'Drogowa', 'slug' => 'drogowa', 'description' => 'Zlecenia, podwykonawcy, giełdy transportowe, dokumenty.' ),
					array( 'name' => 'Morska', 'slug' => 'morska', 'description' => 'Booking, konosament, port, agent, opłaty dodatkowe.' ),
					array( 'name' => 'Kolejowa', 'slug' => 'kolejowa', 'description' => 'Operatorzy, terminale, dokumenty przewozowe, opóźnienia.' ),
					array( 'name' => 'Lotnicza', 'slug' => 'lotnicza', 'description' => 'AWB, handling, bezpieczeństwo cargo, towary specjalne.' ),
				),
			),
			array( 'name' => 'Flota i utrzymanie', 'slug' => 'flota-utrzymanie', 'mega' => true, 'description' => 'Zakup, serwis, dostępność, opony, paliwo, telematyka.' ),
			array( 'name' => 'Magazyn i logistyka kontraktowa', 'slug' => 'magazyn-logistyka-kontraktowa', 'mega' => true, 'description' => 'Przyjęcie, składowanie, kompletacja, SLA, inwentaryzacja.' ),
			array( 'name' => 'Planowanie i łańcuch dostaw', 'slug' => 'planowanie-lancuch-dostaw', 'mega' => true, 'description' => 'Popyt, zapasy, przepustowość, okna czasowe, odporność.' ),
			array( 'name' => 'Podwykonawcy i jakość operacyjna', 'slug' => 'podwykonawcy-jakosc', 'description' => 'Weryfikacja, onboarding, KPI, reklamacje, audyt.' ),
			array( 'name' => 'Cross-border i ciągłość operacji', 'slug' => 'cross-border-ciaglosc', 'mega' => true, 'description' => 'Granice, porty, strajki, zamknięcia tras, alternatywne scenariusze.' ),
		),
	),
	array(
		'name'        => 'Ludzie i organizacja',
		'slug'        => 'ludzie-organizacja',
		'short'       => 'Ludzie',
		'promise'     => 'Kierowcy, spedytorzy i menedżerowie — zespół, który ogranicza ryzyko zamiast je tworzyć.',
		'description' => 'Rekrutacja, onboarding, wynagrodzenia, retencja, kompetencje, odpowiedzialność menedżerska i planowanie zasobów.',
		'children'    => array(
			array( 'name' => 'Kierowcy', 'slug' => 'kierowcy', 'mega' => true, 'description' => 'Rekrutacja, onboarding, czas pracy, bezpieczeństwo, retencja.' ),
			array( 'name' => 'Spedytorzy i dyspozytorzy', 'slug' => 'spedytorzy-dyspozytorzy', 'mega' => true, 'description' => 'Kompetencje, obciążenie, błędy, systemy premiowe, odpowiedzialność.' ),
			array( 'name' => 'Rekrutacja i employer branding', 'slug' => 'rekrutacja', 'mega' => true, 'description' => 'Źródła kandydatów, selekcja, wizerunek pracodawcy.' ),
			array( 'name' => 'Onboarding i standardy pracy', 'slug' => 'onboarding', 'description' => 'Wdrożenie, checklisty stanowiskowe, mentoring, okres próbny.' ),
			array( 'name' => 'Wynagrodzenia i systemy motywacyjne', 'slug' => 'wynagrodzenia', 'mega' => true, 'description' => 'Premie, prowizje, cele, jakość, bezpieczeństwo.' ),
			array( 'name' => 'Retencja i dobrostan', 'slug' => 'retencja-dobrostan', 'mega' => true, 'description' => 'Rotacja, zmęczenie, wypalenie, absencja, wsparcie.' ),
			array( 'name' => 'Kompetencje i szkolenia', 'slug' => 'kompetencje-szkolenia', 'description' => 'Prawo, szkody, cyber, komunikacja, zarządzanie.' ),
			array( 'name' => 'Menedżerowie i odpowiedzialność', 'slug' => 'menedzerowie', 'mega' => true, 'description' => 'Delegowanie, RACI, nadzór, decyzje kryzysowe.' ),
			array( 'name' => 'Kultura, komunikacja i konflikty', 'slug' => 'kultura-konflikty', 'description' => 'Sprzedaż–operacje, zmiany, praca zmianowa, feedback.' ),
			array( 'name' => 'Planowanie zasobów', 'slug' => 'planowanie-zasobow', 'description' => 'Grafiki, sezonowość, zastępstwa, sukcesja stanowiskowa.' ),
		),
	),
	array(
		'name'        => 'Ryzyko i bezpieczeństwo',
		'slug'        => 'ryzyko-bezpieczenstwo',
		'short'       => 'Ryzyko',
		'promise'     => 'Ubezpieczenia, szkody, oszustwa i cyber — jak zapobiegać i co robić w pierwszych godzinach.',
		'description' => 'Ubezpieczenia, szkody, kradzieże, oszustwa, weryfikacja kontrahentów, bezpieczeństwo fizyczne i cyber, ciągłość działania.',
		'children'    => array(
			array( 'name' => 'Ubezpieczenia', 'slug' => 'ubezpieczenia', 'mega' => true, 'description' => 'OCP, OCS, cargo, mienie, cyber, flota, programy ochrony.' ),
			array( 'name' => 'Szkody i roszczenia', 'slug' => 'szkody-roszczenia', 'mega' => true, 'description' => 'Zgłoszenie, dowody, odpowiedzialność, likwidacja, regres.' ),
			array( 'name' => 'Kradzieże ładunków', 'slug' => 'kradzieze-ladunkow', 'mega' => true, 'description' => 'Trasy, parkingi, magazyny, fałszywi przewoźnicy.' ),
			array( 'name' => 'Oszustwa i nadużycia', 'slug' => 'oszustwa-naduzycia', 'mega' => true, 'description' => 'Podszywanie się, zmiana rachunku, fałszywe dokumenty, insider.' ),
			array( 'name' => 'Weryfikacja kontrahentów', 'slug' => 'weryfikacja-kontrahentow', 'mega' => true, 'description' => 'Przewoźnik, spedytor, klient, dostawca, właściciel rachunku.' ),
			array( 'name' => 'Bezpieczeństwo fizyczne', 'slug' => 'bezpieczenstwo-fizyczne', 'description' => 'Magazyn, obiekt, dostęp, monitoring, ochrona, parkingi.' ),
			array( 'name' => 'Cyberbezpieczeństwo', 'slug' => 'cyberbezpieczenstwo', 'mega' => true, 'description' => 'Poczta, TMS, ransomware, dane tras, dostęp zdalny.' ),
			array( 'name' => 'Ciągłość działania', 'slug' => 'ciaglosc-dzialania', 'description' => 'BCP, DR, alternatywne lokalizacje, dostawcy krytyczni.' ),
			array( 'name' => 'Zarządzanie kryzysowe', 'slug' => 'zarzadzanie-kryzysowe', 'description' => 'Sztab, eskalacja, komunikacja, decyzje pierwszych godzin.' ),
			array( 'name' => 'Analiza i mapa ryzyka', 'slug' => 'mapa-ryzyka', 'description' => 'Rejestr ryzyk, scenariusze, scoring, priorytety działań.' ),
		),
	),
	array(
		'name'        => 'Prawo',
		'slug'        => 'prawo',
		'short'       => 'Prawo',
		'promise'     => 'CMR, umowy, kontrole, cła i sankcje — prawo przełożone na decyzje i procedury.',
		'description' => 'Prawo transportowe, umowy, kontrole, czas pracy, prawo pracy, cła, sankcje, podatki, towary specjalne i zgodność.',
		'children'    => array(
			array( 'name' => 'Prawo transportowe i CMR', 'slug' => 'prawo-transportowe-cmr', 'mega' => true, 'description' => 'Zakres odpowiedzialności, list przewozowy, zastrzeżenia, przedawnienie.' ),
			array( 'name' => 'Umowy i odpowiedzialność', 'slug' => 'umowy-odpowiedzialnosc', 'mega' => true, 'description' => 'Zlecenia, klauzule, kary, limity, jurysdykcja, podwykonawcy.' ),
			array( 'name' => 'Kontrole i postępowania', 'slug' => 'kontrole-postepowania', 'mega' => true, 'description' => 'ITD, PIP, KAS, dokumenty, odwołania, sankcje administracyjne.' ),
			array( 'name' => 'Czas pracy i pakiet mobilności', 'slug' => 'czas-pracy-pakiet-mobilnosci', 'mega' => true, 'description' => 'Ewidencja, delegowanie, odpoczynki, tachografy.' ),
			array( 'name' => 'Prawo pracy', 'slug' => 'prawo-pracy', 'description' => 'Umowy, odpowiedzialność pracownika, monitoring, rozstania.' ),
			array( 'name' => 'Cła i handel międzynarodowy', 'slug' => 'cla-handel-miedzynarodowy', 'mega' => true, 'description' => 'Odprawy, pochodzenie, klasyfikacja, przedstawicielstwo.' ),
			array( 'name' => 'Sankcje, embargo i kontrola eksportu', 'slug' => 'sankcje-embargo', 'mega' => true, 'description' => 'Kontrahent, towar, trasa, kraj, beneficjent rzeczywisty.' ),
			array( 'name' => 'Podatki w TSL', 'slug' => 'podatki', 'description' => 'VAT, dokumentowanie usług, paliwo, opłaty, transakcje międzynarodowe.' ),
			array( 'name' => 'Towary niebezpieczne i specjalne', 'slug' => 'towary-niebezpieczne-specjalne', 'description' => 'ADR, żywność, farmacja, odpady, towary wysokiej wartości.' ),
			array( 'name' => 'Dane, dokumenty i zgodność', 'slug' => 'dane-dokumenty-zgodnosc', 'description' => 'RODO, eCMR, archiwizacja, podpisy, monitoring pracowników.' ),
		),
	),
	array(
		'name'        => 'Technologie i narzędzia',
		'slug'        => 'technologie-narzedzia',
		'short'       => 'Technologie',
		'promise'     => 'TMS, WMS, dane i AI — jak wybrać, wdrożyć i bezpiecznie używać systemów.',
		'description' => 'Systemy TMS, WMS i ERP, telematyka, automatyzacja, AI, dane i BI, obieg dokumentów, cyber i ciągłość IT.',
		'children'    => array(
			array( 'name' => 'TMS', 'slug' => 'tms', 'mega' => true, 'description' => 'Wybór, wdrożenie, integracja, konfiguracja, utrzymanie.' ),
			array( 'name' => 'WMS i technologie magazynowe', 'slug' => 'wms', 'mega' => true, 'description' => 'WMS, automatyka, skanery, RFID, sortery, robotyka.' ),
			array( 'name' => 'ERP i integracje', 'slug' => 'erp-integracje', 'description' => 'Finanse, sprzedaż, magazyn, transport, master data.' ),
			array( 'name' => 'Telematyka i systemy flotowe', 'slug' => 'telematyka', 'mega' => true, 'description' => 'Lokalizacja, paliwo, styl jazdy, serwis, tachograf.' ),
			array( 'name' => 'Automatyzacja procesów', 'slug' => 'automatyzacja', 'description' => 'Workflow, RPA, obieg zleceń i dokumentów.' ),
			array( 'name' => 'Sztuczna inteligencja', 'slug' => 'sztuczna-inteligencja', 'mega' => true, 'description' => 'Planowanie, prognozowanie, dokumenty, obsługa klienta, ryzyka.' ),
			array( 'name' => 'Dane, BI i analityka', 'slug' => 'dane-bi-analityka', 'mega' => true, 'description' => 'KPI, dashboardy, jakość danych, prognozy, benchmarki.' ),
			array( 'name' => 'EDI, eCMR i obieg dokumentów', 'slug' => 'edi-ecmr', 'description' => 'Integracje partnerów, podpisy, archiwizacja, statusy.' ),
			array( 'name' => 'Cyber i ciągłość IT', 'slug' => 'cyber-ciaglosc-it', 'description' => 'Backup, dostęp, dostawcy chmurowi, awarie, odtworzenie.' ),
			array( 'name' => 'Wybór i wdrożenie systemów', 'slug' => 'wybor-wdrozenie-systemow', 'mega' => true, 'description' => 'RFP, wymagania, TCO, pilotaż, migracja, odbiór.' ),
			array( 'name' => 'Biblioteka narzędzi', 'slug' => 'biblioteka-narzedzi', 'description' => 'Checklisty, formularze, kalkulatory, matryce, procedury, wzory.' ),
		),
	),
);
