<?php
/**
 * Margines działu: narzędzia w obszarze i sygnały RiskBrief z działu.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$term      = $args['term'];
$is_pillar = $args['is_pillar'];
$tools     = $args['tools'];
?>
<?php if ( $tools ) : ?>
	<section class="margin-block">
		<p class="margin-heading">Narzędzia w tym obszarze</p>
		<ul class="margin-list">
			<?php foreach ( $tools as $tool ) : ?>
				<li>
					<span class="margin-kind"><?php echo esc_html( tsl_kind_label( $tool ) ); ?></span>
					<a href="<?php echo esc_url( get_permalink( $tool ) ); ?>"><?php echo esc_html( get_the_title( $tool ) ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">Wszystkie narzędzia<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</section>
<?php endif; ?>

<?php
$briefs = tsl_posts(
	array(
		'post_type'      => 'riskbrief',
		'posts_per_page' => 3,
		'tax_query'      => tsl_in_category( $is_pillar ? $term : ( tsl_get_pillar_for_term( $term ) ?? $term ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
?>
<?php if ( $briefs ) : ?>
	<section class="margin-block margin-signal">
		<p class="margin-heading"><span class="signal-dot" aria-hidden="true"></span>RiskBrief TSL</p>
		<ul class="margin-list">
			<?php foreach ( $briefs as $brief ) : ?>
				<li><?php echo tsl_risk_badge( $brief ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( get_permalink( $brief ) ); ?>"><?php echo esc_html( get_the_title( $brief ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>
