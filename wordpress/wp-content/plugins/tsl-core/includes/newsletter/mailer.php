<?php
/**
 * Newsletter: składanie i wysyłka wiadomości.
 *
 * Każdy odbiorca dostaje osobną wiadomość (nigdy wspólne pole „Do” / „UDW”)
 * z własnym linkiem do wypisania się oraz nagłówkami List-Unsubscribe
 * (wypisanie jednym kliknięciem — wymóg Gmaila i Yahoo dla wysyłek masowych).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Link potwierdzenia lub wypisania.
 *
 * @param string $action confirm | unsubscribe.
 * @param string $token  Token subskrybenta.
 */
function tsl_nl_action_url( $action, $token ) {
	return add_query_arg(
		array(
			'tsl_nl' => $action,
			'token'  => $token,
		),
		home_url( '/newsletter/' )
	);
}

/**
 * Licznik wiadomości wysłanych w bieżącej godzinie (wspólny dla potwierdzeń i wydań).
 *
 * @param int $add Ile dodać.
 * @return int Stan licznika.
 */
function tsl_nl_hour_counter( $add = 0 ) {
	$key   = 'tsl_nl_sent_' . gmdate( 'YmdH' );
	$count = (int) get_transient( $key );
	if ( $add ) {
		$count += $add;
		set_transient( $key, $count, HOUR_IN_SECONDS + 300 );
	}
	return $count;
}

/**
 * Ile wiadomości można jeszcze wysłać w tej godzinie.
 */
function tsl_nl_hour_remaining() {
	return max( 0, (int) tsl_nl_setting( 'hourly_limit' ) - tsl_nl_hour_counter() );
}

/**
 * Ostatni błąd wysyłki (pokazywany w panelu wysyłki wydania).
 */
add_action(
	'wp_mail_failed',
	function ( $error ) {
		$GLOBALS['tsl_nl_last_error'] = $error->get_error_message();
	}
);

/**
 * Wysyła jedną wiadomość HTML z wersją tekstową.
 *
 * @param string $to      Odbiorca.
 * @param string $subject Temat.
 * @param string $html    Treść HTML.
 * @param array  $headers Dodatkowe nagłówki.
 * @return bool
 */
function tsl_nl_mail( $to, $subject, $html, $headers = array() ) {
	$text = tsl_nl_html_to_text( $html );

	$alt = function ( $mailer ) use ( $text ) {
		$mailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	};
	add_action( 'phpmailer_init', $alt );

	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$has_reply_to = (bool) preg_grep( '/^Reply-To:/i', $headers );
	if ( ! $has_reply_to && is_email( tsl_nl_setting( 'reply_to' ) ) ) {
		$headers[] = 'Reply-To: ' . tsl_nl_setting( 'reply_to' );
	}

	$sent = wp_mail( $to, $subject, $html, $headers );

	remove_action( 'phpmailer_init', $alt );
	tsl_nl_hour_counter( 1 );
	return $sent;
}

/**
 * Wersja tekstowa wiadomości (dla programów bez HTML i filtrów antyspamowych).
 *
 * @param string $html HTML.
 */
function tsl_nl_html_to_text( $html ) {
	$html = preg_replace( '#<(style|head)[^>]*>.*?</\1>#si', '', $html );
	$html = preg_replace_callback(
		'#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#si',
		function ( $m ) {
			$label = trim( wp_strip_all_tags( $m[2] ) );
			return $label === $m[1] ? $m[1] : $label . ' (' . $m[1] . ')';
		},
		$html
	);
	$html = preg_replace( '#<(br|/p|/h[1-6]|/li|/tr|/div)\b[^>]*>#i', "\n", $html );
	$html = preg_replace( '#<li\b[^>]*>#i', '- ', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( "/[ \t]+/u", ' ', $text );
	return trim( preg_replace( "/\n\s*\n\s*\n+/u", "\n\n", $text ) );
}

/**
 * Szablon wiadomości w barwach serwisu.
 *
 * @param array $args title, preheader, body (HTML), footer (HTML).
 */
function tsl_nl_layout( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'     => '',
			'preheader' => '',
			'body'      => '',
			'footer'    => '',
		)
	);
	ob_start();
	include TSL_CORE_DIR . 'includes/newsletter/email-template.php';
	return ob_get_clean();
}

/**
 * E-mail z prośbą o potwierdzenie zapisu.
 *
 * @param object $subscriber Subskrybent.
 */
