<?php
/**
 * Archiwa kolekcji (rozdz. 7 i 17): wydarzenia nadchodzące / zakończone,
 * filtr statusu RiskBriefu, kolejność ekspertów i partnerów.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'status-wydarzenia';
		$vars[] = 'status-sygnalu';
		return $vars;
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! tsl_events_tec() && $query->is_post_type_archive( 'wydarzenie' ) ) {
			$status = $query->get( 'status-wydarzenia' ) === 'zakonczone' ? 'zakonczone' : 'nadchodzace';
			foreach ( tsl_event_query_args( $status ) as $key => $value ) {
				$query->set( $key, $value );
			}
		}

		if ( $query->is_post_type_archive( 'riskbrief' ) && $query->get( 'status-sygnalu' ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => 'tsl_status',
						'value' => sanitize_key( $query->get( 'status-sygnalu' ) ),
					),
				)
			);
		}

		if ( $query->is_post_type_archive( array( 'ekspert', 'partner' ) ) ) {
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			$query->set( 'posts_per_page', 48 );
		}
	}
);
