<?php
/**
 * Newsletter: kolejka wysyłki wydań.
 *
 * Wysyłka idzie partiami w tle (WP-Cron co 5 minut), z limitem wiadomości
 * na godzinę ustawionym w panelu — tak, by nie przekroczyć limitu serwera
 * pocztowego (np. home.pl). Postęp zapisuje się w metadanych wydania, więc
 * przerwana wysyłka wznawia się od miejsca, w którym stanęła.
 *
 * Odbiorcy są ustalani w chwili rozpoczęcia wysyłki: osoby zapisane później
 * nie dostaną tego wydania, a osoby, które w trakcie się wypiszą — tak.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'cron_schedules', // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval
	function ( $schedules ) {
		$schedules['tsl_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'Co 5 minut (newsletter TSL)',
		);
		return $schedules;
	}
);

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'tsl_nl_process' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'tsl_five_minutes', 'tsl_nl_process' );
		}
	}
);
add_action( 'tsl_nl_process', 'tsl_nl_process' );

/**
 * Stan wysyłki wydania.
 *
 * @param int $issue_id Wydanie.
 * @return array
 */
function tsl_nl_issue_status( $issue_id ) {
	$counts = tsl_nl_counts();
	return array(
		'status'     => get_post_meta( $issue_id, 'tsl_nl_status', true ) ? get_post_meta( $issue_id, 'tsl_nl_status', true ) : 'draft',
		'sent'       => (int) get_post_meta( $issue_id, 'tsl_nl_sent', true ),
		'failed'     => (int) get_post_meta( $issue_id, 'tsl_nl_failed', true ),
		'total'      => (int) get_post_meta( $issue_id, 'tsl_nl_total', true ),
		'started'    => (string) get_post_meta( $issue_id, 'tsl_nl_started', true ),
		'finished'   => (string) get_post_meta( $issue_id, 'tsl_nl_finished', true ),
		'audience'   => $counts['active'],
		'hourLimit'  => (int) tsl_nl_setting( 'hourly_limit' ),
		'published'  => 'publish' === get_post_status( $issue_id ),
		'smtpActive' => (bool) tsl_nl_setting( 'smtp_enabled' ),
		'lastError'  => (string) get_post_meta( $issue_id, 'tsl_nl_last_error', true ),
	);
}

/**
 * Rozpoczyna wysyłkę wydania do aktywnych subskrybentów.
 *
 * @param int $issue_id Wydanie.
 * @return true|WP_Error
 */
function tsl_nl_queue_issue( $issue_id ) {
	global $wpdb;
	$issue = get_post( $issue_id );

	if ( ! $issue || 'wydanie' !== $issue->post_type ) {
		return new WP_Error( 'tsl_nl_issue', 'Nie znaleziono wydania.' );
	}
	if ( 'publish' !== $issue->post_status ) {
		return new WP_Error( 'tsl_nl_unpublished', 'Najpierw opublikuj wydanie — wersja w przeglądarce musi być dostępna dla odbiorców.' );
	}
	if ( in_array( get_post_meta( $issue_id, 'tsl_nl_status', true ), array( 'sending', 'sent' ), true ) ) {
		return new WP_Error( 'tsl_nl_duplicate', 'To wydanie jest już wysyłane lub zostało wysłane.' );
	}

	$max_id = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . tsl_nl_table() . " WHERE status = 'active'" ); // phpcs:ignore WordPress.DB
	$total  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . tsl_nl_table() . " WHERE status = 'active' AND id <= %d", $max_id ) ); // phpcs:ignore WordPress.DB
	if ( ! $total ) {
		return new WP_Error( 'tsl_nl_empty', 'Brak aktywnych subskrybentów.' );
	}

	update_post_meta( $issue_id, 'tsl_nl_status', 'sending' );
	update_post_meta( $issue_id, 'tsl_nl_max_id', $max_id );
	update_post_meta( $issue_id, 'tsl_nl_cursor', 0 );
	update_post_meta( $issue_id, 'tsl_nl_sent', 0 );
	update_post_meta( $issue_id, 'tsl_nl_failed', 0 );
	update_post_meta( $issue_id, 'tsl_nl_total', $total );
	update_post_meta( $issue_id, 'tsl_nl_started', current_time( 'mysql' ) );
	// Treść zamrożona w chwili startu — wszyscy dostają to samo wydanie.
	update_post_meta( $issue_id, 'tsl_nl_body', wp_slash( tsl_nl_issue_body( $issue ) ) );

	wp_schedule_single_event( time(), 'tsl_nl_process' );
	spawn_cron();
	return true;
}

