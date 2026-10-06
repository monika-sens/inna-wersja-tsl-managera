<?php
/**
 * Wydanie newslettera w przeglądarce: sekcje automatyczne jako karty.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];

if ( ! function_exists( 'tsl_nl_issue_sections' ) ) {
	return;
}
?>
<?php foreach ( tsl_nl_issue_sections( $post_obj ) as $heading => $items ) : ?>
	<section class="expert-work">
		<h2><?php echo esc_html( $heading ); ?></h2>
		<div class="post-list">
			<?php foreach ( $items as $item ) : ?>
				<?php tsl_card( $item, 'row' ); ?>
			<?php endforeach; ?>
		</div>
	</section>
<?php endforeach; ?>