function tsl_nl_send_confirmation( $subscriber ) {
	$url  = tsl_nl_action_url( 'confirm', $subscriber->token );
	$body = '<p>Dzień dobry,</p>'
		. '<p>dziękujemy za zapis do newslettera TSL Manager. Aby zacząć otrzymywać analizy, alerty RiskBrief i narzędzia dla menedżerów TSL, potwierdź swój adres:</p>'
		. '<p style="margin:28px 0;"><a href="' . esc_url( $url ) . '" style="background:#B8734A;color:#0B1D33;padding:14px 24px;border-radius:4px;font-weight:700;text-decoration:none;display:inline-block;">Potwierdzam zapis</a></p>'
		. '<p style="color:#5B616B;font-size:14px;">Jeśli to nie Ty zapisałeś(-aś) ten adres, zignoruj tę wiadomość — bez potwierdzenia nie wyślemy żadnego newslettera.</p>';

	return tsl_nl_mail(
		$subscriber->email,
		'Potwierdź zapis do newslettera TSL Manager',
		tsl_nl_layout(
			array(
				'title'     => 'Potwierdź zapis',
				'preheader' => 'Jedno kliknięcie i gotowe.',
				'body'      => $body,
				'footer'    => esc_html( tsl_nl_setting( 'publisher' ) ),
			)
		)
	);
}

/**
 * Treść wydania: wstęp redakcji + sekcje automatyczne (RiskBrief, analizy).
 *
 * @param WP_Post $issue Wydanie.
 * @return string HTML.
 */
function tsl_nl_issue_body( $issue ) {
	global $post;
	$previous = $post;
	$post     = $issue; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $issue );
	$intro = apply_filters( 'the_content', $issue->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	$post  = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	wp_reset_postdata();

	$html = tsl_nl_inline_styles( $intro );
	foreach ( tsl_nl_issue_sections( $issue ) as $heading => $items ) {
		$html .= tsl_nl_section_html( $heading, $items );
	}
	return $html;
}

/**
 * Sekcje automatyczne wydania: materiały opublikowane od poprzedniego wydania.
 * Używane w wiadomości i w wersji w przeglądarce.
 *
 * @param WP_Post $issue Wydanie.
 * @return array<string, WP_Post[]> Nagłówek => materiały.
 */
function tsl_nl_issue_sections( $issue ) {
	$since    = tsl_nl_previous_issue_date( $issue );
	$until    = get_post_meta( $issue->ID, 'tsl_nl_started', true );
	$sections = array(
		'tsl_auto_riskbrief' => array( 'riskbrief', 'RiskBrief TSL — sygnały tygodnia', 5 ),
		'tsl_auto_posts'     => array( 'post', 'Nowe analizy i poradniki', 6 ),
		'tsl_auto_tools'     => array( 'narzedzie', 'Nowe narzędzia', 4 ),
	);
	$out      = array();
	foreach ( $sections as $flag => $section ) {
		if ( ! get_post_meta( $issue->ID, $flag, true ) ) {
			continue;
		}
		$range = array( 'after' => $since ? $since : '14 days ago' );
		if ( $until ) {
			$range['before'] = $until; // Wersja w przeglądarce = to, co dostali odbiorcy.
		}
		$items = get_posts(
			array(
				'post_type'      => $section[0],
				'posts_per_page' => $section[2],
				'post_status'    => 'publish',
				'date_query'     => array( $range ),
			)
		);
		if ( $items ) {
			$out[ $section[1] ] = $items;
		}
	}
	return $out;
}

/**
 * Data wysłania poprzedniego wydania (sekcje automatyczne obejmują tylko nowości).
 *
 * @param WP_Post $issue Wydanie.
 */
function tsl_nl_previous_issue_date( $issue ) {
	$started = get_post_meta( $issue->ID, 'tsl_nl_started', true );
	$prev    = get_posts(
		array(
			'post_type'      => 'wydanie',
			'posts_per_page' => 1,
			'post__not_in'   => array( $issue->ID ),
			'meta_key'       => 'tsl_nl_started', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
				array(
					'key'     => 'tsl_nl_started',
					'value'   => $started ? $started : current_time( 'mysql' ),
					'compare' => '<',
				),
			),
		)
	);
	return $prev ? get_post_meta( $prev[0]->ID, 'tsl_nl_started', true ) : '';
}

/**
 * Sekcja listy materiałów w wiadomości.
 *
 * @param string    $heading Nagłówek.
 * @param WP_Post[] $items   Materiały.
 */
