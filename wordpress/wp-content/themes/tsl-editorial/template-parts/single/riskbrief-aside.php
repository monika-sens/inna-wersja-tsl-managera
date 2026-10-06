<?php
/**
 * RiskBrief: dalsza ścieżka — pełna analiza i podobne sygnały (rozdz. 17).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
tsl_link_list( tsl_related( 'post', $post_obj, 3 ), 'Pełna analiza' );

$level   = tsl_risk_level( $post_obj );
$similar = tsl_posts(
	array(
		'post_type'      => 'riskbrief',
		'posts_per_page' => 3,
		'post__not_in'   => array( $post_obj->ID ),
		'tax_query'      => $level ? tsl_in_term( 'poziom_ryzyka', $level->slug ) : array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
tsl_link_list( $similar, 'Podobne sygnały' );
?>
<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'riskbrief' ) ); ?>">Archiwum RiskBrief TSL<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
