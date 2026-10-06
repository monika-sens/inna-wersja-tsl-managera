<?php
/**
 * Funkcje szablonów: znak słowny, etykiety, metadane, breadcrumbs,
 * otwarcia sekcji, materiały („story”), ikony.
 *
 * Nazwy funkcji pomocniczych są takie same jak w motywie 0.10.0
 * (tsl_kind_label, tsl_post_meta, tsl_card…), dzięki czemu motywy są
 * wymienne na tej samej bazie, a części szablonów są czytelne dla zespołu.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ikona SVG (dekoracyjna — ukryta przed czytnikami ekranu).
 * Motyw używa ikon oszczędnie: strzałki i przyciski funkcyjne.
 *
 * @param string $name Nazwa ikony.
 */
function tsl_icon( $name ) {
	$paths = array(
		'search'   => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.8-4.8"/>',
		'menu'     => '<path d="M3 7h18M3 12h18M3 17h18"/>',
		'close'    => '<path d="M5 5l14 14M19 5 5 19"/>',
		'arrow'    => '<path d="M4 12h15M13.5 6.5 19 12l-5.5 5.5"/>',
		'arrow-up' => '<path d="M7 17 17 7M8 7h9v9"/>',
		'chevron'  => '<path d="m7 10 5 5 5-5"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'print'    => '<path d="M6 9V3h12v6"/><path d="M6 18H3V9h18v9h-3"/><path d="M6 14h12v7H6z"/>',
		'download' => '<path d="M12 4v11M7 10.5 12 15.5l5-5M4 20h16"/>',
		'calendar' => '<path d="M3 5h18v16H3zM3 10h18M8 3v4M16 3v4"/>',
		'tool'     => '<path d="M4 20 13 11M14.5 3.5a4 4 0 0 0 5 5l-3 3-5-5z"/>',
		'chart'    => '<path d="M4 20V11M10 20V5M16 20v-6M2 20h20"/>',
		'alert'    => '<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>',
		'mail'     => '<path d="M3 5h18v14H3z"/><path d="m3 6 9 7 9-7"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon icon-' . esc_attr( $name ) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="square" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Znak słowny TSL Manager (tekst, nie grafika — czytelny dla czytników ekranu
 * i ostry w każdej skali). Logo robocze do czasu wyboru finalnego znaku.
 *
 * @param string $context Miejsce użycia: header | compact | footer.
 */
function tsl_logo( $context = 'header' ) {
	return '<span class="wordmark wordmark-' . esc_attr( sanitize_key( $context ) ) . '"><span class="wm-tsl">TSL</span><span class="wm-manager">Manager</span></span>';
}

/**
 * Etykieta rodzaju materiału: format artykułu, typ narzędzia, „RiskBrief”…
 *
 * @param WP_Post|int|null $post Materiał.
 */
function tsl_kind_label( $post = null ) {
	$post = get_post( $post );
	$map  = array(
		'riskbrief'    => 'RiskBrief TSL',
		'wydarzenie'   => 'tsl_typ_wydarzenia',
		'tribe_events' => 'tsl_typ_wydarzenia',
		'raport'       => 'tsl_typ_raportu',
		'narzedzie'    => 'tsl_typ_narzedzia',
		'post'         => 'tsl_format',
		'ekspert'      => 'Ekspert',
		'partner'      => 'tsl_poziom_partnerstwa',
	);
	$key = $map[ $post->post_type ] ?? '';

	if ( str_starts_with( $key, 'tsl_' ) ) {
		$terms = get_the_terms( $post, $key );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return $terms[0]->name;
		}
		$object = get_post_type_object( $post->post_type );
		return $object ? $object->labels->singular_name : '';
	}
	return $key;
}

/**
 * Szacowany czas czytania w minutach.
 *
 * @param WP_Post|int|null $post Materiał.
 */
function tsl_reading_time( $post = null ) {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post ) ), 0, 'ąćęłńóśźżĄĆĘŁŃÓŚŹŻ' );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Poziom ryzyka RiskBriefu.
 *
 * @param WP_Post|int|null $post Materiał.
 * @return WP_Term|null
 */
