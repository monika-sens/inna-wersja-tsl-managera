<?php
/**
 * Strona informacyjna (O projekcie, Redakcja, Zasady redakcyjne…).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry entry-page' ); ?>>
		<header class="entry-head">
			<div class="wrap entry-head-inner">
				<?php tsl_breadcrumbs(); ?>
				<p class="entry-kicker"><span class="story-kind">TSL Manager</span></p>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="entry-standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<div class="wrap entry-layout entry-layout-page">
			<div class="entry-main">
				<div class="entry-content"><?php the_content(); ?></div>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
