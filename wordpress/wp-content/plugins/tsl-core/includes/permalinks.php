<?php
/**
 * Adresy URL (rozdz. 8 mapy serwisu).
 *
 *   Filar          /zarzadzanie-finanse/
 *   Podkategoria   /transport-operacje/transport/morski/
 *   Artykuł        /prawo/umowy-odpowiedzialnosc/klauzule-po-szkodzie/
 *   RiskBrief      /riskbrief/strajk-w-porcie-aktualizacja/
 *   Narzędzie      /narzedzia/checklisty/weryfikacja-przewoznika/
 *   Wydarzenie     /wydarzenia/webinar-cmr-odpowiedzialnosc/
 *   Raport         /raporty/odpornosc-operacyjna-tsl-2026/
 *
 * Struktura bezpośrednich odnośników: /%category%/%postname%/ (ustawiana przez
 * seeder). Prefiks /category/ jest usuwany z adresów filarów i podkategorii.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pełna ścieżka terminu kategorii, np. "transport-operacje/transport/morski".
 *
 * @param WP_Term $term Termin.
 */
function tsl_category_path( $term ) {
	$slugs = array( $term->slug );
	foreach ( get_ancestors( $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'category' );
		if ( $ancestor instanceof WP_Term ) {
			array_unshift( $slugs, $ancestor->slug );
		}
	}
	return implode( '/', $slugs );
}

/**
 * Reguły dla filarów i podkategorii bez prefiksu /category/.
 * Zastępują domyślne reguły kategorii (te są dodawane na początku listy,
 * więc mają pierwszeństwo przed regułami artykułów /%category%/%postname%/).
 *
 * @return array<string, string>
 */
function tsl_category_rewrite_rules() {
	$rules = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return $rules;
	}

	foreach ( $terms as $term ) {
		$path = preg_quote( tsl_category_path( $term ), '#' );
		$var  = 'index.php?category_name=' . tsl_category_path( $term );

		$rules[ $path . '/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = $var . '&feed=$matches[1]';
		$rules[ $path . '/page/?([0-9]{1,})/?$' ]                  = $var . '&paged=$matches[1]';
		$rules[ $path . '/?$' ]                                    = $var;
	}

	// Stare adresy /category/… przekierowujemy na nowe.
	$rules['category/(.+?)/?$'] = 'index.php?tsl_category_redirect=$matches[1]';

	return $rules;
}
add_filter( 'category_rewrite_rules', 'tsl_category_rewrite_rules' );

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'tsl_category_redirect';
		return $vars;
	}
);

add_action(
	'template_redirect',
	function () {
		$path = get_query_var( 'tsl_category_redirect' );
		if ( ! $path ) {
			return;
		}
		$term = get_category_by_path( $path, true );
		if ( $term ) {
			wp_safe_redirect( get_term_link( $term ), 301 );
			exit;
		}
	}
);

/**
 * Link do filaru / podkategorii bez prefiksu /category/.
 *
 * @param string  $link     Domyślny link.
 * @param WP_Term $term     Termin.
 * @param string  $taxonomy Taksonomia.
 */
function tsl_category_term_link( $link, $term, $taxonomy ) {
	if ( 'category' !== $taxonomy || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}
	return home_url( user_trailingslashit( tsl_category_path( $term ), 'category' ) );
}
add_filter( 'term_link', 'tsl_category_term_link', 10, 3 );

/**
 * Zmiana drzewa kategorii wymaga przebudowy reguł.
 */
function tsl_schedule_rewrite_flush() {
	update_option( 'tsl_flush_rewrite', 1, false );
}
add_action( 'created_category', 'tsl_schedule_rewrite_flush' );
add_action( 'edited_category', 'tsl_schedule_rewrite_flush' );
add_action( 'delete_category', 'tsl_schedule_rewrite_flush' );

add_action(
	'init',
	function () {
		if ( get_option( 'tsl_flush_rewrite' ) ) {
			delete_option( 'tsl_flush_rewrite' );
			flush_rewrite_rules( false );
		}
	},
	99
);

/**
 * Adres narzędzia: /narzedzia/{typ}/{slug}/.
 *
 * @param string  $link Link.
 * @param WP_Post $post Narzędzie.
 */
function tsl_tool_permalink( $link, $post ) {
	if ( 'narzedzie' !== $post->post_type || false === strpos( $link, '%tsl_typ_narzedzia%' ) ) {
		return $link;
	}
	$terms = get_the_terms( $post, 'tsl_typ_narzedzia' );
	$slug  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : 'inne';
	return str_replace( '%tsl_typ_narzedzia%', $slug, $link );
}
add_filter( 'post_type_link', 'tsl_tool_permalink', 10, 2 );

/**
 * Taksonomie poprzeczne nie tworzą indeksowanych archiwów (rozdz. 8):
 * filtrowane widoki dostają noindex i nie trafiają do mapy witryny.
 */
function tsl_indexable_taxonomies() {
	return array( 'category', 'tsl_typ_narzedzia' );
}

/**
 * Czy bieżący widok to archiwum z dodatkowym filtrem (?format=…, ?transport=…,
 * ?status-wydarzenia=…) — taki widok nie jest indeksowany.
 */
function tsl_seo_is_filtered_view() {
	$vars = array( 'status-wydarzenia', 'status-sygnalu' );
	foreach ( tsl_taxonomy_definitions() as $def ) {
		$vars[] = $def['query_var'];
	}
	foreach ( $vars as $var ) {
		if ( isset( $_GET[ $var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}
	}
	return false;
}

add_filter(
	'wp_robots',
	function ( $robots ) {
		$noindex = is_tag() || is_search() || is_404() || ( is_tax() && ! is_tax( tsl_indexable_taxonomies() ) ) || tsl_seo_is_filtered_view();

		if ( $noindex ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

add_filter(
	'wp_sitemaps_taxonomies',
	function ( $taxonomies ) {
		return array_intersect_key( $taxonomies, array_flip( tsl_indexable_taxonomies() ) );
	}
);
