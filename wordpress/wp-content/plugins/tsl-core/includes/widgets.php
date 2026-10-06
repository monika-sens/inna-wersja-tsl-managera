<?php
/**
 * Widżety zewnętrzne w treści narzędzi.
 *
 *  [tsl_etoll]                       oficjalny kalkulator trasy e-TOLL
 *  [tsl_kalkulator klucz="…"]        gotowy kalkulator Cost Calculator Builder
 *
 * Gotowe kalkulatory TSL (includes/data/ccb-kalkulatory.json) instalują się
 * same w Cost Calculator Builder, gdy wtyczka jest włączona. Kalkulatory
 * zbudowane ręcznie wstawia się blokiem CCB lub kodem [stm-calc id="…"].
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Oficjalny kalkulator trasy e-TOLL.
 *
 * Domyślnie karta z przyciskiem otwierającym kalkulator w nowej karcie —
 * serwisy administracji publicznej zwykle blokują osadzanie w ramkach
 * (iframe). Jeśli osadzanie działa, użyj [tsl_etoll osadz="tak"].
 *
 * Atrybuty:
 *  url    adres kalkulatora (domyślnie ustawienie „Adres kalkulatora e-TOLL”),
 *  osadz  „tak” = próba osadzenia w ramce (z przyciskiem awaryjnym),
 *  wysokosc  wysokość ramki w pikselach (domyślnie 760).
 *
 * @param array $atts Atrybuty.
 */
function tsl_etoll_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'url'      => get_option( 'tsl_etoll_url', 'https://etoll.gov.pl/kalkulator-trasy-w-e-toll/' ),
			'osadz'    => 'nie',
			'wysokosc' => 760,
		),
		$atts,
		'tsl_etoll'
	);
	$url = esc_url( $atts['url'] );

	ob_start();
	?>
	<div class="tsl-widget tsl-etoll">
		<div class="tsl-etoll-head">
			<p class="tsl-widget-kicker">Oficjalne narzędzie · e-TOLL</p>
			<p class="tsl-widget-title">Kalkulator trasy e-TOLL</p>
			<p>Sprawdź szacunkowy koszt przejazdu płatnymi odcinkami dróg w Polsce: wybierz typ pojazdu, punkty trasy i wariant (najszybsza lub najkrótsza). Kalkulator działa w oficjalnym serwisie e-TOLL — wyniki mają charakter szacunkowy.</p>
		</div>
		<?php if ( 'tak' === $atts['osadz'] ) : ?>
			<div class="tsl-etoll-frame">
				<iframe src="<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url wyżej. ?>" title="Kalkulator trasy e-TOLL" loading="lazy" style="height:<?php echo (int) $atts['wysokosc']; ?>px" referrerpolicy="no-referrer"></iframe>
			</div>
			<p class="tsl-etoll-fallback">Kalkulator się nie wyświetla? <a href="<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank" rel="noopener" data-track="Narzędzie|e-TOLL|otwarcie">Otwórz go w nowej karcie</a>.</p>
		<?php else : ?>
			<p><a class="btn btn-accent" href="<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank" rel="noopener" data-track="Narzędzie|e-TOLL|otwarcie">Otwórz kalkulator e-TOLL</a></p>
		<?php endif; ?>
		<p class="tsl-widget-note">Narzędzie zewnętrzne — serwis TSL Manager nie odpowiada za jego działanie ani wyniki. Pełny koszt trasy (paliwo, czas pracy, puste przebiegi) policzysz w <a href="<?php echo esc_url( home_url( '/narzedzia/kalkulatory/' ) ); ?>">kalkulatorach TSL Manager</a>.</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'tsl_etoll', 'tsl_etoll_shortcode' );

/**
 * Adres kalkulatora e-TOLL (Ustawienia → Ogólne), żeby zmiana adresu
 * po stronie e-TOLL nie wymagała edycji każdego materiału.
 */
add_action(
	'admin_init',
	function () {
		register_setting(
			'general',
			'tsl_etoll_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => 'https://etoll.gov.pl/kalkulator-trasy-w-e-toll/',
			)
		);
		add_settings_field(
			'tsl_etoll_url',
			'Adres kalkulatora e-TOLL',
			function () {
				echo '<input type="url" class="regular-text" name="tsl_etoll_url" value="' . esc_attr( get_option( 'tsl_etoll_url', 'https://etoll.gov.pl/kalkulator-trasy-w-e-toll/' ) ) . '">';
				echo '<p class="description">Link używany przez kod [tsl_etoll]. Wklej dokładny adres strony kalkulatora z serwisu e-TOLL.</p>';
			},
			'general'
		);
	}
);

/**
 * Gotowe kalkulatory TSL zapisane w formacie importu Cost Calculator Builder.
 *
 * @return array Lista kalkulatorów (każdy z kluczem tsl_key).
 */
function tsl_ccb_definitions() {
	$file = TSL_CORE_DIR . 'includes/data/ccb-kalkulatory.json';
	$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return is_array( $data ) && ! empty( $data['calculators'] ) ? $data['calculators'] : array();
}

/**
 * Czy Cost Calculator Builder jest włączony.
 */
function tsl_ccb_active() {
	return class_exists( '\cBuilder\Classes\CCBExportImport' ) && post_type_exists( 'cost-calc' );
}

/**
 * ID kalkulatora CCB o danym kluczu TSL (0 = brak).
 *
 * @param string $key Klucz, np. „koszt-kilometra”.
 */
