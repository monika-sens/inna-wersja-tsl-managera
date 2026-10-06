<?php
/**
 * Raport: wydarzenie premierowe i artykuły pochodne.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
tsl_link_list( tsl_related( 'wydarzenie', $post_obj, 2 ), 'Wydarzenie premierowe' );
tsl_link_list( tsl_related( 'post', $post_obj, 5 ), 'Artykuły pochodne' );