/**
 * Wysyła kolejną partię wszystkich trwających wydań.
 */
function tsl_nl_process() {
	if ( get_transient( 'tsl_nl_lock' ) ) {
		return;
	}
	set_transient( 'tsl_nl_lock', 1, 4 * MINUTE_IN_SECONDS );

	global $wpdb;
	$deadline = time() + 45;
	$batch    = max( 1, (int) ceil( tsl_nl_setting( 'hourly_limit' ) / 12 ) );

	$issues = get_posts(
		array(
			'post_type'      => 'wydanie',
			'post_status'    => 'any',
			'posts_per_page' => 5,
			'meta_key'       => 'tsl_nl_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'meta_value'     => 'sending', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
		)
	);

	foreach ( $issues as $issue ) {
		$limit = min( $batch, tsl_nl_hour_remaining() );
		if ( $limit < 1 || time() > $deadline ) {
			break;
		}

		$cursor = (int) get_post_meta( $issue->ID, 'tsl_nl_cursor', true );
		$max_id = (int) get_post_meta( $issue->ID, 'tsl_nl_max_id', true );
		$body   = (string) get_post_meta( $issue->ID, 'tsl_nl_body', true );

		$recipients = $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( 'SELECT * FROM ' . tsl_nl_table() . " WHERE status = 'active' AND id > %d AND id <= %d ORDER BY id ASC LIMIT %d", $cursor, $max_id, $limit ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( ! $recipients ) {
			update_post_meta( $issue->ID, 'tsl_nl_status', 'sent' );
			update_post_meta( $issue->ID, 'tsl_nl_finished', current_time( 'mysql' ) );
			delete_post_meta( $issue->ID, 'tsl_nl_body' );
			continue;
		}

		$sent   = (int) get_post_meta( $issue->ID, 'tsl_nl_sent', true );
		$failed = (int) get_post_meta( $issue->ID, 'tsl_nl_failed', true );

		$streak       = 0;
		$streak_start = $cursor;
		foreach ( $recipients as $subscriber ) {
			if ( time() > $deadline ) {
				break;
			}
			$message = tsl_nl_issue_message( $issue, $subscriber, $body );
			if ( tsl_nl_mail( $subscriber->email, $message['subject'], $message['html'], $message['headers'] ) ) {
				++$sent;
				$streak = 0;
				delete_post_meta( $issue->ID, 'tsl_nl_last_error' );
			} else {
				if ( 0 === $streak ) {
					$streak_start = $cursor;
				}
				++$failed;
				++$streak;
				// Trzy błędy z rzędu = problem po stronie serwera pocztowego (np. limit).
				// Cofamy te adresy i wstrzymujemy partię — ponowimy w kolejnym cyklu.
				if ( $streak >= 3 ) {
					$failed -= $streak;
					$cursor  = $streak_start;
					update_post_meta( $issue->ID, 'tsl_nl_cursor', $cursor );
					update_post_meta( $issue->ID, 'tsl_nl_last_error', $GLOBALS['tsl_nl_last_error'] ?? 'Serwer pocztowy odrzuca wiadomości.' );
					break;
				}
			}
			$cursor = (int) $subscriber->id;
			update_post_meta( $issue->ID, 'tsl_nl_cursor', $cursor );
		}

		update_post_meta( $issue->ID, 'tsl_nl_sent', $sent );
		update_post_meta( $issue->ID, 'tsl_nl_failed', $failed );

		// Ostatnia partia — zamykamy od razu, bez czekania na kolejny cykl.
		if ( $cursor >= $max_id && $streak < 3 ) {
			update_post_meta( $issue->ID, 'tsl_nl_status', 'sent' );
			update_post_meta( $issue->ID, 'tsl_nl_finished', current_time( 'mysql' ) );
			delete_post_meta( $issue->ID, 'tsl_nl_body' );
		}
	}

	delete_transient( 'tsl_nl_lock' );
}
