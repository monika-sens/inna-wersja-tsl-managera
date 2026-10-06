<?php
/**
 * Strona /kontakt/: formularz z tematem (redakcja, partnerstwa, wydarzenia…),
 * treść strony i wskazówki „Zanim napiszesz”.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Temat można wskazać w adresie, np. /kontakt/?temat=partnerstwo.
$topic = isset( $_GET['temat'] ) ? sanitize_key( wp_unslash( $_GET['temat'] ) ) : 'redakcja'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<header class="page-head">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<p class="page-kicker">Kontakt</p>
		<h1 class="page-title"><?php the_title(); ?></h1>
		<p class="page-lead">Propozycja tematu, pytanie do redakcji, współpraca partnerska lub wydarzenia — wybierz temat, a wiadomość trafi do właściwej osoby.</p>
	</div>
</header>

<div class="wrap page-split">
	<div class="page-split-main">
		<?php get_template_part( 'template-parts/form', null, array( 'type' => 'kontakt', 'values' => array( 'topic' => $topic ) ) ); ?>
	</div>
	<aside class="page-split-aside">
		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( wp_strip_all_tags( get_the_content() ) ) && false === strpos( get_the_content(), 'Do uzupełnienia' ) ) :
				?>
				<section class="margin-block entry-content"><?php the_content(); ?></section>
				<?php
			endif;
		endwhile;
		?>
		<section class="margin-block">
			<p class="margin-heading">Zanim napiszesz</p>
			<ul class="check-plain">
				<li>Zgłoszenie szkody lub incydentu? Zobacz <a href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">procedury i checklisty</a>.</li>
				<li>Chcesz zostać partnerem? Poznaj <a href="<?php echo esc_url( tsl_page_url( 'wspolpraca-partnerska' ) ); ?>">zasady współpracy</a>.</li>
				<li>Interesuje Cię webinar? Sprawdź <a href="<?php echo esc_url( tsl_events_url() ); ?>">kalendarz wydarzeń</a>.</li>
			</ul>
		</section>
	</aside>
</div>

<?php
get_footer();
