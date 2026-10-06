<?php
/**
 * Pola materiałów i powiązania (rozdz. 5, 7 i Załącznik B mapy serwisu).
 *
 * Jeden opis pól (tsl_field_schema) służy do:
 *  - rejestracji metadanych w WordPressie i REST API,
 *  - zbudowania panelu „Szczegóły” w edytorze (assets/editor.js),
 *  - odczytu wartości w motywie (tsl_field, tsl_related).
 *
 * Powiązania są obustronne: pole `tsl_rel_{typ}` przechowuje ID materiałów
 * danego typu (wiele wierszy meta). Materiał B pokazuje A, jeśli A wskazuje B
 * albo B wskazuje A — redakcja łączy treści tylko po jednej stronie.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Opis pól dla typów treści.
 *
 * Typy pól: text, url, textarea, lines (lista — jedna pozycja w linii),
 * select, date, datetime, toggle, media (ID załącznika), pillars
 * (kontekst dodatkowy: 0–2 filary), relation (powiązane materiały).
 *
 * @return array<string, array<int, array>>
 */
function tsl_field_schema() {
	$schema = tsl_field_schema_base();
	if ( ! tsl_events_tec() ) {
		return $schema;
	}

	// The Events Calendar: termin i miejsce obsługuje TEC; reszta pól zostaje.
	$schema['tribe_events'] = array_values(
		array_filter(
			$schema['wydarzenie'],
			function ( $field ) {
				return ! in_array( $field['key'], array( 'tsl_start', 'tsl_end', 'tsl_location' ), true );
			}
		)
	);
	unset( $schema['wydarzenie'] );
	foreach ( $schema as $type => $fields ) {
		foreach ( $fields as $i => $field ) {
			if ( isset( $field['target'] ) ) {
				$schema[ $type ][ $i ]['target'] = tsl_map_event_type( $field['target'] );
			}
		}
	}
	return $schema;
}

/**
 * Opis pól niezależny od silnika wydarzeń.
 *
 * @return array<string, array<int, array>>
 */
