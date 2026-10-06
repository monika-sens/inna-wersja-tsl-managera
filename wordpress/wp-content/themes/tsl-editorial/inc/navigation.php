<?php
/**
 * Nawigacja: pasek wydania, winieta, pasek działów z panelami działów,
 * wyszukiwarka i menu pełnoekranowe.
 *
 * Menu jest budowane z drzewa filarów (wtyczka tsl-core), więc zmiana nazwy,
 * kolejności lub widoczności podkategorii odbywa się w panelu Filary
 * i podkategorie — bez edycji menu.
 *
 * Panele działów otwierają się kliknięciem lub klawiaturą (bez hover).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Linki paska wydania nad winietą.
 *
 * @return array{left: array, right: array}
 */
function tsl_topbar_links() {
	return array(
		'left'  => array(
			array( get_post_type_archive_link( 'riskbrief' ), 'RiskBrief TSL', 'riskbrief' ),
			array( get_post_type_archive_link( 'raport' ), 'Raporty i dane', '' ),
			array( tsl_page_url( 'newsletter' ), 'Newsletter', '' ),
		),
		'right' => array(
			array( tsl_events_url(), 'Wydarzenia', '' ),
			array( get_post_type_archive_link( 'partner' ), 'Partnerzy', '' ),
			array( tsl_page_url( 'o-projekcie' ), 'O projekcie', '' ),
			array( tsl_page_url( 'kontakt' ), 'Kontakt', '' ),
		),
	);
}

/**
 * Pasek wydania: data, skróty do kolekcji i informacji o projekcie.
 */
