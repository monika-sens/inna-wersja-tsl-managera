<?php
/**
 * Reguły klasyfikacji (rozdz. 1 i 8 mapy serwisu).
 *
 * - Każdy materiał ma dokładnie jeden filar i jedną podkategorię główną:
 *   przypisanie w taksonomii `category` jest ograniczane do jednego,
 *   najgłębszego terminu (podkategoria wskazuje filar przez rodzica).
 * - Format artykułu: dokładnie jeden.
 * - Strony filarów i podkategorii pokazują wszystkie typy treści z drzewa.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Taksonomie, w których materiał może mieć tylko jeden termin.
 */
function tsl_single_term_taxonomies() {
	return array( 'category', 'tsl_format', 'tsl_typ_narzedzia', 'tsl_poziom_ryzyka' );
}

/**
 * Głębokość terminu w drzewie (0 = filar).
 *
 * @param int    $term_id  ID terminu.
 * @param string $taxonomy Taksonomia.
 */
function tsl_term_depth( $term_id, $taxonomy ) {
	return count( get_ancestors( $term_id, $taxonomy, 'taxonomy' ) );
}

/**
 * Po zapisaniu terminów zostawia jeden — najgłębszy (a przy remisie pierwszy).
 * Działa niezależnie od tego, czy zapis przyszedł z edytora, REST API czy WP-CLI.
 *
 * @param int    $object_id Obiekt.
 * @param array  $terms     Przekazane terminy.
 * @param array  $tt_ids    ID term_taxonomy po zapisie.
 * @param string $taxonomy  Taksonomia.
 */
function tsl_enforce_single_term( $object_id, $terms, $tt_ids, $taxonomy ) {
	static $running = false;

	if ( $running || count( $tt_ids ) < 2 || ! in_array( $taxonomy, tsl_single_term_taxonomies(), true ) ) {
		return;
	}

	$term_ids = array();
	foreach ( $tt_ids as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, $taxonomy );
		if ( $term ) {
			$term_ids[] = (int) $term->term_id;
		}
	}

	// Domyślna „Bez kategorii” nigdy nie wygrywa z prawdziwym filarem.
	if ( 'category' === $taxonomy ) {
		$default  = (int) get_option( 'default_category' );
		$filtered = array_values( array_diff( $term_ids, array( $default ) ) );
		if ( $filtered ) {
			$term_ids = $filtered;
		}
	}

	$keep  = $term_ids[0];
	$depth = tsl_term_depth( $keep, $taxonomy );
	foreach ( $term_ids as $term_id ) {
		$d = tsl_term_depth( $term_id, $taxonomy );
		if ( $d > $depth ) {
			$keep  = $term_id;
			$depth = $d;
		}
	}

	$running = true;
	wp_set_object_terms( $object_id, array( $keep ), $taxonomy, false );
	$running = false;
}
add_action( 'set_object_terms', 'tsl_enforce_single_term', 10, 4 );

/**
 * Strony filarów i podkategorii zawierają artykuły, RiskBriefy, narzędzia,
 * wydarzenia i raporty przypisane do danego miejsca w drzewie.
 *
 * @param WP_Query $query Zapytanie.
 */
function tsl_category_archive_post_types( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_category() && ! $query->get( 'post_type' ) ) {
		$query->set( 'post_type', tsl_pillar_post_types() );
	}
	if ( $query->is_search() && ! $query->get( 'post_type' ) ) {
		$query->set( 'post_type', array_merge( tsl_pillar_post_types(), array( 'ekspert' ) ) );
	}
}
add_action( 'pre_get_posts', 'tsl_category_archive_post_types' );

/**
 * Filar (termin najwyższego poziomu) dla materiału.
 *
 * @param int|WP_Post|null $post Materiał.
 * @return WP_Term|null
 */
function tsl_get_pillar( $post = null ) {
	$term = tsl_get_primary_term( $post );
	return $term ? tsl_get_pillar_for_term( $term ) : null;
}

/**
 * Filar, do którego należy termin kategorii (sam termin, jeśli jest filarem).
 *
 * @param WP_Term $term Termin kategorii.
 * @return WP_Term|null
 */
function tsl_get_pillar_for_term( $term ) {
	$ancestors = get_ancestors( $term->term_id, 'category', 'taxonomy' );
	$top       = $ancestors ? end( $ancestors ) : $term->term_id;
	$pillar    = get_term( $top, 'category' );

	return ( $pillar instanceof WP_Term && tsl_is_pillar( $pillar ) ) ? $pillar : null;
}

/**
 * Podkategoria główna (jedyny termin `category`) dla materiału.
 *
 * @param int|WP_Post|null $post Materiał.
 * @return WP_Term|null
 */
function tsl_get_primary_term( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$terms = get_the_terms( $post, 'category' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	return $terms[0];
}