function tsl_field_schema_base() {
	$editorial = array(
		'key'     => 'tsl_label',
		'type'    => 'select',
		'label'   => 'Oznaczenie materiału',
		'help'    => 'Materiały partnerskie i sponsorowane są jawnie oznaczane (zasady redakcyjne).',
		'options' => array(
			''             => 'Materiał redakcyjny',
			'partnerski'   => 'Materiał partnerski',
			'sponsorowany' => 'Materiał sponsorowany',
		),
	);
	$context   = array(
		'key'   => 'tsl_context',
		'type'  => 'pillars',
		'label' => 'Kontekst dodatkowy',
		'help'  => 'Maksymalnie 2 inne filary, w których materiał jest też istotny. Nie zmienia filaru głównego ani adresu.',
	);
	$sources   = array(
		'key'   => 'tsl_sources',
		'type'  => 'lines',
		'label' => 'Źródła',
		'help'  => 'Jedno źródło w linii. Adresy URL zostaną podlinkowane.',
	);

	return array(
		'post'       => array(
			$editorial,
			$context,
			array(
				'key'   => 'tsl_takeaways',
				'type'  => 'lines',
				'label' => 'Co zrobić w poniedziałek',
				'help'  => '3–5 konkretnych działań, jedno w linii. Wyświetlane w ramce po treści.',
			),
			$sources,
			array(
				'key'     => 'tsl_cta',
				'type'    => 'select',
				'label'   => 'Główny następny krok',
				'help'    => 'Domyślnie: powiązane narzędzie, a jeśli go brak — narzędzie z tej samej podkategorii.',
				'options' => array(
					''           => 'Automatycznie (narzędzie)',
					'wydarzenie' => 'Wydarzenie',
					'ekspert'    => 'Ekspert',
					'newsletter' => 'Newsletter',
					'kontakt'    => 'Kontakt z redakcją',
				),
			),
			array( 'key' => 'tsl_rel_narzedzie', 'type' => 'relation', 'target' => 'narzedzie', 'label' => 'Powiązane narzędzia' ),
			array( 'key' => 'tsl_rel_wydarzenie', 'type' => 'relation', 'target' => 'wydarzenie', 'label' => 'Powiązane wydarzenia' ),
			array( 'key' => 'tsl_rel_ekspert', 'type' => 'relation', 'target' => 'ekspert', 'label' => 'Eksperci', 'help' => 'Autor merytoryczny lub konsultant materiału.' ),
			array( 'key' => 'tsl_rel_partner', 'type' => 'relation', 'target' => 'partner', 'label' => 'Partner', 'help' => 'Dla materiałów partnerskich i sponsorowanych.' ),
			array( 'key' => 'tsl_biz_cta', 'type' => 'textarea', 'label' => 'CTA biznesowe', 'help' => 'Ramka pod artykułem. Link w formacie [tekst](https://adres). Puste = brak ramki.' ),
			array( 'key' => 'tsl_code', 'type' => 'text', 'label' => 'ID materiału', 'help' => 'Np. ZF-03-1. Ilustracja z tym ID na początku nazwy pliku sama zostanie obrazkiem wyróżniającym.' ),
			array( 'key' => 'tsl_seo_title', 'type' => 'text', 'label' => 'Tytuł SEO', 'help' => 'Tytuł w wynikach wyszukiwania (do ok. 60 znaków). Puste = tytuł artykułu.' ),
			array( 'key' => 'tsl_seo_desc', 'type' => 'textarea', 'label' => 'Opis SEO (meta description)', 'help' => 'Do ok. 155 znaków. Puste = lead.' ),
			array( 'key' => 'tsl_nl_teaser', 'type' => 'textarea', 'label' => 'Zajawka do newslettera' ),
		),
		'riskbrief'  => array(
			array(
				'key'     => 'tsl_status',
				'type'    => 'select',
				'label'   => 'Status',
				'options' => array(
					'nowe'         => 'Nowe',
					'aktualizacja' => 'Aktualizacja',
					'stabilna'     => 'Sytuacja stabilna',
					'wygaslo'      => 'Wygasło',
				),
			),
			array( 'key' => 'tsl_who', 'type' => 'textarea', 'label' => 'Kogo dotyczy', 'help' => 'Jednoznaczne wskazanie odbiorców.' ),
			array( 'key' => 'tsl_impact', 'type' => 'textarea', 'label' => 'Możliwy skutek', 'help' => 'Koszt, opóźnienie, odpowiedzialność, ryzyko operacyjne lub reputacyjne.' ),
			array( 'key' => 'tsl_actions', 'type' => 'lines', 'label' => 'Co zrobić teraz', 'help' => '1–3 działania możliwe do wykonania od razu, jedno w linii.' ),
			array( 'key' => 'tsl_valid_until', 'type' => 'date', 'label' => 'Ważne do' ),
			array( 'key' => 'tsl_review_date', 'type' => 'date', 'label' => 'Ponowna weryfikacja' ),
			$sources,
			array( 'key' => 'tsl_rel_post', 'type' => 'relation', 'target' => 'post', 'label' => 'Pełna analiza' ),
			array( 'key' => 'tsl_rel_narzedzie', 'type' => 'relation', 'target' => 'narzedzie', 'label' => 'Narzędzie / procedura' ),
		),
		'narzedzie'  => array(
			array( 'key' => 'tsl_version', 'type' => 'text', 'label' => 'Wersja', 'help' => 'Np. 1.0. Każda zmiana merytoryczna podnosi wersję.' ),
			array( 'key' => 'tsl_version_date', 'type' => 'date', 'label' => 'Data aktualizacji' ),
			array( 'key' => 'tsl_file', 'type' => 'media', 'label' => 'Plik do pobrania', 'help' => 'PDF, arkusz lub edytowalny dokument.' ),
			array(
				'key'     => 'tsl_access',
				'type'    => 'select',
				'label'   => 'Dostęp',
				'options' => array(
					''       => 'Otwarty',
					'e-mail' => 'Po podaniu e-maila',
				),
			),
			array( 'key' => 'tsl_steps', 'type' => 'lines', 'label' => 'Jak korzystać', 'help' => 'Krótka instrukcja, jeden krok w linii.' ),
			array(
				'key'   => 'tsl_checklist',
				'type'  => 'lines',
				'label' => 'Punkty checklisty',
				'help'  => 'Jeden punkt w linii. „## Nazwa” = nagłówek sekcji. „!” na początku = punkt krytyczny (czerwona flaga). Wypełnione pole zamienia narzędzie w interaktywną checklistę z wydrukiem do PDF.',
			),
			array( 'key' => 'tsl_checklist_alert', 'type' => 'textarea', 'label' => 'Komunikat przy niespełnionych punktach krytycznych', 'help' => 'Np. „Nie wydawaj ładunku — skontaktuj się z działem bezpieczeństwa.” Puste = komunikat domyślny.' ),
			$context,
			array( 'key' => 'tsl_rel_ekspert', 'type' => 'relation', 'target' => 'ekspert', 'label' => 'Właściciel merytoryczny' ),
			array( 'key' => 'tsl_rel_post', 'type' => 'relation', 'target' => 'post', 'label' => 'Przeczytaj przed użyciem' ),
			array( 'key' => 'tsl_rel_wydarzenie', 'type' => 'relation', 'target' => 'wydarzenie', 'label' => 'Powiązane wydarzenie' ),
		),
		'wydarzenie' => array(
			array( 'key' => 'tsl_start', 'type' => 'datetime', 'label' => 'Początek' ),
			array( 'key' => 'tsl_end', 'type' => 'datetime', 'label' => 'Koniec' ),
			array(
				'key'     => 'tsl_mode',
				'type'    => 'select',
				'label'   => 'Forma',
				'options' => array(
					'online'      => 'Online',
					'stacjonarne' => 'Stacjonarnie',
					'hybrydowe'   => 'Hybrydowo',
				),
			),
			array( 'key' => 'tsl_location', 'type' => 'text', 'label' => 'Miejsce', 'help' => 'Adres lub platforma online.' ),
			array( 'key' => 'tsl_registration_url', 'type' => 'url', 'label' => 'Link do rejestracji', 'help' => 'Do czasu uruchomienia formularza w serwisie (etap 3).' ),
			array( 'key' => 'tsl_agenda', 'type' => 'lines', 'label' => 'Agenda', 'help' => 'Jeden punkt w linii, np. „10:00 — Otwarcie”.' ),
			array( 'key' => 'tsl_recording_url', 'type' => 'url', 'label' => 'Nagranie', 'help' => 'Po wydarzeniu strona pokazuje nagranie i materiały.' ),
			$editorial,
			array( 'key' => 'tsl_rel_ekspert', 'type' => 'relation', 'target' => 'ekspert', 'label' => 'Prelegenci' ),
			array( 'key' => 'tsl_rel_partner', 'type' => 'relation', 'target' => 'partner', 'label' => 'Partnerzy wydarzenia' ),
			array( 'key' => 'tsl_rel_post', 'type' => 'relation', 'target' => 'post', 'label' => 'Materiały po wydarzeniu' ),
			array( 'key' => 'tsl_rel_raport', 'type' => 'relation', 'target' => 'raport', 'label' => 'Premiera raportu' ),
		),
		'raport'     => array(
			array( 'key' => 'tsl_takeaways', 'type' => 'lines', 'label' => 'Kluczowe wnioski', 'help' => 'Zawsze publiczne — także gdy pełny raport wymaga e-maila.' ),
			array( 'key' => 'tsl_methodology', 'type' => 'textarea', 'label' => 'Metodologia' ),
			array( 'key' => 'tsl_data_scope', 'type' => 'text', 'label' => 'Zakres i data danych', 'help' => 'Np. „Badanie CAWI, n=214, czerwiec 2026”.' ),
			array( 'key' => 'tsl_file', 'type' => 'media', 'label' => 'Pełny raport (plik)' ),
			array(
				'key'     => 'tsl_access',
				'type'    => 'select',
				'label'   => 'Dostęp do pełnej wersji',
				'options' => array(
					''       => 'Otwarty',
					'e-mail' => 'Po podaniu e-maila',
				),
			),
			$editorial,
			array( 'key' => 'tsl_rel_ekspert', 'type' => 'relation', 'target' => 'ekspert', 'label' => 'Autorzy' ),
			array( 'key' => 'tsl_rel_partner', 'type' => 'relation', 'target' => 'partner', 'label' => 'Partnerzy badania' ),
			array( 'key' => 'tsl_rel_wydarzenie', 'type' => 'relation', 'target' => 'wydarzenie', 'label' => 'Wydarzenie premierowe' ),
			array( 'key' => 'tsl_rel_post', 'type' => 'relation', 'target' => 'post', 'label' => 'Artykuły pochodne' ),
		),
		'ekspert'    => array(
			array( 'key' => 'tsl_position', 'type' => 'text', 'label' => 'Stanowisko' ),
			array( 'key' => 'tsl_organization', 'type' => 'text', 'label' => 'Organizacja', 'help' => 'Jawne powiązanie z organizacją (zasady redakcyjne).' ),
			array( 'key' => 'tsl_competences', 'type' => 'text', 'label' => 'Obszary kompetencji', 'help' => 'Po przecinku, np. „CMR, szkody, umowy przewozu”.' ),
			array( 'key' => 'tsl_linkedin', 'type' => 'url', 'label' => 'Profil LinkedIn' ),
		),
		'wydanie'    => array(
			array( 'key' => 'tsl_nl_send', 'type' => 'send', 'label' => 'Wysyłka' ),
			array( 'key' => 'tsl_auto_riskbrief', 'type' => 'toggle', 'label' => 'Dołącz nowe RiskBriefy', 'help' => 'Sygnały opublikowane od poprzedniego wydania.' ),
			array( 'key' => 'tsl_auto_posts', 'type' => 'toggle', 'label' => 'Dołącz nowe analizy i poradniki' ),
			array( 'key' => 'tsl_auto_tools', 'type' => 'toggle', 'label' => 'Dołącz nowe narzędzia' ),
		),
		'partner'    => array(
			array( 'key' => 'tsl_website', 'type' => 'url', 'label' => 'Strona internetowa' ),
			array( 'key' => 'tsl_area', 'type' => 'text', 'label' => 'Obszar kompetencji w projekcie' ),
		),
	);
}

