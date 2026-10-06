<?php
/**
 * Taksonomie (rozdz. 8 mapy serwisu i Załącznik B).
 *
 * Filar i podkategoria to wbudowana, hierarchiczna taksonomia `category`
 * (wymuszone jedno przypisanie — patrz classification.php). Pozostałe
 * taksonomie są warstwą poprzeczną: służą do filtrowania i powiązań,
 * nie tworzą indeksowanych archiwów (brak własnych adresów URL), z wyjątkiem
 * typów narzędzi, które mają strony /narzedzia/{typ}/.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Typy treści, które dzielą drzewo filarów (taksonomia `category`).
 */
function tsl_pillar_post_types() {
	return array( 'post', 'riskbrief', 'narzedzie', tsl_event_post_type(), 'raport' );
}

/**
 * Definicje taksonomii pomocniczych.
 *
 * Klucz to nazwa w słowniku data/terms.php; zarejestrowana taksonomia ma
 * prefiks `tsl_`, a parametr w adresie (query var) jest krótki i czytelny,
 * np. /wyszukiwarka/?s=cmr&format=decyzja-pod-lupa.
 *
 * @return array<string, array>
 */
function tsl_taxonomy_definitions() {
	return array(
		'format'             => array(
			'labels'      => array( 'Format', 'Formaty' ),
			'object'      => array( 'post' ),
			'query_var'   => 'format',
			'description' => 'Dokładnie 1 na materiał. Stały format redakcyjny.',
		),
		'rodzaj_transportu'  => array(
			'labels'    => array( 'Rodzaj transportu', 'Rodzaje transportu' ),
			'object'    => array( 'post', 'riskbrief', 'narzedzie', 'raport' ),
			'query_var' => 'transport',
		),
		'rola_odbiorcy'      => array(
			'labels'    => array( 'Rola odbiorcy', 'Role odbiorców' ),
			'object'    => array( 'post', 'riskbrief', 'narzedzie', 'wydarzenie' ),
			'query_var' => 'rola',
			'description' => '1–3 role na materiał.',
		),
		'problem'            => array(
			'labels'    => array( 'Problem / ryzyko', 'Problemy / ryzyka' ),
			'object'    => array( 'post', 'riskbrief', 'narzedzie' ),
			'query_var' => 'problem',
			'description' => '1–3 problemy na materiał.',
		),
		'typ_organizacji'    => array(
			'labels'    => array( 'Typ organizacji', 'Typy organizacji' ),
			'object'    => array( 'post', 'riskbrief', 'narzedzie' ),
			'query_var' => 'organizacja',
		),
		'region'             => array(
			'labels'    => array( 'Region', 'Regiony' ),
			'object'    => array( 'post', 'riskbrief', 'raport' ),
			'query_var' => 'region',
		),
		'towar_sektor'       => array(
			'labels'    => array( 'Towar / sektor', 'Towary / sektory' ),
			'object'    => array( 'post', 'riskbrief' ),
			'query_var' => 'sektor',
		),
		'typ_narzedzia'      => array(
			'labels'    => array( 'Typ narzędzia', 'Typy narzędzi' ),
			'object'    => array( 'narzedzie' ),
			'query_var' => 'typ-narzedzia',
			// Strony /narzedzia/checklisty/ itd. (Załącznik A).
			'rewrite'   => array( 'slug' => 'narzedzia', 'with_front' => false ),
		),
		'poziom_ryzyka'      => array(
			'labels'    => array( 'Poziom ryzyka', 'Poziomy ryzyka' ),
			'object'    => array( 'riskbrief' ),
			'query_var' => 'poziom',
		),
		'typ_wydarzenia'     => array(
			'labels'    => array( 'Typ wydarzenia', 'Typy wydarzeń' ),
			'object'    => array( 'wydarzenie' ),
			'query_var' => 'typ-wydarzenia',
		),
		'typ_raportu'        => array(
			'labels'    => array( 'Typ materiału', 'Typy materiałów' ),
			'object'    => array( 'raport' ),
			'query_var' => 'typ-raportu',
		),
		'poziom_partnerstwa' => array(
			'labels'    => array( 'Poziom partnerstwa', 'Poziomy partnerstwa' ),
			'object'    => array( 'partner' ),
			'query_var' => 'poziom-partnerstwa',
		),
	);
}

/**
 * Rejestruje taksonomie. Wywoływane przed typami treści, bo adres narzędzia
 * zawiera znacznik %tsl_typ_narzedzia%.
 */
function tsl_register_taxonomies() {
	foreach ( tsl_taxonomy_definitions() as $key => $def ) {
		list( $singular, $plural ) = $def['labels'];

		register_taxonomy(
			'tsl_' . $key,
			array_map( 'tsl_map_event_type', $def['object'] ),
			array(
				'labels'             => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'menu_name'     => $plural,
					'all_items'     => 'Wszystkie',
					'edit_item'     => 'Edytuj',
					'add_new_item'  => 'Dodaj nowy',
					'search_items'  => 'Szukaj',
					'not_found'     => 'Brak pozycji',
				),
				'description'        => $def['description'] ?? '',
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => false,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'show_tagcloud'      => false,
				'query_var'          => $def['query_var'],
				'rewrite'            => $def['rewrite'] ?? false,
			)
		);
	}

	// Filar i podkategoria są wspólne dla wszystkich typów treści z drzewem.
	foreach ( tsl_pillar_post_types() as $post_type ) {
		register_taxonomy_for_object_type( 'category', $post_type );
	}
}
add_action( 'init', 'tsl_register_taxonomies', 5 );

/**
 * Etykiety wbudowanej kategorii zgodne z językiem serwisu.
 */
add_action(
	'init',
	function () {
		$tax = get_taxonomy( 'category' );
		if ( $tax ) {
			$tax->labels->name          = 'Filary i podkategorie';
			$tax->labels->singular_name = 'Filar / podkategoria';
			$tax->labels->menu_name     = 'Filary i podkategorie';
		}
	},
	20
);
