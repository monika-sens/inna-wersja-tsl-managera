<?php
/**
 * Tryb demonstracyjny i produkcyjny.
 *
 * Tryb demonstracyjny (Narzędzia → TSL Manager): materiały przykładowe (_tsl_demo)
 * są widoczne, strony informacyjne z treścią „Do uzupełnienia” są publikowane,
 * puste działy pokazują komunikat „Sekcja w przygotowaniu”, a formularze bez
 * skonfigurowanej poczty nie wysyłają danych, tylko informują o wersji demo.
 *
 * Tryb produkcyjny (domyślny): materiały przykładowe są ukryte na stronie
 * (zostają w bazie), nie można ich wczytać, strony z treścią zastępczą powstają
 * jako szkice, importowane artykuły trafiają do szkiców, a puste podkategorie
 * nie są pokazywane w nawigacji.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Czy serwis działa w trybie demonstracyjnym.
 */
function tsl_is_demo_mode() {
	return '1' === (string) get_option( 'tsl_demo_mode', '0' );
}

/**
 * Czy poczta wychodząca (SMTP) jest skonfigurowana w ustawieniach newslettera.
 */
function tsl_mail_configured() {
	return function_exists( 'tsl_nl_setting' ) && tsl_nl_setting( 'smtp_enabled' ) && '' !== trim( (string) tsl_nl_setting( 'smtp_host' ) );
}

/**
 * Formularze w demo bez poczty: zgłoszenie nie jest wysyłane ani zapisywane.
 */
function tsl_forms_demo_only() {
	return tsl_is_demo_mode() && ! tsl_mail_configured();
}

/**
 * Komunikat formularza w wersji demonstracyjnej.
 */
function tsl_demo_form_message() {
	return 'To wersja demonstracyjna serwisu — formularz działa, ale nie wysyła ani nie zapisuje danych.';
}

/**
 * Tryb produkcyjny: materiały przykładowe nie pojawiają się na stronie
 * (listy, wyszukiwarka, powiązania, pojedyncze adresy → 404).
 * Panel i WP-CLI widzą wszystko, żeby można było je usunąć.
 *
 * @param WP_Query $query Zapytanie.
 */
function tsl_hide_demo_objects( $query ) {
	if ( tsl_is_demo_mode() || is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
		return; // Edytor bloków w panelu.
	}
	$meta   = (array) $query->get( 'meta_query' );
	$meta[] = array(
		'key'     => '_tsl_demo',
		'compare' => 'NOT EXISTS',
	);
	$query->set( 'meta_query', $meta );
}
add_action( 'pre_get_posts', 'tsl_hide_demo_objects' );

/**
 * Czy kategoria (z podkategoriami) ma jakąkolwiek opublikowaną treść.
 *
 * @param WP_Term $term Kategoria.
 */
function tsl_term_has_content( $term ) {
	static $cache = array();
	if ( ! isset( $cache[ $term->term_id ] ) ) {
		$found                     = get_posts(
			array(
				'post_type'      => array_values( get_post_types( array( 'public' => true ) ) ),
				'post_status'    => 'publish',
				'cat'            => $term->term_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$cache[ $term->term_id ] = (bool) $found;
	}
	return $cache[ $term->term_id ];
}

/**
 * Status stron informacyjnych tworzonych przez wtyczkę.
 */
function tsl_placeholder_status() {
	return tsl_is_demo_mode() ? 'publish' : 'draft';
}

/**
 * Przypomnienie w panelu, że serwis jest w trybie demonstracyjnym.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! tsl_is_demo_mode() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'tools_page_tsl-demo' === $screen->id ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>TSL Manager działa w trybie demonstracyjnym.</strong> Materiały przykładowe są widoczne, a formularze bez skonfigurowanej poczty nie wysyłają danych. Przed startem serwisu wyłącz tryb w <a href="' . esc_url( admin_url( 'tools.php?page=tsl-demo' ) ) . '">Narzędzia → TSL Manager</a>.</p></div>';
	}
);

/**
 * Panel: oznaczenie materiałów przykładowych i stron z treścią zastępczą
 * na listach wpisów (np. „Artykuły — Demo”).
 */
add_filter(
	'display_post_states',
	function ( $states, $post ) {
		if ( get_post_meta( $post->ID, '_tsl_demo', true ) ) {
			$states['tsl_demo'] = 'Demo';
		}
		$label = (string) get_post_meta( $post->ID, 'tsl_label', true );
		if ( in_array( $label, array( 'partnerski', 'sponsorowany' ), true ) ) {
			$states['tsl_label'] = 'partnerski' === $label ? 'Materiał partnerski' : 'Materiał sponsorowany';
		}
		if ( 'page' === $post->post_type && false !== strpos( (string) $post->post_content, 'Do uzupełnienia' ) ) {
			$states['tsl_todo'] = 'Do uzupełnienia';
		}
		return $states;
	},
	10,
	2
);
