<?php
/**
 * Analityka niezależna od narzędzia (rozdz. 4, 17 i 20 mapy serwisu).
 *
 * Serwis opisuje zdarzenia jeden raz (atrybuty data-track w motywie
 * i funkcja window.tslTrack), a ten moduł przekazuje je do wybranych narzędzi:
 *  - Matomo — kod dodaje wtyczka „Matomo Analytics” albo ten moduł
 *    (własna instalacja / Matomo Cloud: adres + ID strony),
 *  - Google Analytics 4 — po zgodzie użytkownika (baner cookies).
 * Zmiana narzędzia = zmiana ustawień w Ustawienia → Analityka TSL.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ustawienia analityki.
 *
 * @param string $key Klucz.
 * @return mixed
 */
function tsl_analytics_setting( $key ) {
	$defaults = array(
		'matomo_mode'    => 'plugin', // off | plugin | own.
		'matomo_url'     => '',
		'matomo_site'    => '1',
		'matomo_consent' => 0,        // 1 = Matomo dopiero po zgodzie.
		'ga4_id'         => '',
	);
	$settings = wp_parse_args( (array) get_option( 'tsl_analytics', array() ), $defaults );
	return $settings[ $key ] ?? null;
}

/**
 * Czy potrzebny jest baner zgody (GA4 albo Matomo ustawione „po zgodzie”).
 */
function tsl_analytics_needs_consent() {
	return (bool) tsl_analytics_setting( 'ga4_id' ) || ( 'off' !== tsl_analytics_setting( 'matomo_mode' ) && tsl_analytics_setting( 'matomo_consent' ) );
}

/**
 * Kod pomiaru w <head>. Nic nie jest wysyłane z panelu administracyjnego
 * ani dla zalogowanych redaktorów (nie zawyżają statystyk).
 */
add_action(
	'wp_head',
	function () {
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			echo "<script>window.tslAnalytics={disabled:true};</script>\n";
			return;
		}

		$config = array(
			'matomo'         => 'off' !== tsl_analytics_setting( 'matomo_mode' ),
			'matomoConsent'  => (bool) tsl_analytics_setting( 'matomo_consent' ),
			'ga4'            => (string) tsl_analytics_setting( 'ga4_id' ),
			'needsConsent'   => tsl_analytics_needs_consent(),
			'pageType'       => tsl_analytics_page_type(),
		);
		echo '<script>window.tslAnalytics=' . wp_json_encode( $config ) . ";</script>\n";

		// Matomo „po zgodzie” także przy kodzie z wtyczki Matomo: kolejka _paq wstrzymuje pomiar do zgody.
		if ( 'plugin' === tsl_analytics_setting( 'matomo_mode' ) && tsl_analytics_setting( 'matomo_consent' ) ) {
			echo "<script>var _paq=window._paq=window._paq||[];_paq.push(['requireConsent']);</script>\n";
		}

		// Matomo z własnej instalacji lub Matomo Cloud (tryb „plugin”: kod dodaje wtyczka Matomo).
		if ( 'own' === tsl_analytics_setting( 'matomo_mode' ) && tsl_analytics_setting( 'matomo_url' ) ) {
			$url  = trailingslashit( esc_url_raw( tsl_analytics_setting( 'matomo_url' ) ) );
			$site = (int) tsl_analytics_setting( 'matomo_site' );
			?>
<script>
var _paq = window._paq = window._paq || [];
_paq.push(['disableCookies']);
<?php if ( tsl_analytics_setting( 'matomo_consent' ) ) : ?>
_paq.push(['requireConsent']);
<?php endif; ?>
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
(function(){var u=<?php echo wp_json_encode( $url ); ?>;_paq.push(['setTrackerUrl',u+'matomo.php']);_paq.push(['setSiteId',<?php echo (int) $site; ?>]);
var d=document,g=d.createElement('script'),s=d.getElementsByTagName('script')[0];g.async=true;g.src=u+'matomo.js';s.parentNode.insertBefore(g,s);})();
</script>
			<?php
		}
	},
	2
);

/**
 * Typ strony (wymiar raportów: które szablony prowadzą do działania).
 */
function tsl_analytics_page_type() {
	if ( is_front_page() ) {
		return 'strona-glowna';
	}
	if ( is_category() ) {
		return function_exists( 'tsl_is_pillar' ) && tsl_is_pillar( get_queried_object() ) ? 'filar' : 'podkategoria';
	}
	if ( is_search() ) {
		return 'wyszukiwarka';
	}
	if ( is_singular() ) {
		return 'post' === get_post_type() ? 'artykul' : get_post_type();
	}
	if ( is_post_type_archive() ) {
		return 'archiwum-' . get_query_var( 'post_type' );
	}
	if ( is_404() ) {
		return '404';
	}
	return 'inne';
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'tsl-analytics', plugins_url( 'assets/analytics.js', TSL_CORE_DIR . 'tsl-core.php' ), array(), TSL_CORE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);

