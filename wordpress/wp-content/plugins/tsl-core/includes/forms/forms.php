<?php
/**
 * Formularze: kontakt, rejestracja na wydarzenie, pobranie materiału.
 *
 * Zgłoszenia NIE są zapisywane w bazie — trafiają wyłącznie e-mailem na adresy
 * ustawione w Ustawienia → Formularze TSL. Zgłaszający może odpowiedzieć
 * bezpośrednio z poczty (nagłówek Reply-To).
 *
 * Zgodnie z rozdz. 16 mapy serwisu każde zgłoszenie zawiera kontekst:
 * stronę źródłową, materiał, którego dotyczy, filar i deklarowany problem.
 *
 *  POST /wp-json/tsl/v1/form/{kontakt|wydarzenie|pobranie}
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definicje formularzy (pola renderowane przez motyw, walidowane tutaj).
 *
 * @return array<string, array>
 */
function tsl_form_definitions() {
	$person = array(
		'name'    => array( 'label' => 'Imię i nazwisko', 'type' => 'text', 'required' => true, 'autocomplete' => 'name' ),
		'email'   => array( 'label' => 'E-mail służbowy', 'type' => 'email', 'required' => true, 'autocomplete' => 'email' ),
		'company' => array( 'label' => 'Firma', 'type' => 'text', 'autocomplete' => 'organization' ),
		'role'    => array( 'label' => 'Stanowisko', 'type' => 'text', 'autocomplete' => 'organization-title' ),
	);

	return array(
		'kontakt'    => array(
			'title'   => 'Formularz kontaktowy',
			'submit'  => 'Wyślij wiadomość',
			'success' => 'Dziękujemy! Wiadomość trafiła do redakcji. Odpowiemy na podany adres e-mail.',
			'fields'  => array_merge(
				array(
					'topic' => array(
						'label'    => 'Temat',
						'type'     => 'select',
						'required' => true,
						'options'  => array(
							'redakcja'    => 'Sprawa redakcyjna / propozycja tematu',
							'partnerstwo' => 'Partnerstwo i współpraca',
							'wydarzenia'  => 'Wydarzenia',
							'inne'        => 'Inna sprawa',
						),
					),
				),
				$person,
				array(
					'message' => array( 'label' => 'Wiadomość', 'type' => 'textarea', 'required' => true ),
				)
			),
		),
		'wydarzenie' => array(
			'title'   => 'Rejestracja na wydarzenie',
			'submit'  => 'Zarejestruj się',
			'success' => 'Dziękujemy za rejestrację! Potwierdzenie wysłaliśmy na podany adres e-mail.',
			'fields'  => array_merge(
				$person,
				array(
					'question' => array( 'label' => 'Pytanie do prelegentów (opcjonalnie)', 'type' => 'textarea' ),
				)
			),
		),
		'pobranie'   => array(
			'title'   => 'Pobierz materiał',
			'submit'  => 'Wyślij link do pobrania',
			'success' => 'Gotowe! Link do pobrania wysłaliśmy na podany adres e-mail.',
			'fields'  => array(
				'email'   => $person['email'],
				'name'    => array_merge( $person['name'], array( 'required' => false ) ),
				'company' => $person['company'],
				'role'    => $person['role'],
			),
		),
	);
}

/**
 * Adresaci zgłoszeń (Ustawienia → Formularze TSL).
 *
 * @param string $key kontakt_redakcja | kontakt_partnerstwo | kontakt_wydarzenia | kontakt_inne | wydarzenie | pobranie.
 * @return string[]
 */
function tsl_form_recipients( $key ) {
	$settings = (array) get_option( 'tsl_forms', array() );
	$value    = $settings[ $key ] ?? '';
	if ( ! $value && str_starts_with( $key, 'kontakt_' ) ) {
		$value = $settings['kontakt_redakcja'] ?? '';
	}
	$emails = array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'is_email' );
	return $emails ? array_values( $emails ) : array( get_option( 'admin_email' ) );
}

/**
 * Klauzula informacyjna pod formularzami (do zatwierdzenia przez prawnika).
 */