/**
 * Definicja jednego pola.
 *
 * @param string $post_type Typ treści.
 * @param string $key       Klucz pola.
 * @return array|null
 */
function tsl_field_def( $post_type, $key ) {
	foreach ( tsl_field_schema()[ $post_type ] ?? array() as $field ) {
		if ( $field['key'] === $key ) {
			return $field;
		}
	}
	return null;
}

/**
 * Czy pole przechowuje wiele wartości (wiele wierszy meta).
 *
 * @param array $field Pole.
 */
function tsl_field_is_multi( $field ) {
	return in_array( $field['type'], array( 'relation', 'pillars' ), true );
}

/**
 * Rejestracja metadanych.
 */
function tsl_register_fields() {
	foreach ( tsl_field_schema() as $post_type => $fields ) {
		foreach ( $fields as $field ) {
			if ( 'send' === $field['type'] ) {
				continue; // Panel wysyłki, nie pole w bazie.
			}
			$multi = tsl_field_is_multi( $field );
			$type  = 'string';
			if ( $multi || 'media' === $field['type'] ) {
				$type = 'integer';
			} elseif ( 'toggle' === $field['type'] ) {
				$type = 'boolean';
			}

			$args = array(
				'type'              => $type,
				'single'            => ! $multi,
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) use ( $field ) {
					return tsl_sanitize_field( $value, $field );
				},
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			);
			if ( ! $multi ) {
				$args['default'] = 'integer' === $type ? 0 : ( 'boolean' === $type ? false : '' );
			}
			register_post_meta( $post_type, $field['key'], $args );
		}
	}
}
add_action( 'init', 'tsl_register_fields', 7 );

