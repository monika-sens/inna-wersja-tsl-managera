<?php
/**
 * Newsletter: ustawienia (nadawca, limit wysyłki, poczta wychodząca SMTP).
 *
 * Hasło SMTP można też zdefiniować w wp-config.php — wtedy nie jest
 * przechowywane w bazie danych:
 *   define( 'TSL_SMTP_PASSWORD', '…' );
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Domyślne ustawienia.
 */
function tsl_nl_defaults() {
	return array(
		'from_name'     => 'TSL Manager',
		'from_email'    => get_option( 'admin_email' ),
		'reply_to'      => '',
		'hourly_limit'  => 300,
		'publisher'     => 'TSL Manager · dane wydawcy do uzupełnienia (nazwa, adres)',
		'consent_text'  => 'Wyrażam zgodę na otrzymywanie newslettera TSL Manager na podany adres e-mail. Zgodę mogę wycofać w każdej chwili, klikając link „Wypisz się” w stopce wiadomości.',
		'smtp_enabled'  => 0,
		'smtp_host'     => '',
		'smtp_port'     => 465,
		'smtp_secure'   => 'ssl',
		'smtp_user'     => '',
		'smtp_password' => '',
	);
}

/**
 * Wartość ustawienia.
 *
 * @param string $key Klucz.
 * @return mixed
 */
function tsl_nl_setting( $key ) {
	if ( 'smtp_password' === $key && defined( 'TSL_SMTP_PASSWORD' ) ) {
		return TSL_SMTP_PASSWORD;
	}
	$settings = wp_parse_args( (array) get_option( 'tsl_newsletter', array() ), tsl_nl_defaults() );
	return $settings[ $key ] ?? null;
}

/**
 * Oczyszczenie ustawień przy zapisie.
 *
 * @param array $input Dane z formularza.
 */
function tsl_nl_sanitize_settings( $input ) {
	$old = (array) get_option( 'tsl_newsletter', array() );
	$out = array(
		'from_name'    => sanitize_text_field( $input['from_name'] ?? '' ),
		'from_email'   => sanitize_email( $input['from_email'] ?? '' ),
		'reply_to'     => sanitize_email( $input['reply_to'] ?? '' ),
		'hourly_limit' => max( 10, min( 5000, absint( $input['hourly_limit'] ?? 300 ) ) ),
		'publisher'    => sanitize_textarea_field( $input['publisher'] ?? '' ),
		'consent_text' => sanitize_textarea_field( $input['consent_text'] ?? '' ),
		'smtp_enabled' => empty( $input['smtp_enabled'] ) ? 0 : 1,
		'smtp_host'    => sanitize_text_field( $input['smtp_host'] ?? '' ),
		'smtp_port'    => absint( $input['smtp_port'] ?? 465 ),
		'smtp_secure'  => in_array( $input['smtp_secure'] ?? '', array( 'ssl', 'tls', '' ), true ) ? $input['smtp_secure'] : 'ssl',
		'smtp_user'    => sanitize_text_field( $input['smtp_user'] ?? '' ),
	);
	// Puste pole hasła nie kasuje zapisanego hasła.
	$out['smtp_password'] = '' !== ( $input['smtp_password'] ?? '' ) ? (string) $input['smtp_password'] : ( $old['smtp_password'] ?? '' );
	return $out;
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'tsl_newsletter',
			'tsl_newsletter',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'tsl_nl_sanitize_settings',
				'show_in_rest'      => false,
			)
		);
	}
);

/**
 * Poczta wychodząca przez SMTP (np. skrzynka w home.pl) — dotyczy wszystkich
 * e-maili WordPressa, nie tylko newslettera.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer PHPMailer.
 */
function tsl_nl_configure_smtp( $mailer ) {
	if ( ! tsl_nl_setting( 'smtp_enabled' ) || ! tsl_nl_setting( 'smtp_host' ) ) {
		return;
	}
	$mailer->isSMTP();
	$mailer->Host       = tsl_nl_setting( 'smtp_host' );       // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Port       = (int) tsl_nl_setting( 'smtp_port' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->SMTPSecure = tsl_nl_setting( 'smtp_secure' );     // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->SMTPAuth   = (bool) tsl_nl_setting( 'smtp_user' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Username   = tsl_nl_setting( 'smtp_user' );       // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Password   = tsl_nl_setting( 'smtp_password' );   // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Timeout    = 20; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
add_action( 'phpmailer_init', 'tsl_nl_configure_smtp' );

/**
 * Nadawca wszystkich e-maili serwisu.
 */
add_filter(
	'wp_mail_from',
	function ( $from ) {
		$email = tsl_nl_setting( 'from_email' );
		return is_email( $email ) ? $email : $from;
	}
);
add_filter(
	'wp_mail_from_name',
	function ( $name ) {
		return tsl_nl_setting( 'from_name' ) ? tsl_nl_setting( 'from_name' ) : $name;
	}
);
