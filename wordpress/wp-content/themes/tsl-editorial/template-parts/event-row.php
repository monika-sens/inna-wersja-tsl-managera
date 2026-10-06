<?php
/**
 * Wydarzenie w kalendarium: duża data, rodzaj, tytuł, godzina i forma.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$event = $args['post'] ?? null;
if ( ! $event ) {
	return;
}
$start = tsl_event_start( $event );
$ts    = $start ? date_create_immutable( $start, wp_timezone() ) : null;
$past  = tsl_event_is_past( $event );
$mode  = tsl_field_label( 'tsl_mode', $event );
?>
<li class="cal-item<?php echo $past ? ' is-past' : ''; ?>">
	<?php if ( $ts ) : ?>
		<time class="cal-date" datetime="<?php echo esc_attr( $start ); ?>">
			<span class="cal-day"><?php echo esc_html( wp_date( 'j', $ts->getTimestamp() ) ); ?></span>
			<span class="cal-month"><?php echo esc_html( wp_date( 'M Y', $ts->getTimestamp() ) ); ?></span>
		</time>
	<?php else : ?>
		<span class="cal-date"><span class="cal-day">—</span></span>
	<?php endif; ?>
	<div class="cal-body">
		<p class="cal-kind"><?php echo esc_html( tsl_kind_label( $event ) ); ?><?php echo $past ? ' · zakończone' : ''; ?></p>
		<p class="cal-title card-title"><a href="<?php echo esc_url( get_permalink( $event ) ); ?>"><?php echo esc_html( get_the_title( $event ) ); ?></a></p>
		<?php if ( $start ) : ?>
			<p class="cal-meta"><?php echo esc_html( tsl_event_when( $event ) ); ?><?php echo $mode ? ' · ' . esc_html( $mode ) : ''; ?></p>
		<?php endif; ?>
	</div>
</li>
