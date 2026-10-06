<?php
/**
 * Newsletter: zapis, potwierdzenie i wypisanie (strona czytelnika).
 *
 *  POST /wp-json/tsl/v1/subscribe            zapis z formularza
 *  /newsletter/?tsl_nl=confirm&token=…       potwierdzenie (link z e-maila)
 *  /newsletter/?tsl_nl=unsubscribe&token=…   wypisanie: GET pokazuje przycisk,
 *                                            POST wypisuje (także „jednym
 *                                            kliknięciem” z programu pocztowego)
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'tsl/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'tsl_nl_rest_subscribe',
				'args'                => array(
					'email'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'consent' => array(
						'type'     => 'boolean',
						'required' => true,
					),
					'source'  => array( 'type' => 'string' ),
					'website' => array( 'type' => 'string' ), // Pułapka na boty — ludzie zostawiają puste.
				),
			)
		);
	}
);

/**
 * Obsługa zapisu.
 *
 * Odpowiedź jest taka sama niezależnie od tego, czy adres już był na liście —
 * nie ujawniamy, kto jest subskrybentem.
 *
 * @param WP_REST_Request $request Żądanie.
 */
function tsl_nl_rest_subscribe( WP_REST_Request $request ) {
	$ok = new WP_REST_Response(
		array(
			'ok'      => true,
			'message' => 'Dziękujemy! Sprawdź skrzynkę i kliknij link, aby potwierdzić zapis.',
		)
	);

	if ( '' !== (string) $request->get_param( 'website' ) ) {
		return $ok;
	}
	if ( ! $request->get_param( 'consent' ) ) {
		return new WP_Error( 'tsl_nl_consent', 'Zaznacz zgodę, aby zapisać się do newslettera.', array( 'status' => 400 ) );
	}

	// Wersja demonstracyjna bez poczty: adres jest tylko sprawdzany, nie zapisywany.
	if ( tsl_forms_demo_only() ) {
		if ( ! is_email( (string) $request->get_param( 'email' ) ) ) {
			return new WP_Error( 'tsl_nl_email', 'Podaj poprawny adres e-mail.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'demo'    => true,
				'message' => tsl_demo_form_message(),
			)
		);
	}

	// Ograniczenie liczby prób z jednego adresu IP.
	$ip_key = 'tsl_nl_rate_' . md5( tsl_nl_client_ip() );
	$tries  = (int) get_transient( $ip_key );
	if ( $tries >= 5 ) {
		return new WP_Error( 'tsl_nl_rate', 'Zbyt wiele prób. Spróbuj ponownie za kilka minut.', array( 'status' => 429 ) );
	}
	set_transient( $ip_key, $tries + 1, 10 * MINUTE_IN_SECONDS );

	$subscriber = tsl_nl_subscribe( (string) $request->get_param( 'email' ), (string) $request->get_param( 'source' ) );
	if ( is_wp_error( $subscriber ) ) {
		$subscriber->add_data( array( 'status' => 400 ) );
		return $subscriber;
	}

	if ( 'pending' === $subscriber->status ) {
		tsl_nl_send_confirmation( $subscriber );
	}
	return $ok;
}

/**
 * Potwierdzenie i wypisanie z linków w wiadomościach.
 */
add_action(
	'template_redirect',
	function () {
		$action = isset( $_GET['tsl_nl'] ) ? sanitize_key( wp_unslash( $_GET['tsl_nl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $action ) {
			return;
		}
		$token      = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subscriber = tsl_nl_get_by_token( $token );
		$page       = home_url( '/newsletter/' );
		$is_post    = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );

		if ( ! $subscriber ) {
			wp_safe_redirect( add_query_arg( 'nl', 'blad', $page ) );
			exit;
		}

		if ( 'confirm' === $action ) {
			tsl_nl_confirm( $subscriber );
			wp_safe_redirect( add_query_arg( 'nl', 'potwierdzono', $page ) );
			exit;
		}

		if ( 'unsubscribe' === $action ) {
			if ( $is_post ) {
				tsl_nl_unsubscribe( $subscriber );
				// Wypisanie „jednym kliknięciem” (RFC 8058) — program pocztowy oczekuje odpowiedzi 200.
				if ( isset( $_POST['List-Unsubscribe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					status_header( 200 );
					echo 'OK';
					exit;
				}
				wp_safe_redirect( add_query_arg( 'nl', 'wypisano', $page ) );
				exit;
			}
			// GET: tylko pokazujemy przycisk — skanery linków w poczcie nie wypiszą nikogo przypadkiem.
			wp_safe_redirect(
				add_query_arg(
					array(
						'nl'    => 'wypisz',
						'token' => $subscriber->token,
					),
					$page
				)
			);
			exit;
		}
	},
	1
);
