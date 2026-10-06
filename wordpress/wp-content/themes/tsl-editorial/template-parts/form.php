<?php
/**
 * Formularz (kontakt, rejestracja na wydarzenie, pobranie materiału).
 *
 * Pola pochodzą z definicji we wtyczce (tsl_form_definitions), więc walidacja
 * w przeglądarce i na serwerze opiera się na tym samym opisie.
 *
 * Argumenty: type (kontakt|wydarzenie|pobranie), post (WP_Post|null),
 * values (wartości początkowe), title (nagłówek), id (kotwica).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'tsl_form_definitions' ) ) {
	return;
}

$form_type = $args['type'];
$defs      = tsl_form_definitions();
$def       = $defs[ $form_type ];
$form_post = $args['post'] ?? null;
$values    = $args['values'] ?? array();
$uid       = wp_unique_id( 'form-' );
?>
<form class="tsl-form" data-tsl-form data-endpoint="<?php echo esc_url( rest_url( 'tsl/v1/form/' . $form_type ) ); ?>" novalidate <?php echo ! empty( $args['id'] ) ? 'id="' . esc_attr( $args['id'] ) . '"' : ''; ?>>
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h2 class="form-title"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php endif; ?>

	<input type="hidden" name="post_id" value="<?php echo esc_attr( $form_post ? $form_post->ID : 0 ); ?>">
	<p class="hp-field" aria-hidden="true"><label>Strona www <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>

	<div class="form-grid">
		<?php foreach ( $def['fields'] as $key => $field ) : ?>
			<?php
			$id    = $uid . '-' . $key;
			$wide  = in_array( $field['type'], array( 'textarea', 'select' ), true ) ? ' form-field-wide' : '';
			$value = $values[ $key ] ?? '';
			$req   = ! empty( $field['required'] );
			?>
			<div class="form-field<?php echo esc_attr( $wide ); ?>" data-field="<?php echo esc_attr( $key ); ?>">
				<label class="field-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $req ? ' <span class="req" aria-hidden="true">*</span>' : ''; ?></label>
				<?php if ( 'textarea' === $field['type'] ) : ?>
					<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="5" <?php echo $req ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
				<?php elseif ( 'select' === $field['type'] ) : ?>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>" <?php echo $req ? 'required' : ''; ?>>
						<?php foreach ( $field['options'] as $opt => $label ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $value, $opt ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input id="<?php echo esc_attr( $id ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo ! empty( $field['autocomplete'] ) ? 'autocomplete="' . esc_attr( $field['autocomplete'] ) . '"' : ''; ?> <?php echo $req ? 'required' : ''; ?>>
				<?php endif; ?>
				<p class="field-error" id="<?php echo esc_attr( $id ); ?>-error" aria-live="polite"></p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="form-consents">
		<p class="consent" data-field="privacy">
			<input id="<?php echo esc_attr( $uid ); ?>-privacy" type="checkbox" name="privacy" value="1" required>
			<label for="<?php echo esc_attr( $uid ); ?>-privacy"><?php echo esc_html( tsl_form_privacy_text() ); ?> <a href="<?php echo esc_url( tsl_page_url( 'polityka-prywatnosci' ) ); ?>">Polityka prywatności</a>. <span class="req" aria-hidden="true">*</span></label>
		</p>
		<p class="field-error" id="<?php echo esc_attr( $uid ); ?>-privacy-error" aria-live="polite"></p>
		<p class="consent">
			<input id="<?php echo esc_attr( $uid ); ?>-newsletter" type="checkbox" name="newsletter" value="1">
			<label for="<?php echo esc_attr( $uid ); ?>-newsletter">Chcę też otrzymywać newsletter TSL Manager (opcjonalnie; zapis potwierdzisz linkiem z e-maila).</label>
		</p>
	</div>

	<div class="form-actions">
		<button class="btn btn-primary" type="submit"><?php echo esc_html( $def['submit'] ); ?><?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		<p class="form-status" role="status" aria-live="polite"></p>
	</div>
</form>
