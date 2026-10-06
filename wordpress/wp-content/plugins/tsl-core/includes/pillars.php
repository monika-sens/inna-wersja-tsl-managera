<?php
/**
 * Filary i podkategorie: metadane terminów i funkcje pomocnicze dla motywu.
 *
 * Metadane terminu `category`:
 *  - tsl_pillar  1 = filar (termin najwyższego poziomu w nawigacji)
 *  - tsl_order   kolejność wyświetlania
 *  - tsl_short   skrócona etykieta w nagłówku desktopowym (filary)
 *  - tsl_promise jednozdaniowa obietnica (filary)
 *  - tsl_mega    1 = podkategoria widoczna w mega menu
 *
 * Wszystkie pola można edytować w panelu: Artykuły → Filary i podkategorie.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Czy termin jest filarem.
 *
 * @param WP_Term $term Termin.
 */
function tsl_is_pillar( $term ) {
	return $term instanceof WP_Term && 'category' === $term->taxonomy && (bool) get_term_meta( $term->term_id, 'tsl_pillar', true );
}

/**
 * Sześć filarów w ustalonej kolejności.
 *
 * @return WP_Term[]
 */
function tsl_get_pillars() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'parent'     => 0,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
				array(
					'key'   => 'tsl_pillar',
					'value' => '1',
				),
			),
		)
	);
	return is_wp_error( $terms ) ? array() : tsl_sort_terms( $terms );
}

/**
 * Sortuje terminy według tsl_order (brak wartości = na końcu), potem nazwy.
 *
 * @param WP_Term[] $terms Terminy.
 * @return WP_Term[]
 */
function tsl_sort_terms( $terms ) {
	usort(
		$terms,
		function ( $a, $b ) {
			$oa = get_term_meta( $a->term_id, 'tsl_order', true );
			$ob = get_term_meta( $b->term_id, 'tsl_order', true );
			$oa = '' === $oa ? PHP_INT_MAX : (int) $oa;
			$ob = '' === $ob ? PHP_INT_MAX : (int) $ob;
			return $oa <=> $ob ?: strcoll( $a->name, $b->name );
		}
	);
	return $terms;
}

/**
 * Bezpośrednie podkategorie terminu.
 *
 * @param WP_Term $term      Rodzic.
 * @param bool    $mega_only Tylko pozycje oznaczone do mega menu.
 * @return WP_Term[]
 */
function tsl_get_subcategories( $term, $mega_only = false ) {
	$args = array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'parent'     => $term->term_id,
	);
	if ( $mega_only ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
			array(
				'key'   => 'tsl_mega',
				'value' => '1',
			),
		);
	}
	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	// Tryb produkcyjny: puste podkategorie nie są pokazywane na stronie.
	if ( ! is_admin() && function_exists( 'tsl_is_demo_mode' ) && ! tsl_is_demo_mode() ) {
		$terms = array_filter( $terms, 'tsl_term_has_content' );
	}
	return tsl_sort_terms( $terms );
}

/**
 * Etykieta do nagłówka: skrócona dla filarów, pełna dla pozostałych.
 *
 * @param WP_Term $term Termin.
 */
function tsl_term_short_label( $term ) {
	$short = get_term_meta( $term->term_id, 'tsl_short', true );
	return $short ? $short : $term->name;
}

/**
 * Obietnica filaru.
 *
 * @param WP_Term $term Termin.
 */
function tsl_term_promise( $term ) {
	return (string) get_term_meta( $term->term_id, 'tsl_promise', true );
}

/*
 * Pola w panelu edycji kategorii.
 */

/**
 * Lista edytowalnych pól.
 */
function tsl_term_meta_fields() {
	return array(
		'tsl_pillar'  => array( 'label' => 'Filar', 'type' => 'checkbox', 'help' => 'Zaznacz tylko dla sześciu filarów najwyższego poziomu.' ),
		'tsl_short'   => array( 'label' => 'Skrócona etykieta', 'type' => 'text', 'help' => 'Nagłówek desktopowy, np. „Zarządzanie”.' ),
		'tsl_promise' => array( 'label' => 'Obietnica filaru', 'type' => 'text', 'help' => 'Jedno zdanie w mega menu i na stronie filaru.' ),
		'tsl_mega'    => array( 'label' => 'Pokaż w mega menu', 'type' => 'checkbox', 'help' => 'Maksymalnie 5–6 podkategorii na filar.' ),
		'tsl_order'   => array( 'label' => 'Kolejność', 'type' => 'number', 'help' => 'Mniejsza liczba = wyżej.' ),
	);
}

add_action(
	'category_edit_form_fields',
	function ( $term ) {
		wp_nonce_field( 'tsl_term_meta', 'tsl_term_meta_nonce' );
		foreach ( tsl_term_meta_fields() as $key => $field ) {
			$value = get_term_meta( $term->term_id, $key, true );
			echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			if ( 'checkbox' === $field['type'] ) {
				echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( $value, '1', false ) . '>';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
			}
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p></td></tr>';
		}
	}
);

add_action(
	'edited_category',
	function ( $term_id ) {
		if ( ! isset( $_POST['tsl_term_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tsl_term_meta_nonce'] ), 'tsl_term_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		foreach ( tsl_term_meta_fields() as $key => $field ) {
			if ( 'checkbox' === $field['type'] ) {
				$value = isset( $_POST[ $key ] ) ? '1' : '';
			} else {
				$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			}
			if ( '' === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}
);
