<?php
/**
 * Dział (filar) i obszar (podkategoria).
 *
 * Dział: otwarcie z numerem działu i obietnicą, spis obszarów, materiał
 * wyróżniony, „Najnowsze w filarze”, margines z narzędziami i sygnałami.
 * Obszar: opis zakresu, obszary potomne, filtr formatów (?format=),
 * lista materiałów, narzędzie i sygnały z działu.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term      = get_queried_object();
$is_pillar = tsl_is_pillar( $term );
$children  = tsl_get_subcategories( $term );
$featured  = $is_pillar ? tsl_pillar_featured( $term ) : null;
$format    = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$pillar    = $is_pillar ? $term : tsl_get_pillar_for_term( $term );
$num       = tsl_pillar_number( $pillar );
$tools     = tsl_posts(
	array(
		'post_type'      => 'narzedzie',
		'posts_per_page' => $is_pillar ? 3 : 1,
		'tax_query'      => tsl_in_category( $term ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
?>

<header class="dept-head<?php echo $is_pillar ? ' is-pillar' : ' is-area'; ?>">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<div class="dept-grid">
			<div class="dept-intro">
				<p class="dept-num">
					<?php if ( $is_pillar ) : ?>
						Dział <?php echo esc_html( $num ); ?>
					<?php elseif ( $pillar ) : ?>
						Obszar · <a href="<?php echo esc_url( get_term_link( $pillar ) ); ?>">Dział <?php echo esc_html( $num ); ?> — <?php echo esc_html( $pillar->name ); ?></a>
					<?php else : ?>
						Obszar
					<?php endif; ?>
				</p>
				<h1 class="dept-title"><?php echo esc_html( $term->name ); ?></h1>
				<?php if ( $is_pillar && tsl_term_promise( $term ) ) : ?>
					<p class="dept-lead"><?php echo esc_html( tsl_term_promise( $term ) ); ?></p>
				<?php elseif ( $term->description ) : ?>
					<p class="dept-lead"><?php echo esc_html( $term->description ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $children ) : ?>
				<nav class="dept-areas" aria-label="Podkategorie">
					<p class="dept-areas-title"><?php echo $is_pillar ? 'Obszary działu' : 'Obszary'; ?></p>
					<ol>
						<?php foreach ( $children as $child ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $child ) ); ?>"><?php echo esc_html( $child->name ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php
// Margines działu (narzędzia, sygnały) — renderowany najpierw, żeby bez treści lista zajęła całą szerokość.
ob_start();
get_template_part( 'template-parts/dept-aside', null, array( 'term' => $term, 'is_pillar' => $is_pillar, 'tools' => $tools ) );
$aside = trim( ob_get_clean() );
?>
<div class="wrap dept-body<?php echo $aside ? '' : ' no-aside'; ?>">
	<div class="dept-main">

		<?php if ( $featured && ! $format && ! is_paged() ) : ?>
			<section class="dept-featured" aria-label="Temat wyróżniony">
				<?php tsl_card( $featured, 'lead', array( 'heading' => 'h2' ) ); ?>
			</section>
		<?php endif; ?>

		<?php if ( ! $is_pillar ) : ?>
			<?php
			$formats = get_terms(
				array(
					'taxonomy'   => 'tsl_format',
					'hide_empty' => true,
				)
			);
			?>
			<?php if ( $formats && ! is_wp_error( $formats ) ) : ?>
				<nav class="filter-tabs" aria-label="Filtr formatów" data-format-filter>
					<span class="filter-label" aria-hidden="true">Format</span>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" data-format="" <?php echo $format ? '' : 'aria-current="true"'; ?>>Wszystkie</a>
					<?php foreach ( $formats as $f ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'format', $f->slug, get_term_link( $term ) ) ); ?>" data-format="<?php echo esc_attr( $f->slug ); ?>" <?php echo $format === $f->slug ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $f->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>

		<?php tsl_section_header( $is_pillar ? 'Najnowsze w filarze' : 'Materiały' ); ?>

		<?php if ( have_posts() ) : ?>
			<div class="post-list" data-scope="<?php echo esc_attr( $term->description ); ?>">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( $featured && $featured->ID === get_the_ID() && ! $format && ! is_paged() ) {
						continue;
					}
					tsl_card( get_post(), 'river' );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'prev_text' => 'Poprzednie',
					'next_text' => 'Następne',
				)
			);
			?>
		<?php else : ?>
			<?php tsl_empty_state( $term->description, (bool) $format ); ?>
		<?php endif; ?>
	</div>

	<?php if ( $aside ) : ?>
		<aside class="dept-aside" aria-label="Narzędzia i sygnały z działu"><?php echo $aside; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></aside>
	<?php endif; ?>
</div>

<?php get_template_part( 'template-parts/newsletter' ); ?>

<?php
get_footer();
