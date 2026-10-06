<?php
/**
 * Wydarzenie: partnerzy i raport premierowy.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$partners = tsl_related( 'partner', $post_obj );
if ( $partners ) {
	echo '<section class="margin-block"><p class="margin-heading">Partnerzy wydarzenia</p><ul class="margin-list">';
	foreach ( $partners as $partner ) {
		$level = get_the_terms( $partner, 'tsl_poziom_partnerstwa' );
		echo '<li><span class="margin-kind">' . esc_html( $level && ! is_wp_error( $level ) ? $level[0]->name : 'Partner' ) . '</span><a href="' . esc_url( get_permalink( $partner ) ) . '">' . esc_html( get_the_title( $partner ) ) . '</a></li>';
	}
	echo '</ul></section>';
}
if ( ! tsl_event_is_past( $post_obj ) ) {
	tsl_link_list( tsl_related( 'raport', $post_obj, 1 ), 'Premiera raportu' );
	tsl_link_list( tsl_related( 'post', $post_obj, 3 ), 'Przeczytaj przed wydarzeniem' );
}