function tsl_form_privacy_text() {
	$settings = (array) get_option( 'tsl_forms', array() );
	$default  = 'Administratorem danych jest wydawca serwisu TSL Manager. Dane z formularza wykorzystamy wyłącznie do obsługi zgłoszenia. Szczegóły, w tym Twoje prawa, opisuje polityka prywatności.';
	return ! empty( $settings['privacy_text'] ) ? $settings['privacy_text'] : $default;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'tsl/v1',
			'/form/(?P<type>kontakt|wydarzenie|pobranie)',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'tsl_form_handle',
			)
		);
	}
);

/**
 * Obsługa zgłoszenia.
 *
 * @param WP_REST_Request $request Żądanie.
 */
function tsl_form_handle( WP_REST_Request $request ) {
	$type = $request['type'];
	$defs = tsl_form_definitions();
	$def  = $defs[ $type ];

	// Pułapka na boty: ukryte pole wypełniają tylko automaty.
	if ( '' !== (string) $request->get_param( 'website' ) ) {
		return array(
			'ok'      => true,
			'message' => $def['success'],
		);
	}

	// Walidacja pól.
	$data   = array();
	$errors = array();
	foreach ( $def['fields'] as $key => $field ) {
		$raw   = (string) $request->get_param( $key );
		$value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		$value = mb_substr( $value, 0, 'textarea' === $field['type'] ? 5000 : 200 );

		if ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) {
			$errors[ $key ] = 'Podaj poprawny adres e-mail.';
		}
		if ( 'select' === $field['type'] && '' !== $value && ! isset( $field['options'][ $value ] ) ) {
			$value = '';
		}
		if ( ! empty( $field['required'] ) && '' === $value ) {
			$errors[ $key ] = 'To pole jest wymagane.';
		}
		$data[ $key ] = $value;
	}
	if ( ! $request->get_param( 'privacy' ) ) {
		$errors['privacy'] = 'Potwierdź zapoznanie się z informacją o przetwarzaniu danych.';
	}
	if ( $errors ) {
		return new WP_Error( 'tsl_form_invalid', 'Popraw zaznaczone pola.', array( 'status' => 400, 'fields' => $errors ) );
	}

	// Wersja demonstracyjna bez poczty: nic nie wysyłamy ani nie zapisujemy.
	// Sprawdzane po walidacji pól (prezentacja pokazuje komunikaty), przed limitem
	// prób i kontrolą pliku, żeby pokaz nie kończył się błędem.
	if ( tsl_forms_demo_only() ) {
		return array(
			'ok'      => true,
			'demo'    => true,
			'message' => tsl_demo_form_message(),
		);
	}

	$ip_key = 'tsl_form_rate_' . md5( tsl_nl_client_ip() );
	$tries  = (int) get_transient( $ip_key );
	if ( $tries >= 8 ) {
		return new WP_Error( 'tsl_form_rate', 'Zbyt wiele zgłoszeń. Spróbuj ponownie za kilka minut.', array( 'status' => 429 ) );
	}
	set_transient( $ip_key, $tries + 1, 10 * MINUTE_IN_SECONDS );

	// Kontekst: materiał, z którego wysłano formularz.
	$post_id = absint( $request->get_param( 'post_id' ) );
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( $post && 'publish' !== $post->post_status ) {
		$post = null;
	}
	if ( in_array( $type, array( 'wydarzenie', 'pobranie' ), true ) && ! $post ) {
		return new WP_Error( 'tsl_form_post', 'Nie znaleziono materiału.', array( 'status' => 400 ) );
	}
	if ( 'wydarzenie' === $type && ( tsl_event_post_type() !== $post->post_type || tsl_event_is_past( $post ) ) ) {
		return new WP_Error( 'tsl_form_closed', 'Rejestracja na to wydarzenie jest zamknięta.', array( 'status' => 400 ) );
	}
	if ( 'pobranie' === $type && ! wp_get_attachment_url( (int) tsl_field( 'tsl_file', $post ) ) ) {
		return new WP_Error( 'tsl_form_nofile', 'Ten materiał nie jest jeszcze dostępny do pobrania.', array( 'status' => 400 ) );
	}
	$source = esc_url_raw( (string) $request->get_param( 'source' ) );

	$sent = call_user_func( 'tsl_form_send_' . $type, $data, $post, $source, $def );
	if ( ! $sent ) {
		return new WP_Error( 'tsl_form_mail', 'Nie udało się wysłać zgłoszenia. Spróbuj ponownie albo napisz bezpośrednio na adres redakcji.', array( 'status' => 500 ) );
	}

	// Opcjonalny zapis do newslettera — osobna zgoda, z podwójnym potwierdzeniem.
	if ( $request->get_param( 'newsletter' ) && function_exists( 'tsl_nl_subscribe' ) ) {
		$subscriber = tsl_nl_subscribe( $data['email'], $source );
		if ( ! is_wp_error( $subscriber ) && 'pending' === $subscriber->status ) {
			tsl_nl_send_confirmation( $subscriber );
		}
	}

	return array(
		'ok'      => true,
		'message' => $def['success'],
	);
}

