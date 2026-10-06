<?php
/**
 * Wydarzenia: wspólny interfejs dla dwóch silników.
 *
 *  - The Events Calendar (TEC), gdy wtyczka jest włączona — kalendarz,
 *    widoki miesiąc/lista/dzień, eksport do kalendarzy (iCal, Google),
 *    miejsca wydarzeń. Typ treści: tribe_events.
 *  - Moduł wbudowany (typ treści `wydarzenie`) — gdy TEC nie ma.
 *
 * Reszta serwisu (filary, prelegenci, partnerzy, rejestracja, materiały
 * po wydarzeniu, strona główna) korzysta wyłącznie z funkcji z tego pliku,
 * więc działa z każdym z silników.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Czy wydarzenia obsługuje The Events Calendar.
 */
function tsl_events_tec() {
	return class_exists( 'Tribe__Events__Main' );
}

/**
 * Typ treści wydarzeń.
 */
function tsl_event_post_type() {
	return tsl_events_tec() ? 'tribe_events' : 'wydarzenie';
}

/**
 * Zamienia umowną nazwę „wydarzenie” na faktyczny typ treści.
 *
 * @param string $type Typ treści.
 */
function tsl_map_event_type( $type ) {
	return 'wydarzenie' === $type ? tsl_event_post_type() : $type;
}

/**
 * Nazwa typu w kluczach powiązań — stała niezależnie od silnika
 * (tsl_rel_wydarzenie), więc zmiana silnika nie gubi powiązań.
 *
 * @param string $type Typ treści.
 */
function tsl_rel_key( $type ) {
	return 'tribe_events' === $type ? 'wydarzenie' : $type;
}

/**
 * Data i godzina w formacie Y-m-d\TH:i (czas serwisu).
 *
 * @param string $value Data z bazy.
 */
function tsl_event_normalize( $value ) {
	return $value ? str_replace( ' ', 'T', substr( (string) $value, 0, 16 ) ) : '';
}

/**
 * Początek wydarzenia.
 *
 * @param int|WP_Post|null $post Wydarzenie.
 */
function tsl_event_start( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	return 'tribe_events' === $post->post_type
		? tsl_event_normalize( get_post_meta( $post->ID, '_EventStartDate', true ) )
		: (string) get_post_meta( $post->ID, 'tsl_start', true );
}

/**
 * Koniec wydarzenia.
 *
 * @param int|WP_Post|null $post Wydarzenie.
 */
function tsl_event_end( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	return 'tribe_events' === $post->post_type
		? tsl_event_normalize( get_post_meta( $post->ID, '_EventEndDate', true ) )
		: (string) get_post_meta( $post->ID, 'tsl_end', true );
}

/**
 * Bieżący czas w formacie dat wydarzeń.
 */
function tsl_now() {
	return current_time( 'Y-m-d\TH:i' );
}

/**
 * Czy wydarzenie już się odbyło.
 *
 * @param int|WP_Post|null $post Wydarzenie.
 */
function tsl_event_is_past( $post = null ) {
	$end = tsl_event_end( $post ) ? tsl_event_end( $post ) : tsl_event_start( $post );
	return $end && $end < tsl_now();
}

/**
 * Termin czytelnym tekstem, np. „23 października 2026, 10:00–11:30”.
 *
 * @param int|WP_Post|null $post Wydarzenie.
 */
function tsl_event_when( $post = null ) {
	$start = tsl_event_start( $post );
	$end   = tsl_event_end( $post );
	if ( ! $start ) {
		return '';
	}
	$tz     = wp_timezone();
	$s      = date_create_immutable( $start, $tz );
	$e      = $end ? date_create_immutable( $end, $tz ) : null;
	$format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
	$text   = wp_date( $format, $s->getTimestamp() );
	if ( $e && $e > $s ) {
		$same  = $s->format( 'Y-m-d' ) === $e->format( 'Y-m-d' );
		$text .= '–' . wp_date( $same ? get_option( 'time_format' ) : $format, $e->getTimestamp() );
	}
	return $text;
}

/**
 * Miejsce: forma (online / stacjonarnie / hybrydowo) i adres lub miejsce z TEC.
 *
 * @param int|WP_Post|null $post Wydarzenie.
 */
function tsl_event_where( $post = null ) {
	$post  = get_post( $post );
	$parts = array( tsl_field_label( 'tsl_mode', $post ) );
	if ( 'tribe_events' === $post->post_type && function_exists( 'tribe_get_venue' ) ) {
		$parts[] = trim( implode( ', ', array_filter( array( tribe_get_venue( $post->ID ), tribe_get_city( $post->ID ) ) ) ) );
	} else {
		$parts[] = (string) tsl_field( 'tsl_location', $post );
	}
	return implode( ' · ', array_filter( $parts ) );
}

/**
 * Adres kalendarza wydarzeń.
 *
 * @param string $status '' | zakonczone.
 */
function tsl_events_url( $status = '' ) {
	if ( tsl_events_tec() && function_exists( 'tribe_get_events_link' ) ) {
		return 'zakonczone' === $status ? add_query_arg( 'eventDisplay', 'past', tribe_get_listview_link() ) : tribe_get_events_link();
	}
	$url = get_post_type_archive_link( 'wydarzenie' );
	return 'zakonczone' === $status ? add_query_arg( 'status-wydarzenia', 'zakonczone', $url ) : $url;
}

