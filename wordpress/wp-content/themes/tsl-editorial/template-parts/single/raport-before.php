<?php
/**
 * Raport: kluczowe wnioski zawsze publiczne + pobranie pełnej wersji (rozdz. 7.4).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$takeaways = tsl_field_lines( 'tsl_takeaways', $post_obj );
$download  = tsl_download_button( $post_obj, 'Pobierz pełny raport' );
?>
<?php if ( $takeaways || $download ) : ?>
	<section class="takeaways takeaways-report">
		<?php if ( $takeaways ) : ?>
			<p class="block-kicker">Najważniejsze w skrócie</p>
			<h2 class="takeaways-title">Kluczowe wnioski</h2>
			<?php echo tsl_lines_html( $takeaways, 'ol', 'check-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php if ( $download ) : ?>
			<div class="tool-download"><?php echo $download; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
	</section>
<?php endif; ?>
<?php tsl_download_form( $post_obj ); ?>
