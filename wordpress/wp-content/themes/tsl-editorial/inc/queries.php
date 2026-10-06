<?php
/**
 * Zapytania o treści dla modułów strony głównej, filarów i nawigacji.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lista materiałów.
 *
 * @param array $args Argumenty WP_Query.
 * @return WP_Post[]
 */
function tsl_posts( $args = array() ) {
	$query = new WP_Query(
		array_merge(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => 4,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			),
			$args
		)
	);
	return $query->posts;
}

/**
 * Warunek tax_query dla filaru lub podkategorii (z potomkami).
 *
 * @param WP_Term $term Termin kategorii.
 */
function tsl_in_category( $term ) {
	return array(
		array(
			'taxonomy'         => 'category',
			'terms'            => $term->term_id,
			'include_children' => true,
		),
	);
}

/**
 * Warunek tax_query dla terminu taksonomii pomocniczej.
 *
 * @param string          $taxonomy Taksonomia (bez prefiksu tsl_).
 * @param string|string[] $slugs    Slugi.
 */
function tsl_in_term( $taxonomy, $slugs ) {
	return array(
		array(
			'taxonomy' => 'tsl_' . $taxonomy,
			'field'    => 'slug',
			'terms'    => (array) $slugs,
		),
	);
}

/**
 * Materiał wyróżniony w filarze (mega menu i strona filaru): przypięty
 * artykuł z filaru, a jeśli go nie ma — najnowszy. Wynik w cache do zapisu treści.
 *
 * @param WP_Term $pillar Filar.
 * @return WP_Post|null
 */
function tsl_pillar_featured( $pillar ) {
	$key = 'tsl_featured_' . $pillar->term_id;
	$id  = get_transient( $key );

	if ( false === $id ) {
		$sticky = get_option( 'sticky_posts' );
		$posts  = $sticky ? tsl_posts(
			array(
				'post__in'       => $sticky,
				'posts_per_page' => 1,
				'tax_query'      => tsl_in_category( $pillar ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
			)
		) : array();
		if ( ! $posts ) {
			$posts = tsl_posts(
				array(
					'posts_per_page' => 1,
					'tax_query'      => tsl_in_category( $pillar ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
				)
			);
		}
		$id = $posts ? $posts[0]->ID : 0;
		set_transient( $key, $id, HOUR_IN_SECONDS );
	}

	return $id ? get_post( $id ) : null;
}

/**
 * Główna analiza na stronę główną: najnowszy przypięty artykuł lub najnowszy.
 *
 * @return WP_Post|null
 */
function tsl_hero_post() {
	$sticky = get_option( 'sticky_posts' );
	if ( $sticky ) {
		$posts = tsl_posts(
			array(
				'post__in'       => $sticky,
				'posts_per_page' => 1,
			)
		);
		if ( $posts ) {
			return $posts[0];
		}
	}
	$posts = tsl_posts( array( 'posts_per_page' => 1 ) );
	return $posts ? $posts[0] : null;
}

/**
 * Zapis treści unieważnia cache wyróżnień.
 */
add_action(
	'save_post',
	function () {
		if ( ! function_exists( 'tsl_get_pillars' ) ) {
			return;
		}
		foreach ( tsl_get_pillars() as $pillar ) {
			delete_transient( 'tsl_featured_' . $pillar->term_id );
		}
	}
);
