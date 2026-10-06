<?php
/**
 * Newsletter: REST API dla panelu „Wysyłka” w edytorze wydania.
 *
 *  GET  /wp-json/tsl/v1/newsletter/{id}        stan wysyłki
 *  POST /wp-json/tsl/v1/newsletter/{id}/test   wiadomość testowa na adres redaktora
 *  POST /wp-json/tsl/v1/newsletter/{id}/send   start wysyłki do subskrybentów
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		$can = function ( WP_REST_Request $request ) {
			return current_user_can( 'edit_post', (int) $request['id'] ) && current_user_can( 'publish_posts' );
		};

		register_rest_route(
			'tsl/v1',
			'/newsletter/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => $can,
				'callback'            => function ( WP_REST_Request $request ) {
					return tsl_nl_issue_status( (int) $request['id'] );
				},
			)
		);

		register_rest_route(
			'tsl/v1',
			'/newsletter/(?P<id>\d+)/test',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => function ( WP_REST_Request $request ) {
					$issue = get_post( (int) $request['id'] );
					$email = $request->get_param( 'email' ) ? sanitize_email( $request->get_param( 'email' ) ) : wp_get_current_user()->user_email;
					if ( ! $issue || ! is_email( $email ) ) {
						return new WP_Error( 'tsl_nl_test', 'Nieprawidłowe wydanie lub adres.', array( 'status' => 400 ) );
					}
					$message = tsl_nl_issue_message( $issue );
					if ( ! tsl_nl_mail( $email, '[TEST] ' . $message['subject'], $message['html'] ) ) {
						return new WP_Error( 'tsl_nl_test', 'Nie udało się wysłać wiadomości: ' . ( $GLOBALS['tsl_nl_last_error'] ?? 'sprawdź ustawienia poczty.' ), array( 'status' => 500 ) );
					}
					return array(
						'ok'      => true,
						'message' => 'Wysłano wiadomość testową na ' . $email . '.',
					);
				},
			)
		);

		register_rest_route(
			'tsl/v1',
			'/newsletter/(?P<id>\d+)/send',
			array(
				'methods'             => 'POST',
				'permission_callback' => $can,
				'callback'            => function ( WP_REST_Request $request ) {
					$result = tsl_nl_queue_issue( (int) $request['id'] );
					if ( is_wp_error( $result ) ) {
						$result->add_data( array( 'status' => 400 ) );
						return $result;
					}
					return tsl_nl_issue_status( (int) $request['id'] );
				},
			)
		);
	}
);