/**
 * Oczyszczanie wartości pola.
 *
 * @param mixed $value Wartość.
 * @param array $field Pole.
 * @return mixed
 */
function tsl_sanitize_field( $value, $field ) {
	switch ( $field['type'] ) {
		case 'url':
			return esc_url_raw( (string) $value );
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $value );
		case 'select':
			return array_key_exists( (string) $value, $field['options'] ) ? (string) $value : '';
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? (string) $value : '';
		case 'datetime':
			return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', substr( (string) $value, 0, 16 ) ) ? substr( (string) $value, 0, 16 ) : '';
		case 'toggle':
			return (bool) $value;
		case 'media':
		case 'relation':
		case 'pillars':
			return absint( $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Kontekst dodatkowy: najwyżej 2 filary, inne niż filar główny.
 *
 * @param int    $object_id Materiał.
 * @param int    $meta_id   Meta.
 * @param string $meta_key  Klucz.
 */
function tsl_limit_context( $object_id, $meta_id = 0, $meta_key = '' ) {
	static $running = false;
	if ( $running || 'tsl_context' !== $meta_key ) {
		return;
	}
	$ids    = array_map( 'intval', get_post_meta( $object_id, 'tsl_context' ) );
	$pillar = function_exists( 'tsl_get_pillar' ) ? tsl_get_pillar( $object_id ) : null;
	$keep   = array_slice( array_values( array_unique( array_diff( $ids, array( $pillar ? $pillar->term_id : 0, 0 ) ) ) ), 0, 2 );

	if ( $keep !== $ids ) {
		$running = true;
		delete_post_meta( $object_id, 'tsl_context' );
		foreach ( $keep as $id ) {
			add_post_meta( $object_id, 'tsl_context', $id );
		}
		$running = false;
	}
}
add_action( 'added_post_meta', 'tsl_limit_context', 10, 3 );

/**
 * Wartość pola pojedynczego.
 *
 * @param string           $key  Klucz.
 * @param int|WP_Post|null $post Materiał.
 * @return mixed
 */
function tsl_field( $key, $post = null ) {
	$post = get_post( $post );
	return $post ? get_post_meta( $post->ID, $key, true ) : '';
}

/**
 * Pole typu „lines” jako tablica niepustych linii.
 *
 * @param string           $key  Klucz.
 * @param int|WP_Post|null $post Materiał.
 * @return string[]
 */
function tsl_field_lines( $key, $post = null ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) tsl_field( $key, $post ) ) ) ) );
}