function tsl_nl_section_html( $heading, $items ) {
	$html = '<h2 style="font-family:Georgia,serif;font-size:24px;color:#0B1D33;margin:36px 0 12px;font-weight:600;">' . esc_html( $heading ) . '</h2>';
	foreach ( $items as $item ) {
		$label = '';
		if ( 'riskbrief' === $item->post_type ) {
			$level = get_the_terms( $item, 'tsl_poziom_ryzyka' );
			$label = $level && ! is_wp_error( $level ) ? $level[0]->name : '';
		} else {
			$kind  = get_the_terms( $item, 'post' === $item->post_type ? 'tsl_format' : 'tsl_typ_narzedzia' );
			$label = $kind && ! is_wp_error( $kind ) ? $kind[0]->name : '';
		}
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E6E8EB;"><tr><td style="padding:14px 0;">';
		if ( $label ) {
			$html .= '<p style="margin:0 0 4px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:#8F5430;">' . esc_html( $label ) . '</p>';
		}
		$html .= '<p style="margin:0 0 4px;font-size:17px;font-weight:700;line-height:1.35;"><a href="' . esc_url( get_permalink( $item ) ) . '" style="color:#0B1D33;text-decoration:none;">' . esc_html( get_the_title( $item ) ) . '</a></p>';
		if ( has_excerpt( $item ) ) {
			$html .= '<p style="margin:0;font-size:15px;color:#5B616B;line-height:1.5;">' . esc_html( get_the_excerpt( $item ) ) . '</p>';
		}
		$html .= '</td></tr></table>';
	}
	return $html;
}

/**
 * Proste style w atrybutach dla treści z edytora (programy pocztowe
 * ignorują arkusze stylów strony).
 *
 * @param string $html HTML.
 */
function tsl_nl_inline_styles( $html ) {
	$map = array(
		'h2' => 'font-family:Georgia,serif;font-size:24px;color:#0B1D33;margin:32px 0 12px;font-weight:600;line-height:1.2;',
		'h3' => 'font-size:18px;color:#0B1D33;margin:24px 0 8px;line-height:1.3;',
		'p'  => 'margin:0 0 16px;font-size:16px;line-height:1.6;color:#2B2F36;',
		'li' => 'margin:0 0 8px;font-size:16px;line-height:1.6;color:#2B2F36;',
		'a'  => 'color:#0B1D33;',
	);
	foreach ( $map as $tag => $style ) {
		$html = preg_replace( '#<' . $tag . '(\s[^>]*)?>#i', '<' . $tag . '$1 style="' . $style . '">', $html );
	}
	// Atrybut style dodany dwukrotnie (gdy blok miał już własny) — zostawiamy pierwszy.
	$html = preg_replace( '#(style="[^"]*")(\s[^>]*?)\sstyle="[^"]*"#i', '$1$2', $html );
	return $html;
}

/**
 * Pełna wiadomość wydania dla konkretnego odbiorcy.
 *
 * @param WP_Post     $issue Wydanie.
 * @param object|null $subscriber Subskrybent (null = test).
 * @param string|null $body  Gotowa treść (żeby nie składać jej dla każdego odbiorcy).
 * @return array{subject: string, html: string, headers: string[]}
 */
function tsl_nl_issue_message( $issue, $subscriber = null, $body = null ) {
	$unsubscribe = $subscriber ? tsl_nl_action_url( 'unsubscribe', $subscriber->token ) : home_url( '/newsletter/' );

	$footer = 'Otrzymujesz tę wiadomość, ponieważ zapisałeś(-aś) się do newslettera TSL Manager.<br>'
		. '<a href="' . esc_url( $unsubscribe ) . '" style="color:#E6E8EB;">Wypisz się</a> · '
		. '<a href="' . esc_url( get_permalink( $issue ) ) . '" style="color:#E6E8EB;">Zobacz w przeglądarce</a> · '
		. '<a href="' . esc_url( home_url( '/polityka-prywatnosci/' ) ) . '" style="color:#E6E8EB;">Polityka prywatności</a><br><br>'
		. nl2br( esc_html( tsl_nl_setting( 'publisher' ) ) );

	$headers = array();
	if ( $subscriber ) {
		$headers[] = 'List-Unsubscribe: <' . esc_url_raw( $unsubscribe ) . '>';
		$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
	}

	return array(
		'subject' => get_the_title( $issue ),
		'html'    => tsl_nl_layout(
			array(
				'title'     => get_the_title( $issue ),
				'preheader' => has_excerpt( $issue ) ? get_the_excerpt( $issue ) : '',
				'body'      => null === $body ? tsl_nl_issue_body( $issue ) : $body,
				'footer'    => $footer,
			)
		),
		'headers' => $headers,
	);
}
