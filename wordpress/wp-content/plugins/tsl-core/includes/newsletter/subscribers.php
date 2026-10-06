<?php
/**
 * Newsletter: lista subskrybentów (własna tabela w bazie WordPressa).
 *
 * Subskrybent przechodzi przez statusy:
 *   pending      — zapisał się, czeka na kliknięcie linku potwierdzającego,
 *   active       — potwierdził zapis (podwójna zgoda / double opt-in),
 *   unsubscribed — wypisał się.
 *
 * Dla każdego zapisu przechowujemy dowód zgody (treść zgody, datę, źródło, IP),
 * wymagany do wykazania podstawy wysyłki (RODO, art. 7 ust. 1).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'TSL_NL_DB_VERSION', '1' );

/**
 * Nazwa tabeli subskrybentów.
 */
function tsl_nl_table() {
	global $wpdb;
	return $wpdb->prefix . 'tsl_subscribers';
}

/**
 * Tworzy lub aktualizuje tabelę.
 */
function tsl_nl_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = tsl_nl_table();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			token char(40) NOT NULL,
			source varchar(255) NOT NULL DEFAULT '',
			consent_text text NOT NULL,
			consent_ip varchar(45) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			confirmed_at datetime NULL,
			unsubscribed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email),
			UNIQUE KEY token (token),
			KEY status (status)
		) {$charset};"
	);
	update_option( 'tsl_nl_db_version', TSL_NL_DB_VERSION, false );
}

/**
 * Aktualizacja tabeli po wgraniu nowej wersji wtyczki (bez ponownej aktywacji).
 */
add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'tsl_nl_db_version' ) !== TSL_NL_DB_VERSION ) {
			tsl_nl_install();
		}
	}
);

/**
 * Losowy token (potwierdzenie i wypisanie).
 */
function tsl_nl_new_token() {
	return bin2hex( random_bytes( 20 ) );
}

/**
 * Subskrybent po adresie e-mail.
 *
 * @param string $email Adres.
 * @return object|null
 */
function tsl_nl_get_by_email( $email ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . tsl_nl_table() . ' WHERE email = %s', strtolower( $email ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
}

/**
 * Subskrybent po tokenie.
 *
 * @param string $token Token.
 * @return object|null
 */
function tsl_nl_get_by_token( $token ) {
	global $wpdb;
	if ( ! preg_match( '/^[a-f0-9]{40}$/', (string) $token ) ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . tsl_nl_table() . ' WHERE token = %s', $token ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
}

/**
 * Zapis (lub ponowny zapis) — zawsze wymaga potwierdzenia e-mailem.
 *
 * @param string $email  Adres.
 * @param string $source Skąd zapis (adres strony).
 * @return object|WP_Error Subskrybent albo błąd.
 */
function tsl_nl_subscribe( $email, $source = '' ) {
	global $wpdb;

	$email = strtolower( sanitize_email( $email ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'tsl_nl_email', 'Podaj poprawny adres e-mail.' );
	}

	$now      = current_time( 'mysql', true );
	$existing = tsl_nl_get_by_email( $email );
	$data     = array(
		'status'       => 'pending',
		'token'        => tsl_nl_new_token(),
		'source'       => substr( esc_url_raw( $source ), 0, 255 ),
		'consent_text' => tsl_nl_setting( 'consent_text' ),
		'consent_ip'   => tsl_nl_client_ip(),
		'created_at'   => $now,
	);

	if ( $existing ) {
		// Aktywny subskrybent nie jest „resetowany” — nic się nie zmienia.
		if ( 'active' === $existing->status ) {
			return $existing;
		}
		$wpdb->update( tsl_nl_table(), $data, array( 'id' => $existing->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return tsl_nl_get_by_email( $email );
	}

	$wpdb->insert( tsl_nl_table(), array_merge( $data, array( 'email' => $email ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return tsl_nl_get_by_email( $email );
}

/**
 * Potwierdzenie zapisu.
 *
 * @param object $subscriber Subskrybent.
 */
function tsl_nl_confirm( $subscriber ) {
	global $wpdb;
	if ( 'active' === $subscriber->status ) {
		return;
	}
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		tsl_nl_table(),
		array(
			'status'          => 'active',
			'confirmed_at'    => current_time( 'mysql', true ),
			'unsubscribed_at' => null,
		),
		array( 'id' => $subscriber->id )
	);
}

/**
 * Wypisanie.
 *
 * @param object $subscriber Subskrybent.
 */
function tsl_nl_unsubscribe( $subscriber ) {
	global $wpdb;
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		tsl_nl_table(),
		array(
			'status'          => 'unsubscribed',
			'unsubscribed_at' => current_time( 'mysql', true ),
		),
		array( 'id' => $subscriber->id )
	);
}

/**
 * Trwałe usunięcie (żądanie usunięcia danych).
 *
 * @param int $id ID subskrybenta.
 */
function tsl_nl_delete( $id ) {
	global $wpdb;
	$wpdb->delete( tsl_nl_table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Liczba subskrybentów według statusu.
 *
 * @return array<string, int>
 */
function tsl_nl_counts() {
	global $wpdb;
	$rows   = $wpdb->get_results( 'SELECT status, COUNT(*) AS n FROM ' . tsl_nl_table() . ' GROUP BY status' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	$counts = array(
		'active'       => 0,
		'pending'      => 0,
		'unsubscribed' => 0,
	);
	foreach ( $rows as $row ) {
		$counts[ $row->status ] = (int) $row->n;
	}
	return $counts;
}

/**
 * Lista subskrybentów (panel administracyjny).
 *
 * @param array $args status, search, page, per_page.
 * @return array{items: object[], total: int}
 */
function tsl_nl_query( $args = array() ) {
	global $wpdb;
	$args  = wp_parse_args(
		$args,
		array(
			'status'   => '',
			'search'   => '',
			'page'     => 1,
			'per_page' => 50,
		)
	);
	$where = array( '1=1' );
	$vals  = array();
	if ( $args['status'] ) {
		$where[] = 'status = %s';
		$vals[]  = $args['status'];
	}
	if ( $args['search'] ) {
		$where[] = 'email LIKE %s';
		$vals[]  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
	}
	$sql_where = implode( ' AND ', $where );
	$total     = (int) $wpdb->get_var( $vals ? $wpdb->prepare( 'SELECT COUNT(*) FROM ' . tsl_nl_table() . " WHERE {$sql_where}", $vals ) : 'SELECT COUNT(*) FROM ' . tsl_nl_table() . " WHERE {$sql_where}" ); // phpcs:ignore WordPress.DB
	$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
	$items     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . tsl_nl_table() . " WHERE {$sql_where} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $vals, array( (int) $args['per_page'], $offset ) ) ) ); // phpcs:ignore WordPress.DB

	return array(
		'items' => $items,
		'total' => $total,
	);
}

/**
 * Adres IP zapisującego się (dowód zgody).
 */
function tsl_nl_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}