/**
 * Tabela pól zgłoszenia + kontekst — treść e-maila do redakcji.
 *
 * @param array        $rows   Etykieta => wartość.
 * @param WP_Post|null $post   Materiał.
 * @param string       $source Strona źródłowa.
 */
function tsl_form_table( $rows, $post = null, $source = '' ) {
	if ( $post ) {
		$rows['Materiał'] = '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		$pillar           = function_exists( 'tsl_get_pillar' ) ? tsl_get_pillar( $post ) : null;
		if ( $pillar ) {
			$rows['Filar'] = esc_html( $pillar->name );
		}
		$problems = get_the_terms( $post, 'tsl_problem' );
		if ( $problems && ! is_wp_error( $problems ) ) {
			$rows['Problem / ryzyko'] = esc_html( implode( ', ', wp_list_pluck( $problems, 'name' ) ) );
		}
	}
	if ( $source ) {
		$rows['Wysłano ze strony'] = '<a href="' . esc_url( $source ) . '">' . esc_html( $source ) . '</a>';
	}
	$rows['Data'] = esc_html( wp_date( 'Y-m-d H:i' ) );

	$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:15px;">';
	foreach ( $rows as $label => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$html .= '<tr><td style="padding:8px 12px 8px 0;border-bottom:1px solid #E6E8EB;color:#5B616B;vertical-align:top;width:170px;">' . esc_html( $label ) . '</td>'
			. '<td style="padding:8px 0;border-bottom:1px solid #E6E8EB;color:#0B1D33;">' . $value . '</td></tr>';
	}
	return $html . '</table>';
}

/**
 * Wartość pola tekstowego do tabeli (wieloliniowe z zachowaniem akapitów).
 *
 * @param string $value Wartość.
 */
function tsl_form_value( $value ) {
	return nl2br( esc_html( $value ) );
}

/**
 * Kontakt: e-mail do adresata właściwego dla tematu.
 *
 * @param array        $data   Dane.
 * @param WP_Post|null $post   Materiał.
 * @param string       $source Strona źródłowa.
 * @param array        $def    Definicja formularza.
 */
function tsl_form_send_kontakt( $data, $post, $source, $def ) {
	$topic = $def['fields']['topic']['options'][ $data['topic'] ] ?? 'Kontakt';
	$body  = tsl_form_table(
		array(
			'Temat'           => esc_html( $topic ),
			'Imię i nazwisko' => tsl_form_value( $data['name'] ),
			'E-mail'          => '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>',
			'Firma'           => tsl_form_value( $data['company'] ),
			'Stanowisko'      => tsl_form_value( $data['role'] ),
			'Wiadomość'       => tsl_form_value( $data['message'] ),
		),
		$post,
		$source
	);

	return tsl_nl_mail(
		tsl_form_recipients( 'kontakt_' . $data['topic'] ),
		'[Kontakt: ' . $topic . '] ' . $data['name'],
		tsl_nl_layout(
			array(
				'title'  => 'Nowa wiadomość z formularza kontaktowego',
				'body'   => $body . '<p style="margin-top:20px;font-size:14px;color:#5B616B;">Odpowiedz na tę wiadomość, aby napisać bezpośrednio do nadawcy.</p>',
				'footer' => 'Wiadomość z formularza serwisu TSL Manager.',
			)
		),
		array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' )
	);
}

