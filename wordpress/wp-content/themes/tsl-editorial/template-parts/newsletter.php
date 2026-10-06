<?php
/**
 * Pas newslettera / RiskBrief na e-mail.
 *
 * Zapis trafia do listy subskrybentów w WordPressie (wtyczka tsl-core) i wymaga
 * potwierdzenia linkiem z e-maila (podwójna zgoda). Atrybuty data-tsl-newsletter
 * i data-endpoint oraz pole-pułapka „website” są kontraktem z wtyczką.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$uid = wp_unique_id( 'nl-' );
?>
<section class="newsletter" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
	<div class="wrap newsletter-grid">
		<div class="newsletter-copy">
			<p class="newsletter-kicker">Newsletter i RiskBrief TSL</p>
			<h2 class="newsletter-title" id="<?php echo esc_attr( $uid ); ?>-title">Kluczowe zmiany w TSL — raz w tygodniu na Twoją skrzynkę</h2>
			<p class="newsletter-lead">Analizy, alerty RiskBrief i praktyczne narzędzia dla menedżerów transportu, spedycji i logistyki. Bez spamu, z możliwością wypisania się w każdej chwili.</p>
		</div>
		<form class="newsletter-form" data-tsl-newsletter data-endpoint="<?php echo esc_url( rest_url( 'tsl/v1/subscribe' ) ); ?>" action="<?php echo esc_url( tsl_page_url( 'newsletter' ) ); ?>" method="get" novalidate>
			<p class="hp-field" aria-hidden="true"><label>Strona www <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<label class="field-label" for="<?php echo esc_attr( $uid ); ?>-email">Adres e-mail</label>
			<div class="newsletter-row">
				<input id="<?php echo esc_attr( $uid ); ?>-email" type="email" name="email" autocomplete="email" placeholder="imie.nazwisko@firma.pl" required>
				<button class="btn btn-primary" type="submit">Zapisz się<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<p class="consent">
				<input id="<?php echo esc_attr( $uid ); ?>-consent" type="checkbox" required>
				<label for="<?php echo esc_attr( $uid ); ?>-consent"><?php echo esc_html( function_exists( 'tsl_nl_setting' ) ? tsl_nl_setting( 'consent_text' ) : 'Wyrażam zgodę na otrzymywanie newslettera.' ); ?> <a href="<?php echo esc_url( tsl_page_url( 'polityka-prywatnosci' ) ); ?>">Polityka prywatności</a>.</label>
			</p>
			<p class="form-status" role="status" aria-live="polite"></p>
		</form>
	</div>
</section>
