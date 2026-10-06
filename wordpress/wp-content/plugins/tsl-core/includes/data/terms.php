<?php
/**
 * Słowniki taksonomii pomocniczych (rozdz. 7, 8 i 9 mapy serwisu).
 *
 * Klucz = nazwa taksonomii, wartość = lista [ slug => nazwa ] albo
 * [ slug => [ 'name' => ..., 'description' => ..., 'meta' => [...] ] ].
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'format'            => array(
		'analiza-menedzerska'    => array( 'name' => 'Analiza menedżerska', 'description' => 'Wyjaśnienie zjawiska z perspektywy decyzji zarządczej. Kończy się podsumowaniem „Co zrobić w poniedziałek”.' ),
		'poradnik-operacyjny'    => array( 'name' => 'Poradnik operacyjny', 'description' => 'Instrukcja procesu krok po kroku. Następny krok: procedura, formularz lub lista kontrolna.' ),
		'szkoda-pod-lupa'        => array( 'name' => 'Szkoda pod lupą', 'description' => 'Rzeczywisty lub zanonimizowany przypadek: zdarzenie, dokumenty, odpowiedzialność, przebieg likwidacji, wnioski.' ),
		'decyzja-pod-lupa'       => array( 'name' => 'Decyzja pod lupą', 'description' => 'Porównanie wariantów decyzji i ich konsekwencji finansowych, operacyjnych, prawnych oraz ryzyk.' ),
		'pierwsze-24-godziny'    => array( 'name' => 'Pierwsze 24 godziny', 'description' => 'Chronologiczna procedura po incydencie: kto, co, kiedy, jakie dowody i eskalacja.' ),
		'ile-to-kosztuje'        => array( 'name' => 'Ile to naprawdę kosztuje?', 'description' => 'Pełny koszt decyzji lub zdarzenia, w tym koszty ukryte i utracone korzyści.' ),
		'przed-kontrola'         => array( 'name' => 'Przed kontrolą', 'description' => 'Zakres kontroli, dokumenty, role, błędy i sposób przygotowania organizacji.' ),
		'rozmowa-z-praktykiem'   => array( 'name' => 'Rozmowa z praktykiem', 'description' => 'Wywiad skoncentrowany na decyzjach, błędach i rezultatach.' ),
		'analiza-prawna'         => array( 'name' => 'Analiza prawna', 'description' => 'Interpretacja przepisów i orzecznictwa w kontekście decyzji firmy TSL.' ),
	),
	'rodzaj_transportu' => array(
		'drogowy'     => 'Drogowy',
		'morski'      => 'Morski',
		'kolejowy'    => 'Kolejowy',
		'lotniczy'    => 'Lotniczy',
		'intermodalny' => 'Intermodalny',
	),
	'rola_odbiorcy'     => array(
		'zarzad'            => 'Właściciel / zarząd',
		'operacje'          => 'Operacje',
		'finanse'           => 'Finanse',
		'hr'                => 'HR',
		'security'          => 'Security',
		'prawo-compliance'  => 'Prawo / compliance',
		'it'                => 'IT',
		'supply-chain'      => 'Supply chain',
	),
	'problem'           => array(
		'plynnosc'      => 'Płynność',
		'koszty'        => 'Koszty i marża',
		'kradziez'      => 'Kradzież',
		'oszustwo'      => 'Oszustwo',
		'opoznienie'    => 'Opóźnienie',
		'awaria'        => 'Awaria',
		'cyberincydent' => 'Cyberincydent',
		'kontrola'      => 'Kontrola',
		'roszczenie'    => 'Roszczenie',
		'rekrutacja'    => 'Rekrutacja',
		'rotacja'       => 'Rotacja',
		'wdrozenie'     => 'Wdrożenie',
	),
	'typ_organizacji'   => array(
		'przewoznik'          => 'Przewoźnik',
		'spedytor'            => 'Spedytor',
		'operator-logistyczny' => 'Operator logistyczny',
		'magazyn'             => 'Magazyn',
		'producent'           => 'Producent',
		'handel-ecommerce'    => 'Handel / e-commerce',
	),
	'region'            => array(
		'polska'           => 'Polska',
		'ue'               => 'Unia Europejska',
		'europa-wschodnia' => 'Europa Wschodnia',
		'globalnie'        => 'Globalnie',
	),
	'towar_sektor'      => array(
		'fmcg'                    => 'FMCG',
		'pharma'                  => 'Pharma',
		'automotive'              => 'Automotive',
		'towary-wysokiej-wartosci' => 'Towary wysokiej wartości',
		'adr'                     => 'ADR',
		'chlodnia'                => 'Temperatura kontrolowana',
	),
	'typ_narzedzia'     => array(
		'checklisty'  => array( 'name' => 'Checklisty', 'description' => 'Podgląd online i PDF, z wersją i datą aktualizacji.' ),
		'formularze'  => array( 'name' => 'Formularze', 'description' => 'Formularz online lub edytowalny plik.' ),
		'kalkulatory' => array( 'name' => 'Kalkulatory', 'description' => 'Wynik na stronie; zapis wyniku opcjonalnie po podaniu e-maila.' ),
		'matryce'     => array( 'name' => 'Matryce decyzyjne', 'description' => 'Tabela punktowa z instrukcją interpretacji.' ),
		'procedury'   => array( 'name' => 'Procedury', 'description' => 'Kroki, role, kontakty, dokumenty i moment eskalacji.' ),
		'wzory'       => array( 'name' => 'Wzory dokumentów', 'description' => 'Plik do pobrania z informacją o konieczności dostosowania.' ),
		'testy'       => array( 'name' => 'Testy i autodiagnozy', 'description' => 'Wynik opisowy, rekomendowane kroki i powiązane treści.' ),
	),
	'poziom_ryzyka'     => array(
		'krytyczne'    => array( 'name' => 'Krytyczne', 'meta' => array( 'tsl_order' => 1 ), 'description' => 'Wymaga natychmiastowej reakcji zarządu lub operacji.' ),
		'wysokie'      => array( 'name' => 'Wysokie', 'meta' => array( 'tsl_order' => 2 ), 'description' => 'Realny wpływ na koszty, terminy lub odpowiedzialność w najbliższych dniach.' ),
		'srednie'      => array( 'name' => 'Średnie', 'meta' => array( 'tsl_order' => 3 ), 'description' => 'Wpływ ograniczony lub dotyczący części firm; warto zaplanować działania.' ),
		'monitorowac'  => array( 'name' => 'Monitorować', 'meta' => array( 'tsl_order' => 4 ), 'description' => 'Sygnał wczesny; obserwować rozwój sytuacji.' ),
	),
	'typ_wydarzenia'    => array(
		'webinar'             => 'Webinar',
		'debata'              => 'Debata',
		'briefing'            => 'Briefing',
		'sniadanie-biznesowe' => 'Śniadanie biznesowe',
		'spotkanie-partnerow' => 'Spotkanie partnerów',
		'konferencja'         => 'Konferencja',
	),
	'typ_raportu'       => array(
		'raporty'      => 'Raporty',
		'benchmarki'   => 'Benchmarki',
		'data-stories' => 'Data stories',
		'metodologie'  => 'Metodologie',
	),
	'poziom_partnerstwa' => array(
		'zalozycielski' => array( 'name' => 'Partner założycielski', 'meta' => array( 'tsl_order' => 1 ) ),
		'strategiczny'  => array( 'name' => 'Partner strategiczny', 'meta' => array( 'tsl_order' => 2 ) ),
		'merytoryczny'  => array( 'name' => 'Partner merytoryczny', 'meta' => array( 'tsl_order' => 3 ) ),
	),
);
