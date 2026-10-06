<?php
/**
 * Konfiguracja motywu, zasoby i zależność od wtyczki modelu treści.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

		// Te same rozmiary co w motywie 0.10.0 — istniejące miniatury w Mediach działają bez regeneracji.
		add_image_size( 'tsl-card', 720, 405, true );
		add_image_size( 'tsl-hero', 1280, 720, true );

		register_nav_menus(
			array(
				'footer-project' => 'Stopka — Projekt',
				'footer-legal'   => 'Stopka — dolna belka (dokumenty)',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$uri = get_template_directory_uri();
		wp_enqueue_style( 'tsl-main', $uri . '/assets/css/main.css', array(), TSL_THEME_VERSION );
		wp_enqueue_script( 'tsl-main', $uri . '/assets/js/main.js', array(), TSL_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);

/**
 * Wczytanie fontów z wyprzedzeniem — tylko kroje używane nad zgięciem strony.
 */
add_action(
	'wp_head',
	function () {
		$base = get_template_directory_uri() . '/assets/fonts/';
		foreach ( array( 'newsreader-latin-wght-normal.woff2', 'instrument-sans-latin-wght-normal.woff2' ) as $file ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . $file ) );
		}
	},
	1
);

/**
 * Bez wtyczki tsl-core motyw nie ma filarów ani typów treści — informujemy
 * administratora zamiast pokazywać pusty serwis.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! function_exists( 'tsl_get_pillars' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p><strong>TSL Manager Editorial:</strong> włącz wtyczkę „TSL Manager — model treści”, aby motyw działał poprawnie.</p></div>';
		}
	}
);

/**
 * Bez kluczowej wtyczki front-end pokazuje komunikat zamiast błędów PHP.
 */
add_action(
	'template_redirect',
	function () {
		if ( ! function_exists( 'tsl_get_pillars' ) ) {
			wp_die( 'Serwis jest w trakcie konfiguracji.', 'TSL Manager', array( 'response' => 503 ) );
		}
	},
	0
);

/**
 * Bez skryptu i stylów emoji WordPressa (zbędne zapytanie do zewnętrznego CDN;
 * przeglądarki wyświetlają emoji same).
 */
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
);

add_filter(
	'excerpt_length',
	function () {
		return 28;
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/**
 * Klasy body: strony działów (filarów) i stron z okładką.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_category() && function_exists( 'tsl_is_pillar' ) && tsl_is_pillar( get_queried_object() ) ) {
			$classes[] = 'is-pillar';
		}
		if ( is_singular( array( 'post', 'raport' ) ) ) {
			$classes[] = 'has-progress';
		}
		return $classes;
	}
);

/**
 * Tabele w treści: opakowanie z przewijaniem poziomym (telefon, tablet).
 * Blok tabeli ma już element <figure class="wp-block-table"> — dodajemy tylko
 * atrybuty dostępności, żeby przewijany obszar był osiągalny z klawiatury.
 */
add_filter(
	'render_block_core/table',
	function ( $html ) {
		return preg_replace( '/<figure class="wp-block-table([^"]*)"/', '<figure class="wp-block-table$1" tabindex="0" role="region" aria-label="Tabela"', $html, 1 );
	}
);