function tsl_ccb_calculator_id( $key ) {
	$ids = get_posts(
		array(
			'post_type'      => 'cost-calc',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => 'tsl_ccb_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Instaluje w CCB brakujące kalkulatory TSL. Istniejących nie nadpisuje —
 * zmiany redakcji w kreatorze CCB zostają.
 *
 * @return int[] Klucz => ID kalkulatora.
 */
function tsl_ccb_install() {
	$result = array();
	if ( ! tsl_ccb_active() ) {
		return $result;
	}
	if ( empty( get_option( 'ccb_general_settings' ) ) && class_exists( '\cBuilder\Classes\CCBSettingsData' ) ) {
		update_option( 'ccb_general_settings', \cBuilder\Classes\CCBSettingsData::general_settings_data() );
	}
	foreach ( tsl_ccb_definitions() as $calc ) {
		$key = $calc['tsl_key'] ?? '';
		if ( ! $key ) {
			continue;
		}
		$id = tsl_ccb_calculator_id( $key );
		if ( ! $id ) {
			$before = get_posts( array( 'post_type' => 'cost-calc', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1 ) );
			$ignore = array();
			if ( class_exists( '\\cBuilder\\Classes\\CCBSettingsData' ) ) {
				$calc['ccb_form_settings'] = array_replace_recursive( \cBuilder\Classes\CCBSettingsData::settings_data(), (array) ( $calc['ccb_form_settings'] ?? array() ) );
			}
			\cBuilder\Classes\CCBExportImport::addCalculatorData( $calc, $ignore, true );
			$after = get_posts( array( 'post_type' => 'cost-calc', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1 ) );
			$new   = array_values( array_diff( $after, $before ) );
			if ( $new ) {
				$id = (int) $new[0];
				update_post_meta( $id, 'tsl_ccb_key', $key );
				tsl_ccb_use_brand_preset( $id );
			}
		}
		$result[ $key ] = $id;
	}
	return $result;
}

/**
 * Jeden motyw wyglądu „TSL Manager” zamiast kopii „Saved N” tworzonej przez
 * CCB przy każdym imporcie.
 *
 * @param int $calc_id ID kalkulatora.
 */
function tsl_ccb_use_brand_preset( $calc_id ) {
	$gen = '\\cBuilder\\Classes\\Appearance\\Presets\\CCBPresetGenerator';
	if ( ! class_exists( $gen ) ) {
		return;
	}
	$presets = (array) $gen::get_static_preset_from_db();
	$created = get_post_meta( $calc_id, 'ccb_calc_preset_idx', true );
	$brand   = '';
	foreach ( $presets as $key => $preset ) {
		if ( 'TSL Manager' === ( $preset['title'] ?? '' ) ) {
			$brand = $key;
			break;
		}
	}
	if ( ! $brand && isset( $presets[ $created ] ) ) {
		$presets[ $created ]['title'] = 'TSL Manager';
		$gen::update_presets( $presets );
		return;
	}
	if ( $brand && $created !== $brand ) {
		$gen::update_preset_key( $calc_id, $brand );
		$in_use = get_posts( array( 'post_type' => 'cost-calc', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => 1, 'meta_key' => 'ccb_calc_preset_idx', 'meta_value' => $created ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( isset( $presets[ $created ] ) && ! $in_use ) {
			unset( $presets[ $created ] );
			$gen::update_presets( $presets );
		}
	}
}

/**
 * Instalacja po włączeniu CCB, po aktualizacji pliku z kalkulatorami albo gdy
 * gotowy kalkulator został usunięty z listy kalkulatorów.
 */
add_action(
	'admin_init',
	function () {
		if ( ! tsl_ccb_active() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$file    = TSL_CORE_DIR . 'includes/data/ccb-kalkulatory.json';
		$version = file_exists( $file ) ? md5_file( $file ) : '';
		$missing = false;
		foreach ( tsl_ccb_definitions() as $calc ) {
			$missing = $missing || ! tsl_ccb_calculator_id( $calc['tsl_key'] ?? '' );
		}
		if ( $missing || get_option( 'tsl_ccb_installed' ) !== $version ) {
			tsl_ccb_install();
			update_option( 'tsl_ccb_installed', $version, false );
		}
	}
);

/**
 * Gotowy kalkulator TSL: [tsl_kalkulator klucz="koszt-kilometra"].
 *
 * Nie wymaga znajomości ID — działa tak samo na kopii testowej i na serwerze.
 * Bez CCB czytelnik nie widzi nic, a redaktor widzi wskazówkę.
 *
 * @param array $atts Atrybuty.
 */
function tsl_kalkulator_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'klucz' => '' ), $atts, 'tsl_kalkulator' );
	$id   = tsl_ccb_active() ? tsl_ccb_calculator_id( sanitize_key( $atts['klucz'] ) ) : 0;
	if ( ! $id ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="tsl-demo-note">Kalkulator „' . esc_html( $atts['klucz'] ) . '” pojawi się tutaj po włączeniu wtyczki Cost Calculator Builder (Wtyczki → Zainstalowane). Ten komunikat widzi tylko redakcja.</p>';
		}
		return '';
	}
	return '<div class="tsl-calc" data-track-module="Kalkulator: ' . esc_attr( $atts['klucz'] ) . '">'
		. do_shortcode( '[stm-calc id="' . $id . '"]' )
		. '<p class="tsl-widget-note tsl-calc-note"><strong>Wartości domyślne są przykładowe.</strong> Wynik jest szacunkiem i nie zastępuje kalkulacji controllingowej w Twojej firmie.</p></div>';
}
add_shortcode( 'tsl_kalkulator', 'tsl_kalkulator_shortcode' );
