<?php
/**
 * Ekspert: publikacje, narzędzia i wydarzenia (realny wkład — rozdz. 17).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$groups = array(
	'Publikacje' => array_merge( tsl_related( 'post', $post_obj ), tsl_related( 'raport', $post_obj ) ),
	'Narzędzia'  => tsl_related( 'narzedzie', $post_obj ),
	'Wydarzenia' => tsl_related( 'wydarzenie', $post_obj ),
);
?>
<?php foreach ( $groups as $heading => $items ) : ?>
	<?php if ( $items ) : ?>
		<section class="expert-work">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<div class="post-list">
				<?php foreach ( $items as $item ) : ?>
					<?php tsl_card( $item, 'row' ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
<?php endforeach; ?>
