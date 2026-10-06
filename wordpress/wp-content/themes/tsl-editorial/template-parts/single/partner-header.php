<?php
/**
 * Partner: obszar kompetencji i strona internetowa.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$area    = tsl_field( 'tsl_area', $post_obj );
$website = tsl_field( 'tsl_website', $post_obj );
?>
<?php if ( $area ) : ?>
	<p class="expert-role"><?php echo esc_html( $area ); ?></p>
<?php endif; ?>
<?php if ( $website ) : ?>
	<a class="link-more" href="<?php echo esc_url( $website ); ?>" rel="noopener sponsored">Strona partnera<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
<?php endif; ?>