/**
 * Rejestracja na wydarzenie: zgłoszenie do organizatora + potwierdzenie dla uczestnika.
 *
 * @param array   $data   Dane.
 * @param WP_Post $post   Wydarzenie.
 * @param string  $source Strona źródłowa.
 */
function tsl_form_send_wydarzenie( $data, $post, $source ) {
	if ( tsl_event_post_type() !== $post->post_type || tsl_event_is_past( $post ) ) {
		return false;
	}
	$when  = tsl_event_when( $post );
	$where = tsl_event_where( $post );

	$organizer = tsl_nl_mail(
		tsl_form_recipients( 'wydarzenie' ),
		'[Rejestracja] ' . get_the_title( $post ) . ' — ' . $data['name'],
		tsl_nl_layout(
			array(
				'title'  => 'Nowa rejestracja na wydarzenie',
				'body'   => tsl_form_table(
					array(
						'Wydarzenie'      => esc_html( get_the_title( $post ) ),
						'Termin'          => esc_html( $when ),
						'Imię i nazwisko' => tsl_form_value( $data['name'] ),
						'E-mail'          => '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>',
						'Firma'           => tsl_form_value( $data['company'] ),
						'Stanowisko'      => tsl_form_value( $data['role'] ),
						'Pytanie'         => tsl_form_value( $data['question'] ),
					),
					$post,
					$source
				),
				'footer' => 'Rejestracja z serwisu TSL Manager.',
			)
		),
		array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' )
	);

	if ( $organizer ) {
		tsl_nl_mail(
			$data['email'],
			'Potwierdzenie rejestracji: ' . get_the_title( $post ),
			tsl_nl_layout(
				array(
					'title'  => 'Dziękujemy za rejestrację',
					'body'   => '<p>Dzień dobry,</p><p>potwierdzamy rejestrację na wydarzenie <strong>' . esc_html( get_the_title( $post ) ) . '</strong>.</p>'
						. tsl_form_table(
							array(
								'Termin'  => esc_html( $when ),
								'Miejsce' => esc_html( $where ),
								'Szczegóły' => '<a href="' . esc_url( get_permalink( $post ) ) . '">strona wydarzenia</a>',
							)
						)
						. '<p style="margin-top:20px;">Szczegóły dostępu prześlemy przed wydarzeniem. Jeśli nie możesz wziąć udziału, odpowiedz na tę wiadomość.</p>',
					'footer' => esc_html( tsl_nl_setting( 'publisher' ) ),
				)
			),
			array( 'Reply-To: ' . implode( ', ', tsl_form_recipients( 'wydarzenie' ) ) )
		);
	}
	return $organizer;
}

/**
 * Pobranie materiału: link na e-mail zgłaszającego + informacja dla redakcji.
 *
 * @param array   $data   Dane.
 * @param WP_Post $post   Raport lub narzędzie.
 * @param string  $source Strona źródłowa.
 */
function tsl_form_send_pobranie( $data, $post, $source ) {
	$file_id = (int) tsl_field( 'tsl_file', $post );
	$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
	if ( ! $url ) {
		return false;
	}

	$user = tsl_nl_mail(
		$data['email'],
		'Twój materiał: ' . get_the_title( $post ),
		tsl_nl_layout(
			array(
				'title'  => get_the_title( $post ),
				'body'   => '<p>Dzień dobry,</p><p>dziękujemy za zainteresowanie. Materiał pobierzesz tutaj:</p>'
					. '<p style="margin:28px 0;"><a href="' . esc_url( $url ) . '" style="background:#B8734A;color:#0B1D33;padding:14px 24px;border-radius:4px;font-weight:700;text-decoration:none;display:inline-block;">Pobierz materiał</a></p>'
					. '<p>Powiązane treści i aktualizacje znajdziesz na <a href="' . esc_url( get_permalink( $post ) ) . '">stronie materiału</a>.</p>',
				'footer' => esc_html( tsl_nl_setting( 'publisher' ) ),
			)
		)
	);

	if ( $user ) {
		$kind = 'raport' === $post->post_type ? 'raport' : 'narzędzie';
		tsl_nl_mail(
			tsl_form_recipients( 'pobranie' ),
			'[Pobranie] ' . get_the_title( $post ) . ' — ' . $data['email'],
			tsl_nl_layout(
				array(
					'title'  => 'Pobrano ' . $kind,
					'body'   => tsl_form_table(
						array(
							'E-mail'          => '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>',
							'Imię i nazwisko' => tsl_form_value( $data['name'] ),
							'Firma'           => tsl_form_value( $data['company'] ),
							'Stanowisko'      => tsl_form_value( $data['role'] ),
						),
						$post,
						$source
					),
					'footer' => 'Informacja z serwisu TSL Manager.',
				)
			),
			array( 'Reply-To: ' . $data['email'] )
		);
	}
	return $user;
}

