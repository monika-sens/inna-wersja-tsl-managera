<?php
/**
 * RiskBrief: model publikacji z rozdz. 7.2 — kogo dotyczy, skutek, działanie teraz, ważność.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$who     = tsl_field( 'tsl_who', $post_obj );
$impact  = tsl_field( 'tsl_impact', $post_obj );
$actions = tsl_field_lines( 'tsl_actions', $post_obj );
$valid   = tsl_field( 'tsl_valid_until', $post_obj );
$review  = tsl_field( 'tsl_review_date', $post_obj );
?>
<?php if ( $who || $impact || $actions ) : ?>
	<section class="brief-box" aria-label="Najważniejsze informacje">
		<?php if ( $who ) : ?>
			<div class="brief-row"><p class="brief-label">Kogo dotyczy</p><p><?php echo esc_html( $who ); ?></p></div>
		<?php endif; ?>
		<?php if ( $impact ) : ?>
			<div class="brief-row"><p class="brief-label">Możliwy skutek</p><p><?php echo esc_html( $impact ); ?></p></div>
		<?php endif; ?>
		<?php if ( $actions ) : ?>
			<div class="brief-row brief-actions"><p class="brief-label">Co zrobić teraz</p><?php echo tsl_lines_html( $actions, 'ol', 'check-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<?php if ( $valid || $review ) : ?>
			<p class="brief-validity">
				<?php if ( $valid ) : ?>
					<span>Ważne do: <strong><?php echo esc_html( tsl_format_date( $valid ) ); ?></strong></span>
				<?php endif; ?>
				<?php if ( $review ) : ?>
					<span>Ponowna weryfikacja: <strong><?php echo esc_html( tsl_format_date( $review ) ); ?></strong></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</section>
<?php endif; ?>
