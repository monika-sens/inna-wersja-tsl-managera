<?php
/**
 * Ekspert: stanowisko, organizacja, kompetencje (rozdz. 5 — jawne powiązanie z organizacją).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$role        = trim( implode( ', ', array_filter( array( tsl_field( 'tsl_position', $post_obj ), tsl_field( 'tsl_organization', $post_obj ) ) ) ) );
$competences = array_filter( array_map( 'trim', explode( ',', (string) tsl_field( 'tsl_competences', $post_obj ) ) ) );
$linkedin    = tsl_field( 'tsl_linkedin', $post_obj );
?>
<div class="expert-head">
	<?php if ( has_post_thumbnail( $post_obj ) ) : ?>
		<?php echo get_the_post_thumbnail( $post_obj, 'thumbnail', array( 'class' => 'expert-photo', 'alt' => '' ) ); ?>
	<?php else : ?>
		<span class="expert-photo person-initials" aria-hidden="true"><?php echo esc_html( tsl_initials( get_the_title( $post_obj ) ) ); ?></span>
	<?php endif; ?>
	<div>
		<?php if ( $role ) : ?>
			<p class="expert-role"><?php echo esc_html( $role ); ?></p>
		<?php endif; ?>
		<?php if ( $competences ) : ?>
			<ul class="tag-list">
				<?php foreach ( $competences as $competence ) : ?>
					<li><?php echo esc_html( $competence ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $linkedin ) : ?>
			<a class="link-more" href="<?php echo esc_url( $linkedin ); ?>" rel="noopener">Profil LinkedIn<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<?php endif; ?>
	</div>
</div>