/**
 * Ustawienia adresatów i klauzuli informacyjnej.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'tsl_forms',
			'tsl_forms',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $input ) {
					$out = array();
					foreach ( array( 'kontakt_redakcja', 'kontakt_partnerstwo', 'kontakt_wydarzenia', 'kontakt_inne', 'wydarzenie', 'pobranie' ) as $key ) {
						$emails      = array_filter( array_map( 'trim', explode( ',', (string) ( $input[ $key ] ?? '' ) ) ), 'is_email' );
						$out[ $key ] = implode( ', ', $emails );
					}
					$out['privacy_text'] = sanitize_textarea_field( $input['privacy_text'] ?? '' );
					return $out;
				},
			)
		);
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Formularze TSL', 'Formularze TSL', 'manage_options', 'tsl-forms', 'tsl_forms_render_settings' );
	}
);

/**
 * Strona ustawień formularzy.
 */
function tsl_forms_render_settings() {
	$s      = (array) get_option( 'tsl_forms', array() );
	$fields = array(
		'kontakt_redakcja'    => array( 'Kontakt — sprawy redakcyjne', 'Też adres domyślny dla pozostałych tematów kontaktu.' ),
		'kontakt_partnerstwo' => array( 'Kontakt — partnerstwa', '' ),
		'kontakt_wydarzenia'  => array( 'Kontakt — wydarzenia', '' ),
		'kontakt_inne'        => array( 'Kontakt — inne sprawy', '' ),
		'wydarzenie'          => array( 'Rejestracje na wydarzenia', '' ),
		'pobranie'            => array( 'Pobrania raportów i narzędzi', 'Informacja o każdym pobraniu za e-mail.' ),
	);
	?>
	<div class="wrap">
		<h1>Formularze TSL</h1>
		<p>Zgłoszenia z formularzy nie są zapisywane w serwisie — trafiają wyłącznie e-mailem na poniższe adresy. Kilka adresów oddziel przecinkami. Puste pole = adres administratora (<?php echo esc_html( get_option( 'admin_email' ) ); ?>).</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'tsl_forms' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $f ) : ?>
					<tr>
						<th scope="row"><label for="tsl-f-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
						<td>
							<input class="regular-text" type="text" id="tsl-f-<?php echo esc_attr( $key ); ?>" name="tsl_forms[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s[ $key ] ?? '' ); ?>" placeholder="redakcja@twojadomena.pl">
							<?php if ( $f[1] ) : ?>
								<p class="description"><?php echo esc_html( $f[1] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="tsl-f-privacy">Informacja o przetwarzaniu danych</label></th>
					<td>
						<textarea class="large-text" rows="3" id="tsl-f-privacy" name="tsl_forms[privacy_text]"><?php echo esc_textarea( $s['privacy_text'] ?? '' ); ?></textarea>
						<p class="description">Wyświetlana pod każdym formularzem. Puste pole = tekst domyślny: „<?php echo esc_html( tsl_form_privacy_text() ); ?>” <strong>Treść powinien zatwierdzić prawnik.</strong></p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Zapisz' ); ?>
		</form>
		<p>Wysyłka korzysta z poczty skonfigurowanej w <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wydanie&page=tsl-newsletter-settings' ) ); ?>">Newsletter → Ustawienia</a> (SMTP).</p>
	</div>
	<?php
}
