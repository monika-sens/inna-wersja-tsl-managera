<?php
/**
 * Wydarzenie: termin, forma, miejsce i rejestracja; po terminie — nagranie.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$start    = tsl_event_start( $post_obj );
$past     = tsl_event_is_past( $post_obj );
$mode     = tsl_field_label( 'tsl_mode', $post_obj );
$where    = tsl_event_where( $post_obj );
$location = trim( (string) substr( $where, strlen( $mode ) ), ' ·' );
$register = tsl_field( 'tsl_registration_url', $post_obj );
$record   = tsl_field( 'tsl_recording_url', $post_obj );
?>
<div class="event-facts">
	<?php if ( $start ) : ?>
		<p class="event-fact"><?php echo tsl_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><time datetime="<?php echo esc_attr( $start ); ?>"><?php echo esc_html( tsl_event_when( $post_obj ) ); ?></time></p>
	<?php endif; ?>
	<?php if ( $mode || $location ) : ?>
		<p class="event-fact"><span class="event-mode"><?php echo esc_html( $mode ); ?></span><?php echo $location ? '<span>' . esc_html( $location ) . '</span>' : ''; ?></p>
	<?php endif; ?>
	<p class="event-status <?php echo $past ? 'is-past' : 'is-upcoming'; ?>"><?php echo $past ? 'Wydarzenie zakończone' : 'Nadchodzące'; ?></p>
</div>
<div class="entry-actions">
	<?php if ( ! $past && $register ) : ?>
		<a class="btn btn-accent" href="<?php echo esc_url( $register ); ?>" rel="noopener">Zarejestruj się</a>
	<?php elseif ( ! $past ) : ?>
		<a class="btn btn-accent" href="#rejestracja">Zarejestruj się</a>
	<?php elseif ( $record ) : ?>
		<a class="btn btn-accent" href="<?php echo esc_url( $record ); ?>" rel="noopener">Obejrzyj nagranie</a>
	<?php endif; ?>
	<?php if ( ! $past && 'tribe_events' === $post_obj->post_type && function_exists( 'tribe_get_gcal_link' ) ) : ?>
		<a class="btn btn-ghost" href="<?php echo esc_url( tribe_get_gcal_link( $post_obj ) ); ?>" rel="noopener" target="_blank" data-track="Wydarzenie|do kalendarza|Google"><?php echo tsl_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Google Calendar</a>
		<a class="btn btn-ghost" href="<?php echo esc_url( tribe_get_single_ical_link() ); ?>" data-track="Wydarzenie|do kalendarza|iCal"><?php echo tsl_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Outlook / iCal</a>
	<?php endif; ?>
</div>
