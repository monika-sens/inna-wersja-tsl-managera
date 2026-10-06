<?php
/**
 * Pojedynczy materiał: artykuł, RiskBrief, narzędzie, wydarzenie, raport,
 * ekspert, partner, wydanie newslettera.
 *
 * Układ magazynowy: duży tytuł i lead, pasek metadanych między liniami,
 * szerokie zdjęcie, wąska kolumna tekstu i margines (następny krok,
 * kontekst, osoby, powiązane listy). Elementy właściwe dla typu są
 * w template-parts/single/{typ}-header|before|after|aside.php.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_obj   = get_post();
	$type       = $post_obj->post_type;
	// Części szablonu wydarzenia są wspólne dla obu silników (wbudowany / The Events Calendar).
	$part       = 'tribe_events' === $type ? 'wydarzenie' : $type;
	$in_tree    = in_array( $type, tsl_pillar_post_types(), true );
	$primary    = $in_tree ? tsl_get_primary_term( $post_obj ) : null;
	$context    = array();
	$context_tx = array(
		'tsl_rola_odbiorcy'     => 'Dla kogo',
		'tsl_rodzaj_transportu' => 'Transport',
		'tsl_problem'           => 'Problem',
		'tsl_typ_organizacji'   => 'Typ firmy',
		'tsl_region'            => 'Region',
		'tsl_towar_sektor'      => 'Sektor',
	);
	foreach ( $context_tx as $tax => $label ) {
		$terms = get_the_terms( $post_obj, $tax );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$context[ $label ] = wp_list_pluck( $terms, 'name' );
		}
	}
	$extra_pillars = array_filter(
		array_map( 'get_term', array_map( 'intval', get_post_meta( $post_obj->ID, 'tsl_context' ) ) ),
		function ( $term ) {
			return $term instanceof WP_Term;
		}
	);
	if ( $extra_pillars ) {
		$context['Także w filarach'] = wp_list_pluck( $extra_pillars, 'name' );
	}

	$next    = tsl_next_step( $post_obj );
	$experts = in_array( $type, array( 'post', 'narzedzie', 'raport' ), true ) ? tsl_related( 'ekspert', $post_obj, 3 ) : array();
	$figure  = has_post_thumbnail() && in_array( $part, array( 'post', 'raport', 'wydarzenie' ), true );
	?>

	<?php if ( in_array( $type, array( 'post', 'raport' ), true ) ) : ?>
		<div class="reading-progress" aria-hidden="true"><span></span></div>
	<?php endif; ?>

	<article <?php post_class( 'entry entry-' . $part ); ?>>
		<header class="entry-head">
			<div class="wrap entry-head-inner">
				<?php tsl_breadcrumbs(); ?>
				<p class="entry-kicker">
					<?php
					if ( 'riskbrief' === $type ) {
						echo '<span class="rb-brand"><span class="signal-dot" aria-hidden="true"></span>RiskBrief TSL</span>';
						echo tsl_risk_badge( $post_obj ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo tsl_status_badge( $post_obj ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} elseif ( 'ekspert' === $type ) {
						echo '<span class="story-kind">Ekspert</span>';
					} elseif ( 'partner' === $type ) {
						$level = get_the_terms( $post_obj, 'tsl_poziom_partnerstwa' );
						echo '<span class="story-kind">' . esc_html( $level && ! is_wp_error( $level ) ? $level[0]->name : 'Partner' ) . '</span>';
					} elseif ( $in_tree ) {
						echo '<span class="story-kind">' . esc_html( tsl_kind_label( $post_obj ) ) . '</span>';
					} elseif ( 'wydanie' === $type ) {
						echo '<span class="story-kind">Wydanie newslettera</span>';
					}
					echo tsl_pillar_label( $post_obj ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo tsl_sponsor_badge( $post_obj ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</p>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="entry-standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<?php
				$byline = in_array( $type, array( 'post', 'riskbrief', 'raport' ), true ) ? tsl_post_meta( $post_obj, true ) : '';
				ob_start();
				get_template_part( 'template-parts/single/' . $part, 'header', array( 'post' => $post_obj ) );
				$type_header = trim( ob_get_clean() );
				?>
				<?php if ( $byline || $type_header ) : ?>
					<div class="entry-byline">
						<?php echo $byline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $type_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( $figure ) : ?>
			<figure class="entry-figure wrap-wide"><?php the_post_thumbnail( 'tsl-hero', array( 'sizes' => '(max-width: 1400px) 100vw, 1400px', 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>

		<div class="wrap entry-layout">
			<div class="entry-main">
				<?php get_template_part( 'template-parts/single/' . $part, 'before', array( 'post' => $post_obj ) ); ?>

				<div class="entry-content">
					<?php the_content(); ?>
				</div>

				<?php get_template_part( 'template-parts/single/' . $part, 'after', array( 'post' => $post_obj ) ); ?>

				<?php $sources = tsl_field_lines( 'tsl_sources', $post_obj ); ?>
				<?php if ( $sources ) : ?>
					<section class="sources">
						<h2 class="sources-title">Źródła</h2>
						<?php echo tsl_lines_html( $sources, 'ol' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</section>
				<?php endif; ?>
			</div>

			<?php ob_start(); ?>
				<?php if ( $next ) : ?>
					<a class="next-step" href="<?php echo esc_url( $next['url'] ); ?>" data-track="CTA|następny krok|<?php echo esc_attr( $next['kind'] . ': ' . $next['title'] ); ?>">
						<span class="next-step-label"><?php echo esc_html( $next['label'] ); ?></span>
						<?php if ( $next['kind'] ) : ?>
							<span class="tool-type"><?php echo esc_html( $next['kind'] ); ?></span>
						<?php endif; ?>
						<span class="tool-title"><?php echo esc_html( $next['title'] ); ?></span>
						<span class="tool-cta"><?php echo esc_html( $next['cta'] ); ?><?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</a>
				<?php endif; ?>

				<?php if ( $context ) : ?>
					<dl class="context-list">
						<?php foreach ( $context as $label => $names ) : ?>
							<div>
								<dt><?php echo esc_html( $label ); ?></dt>
								<dd><?php echo esc_html( implode( ', ', $names ) ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>

				<?php tsl_people_box( $experts, 'raport' === $type ? 'Autorzy' : ( 'narzedzie' === $type ? 'Właściciel merytoryczny' : 'Ekspert' ) ); ?>

				<?php get_template_part( 'template-parts/single/' . $part, 'aside', array( 'post' => $post_obj ) ); ?>
			<?php $margin = trim( ob_get_clean() ); ?>
			<?php if ( $margin ) : ?>
				<aside class="entry-margin" aria-label="Kontekst i następny krok"><?php echo $margin; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></aside>
			<?php endif; ?>
		</div>
	</article>

	<?php
	if ( $primary ) :
		$related = tsl_posts(
			array(
				'post_type'      => array( 'post', 'riskbrief', 'raport' ),
				'posts_per_page' => 3,
				'post__not_in'   => array( get_the_ID() ),
				'tax_query'      => tsl_in_category( tsl_get_pillar_for_term( $primary ) ?? $primary ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
			)
		);
		if ( $related ) :
			?>
			<section class="section section-related">
				<div class="wrap">
					<?php tsl_section_header( 'Powiązane materiały', get_term_link( $primary ), 'Więcej: ' . $primary->name, 'Czytaj dalej w tym dziale' ); ?>
					<div class="story-row">
						<?php foreach ( $related as $item ) : ?>
							<?php tsl_card( $item, 'medium' ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>

	<?php get_template_part( 'template-parts/newsletter' ); ?>

	<?php
endwhile;

get_footer();
