<?php
/**
 * Typy treści (Załącznik B mapy serwisu).
 *
 * `post` pełni rolę artykułu / analizy. Pozostałe typy mają własne archiwa
 * i adresy zgodne z rozdz. 8 (np. /riskbrief/…, /narzedzia/checklisty/…).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definicje typów treści.
 *
 * @return array<string, array>
 */
function tsl_post_type_definitions() {
	return array(
		'riskbrief'  => array(
			'labels'   => array( 'RiskBrief', 'RiskBrief TSL', 'Nowy sygnał' ),
			'slug'     => 'riskbrief',
			'archive'  => 'riskbrief',
			'icon'     => 'dashicons-warning',
			'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'author' ),
			'desc'     => 'Krótkie alerty menedżerskie: co się stało, kogo dotyczy, możliwy skutek i co zrobić teraz.',
		),
		'narzedzie'  => array(
			'labels'   => array( 'Narzędzie', 'Narzędzia', 'Nowe narzędzie' ),
			'slug'     => 'narzedzia/%tsl_typ_narzedzia%',
			'archive'  => 'narzedzia',
			'icon'     => 'dashicons-hammer',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
			'desc'     => 'Checklisty, formularze, kalkulatory, matryce, procedury, wzory i testy — każde przypisane do filaru.',
		),
		'wydarzenie' => array(
			'labels'   => array( 'Wydarzenie', 'Wydarzenia', 'Nowe wydarzenie' ),
			'slug'     => 'wydarzenia',
			'archive'  => 'wydarzenia',
			'icon'     => 'dashicons-calendar-alt',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'desc'     => 'Webinary, debaty, briefingi, śniadania biznesowe i konferencje.',
		),
		'raport'     => array(
			'labels'   => array( 'Raport', 'Raporty i dane', 'Nowy raport' ),
			'slug'     => 'raporty',
			'archive'  => 'raporty',
			'icon'     => 'dashicons-chart-bar',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
			'desc'     => 'Raporty własne, benchmarki, data stories i metodologie.',
		),
		'ekspert'    => array(
			'labels'   => array( 'Ekspert', 'Eksperci', 'Nowy ekspert' ),
			'slug'     => 'eksperci',
			'archive'  => 'eksperci',
			'icon'     => 'dashicons-businessperson',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'desc'     => 'Profile autorów i ekspertów powiązane z realnymi publikacjami.',
		),
		'wydanie'    => array(
			'labels'   => array( 'Wydanie newslettera', 'Newsletter', 'Nowe wydanie' ),
			'slug'     => 'newsletter/archiwum',
			'archive'  => 'newsletter/archiwum',
			'icon'     => 'dashicons-email-alt',
			'supports' => array( 'title', 'editor', 'excerpt', 'revisions' ),
			'desc'     => 'Archiwum wydań newslettera TSL Manager i RiskBrief na e-mail.',
			'search'   => false,
		),
		'partner'    => array(
			'labels'   => array( 'Partner', 'Partnerzy', 'Nowy partner' ),
			'slug'     => 'partnerzy',
			'archive'  => 'partnerzy',
			'icon'     => 'dashicons-groups',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'desc'     => 'Partnerzy projektu z jawnym oznaczeniem roli w ekosystemie.',
		),
	);
}

/**
 * Rejestruje typy treści.
 */
function tsl_register_post_types() {
	$position = 5;

	foreach ( tsl_post_type_definitions() as $type => $def ) {
		if ( 'wydarzenie' === $type && tsl_events_tec() ) {
			continue; // Wydarzenia obsługuje The Events Calendar (includes/events.php).
		}
		list( $singular, $plural, $add_new ) = $def['labels'];

		register_post_type(
			$type,
			array(
				'labels'        => array(
					'name'               => $plural,
					'singular_name'      => $singular,
					'menu_name'          => $plural,
					'add_new'            => 'Dodaj',
					'add_new_item'       => $add_new,
					'edit_item'          => 'Edytuj: ' . $singular,
					'view_item'          => 'Zobacz',
					'all_items'          => 'Wszystkie',
					'search_items'       => 'Szukaj',
					'not_found'          => 'Brak pozycji',
					'not_found_in_trash' => 'Kosz jest pusty',
				),
				'description'   => $def['desc'],
				'public'        => true,
				'show_in_rest'  => true,
				'menu_position' => ++$position,
				'menu_icon'     => $def['icon'],
				'supports'      => $def['supports'],
				'has_archive'   => $def['archive'],
				'exclude_from_search' => isset( $def['search'] ) && ! $def['search'],
				'rewrite'       => array(
					'slug'       => $def['slug'],
					'with_front' => false,
				),
			)
		);
	}

	// Artykuł: etykiety w języku redakcji.
	$post = get_post_type_object( 'post' );
	if ( $post ) {
		$post->labels->name          = 'Artykuły';
		$post->labels->singular_name = 'Artykuł';
		$post->labels->menu_name     = 'Artykuły';
		$post->labels->add_new_item  = 'Nowy artykuł';
		$post->labels->all_items     = 'Wszystkie artykuły';
	}
}
add_action( 'init', 'tsl_register_post_types', 6 );
