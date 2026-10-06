<?php
/**
 * Artykuł: ramka „Co zrobić w poniedziałek” (rozdz. 9 — analiza menedżerska).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php $takeaways = tsl_field_lines( 'tsl_takeaways', $post_obj ); ?>
<?php if ( $takeaways ) : ?>
	<section class="takeaways">
		<p class="block-kicker">Podsumowanie</p>
		<h2 class="takeaways-title">Co zrobić w poniedziałek</h2>
		<?php echo tsl_lines_html( $takeaways, 'ol', 'check-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
<?php endif; ?>
<?php $biz_cta = function_exists( 'tsl_show_biz_cta' ) && tsl_show_biz_cta() ? trim( (string) get_post_meta( $post_obj->ID, 'tsl_biz_cta', true ) ) : ''; ?>
<?php if ( '' !== $biz_cta ) : ?>
	<aside class="biz-cta">
		<p><?php echo tsl_md_links( $biz_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</aside>
<?php endif; ?>