/**
 * Etykieta wybranej opcji pola select.
 *
 * @param string           $key  Klucz.
 * @param int|WP_Post|null $post Materiał.
 */
function tsl_field_label( $key, $post = null ) {
	$post  = get_post( $post );
	$value = tsl_field( $key, $post );
	$def   = $post ? tsl_field_def( $post->post_type, $key ) : null;
	return ( $def && '' !== $value ) ? ( $def['options'][ $value ] ?? '' ) : '';
}

/**
 * Powiązane materiały danego typu — obustronnie.
 *
 * @param string           $target Typ powiązanych materiałów.
 * @param int|WP_Post|null $post   Materiał.
 * @param int              $limit  Limit (-1 = wszystkie).
 * @return WP_Post[]
 */
function tsl_related( $target, $post = null, $limit = -1 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$target = tsl_map_event_type( $target );

	// Wskazane przez ten materiał (kolejność ustalona przez redakcję).
	$ids = array_map( 'intval', get_post_meta( $post->ID, 'tsl_rel_' . tsl_rel_key( $target ) ) );

	// The Events Calendar domyślnie zawęża zapytania do nadchodzących wydarzeń —
	// powiązania mają obejmować także wydarzenia zakończone.
	$tec = 'tribe_events' === $target ? array(
		'tribe_suppress_query_filters' => true,
		'eventDisplay'                 => 'custom',
	) : array();

	// Materiały, które wskazują ten materiał.
	$reverse = get_posts(
		$tec + array(
			'post_type'      => $target,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
				array(
					'key'   => 'tsl_rel_' . tsl_rel_key( $post->post_type ),
					'value' => $post->ID,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	$ids = array_values( array_unique( array_diff( array_merge( $ids, $reverse ), array( $post->ID ) ) ) );
	if ( ! $ids ) {
		return array();
	}

	// Wczytanie po ID z zachowaniem kolejności redakcji (bez orderby=post__in,
	// które The Events Calendar przepisuje na niepoprawne zapytanie).
	_prime_post_caches( $ids, false, false );
	$posts = array();
	foreach ( $ids as $id ) {
		$item = get_post( $id );
		if ( $item && $target === $item->post_type && 'publish' === $item->post_status ) {
			$posts[] = $item;
		}
	}
	return $limit > 0 ? array_slice( $posts, 0, $limit ) : $posts;
}

/**
 * Panel „Szczegóły” w edytorze blokowym.
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		$screen = get_current_screen();
		$type   = $screen ? $screen->post_type : '';
		$schema = tsl_field_schema();
		if ( ! isset( $schema[ $type ] ) ) {
			return;
		}

		$targets = array();
		foreach ( $schema[ $type ] as $field ) {
			if ( 'relation' === $field['type'] ) {
				$object                     = get_post_type_object( $field['target'] );
				$targets[ $field['target'] ] = $object ? ( $object->rest_base ? $object->rest_base : $field['target'] ) : $field['target'];
			}
		}

		$pillars = array();
		foreach ( tsl_get_pillars() as $pillar ) {
			$pillars[] = array(
				'id'   => $pillar->term_id,
				'name' => $pillar->name,
			);
		}

		wp_enqueue_script(
			'tsl-editor',
			plugins_url( 'assets/editor.js', TSL_CORE_DIR . 'tsl-core.php' ),
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-api-fetch', 'wp-block-editor', 'wp-url', 'wp-html-entities' ),
			TSL_CORE_VERSION,
			true
		);
		wp_add_inline_script(
			'tsl-editor',
			'window.tslEditor = ' . wp_json_encode(
				array(
					'fields'  => $schema[ $type ],
					'targets' => $targets,
					'pillars' => $pillars,
				)
			) . ';',
			'before'
		);
	}
);

/**
 * Meta w REST wymaga obsługi pól własnych przez typ treści.
 */
add_action(
	'init',
	function () {
		foreach ( array_keys( tsl_field_schema() ) as $post_type ) {
			add_post_type_support( $post_type, 'custom-fields' );
		}
	},
	8
);
