<?php
/**
 * Moduły strony głównej: włączanie i wyłączanie w Wygląd → Strona główna.
 *
 * Wyłączony moduł nie jest wyświetlany. Włączony moduł bez treści
 * (rzeczywistej ani demonstracyjnej) też nie jest wyświetlany.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moduły w kolejności na stronie: klucz => [nazwa, opis].
 *
 * @return array<string, array{0:string,1:string}>
 */
function tsl_home_modules() {
	return array(
		'pilny'      => array( 'Pasek pilnego sygnału', 'Krytyczny RiskBrief z ostatnich 7 dni.' ),
		'hero'       => array( 'Temat tygodnia (okładka)', 'Przypięty artykuł albo najnowszy materiał. Wyłączony: w nagłówku nazwa serwisu.' ),
		'riskbrief'  => array( 'RiskBrief TSL', 'Pas 5 najnowszych sygnałów ryzyka pod okładką.' ),
		'filary'     => array( 'Sześć filarów', 'Spis sześciu działów z obietnicą i obszarami.' ),
		'najnowsze'  => array( 'Najnowsze analizy', 'Po jednym najnowszym materiale z każdego filaru.' ),
		'decyzja'    => array( 'Decyzja pod lupą', 'Materiał flagowy formatu i dwa kolejne.' ),
		'narzedzia'  => array( 'Narzędzia dla menedżera', 'Checklisty, formularze, kalkulatory, procedury.' ),
		'kalkulator' => array( 'Ile to kosztuje + kalkulator kosztu kilometra', 'Materiał „Ile to naprawdę kosztuje?” i wejście do kalkulatora.' ),
		'transport'  => array( 'Transport i operacje (zakładki)', 'Materiały według rodzaju transportu.' ),
		'pierwsze24' => array( 'Szkoda pod lupą / Pierwsze 24 godziny', 'Materiał o incydencie i powiązana procedura.' ),
		'newsletter' => array( 'Newsletter', 'Zapis na newsletter i RiskBrief na e-mail.' ),
		'raporty'    => array( 'Raporty i dane', 'Najnowszy raport.' ),
		'wydarzenia' => array( 'Wydarzenia', 'Najbliższe (a gdy ich brak — ostatnie) wydarzenia.' ),
		'eksperci'   => array( 'Eksperci', 'Profile ekspertów.' ),
		'partnerzy'  => array( 'Partnerzy projektu', 'Partnerzy z jawnie opisaną rolą.' ),
	);
}

/**
 * Czy moduł strony głównej jest włączony (domyślnie wszystkie).
 *
 * @param string $key Klucz modułu.
 */
function tsl_home_on( $key ) {
	$off = (array) get_option( 'tsl_home_modules_off', array() );
	return ! in_array( $key, $off, true );
}

add_action(
	'admin_menu',
	function () {
		add_theme_page( 'Strona główna — moduły', 'Strona główna', 'edit_theme_options', 'tsl-home-modules', 'tsl_home_modules_page' );
	}
);

add_action(
	'admin_init',
	function () {
		if ( empty( $_POST['tsl_home_modules_save'] ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		check_admin_referer( 'tsl_home_modules' );
		$on  = isset( $_POST['tsl_on'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['tsl_on'] ) ) : array();
		$off = array_values( array_diff( array_keys( tsl_home_modules() ), $on ) );
		update_option( 'tsl_home_modules_off', $off );
		wp_safe_redirect( add_query_arg( array( 'page' => 'tsl-home-modules', 'zapisano' => 1 ), admin_url( 'themes.php' ) ) );
		exit;
	}
);

/**
 * Ekran Wygląd → Strona główna.
 */
function tsl_home_modules_page() {
	?>
	<div class="wrap">
		<h1>Strona główna — moduły</h1>
		<?php if ( ! empty( $_GET['zapisano'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p>Zapisano. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Zobacz stronę główną</a>.</p></div>
		<?php endif; ?>
		<p>Moduły są wyświetlane w tej kolejności. Moduł bez treści nie pojawia się na stronie, nawet gdy jest włączony.</p>
		<form method="post">
			<?php wp_nonce_field( 'tsl_home_modules' ); ?>
			<input type="hidden" name="tsl_home_modules_save" value="1">
			<table class="form-table" role="presentation"><tbody>
				<?php foreach ( tsl_home_modules() as $key => $module ) : ?>
					<tr>
						<th scope="row"><label for="tsl-on-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $module[0] ); ?></label></th>
						<td>
							<input type="checkbox" id="tsl-on-<?php echo esc_attr( $key ); ?>" name="tsl_on[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( tsl_home_on( $key ) ); ?>>
							<span class="description"><?php echo esc_html( $module[1] ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody></table>
			<?php submit_button( 'Zapisz moduły' ); ?>
		</form>
	</div>
	<?php
}
