<?php
/**
 * Partner: wspólne materiały i wydarzenia.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php $items = array_merge( tsl_related( 'post', $post_obj ), tsl_related( 'raport', $post_obj ), tsl_related( 'wydarzenie', $post_obj ) ); ?>
<?php if ( $items ) : ?>
	<section class="expert-work">
		<h2>Wspólne projekty</h2>
		<div class="post-list">
			<?php foreach ( $items as $item ) : ?>
				<?php tsl_card( $item, 'row' ); ?>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>
