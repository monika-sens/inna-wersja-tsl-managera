<?php
/**
 * Raport: metodologia i zakres danych.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$method = tsl_field( 'tsl_methodology', $post_obj );
$scope  = tsl_field( 'tsl_data_scope', $post_obj );
?>
<?php if ( $method || $scope ) : ?>
	<section class="methodology">
		<h2>Metodologia</h2>
		<?php if ( $scope ) : ?>
			<p class="methodology-scope"><?php echo esc_html( $scope ); ?></p>
		<?php endif; ?>
		<?php if ( $method ) : ?>
			<?php echo wp_kses_post( wpautop( esc_html( $method ) ) ); ?>
		<?php endif; ?>
	</section>
<?php endif; ?>
