<?php
/**
 * Osoba (ekspert, prelegent): zdjęcie lub inicjały, nazwisko, rola.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$person = $args['post'] ?? null;
if ( ! $person ) {
	return;
}
$role = trim( implode( ', ', array_filter( array( tsl_field( 'tsl_position', $person ), tsl_field( 'tsl_organization', $person ) ) ) ) );
?>
<a class="person" href="<?php echo esc_url( get_permalink( $person ) ); ?>">
	<?php if ( has_post_thumbnail( $person ) ) : ?>
		<?php echo get_the_post_thumbnail( $person, 'thumbnail', array( 'class' => 'person-photo', 'alt' => '' ) ); ?>
	<?php else : ?>
		<span class="person-photo person-initials" aria-hidden="true"><?php echo esc_html( tsl_initials( get_the_title( $person ) ) ); ?></span>
	<?php endif; ?>
	<span class="person-text">
		<span class="person-name card-title"><?php echo esc_html( get_the_title( $person ) ); ?></span>
		<?php if ( $role ) : ?>
			<span class="person-role"><?php echo esc_html( $role ); ?></span>
		<?php endif; ?>
	</span>
</a>