function tsl_render_topbar() {
	$links = tsl_topbar_links();
	echo '<div class="edition-bar"><div class="wrap edition-inner">';
	echo '<p class="edition-date"><span class="edition-label">Wydanie online</span><span class="edition-day">' . esc_html( tsl_edition_date() ) . '</span></p>';
	echo '<ul class="edition-links">';
	foreach ( array_merge( $links['left'], $links['right'] ) as $link ) {
		$prefix = '';
		$suffix = '';
		$class  = '';
		if ( 'riskbrief' === $link[2] ) {
			$class  = ' class="is-signal"';
			$prefix = '<span class="signal-dot" aria-hidden="true"></span>';
			if ( tsl_has_new_riskbrief() ) {
				$suffix = ' <span class="badge-new">Nowy</span>';
			}
		}
		echo '<li' . $class . '><a href="' . esc_url( $link[0] ) . '">' . $prefix . esc_html( $link[1] ) . $suffix . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ul></div></div>';
}

/**
 * Pasek działów: sześć filarów (panele działów) + Narzędzia.
 *
 * Na szerokim ekranie nazwa działu jest przyciskiem otwierającym panel;
 * na tablecie i telefonie pasek jest przewijany poziomo, a nazwy działów
 * są zwykłymi linkami (szybki dostęp bez otwierania menu).
 */
function tsl_render_primary_nav() {
	$pillars = tsl_get_pillars();

	echo '<nav class="sections" aria-label="Działy serwisu"><div class="wrap sections-inner">';
	echo '<a class="sections-home" href="' . esc_url( home_url( '/' ) ) . '" tabindex="-1" aria-hidden="true">' . tsl_logo( 'compact' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<ul class="sections-list">';

	foreach ( $pillars as $i => $pillar ) {
		$panel_id = 'mega-' . $pillar->slug;
		$current  = tsl_is_current_pillar( $pillar ) ? ' is-current' : '';
		$num      = str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT );

		echo '<li class="section-item' . esc_attr( $current ) . '">';
		printf(
			'<button class="section-btn" type="button" aria-expanded="false" aria-controls="%1$s"><span class="section-num" aria-hidden="true">%2$s</span><span class="label-short">%3$s</span><span class="label-full">%4$s</span>%5$s</button>',
			esc_attr( $panel_id ),
			esc_html( $num ),
			esc_html( tsl_term_short_label( $pillar ) ),
			esc_html( $pillar->name ),
			tsl_icon( 'chevron' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		printf(
			'<a class="section-a" href="%1$s"%2$s>%3$s</a>',
			esc_url( get_term_link( $pillar ) ),
			$current ? ' aria-current="true"' : '',
			esc_html( tsl_term_short_label( $pillar ) )
		);
		tsl_render_mega_panel( $pillar, $panel_id, $num );
		echo '</li>';
	}

	printf(
		'<li class="section-item section-tools%3$s"><a class="section-tools-link" href="%1$s">%2$s</a></li>',
		esc_url( get_post_type_archive_link( 'narzedzie' ) ),
		'Narzędzia',
		( is_post_type_archive( 'narzedzie' ) || is_singular( 'narzedzie' ) || is_tax( 'tsl_typ_narzedzia' ) ) ? ' is-current' : ''
	);

	echo '</ul>';
	// Wyszukiwarka dostępna także po zwinięciu winiety (szeroki ekran).
	echo '<button class="mh-button search-toggle sections-search" type="button" aria-expanded="false" aria-controls="search-panel">' . tsl_icon( 'search' ) . '<span class="screen-reader-text">Szukaj</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div></nav>';
}

/**
 * Czy bieżąca strona należy do filaru.
 *
 * @param WP_Term $pillar Filar.
 */
function tsl_is_current_pillar( $pillar ) {
	if ( is_category() ) {
		$term = get_queried_object();
		return $term->term_id === $pillar->term_id || term_is_ancestor_of( $pillar, $term, 'category' );
	}
	if ( is_singular( tsl_pillar_post_types() ) ) {
		$current = tsl_get_pillar( get_queried_object() );
		return $current && $current->term_id === $pillar->term_id;
	}
	return false;
}

/**
 * Panel działu: obietnica, najważniejsze obszary, rodzaje transportu
 * i spedycji (dział Transport) albo dodatkowe obszary, polecany materiał.
 *
 * @param WP_Term $pillar   Filar.
 * @param string  $panel_id ID panelu.
 * @param string  $num      Numer działu.
 */
function tsl_render_mega_panel( $pillar, $panel_id, $num ) {
	$main  = tsl_get_subcategories( $pillar, true );
	$all   = tsl_get_subcategories( $pillar );
	$extra = array_udiff(
		$all,
		$main,
		function ( $a, $b ) {
			return $a->term_id <=> $b->term_id;
		}
	);

	echo '<div class="mega" id="' . esc_attr( $panel_id ) . '" hidden><div class="wrap mega-inner">';

	echo '<div class="mega-intro">';
	echo '<p class="mega-num">Dział ' . esc_html( $num ) . '</p>';
	echo '<p class="mega-title">' . esc_html( $pillar->name ) . '</p>';
	echo '<p class="mega-promise">' . esc_html( tsl_term_promise( $pillar ) ) . '</p>';
	echo '<a class="link-more" href="' . esc_url( get_term_link( $pillar ) ) . '">Cały dział' . tsl_icon( 'arrow' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	echo '<div class="mega-areas"><p class="mega-heading">Najważniejsze obszary</p><ol class="mega-list">';
	foreach ( $main as $term ) {
		echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
	}
	echo '</ol>';

	$branches = array_filter(
		$main,
		function ( $term ) {
			return in_array( $term->slug, array( 'transport', 'spedycja' ), true );
		}
	);
	if ( $branches ) {
		foreach ( $branches as $branch ) {
			echo '<p class="mega-heading">' . esc_html( $branch->name ) . '</p><ul class="mega-inline">';
			foreach ( tsl_get_subcategories( $branch ) as $term ) {
				echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
			}
			echo '</ul>';
		}
	} elseif ( $extra ) {
		echo '<p class="mega-heading">Więcej obszarów</p><ul class="mega-inline mega-muted">';
		foreach ( $extra as $term ) {
			echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
		}
		echo '</ul>';
	}
	echo '</div>';

	$featured = tsl_pillar_featured( $pillar );
	echo '<div class="mega-featured">';
	if ( $featured ) {
		echo '<p class="mega-heading">Polecamy</p>';
		tsl_card( $featured, 'medium', array( 'heading' => 'p', 'sizes' => '360px' ) );
	}
	echo '</div>';

	echo '</div></div>';
}

/**
 * Menu pełnoekranowe (tablet i telefon): wyszukiwarka, skróty, sześć
 * działów z obszarami w akordeonie, linki dodatkowe.
 */
function tsl_render_mobile_nav() {
	echo '<div class="mobile-nav" id="mobile-nav" hidden>';
	echo '<div class="mobile-nav-inner wrap">';

	echo '<div class="mobile-search">';
	get_search_form();
	echo '</div>';

	echo '<ul class="mobile-shortcuts">';
	$shortcuts = array(
		array( get_post_type_archive_link( 'riskbrief' ), 'RiskBrief TSL' ),
		array( get_post_type_archive_link( 'narzedzie' ), 'Narzędzia' ),
		array( tsl_events_url(), 'Wydarzenia' ),
		array( tsl_page_url( 'newsletter' ), 'Newsletter' ),
	);
	foreach ( $shortcuts as $s ) {
		echo '<li><a href="' . esc_url( $s[0] ) . '">' . esc_html( $s[1] ) . tsl_icon( 'arrow' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ul>';

	echo '<p class="mobile-heading">Działy</p><ol class="mobile-pillars">';
	foreach ( tsl_get_pillars() as $i => $pillar ) {
		$id = 'acc-' . $pillar->slug;
		echo '<li class="mobile-pillar">';
		printf(
			'<button type="button" class="accordion-toggle" aria-expanded="false" aria-controls="%1$s"><span class="acc-num" aria-hidden="true">%2$s</span><span class="acc-name">%3$s</span>%4$s</button>',
			esc_attr( $id ),
			esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ),
			esc_html( $pillar->name ),
			tsl_icon( 'plus' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '<ul class="accordion-panel" id="' . esc_attr( $id ) . '" hidden>';
		echo '<li><a class="all-link" href="' . esc_url( get_term_link( $pillar ) ) . '">Wszystkie treści działu</a></li>';
		foreach ( tsl_get_subcategories( $pillar ) as $term ) {
			echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
		}
		echo '</ul></li>';
	}
	echo '</ol>';

	echo '<ul class="mobile-secondary">';
	$links = tsl_topbar_links();
	$extra = array( array( tsl_page_url( 'wspolpraca-partnerska' ), 'Współpraca partnerska', '' ) );
	foreach ( array_merge( array_slice( $links['left'], 1 ), $links['right'], $extra ) as $link ) {
		echo '<li><a href="' . esc_url( $link[0] ) . '">' . esc_html( $link[1] ) . '</a></li>';
	}
	echo '</ul>';

	echo '</div></div>';
}