/**
 * Wydarzenia nadchodzące lub zakończone.
 *
 * @param string $status nadchodzace | zakonczone.
 * @param int    $limit  Liczba.
 * @return WP_Post[]
 */
function tsl_get_events( $status = 'nadchodzace', $limit = 3 ) {
	$past = 'zakonczone' === $status;

	if ( tsl_events_tec() && function_exists( 'tribe_get_events' ) ) {
		$args = array(
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
		);
		if ( $past ) {
			$args['end_date']     = current_time( 'mysql' );
			$args['eventDisplay'] = 'past';
			$args['order']        = 'DESC';
		} else {
			$args['ends_after'] = 'now';
		}
		return (array) tribe_get_events( $args );
	}

	$query = new WP_Query(
		array_merge(
			array(
				'post_type'           => 'wydarzenie',
				'posts_per_page'      => $limit,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			),
			tsl_event_query_args( $status )
		)
	);
	return $query->posts;
}

/**
 * Argumenty zapytania wbudowanego modułu (nadchodzące / zakończone).
 *
 * @param string $status nadchodzace | zakonczone.
 * @return array
 */
function tsl_event_query_args( $status = 'nadchodzace' ) {
	$past = 'zakonczone' === $status;
	return array(
		'meta_key'   => 'tsl_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
		'orderby'    => 'meta_value',
		'order'      => $past ? 'DESC' : 'ASC',
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
			array(
				'key'     => 'tsl_start',
				'value'   => tsl_now(),
				'compare' => $past ? '<' : '>=',
			),
		),
	);
}

/*
 * Integracja z The Events Calendar.
 */

/**
 * Strona pojedynczego wydarzenia korzysta z szablonu serwisu (single.php):
 * filar, agenda, prelegenci, rejestracja, materiały po wydarzeniu.
 * Widoki kalendarza (miesiąc, lista, dzień) pozostają widokami TEC.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( tsl_events_tec() && is_singular( 'tribe_events' ) ) {
			$single = locate_template( 'single.php' );
			return $single ? $single : $template;
		}
		return $template;
	},
	PHP_INT_MAX
);

/**
 * Ustawienia TEC zgodne z mapą serwisu (adresy, widoki, brak komentarzy).
 * Wywoływane przez seeder, gdy TEC jest aktywny.
 */
function tsl_events_configure_tec() {
	if ( ! tsl_events_tec() || ! function_exists( 'tribe_update_option' ) ) {
		return;
	}
	tribe_update_option( 'eventsSlug', 'wydarzenia' );
	tribe_update_option( 'singleEventSlug', 'wydarzenie' );
	tribe_update_option( 'tribeEnableViews', array( 'list', 'month', 'day' ) );
	tribe_update_option( 'viewOption', 'list' );
	tribe_update_option( 'showComments', false );
	tribe_update_option( 'tribeEventsTemplate', '' );
	tribe_update_option( 'stylesheetOption', 'tribe' );
	update_option( 'tsl_flush_rewrite', 1, false );
}

/**
 * Miejsce wydarzenia w TEC (tworzy miejsce o tej nazwie, jeśli go nie ma).
 *
 * @param int    $event_id Wydarzenie.
 * @param string $name     Nazwa miejsca.
 */
function tsl_events_set_venue( $event_id, $name ) {
	if ( ! function_exists( 'tribe_create_venue' ) ) {
		return;
	}
	$existing = get_posts(
		array(
			'post_type'      => 'tribe_venue',
			'title'          => $name,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$venue_id = $existing ? (int) $existing[0] : (int) tribe_create_venue( array( 'Venue' => $name ) );
	if ( $venue_id ) {
		update_post_meta( $event_id, '_EventVenueID', $venue_id );
	}
}

/**
 * WP-CLI: przeniesienie wydarzeń z modułu wbudowanego do TEC.
 *
 * @return int Liczba przeniesionych wydarzeń.
 */
function tsl_events_migrate_to_tec() {
	global $wpdb;
	if ( ! tsl_events_tec() ) {
		return 0;
	}
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wydarzenie'" ); // phpcs:ignore WordPress.DB
	foreach ( $ids as $id ) {
		$start = get_post_meta( $id, 'tsl_start', true );
		$end   = get_post_meta( $id, 'tsl_end', true );
		set_post_type( $id, 'tribe_events' );
		if ( $start && class_exists( 'Tribe__Events__API' ) ) {
			$s = date_create_immutable( $start, wp_timezone() );
			$e = $end ? date_create_immutable( $end, wp_timezone() ) : $s->modify( '+1 hour' );
			$args = array(
				'EventStartDate'     => $s->format( 'Y-m-d' ),
				'EventStartHour'     => $s->format( 'H' ),
				'EventStartMinute'   => $s->format( 'i' ),
				'EventEndDate'       => $e->format( 'Y-m-d' ),
				'EventEndHour'       => $e->format( 'H' ),
				'EventEndMinute'     => $e->format( 'i' ),
				'EventTimezone'      => wp_timezone_string(),
			);
			Tribe__Events__API::saveEventMeta( $id, $args, get_post( $id ) );
			$location = get_post_meta( $id, 'tsl_location', true );
			if ( $location && 'online' !== get_post_meta( $id, 'tsl_mode', true ) ) {
				tsl_events_set_venue( $id, $location );
			}
		}
	}
	return count( $ids );
}
