<?php
/**
 * Szablon awaryjny (archiwa bez dedykowanego szablonu).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-head">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<h1 class="page-title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
	</div>
</header>

<div class="wrap page-body page-body-narrow">
	<?php if ( have_posts() ) : ?>
		<h2 class="screen-reader-text">Lista materiałów</h2>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				tsl_card( get_post(), 'river' );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<div class="empty-state"><p>Brak materiałów.</p></div>
	<?php endif; ?>
</div>
<?php
get_footer();
