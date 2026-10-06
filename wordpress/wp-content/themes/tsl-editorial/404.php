<?php
/**
 * Nie znaleziono strony.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-head page-head-404">
	<div class="wrap">
		<p class="page-kicker">Błąd 404</p>
		<h1 class="page-title">Nie znaleźliśmy tej strony</h1>
		<p class="page-lead">Adres mógł się zmienić. Skorzystaj z wyszukiwarki albo wybierz jeden z filarów.</p>
	</div>
</header>
<div class="wrap page-body page-body-narrow">
	<?php get_search_form(); ?>
	<ol class="inline-links inline-links-pillars">
		<?php foreach ( tsl_get_pillars() as $pillar ) : ?>
			<li><a href="<?php echo esc_url( get_term_link( $pillar ) ); ?>"><?php echo esc_html( $pillar->name ); ?></a></li>
		<?php endforeach; ?>
	</ol>
</div>
<?php
get_footer();