function tsl_risk_level( $post = null ) {
	$terms = get_the_terms( get_post( $post ), 'tsl_poziom_ryzyka' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Oznaczenie poziomu ryzyka: znacznik + słowo (nigdy sam kolor).
 *
 * @param WP_Post|int|null $post Materiał.
 */
function tsl_risk_badge( $post = null ) {
	$level = tsl_risk_level( $post );
	if ( ! $level ) {
		return '';
	}
	return '<span class="risk risk-' . esc_attr( $level->slug ) . '"><span class="risk-mark" aria-hidden="true"></span>' . esc_html( $level->name ) . '</span>';
}

/**
 * Status sygnału RiskBrief (Nowe, Aktualizacja…).
 *
 * @param WP_Post $post Materiał.
 */
function tsl_status_badge( $post ) {
	if ( ! function_exists( 'tsl_field_label' ) || ! tsl_field_label( 'tsl_status', $post ) ) {
		return '';
	}
	return '<span class="status status-' . esc_attr( tsl_field( 'tsl_status', $post ) ) . '">' . esc_html( tsl_field_label( 'tsl_status', $post ) ) . '</span>';
}

/**
 * Metadane materiału: autor, data / termin wydarzenia / rola osoby,
 * aktualizacja, czas czytania.
 *
 * @param WP_Post|int|null $post Materiał.
 * @param bool             $full Pełna wersja (z autorem i aktualizacją).
 */
function tsl_post_meta( $post = null, $full = false ) {
	$post  = get_post( $post );
	$parts = array();

	$author = $full ? get_the_author_meta( 'display_name', $post->post_author ) : '';
	if ( $author ) {
		$parts[] = '<span class="meta-author">' . esc_html( $author ) . '</span>';
	}
	$start = tsl_event_post_type() === $post->post_type ? tsl_event_start( $post ) : '';
	if ( $start ) {
		$parts[] = '<time datetime="' . esc_attr( $start ) . '">' . esc_html( tsl_event_when( $post ) ) . '</time>';
		if ( tsl_event_is_past( $post ) ) {
			$parts[] = '<span>Zakończone</span>';
		}
	} elseif ( in_array( $post->post_type, array( 'ekspert', 'partner' ), true ) ) {
		$role = trim( implode( ', ', array_filter( array( tsl_field( 'tsl_position', $post ), tsl_field( 'tsl_organization', $post ), tsl_field( 'tsl_area', $post ) ) ) ) );
		if ( $role ) {
			$parts[] = '<span>' . esc_html( $role ) . '</span>';
		}
	} else {
		$parts[] = '<time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( '', $post ) ) . '</time>';
	}

	if ( $full && get_the_modified_date( 'Y-m-d', $post ) !== get_the_date( 'Y-m-d', $post ) ) {
		$parts[] = '<span>Aktualizacja: <time datetime="' . esc_attr( get_the_modified_date( 'c', $post ) ) . '">' . esc_html( get_the_modified_date( '', $post ) ) . '</time></span>';
	}
	if ( in_array( $post->post_type, array( 'post', 'raport' ), true ) ) {
		$parts[] = '<span>' . esc_html( tsl_reading_time( $post ) ) . ' min czytania</span>';
	}
	if ( ! $parts ) {
		return '';
	}
	return '<p class="post-meta">' . implode( '<span class="sep" aria-hidden="true">/</span>', $parts ) . '</p>';
}

/**
 * Numer działu (filaru) w kolejności serwisu: „01”…„06”.
 *
 * @param WP_Term|null $pillar Filar.
 */
function tsl_pillar_number( $pillar ) {
	if ( ! $pillar || ! function_exists( 'tsl_get_pillars' ) ) {
		return '';
	}
	foreach ( tsl_get_pillars() as $i => $term ) {
		if ( $term->term_id === $pillar->term_id ) {
			return str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT );
		}
	}
	return '';
}

/**
 * Nazwa filaru i link (etykieta nad tytułem).
 *
 * @param WP_Post|int|null $post Materiał.
 */
function tsl_pillar_label( $post = null ) {
	$pillar = function_exists( 'tsl_get_pillar' ) ? tsl_get_pillar( $post ) : null;
	if ( ! $pillar ) {
		return '';
	}
	return '<a class="pillar-label" href="' . esc_url( get_term_link( $pillar ) ) . '">' . esc_html( $pillar->name ) . '</a>';
}

/**
 * Oznaczenie materiału partnerskiego / sponsorowanego na liście (zasady redakcyjne).
 *
 * @param WP_Post $post Materiał.
 */
function tsl_sponsor_label( $post ) {
	if ( ! function_exists( 'tsl_field' ) || ! tsl_field( 'tsl_label', $post ) ) {
		return '';
	}
	return '<span class="sponsor-badge">' . esc_html( tsl_field_label( 'tsl_label', $post ) ) . '</span>';
}

/**
 * Breadcrumbs (pełne nazwy filarów).
 */
function tsl_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	$items = array( array( home_url( '/' ), 'Strona główna' ) );

	$add_terms = function ( $term ) use ( &$items ) {
		foreach ( array_reverse( get_ancestors( $term->term_id, 'category', 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'category' );
			$items[]  = array( get_term_link( $ancestor ), $ancestor->name );
		}
		$items[] = array( get_term_link( $term ), $term->name );
	};

	if ( is_category() ) {
		$add_terms( get_queried_object() );
		$items[ count( $items ) - 1 ][0] = '';
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( 'post' !== $post->post_type && 'page' !== $post->post_type ) {
			$items[] = array( get_post_type_archive_link( $post->post_type ), get_post_type_object( $post->post_type )->labels->name );
			if ( 'narzedzie' === $post->post_type ) {
				$types = get_the_terms( $post, 'tsl_typ_narzedzia' );
				if ( $types && ! is_wp_error( $types ) ) {
					$items[] = array( get_term_link( $types[0] ), $types[0]->name );
				}
			}
		} elseif ( 'post' === $post->post_type ) {
			$term = function_exists( 'tsl_get_primary_term' ) ? tsl_get_primary_term( $post ) : null;
			if ( $term ) {
				$add_terms( $term );
			}
		}
		$items[] = array( '', get_the_title( $post ) );
	} elseif ( is_post_type_archive() ) {
		$items[] = array( '', post_type_archive_title( '', false ) );
	} elseif ( is_tax( 'tsl_typ_narzedzia' ) ) {
		$items[] = array( get_post_type_archive_link( 'narzedzie' ), 'Narzędzia' );
		$items[] = array( '', single_term_title( '', false ) );
	} elseif ( is_search() ) {
		$items[] = array( '', 'Wyniki wyszukiwania' );
	} else {
		return;
	}

	echo '<nav class="crumbs" aria-label="Ścieżka nawigacji"><ol>';
	foreach ( $items as $item ) {
		if ( $item[0] ) {
			echo '<li><a href="' . esc_url( $item[0] ) . '">' . esc_html( $item[1] ) . '</a></li>';
		} else {
			echo '<li aria-current="page">' . esc_html( $item[1] ) . '</li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * Otwarcie sekcji: numer, tytuł, opis i jeden link „więcej”.
 *
 * @param string $title  Tytuł.
 * @param string $url    Link.
 * @param string $label  Etykieta linku.
 * @param string $kicker Opis pod tytułem (krótko: zakres sekcji).
 * @param string $num    Numer sekcji (np. „03”); pusty — bez numeru.
 * @param string $id     Id tytułu (aria-labelledby sekcji).
 */
function tsl_section_header( $title, $url = '', $label = '', $kicker = '', $num = '', $id = '' ) {
	echo '<header class="opener">';
	if ( $num ) {
		echo '<span class="opener-num" aria-hidden="true">' . esc_html( $num ) . '</span>';
	}
	echo '<div class="opener-text">';
	echo '<h2 class="opener-title"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . esc_html( $title ) . '</h2>';
	if ( $kicker ) {
		echo '<p class="opener-kicker">' . esc_html( $kicker ) . '</p>';
	}
	echo '</div>';
	if ( $url ) {
		echo '<a class="link-more" href="' . esc_url( $url ) . '">' . esc_html( $label ) . tsl_icon( 'arrow' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</header>';
}

/**
 * Komunikat pustego działu: „Sekcja w przygotowaniu” + zakres z opisu kategorii.
 *
 * @param string $scope    Zakres działu (opis kategorii); pusty = bez zdania o zakresie.
 * @param bool   $filtered Lista jest pusta przez filtr (format), a nie dlatego, że dział jest pusty.
 */
function tsl_empty_state( $scope = '', $filtered = false ) {
	echo '<div class="empty-state">';
	if ( $filtered ) {
		echo '<p class="empty-state-title">Brak materiałów w wybranym formacie</p>';
		echo '<p>Wybierz „Wszystkie”, aby zobaczyć pozostałe materiały działu.</p>';
	} else {
		echo '<p class="empty-state-title">Sekcja w przygotowaniu</p>';
		if ( '' !== trim( $scope ) ) {
			echo '<p>Zakres działu: ' . esc_html( rtrim( trim( $scope ), '.' ) ) . '.</p>';
		}
	}
	echo '</div>';
}

/**
 * Materiał na liście („story”).
 *
 * Warianty:
 *  - lead     duży materiał ze zdjęciem (wyróżnienie działu, dossier),
 *  - medium   zdjęcie + tytuł + zajawka,
 *  - text     bez zdjęcia: tytuł + zajawka,
 *  - compact  sam tytuł z etykietą (mega menu, listy boczne),
 *  - river    wiersz listy w działach i wynikach: data, tytuł, zajawka, miniatura.
 * Warianty motywu 0.10.0 są mapowane: default → medium, feature → lead, row → river.
 *
 * @param WP_Post $post    Materiał.
 * @param string  $variant Wariant.
 * @param array   $extra   Dodatkowe argumenty (heading — poziom nagłówka, num — numer porządkowy).
 */
function tsl_card( $post, $variant = 'medium', $extra = array() ) {
	$aliases = array(
		'default' => 'medium',
		'feature' => 'lead',
		'row'     => 'river',
	);
	get_template_part(
		'template-parts/story',
		null,
		array_merge(
			$extra,
			array(
				'post'    => $post,
				'variant' => $aliases[ $variant ] ?? $variant,
			)
		)
	);
}

/**
 * Link do strony po ścieżce (strony informacyjne tworzone przez seeder).
 *
 * @param string $path Ścieżka strony.
 */
function tsl_page_url( $path ) {
	$page = get_page_by_path( $path );
	return $page ? get_permalink( $page ) : home_url( '/' . $path . '/' );
}

/**
 * Czy jest RiskBrief z ostatnich 7 dni (etykieta „Nowy” w pasku nad winietą).
 */
function tsl_has_new_riskbrief() {
	$posts = tsl_posts(
		array(
			'post_type'      => 'riskbrief',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => '7 days ago' ) ),
		)
	);
	return (bool) $posts;
}

/**
 * Zdjęcie materiału, a gdy go brak — typograficzna plansza z numerem
 * i nazwą działu, żeby moduły ze zdjęciem nigdy nie były puste.
 *
 * @param WP_Post|int|null $post    Materiał.
 * @param string           $size    Rozmiar obrazka.
 * @param string           $loading lazy | eager.
 * @param string           $sizes   Atrybut sizes.
 */
function tsl_media( $post = null, $size = 'tsl-card', $loading = 'lazy', $sizes = '' ) {
	$post = get_post( $post );
	if ( has_post_thumbnail( $post ) ) {
		$attr = array(
			'loading'       => $loading,
			'fetchpriority' => 'eager' === $loading ? 'high' : 'auto',
		);
		if ( $sizes ) {
			$attr['sizes'] = $sizes;
		}
		return get_the_post_thumbnail( $post, $size, $attr );
	}

	$pillar = function_exists( 'tsl_get_pillar' ) ? tsl_get_pillar( $post ) : null;
	$label  = $pillar ? $pillar->name : tsl_kind_label( $post );
	$num    = $pillar ? tsl_pillar_number( $pillar ) : '';
	return '<span class="media-plate" aria-hidden="true"><span class="plate-num">' . esc_html( $num ? $num : '—' ) . '</span><span class="plate-name">' . esc_html( $label ) . '</span></span>';
}

/**
 * Dzisiejsza data wydania w winiecie, np. „wtorek, 6 października 2026”.
 */
function tsl_edition_date() {
	return wp_date( 'l, j F Y' );
}
