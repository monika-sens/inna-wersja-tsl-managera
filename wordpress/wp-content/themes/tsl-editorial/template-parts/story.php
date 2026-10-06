<?php
/**
 * Materiał na liście („story”) — warianty opisane przy tsl_card().
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$story   = $args['post'] ?? get_post();
$variant = $args['variant'] ?? 'medium';

if ( ! $story ) {
	return;
}

$heading  = in_array( $args['heading'] ?? '', array( 'h2', 'h3', 'h4', 'p' ), true ) ? $args['heading'] : 'h3';
$num      = $args['num'] ?? '';
$is_rb    = 'riskbrief' === $story->post_type;
$with_img = in_array( $variant, array( 'lead', 'medium', 'river' ), true ) && ( has_post_thumbnail( $story ) || 'lead' === $variant );
$formats  = get_the_terms( $story, 'tsl_format' );
$modes    = get_the_terms( $story, 'tsl_rodzaj_transportu' );
$pillar   = function_exists( 'tsl_get_pillar' ) ? tsl_get_pillar( $story ) : null;
$data     = array(
	'format'    => ( $formats && ! is_wp_error( $formats ) ) ? implode( ' ', wp_list_pluck( $formats, 'slug' ) ) : '',
	'pillar'    => $pillar ? $pillar->slug : '',
	'transport' => ( $modes && ! is_wp_error( $modes ) ) ? implode( ' ', wp_list_pluck( $modes, 'slug' ) ) : '',
);
$classes  = 'story story-' . $variant . ' story-type-' . $story->post_type . ( $with_img ? ' has-media' : '' );
$sizes    = $args['sizes'] ?? ( 'lead' === $variant ? '(max-width: 900px) 100vw, 60vw' : ( 'river' === $variant ? '240px' : '(max-width: 600px) 100vw, 33vw' ) );
$size     = 'lead' === $variant ? 'tsl-hero' : 'tsl-card';
?>
<article class="<?php echo esc_attr( $classes ); ?>"<?php foreach ( array_filter( $data ) as $key => $value ) { echo ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"'; } ?>>
	<?php if ( $with_img ) : ?>
		<a class="story-media" href="<?php echo esc_url( get_permalink( $story ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo tsl_media( $story, $size, 'lazy', $sizes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	<?php endif; ?>
	<div class="story-body">
		<?php if ( $num ) : ?>
			<span class="story-num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
		<?php endif; ?>
		<p class="story-kicker">
			<?php
			if ( $is_rb ) {
				echo tsl_risk_badge( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo tsl_status_badge( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span class="story-kind">' . esc_html( tsl_kind_label( $story ) ) . '</span>';
			}
			if ( 'compact' !== $variant ) {
				echo tsl_pillar_label( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			// Materiał partnerski / sponsorowany: jawne oznaczenie tekstem także na liście.
			echo tsl_sponsor_label( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</p>
		<<?php echo esc_html( $heading ); ?> class="story-title card-title">
			<a href="<?php echo esc_url( get_permalink( $story ) ); ?>"><?php echo esc_html( get_the_title( $story ) ); ?></a>
		</<?php echo esc_html( $heading ); ?>>
		<?php if ( ! in_array( $variant, array( 'compact' ), true ) && has_excerpt( $story ) ) : ?>
			<p class="story-dek"><?php echo esc_html( get_the_excerpt( $story ) ); ?></p>
		<?php endif; ?>
		<?php echo tsl_post_meta( $story, 'lead' === $variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</article>
