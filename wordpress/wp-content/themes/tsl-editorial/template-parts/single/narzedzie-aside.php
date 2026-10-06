<?php
/**
 * Narzędzie: „przeczytaj przed użyciem” i powiązane wydarzenie (rozdz. 17).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
tsl_link_list( tsl_related( 'post', $post_obj, 3 ), 'Przeczytaj przed użyciem' );
tsl_link_list( tsl_related( 'wydarzenie', $post_obj, 2 ), 'Powiązane wydarzenie' );
