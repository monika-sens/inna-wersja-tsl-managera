<?php
/**
 * Narzędzie: pobranie i instrukcja.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$download = tsl_download_button( $post_obj, 'Pobierz narzędzie' );
$steps    = tsl_field_lines( 'tsl_steps', $post_obj );
?>
<?php tsl_checklist( $post_obj ); ?>

<?php if ( $download || $steps ) : ?>
	<section class="tool-box">
		<?php if ( $steps ) : ?>
			<p class="brief-label">Jak korzystać</p>
			<?php echo tsl_lines_html( $steps, 'ol', 'step-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php if ( $download ) : ?>
			<div class="tool-download"><?php echo $download; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<p class="tool-disclaimer">Narzędzie jest wzorem — dostosuj je do procesów i umów swojej firmy.</p>
	</section>
<?php endif; ?>
<?php tsl_download_form( $post_obj ); ?>