/**
 * Baner zgody — tylko gdy jakieś narzędzie jej wymaga.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! tsl_analytics_needs_consent() || ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ) {
			return;
		}
		$policy = get_page_by_path( 'polityka-cookies' );
		?>
		<div class="tsl-consent" id="tsl-consent" role="dialog" aria-live="polite" aria-label="Zgoda na statystyki" hidden>
			<p>Używamy narzędzi statystycznych, aby wiedzieć, które materiały i narzędzia są najbardziej przydatne. Czy zgadzasz się na pliki cookies statystyczne? <?php if ( $policy ) : ?><a href="<?php echo esc_url( get_permalink( $policy ) ); ?>">Polityka cookies</a><?php endif; ?> <span data-consent-state></span><br>Wybór możesz zmienić w każdej chwili: „Ustawienia prywatności” w stopce.</p>
			<div class="tsl-consent-actions">
				<button type="button" class="btn btn-ghost" data-consent="deny">Odrzuć</button>
				<button type="button" class="btn btn-primary" data-consent="grant">Akceptuję</button>
			</div>
		</div>
		<style>
		.tsl-consent{position:fixed;left:16px;right:16px;bottom:16px;z-index:200;max-width:720px;margin:0 auto;display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center;padding:18px 20px;background:#fff;color:#2B2F36;border:1px solid #D5D8DD;border-radius:8px;box-shadow:0 12px 40px rgba(11,29,51,.18);font-size:14px;line-height:1.5}
		.tsl-consent p{margin:0;flex:1 1 320px}.tsl-consent-actions{display:flex;gap:8px}
		</style>
		<?php
	}
);

/*
 * Ustawienia → Analityka TSL.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'tsl_analytics',
			'tsl_analytics',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $input ) {
					$ga4 = strtoupper( sanitize_text_field( $input['ga4_id'] ?? '' ) );
					return array(
						'matomo_mode'    => in_array( $input['matomo_mode'] ?? '', array( 'off', 'plugin', 'own' ), true ) ? $input['matomo_mode'] : 'plugin',
						'matomo_url'     => esc_url_raw( $input['matomo_url'] ?? '' ),
						'matomo_site'    => (string) max( 1, absint( $input['matomo_site'] ?? 1 ) ),
						'matomo_consent' => empty( $input['matomo_consent'] ) ? 0 : 1,
						'ga4_id'         => preg_match( '/^G-[A-Z0-9]{4,}$/', $ga4 ) ? $ga4 : '',
					);
				},
			)
		);
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Analityka TSL', 'Analityka TSL', 'manage_options', 'tsl-analytics', 'tsl_analytics_render_settings' );
	}
);

/**
 * Strona ustawień analityki.
 */
function tsl_analytics_render_settings() {
	$mode           = tsl_analytics_setting( 'matomo_mode' );
	$plugin_active  = defined( 'MATOMO_ANALYTICS_FILE' ) || class_exists( '\\WpMatomo' );
	?>
	<div class="wrap">
		<h1>Analityka TSL</h1>
		<p>Serwis mierzy zdarzenia jednolicie (moduły strony głównej, wyszukiwarka, pobrania, formularze, newsletter, narzędzia) i przekazuje je do wybranych narzędzi. Zmiana narzędzia nie wymaga zmian w kodzie. Wizyty zalogowanych redaktorów nie są liczone.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'tsl_analytics' ); ?>
			<h2>Matomo</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Źródło kodu Matomo</th>
					<td>
						<?php
						$modes = array(
							'plugin' => 'Wtyczka „Matomo Analytics” (zalecane na start)',
							'own'    => 'Własna instalacja lub Matomo Cloud — podaję adres poniżej',
							'off'    => 'Nie używam Matomo',
						);
						foreach ( $modes as $value => $label ) {
							echo '<label style="display:block;margin-bottom:6px"><input type="radio" name="tsl_analytics[matomo_mode]" value="' . esc_attr( $value ) . '" ' . checked( $mode, $value, false ) . '> ' . esc_html( $label ) . '</label>';
						}
						?>
						<p class="description">
							<?php if ( 'plugin' === $mode && ! $plugin_active ) : ?>
								<strong>Wtyczka Matomo nie jest zainstalowana.</strong> Zainstaluj ją: Wtyczki → Dodaj nową → „Matomo Analytics”, a w jej ustawieniach włącz śledzenie i wyłącz pliki cookies (instrukcja: docs/ANALITYKA.md).
							<?php elseif ( 'plugin' === $mode ) : ?>
								Wtyczka Matomo jest aktywna.
							<?php endif; ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tsl-a-url">Adres Matomo</label></th>
					<td><input class="regular-text" type="url" id="tsl-a-url" name="tsl_analytics[matomo_url]" value="<?php echo esc_attr( tsl_analytics_setting( 'matomo_url' ) ); ?>" placeholder="https://statystyki.twojadomena.pl/"><p class="description">Tylko dla własnej instalacji lub Matomo Cloud.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="tsl-a-site">ID strony w Matomo</label></th>
					<td><input class="small-text" type="number" min="1" id="tsl-a-site" name="tsl_analytics[matomo_site]" value="<?php echo esc_attr( tsl_analytics_setting( 'matomo_site' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Zgoda na Matomo</th>
					<td><label><input type="checkbox" name="tsl_analytics[matomo_consent]" value="1" <?php checked( tsl_analytics_setting( 'matomo_consent' ), 1 ); ?>> Uruchamiaj Matomo dopiero po zgodzie w banerze</label>
					<p class="description">Domyślnie Matomo działa bez plików cookies. Czy w tym trybie baner nie jest potrzebny — <strong>do potwierdzenia przez prawnika</strong>. Zaznacz, jeśli prawnik zaleci zgodę.</p></td>
				</tr>
			</table>

			<h2>Google Analytics 4</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="tsl-a-ga4">Identyfikator pomiaru</label></th>
					<td><input class="regular-text" type="text" id="tsl-a-ga4" name="tsl_analytics[ga4_id]" value="<?php echo esc_attr( tsl_analytics_setting( 'ga4_id' ) ); ?>" placeholder="G-XXXXXXXXXX">
					<p class="description">Puste = GA4 wyłączone. Po wpisaniu identyfikatora serwis pokaże baner zgody, a GA4 uruchomi się dopiero po kliknięciu „Akceptuję”.</p></td>
				</tr>
			</table>
			<?php submit_button( 'Zapisz' ); ?>
		</form>
	</div>
	<?php
}
