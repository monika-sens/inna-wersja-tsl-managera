<?php
/**
 * Strona /newsletter/: zapis, komunikaty po potwierdzeniu i wypisaniu,
 * potwierdzenie wypisania, ostatnie wydania.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$state = isset( $_GET['nl'] ) ? sanitize_key( wp_unslash( $_GET['nl'] ) ) : '';
$token = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : '';
// phpcs:enable

$notices = array(
	'potwierdzono' => array( 'ok', 'Zapis potwierdzony. Witamy w gronie czytelników newslettera TSL Manager!' ),
	'wypisano'     => array( 'ok', 'Wypisaliśmy Twój adres. Nie będziesz już otrzymywać newslettera. Możesz wrócić w każdej chwili.' ),
	'blad'         => array( 'error', 'Link jest nieprawidłowy lub wygasł. Jeśli chcesz się zapisać, użyj formularza poniżej.' ),
);
$issues  = tsl_posts(
	array(
		'post_type'      => 'wydanie',
		'posts_per_page' => 5,
	)
);
?>

<header class="page-head">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<p class="page-kicker">Newsletter i RiskBrief TSL</p>
		<h1 class="page-title"><?php the_title(); ?></h1>
		<p class="page-lead">Raz w tygodniu: sygnały RiskBrief, najważniejsze analizy i nowe narzędzia dla menedżerów transportu, spedycji i logistyki.</p>
	</div>
</header>

<?php if ( isset( $notices[ $state ] ) || ( 'wypisz' === $state && $token ) ) : ?>
	<div class="wrap page-notices">
		<?php if ( isset( $notices[ $state ] ) ) : ?>
			<div class="notice-box notice-<?php echo esc_attr( $notices[ $state ][0] ); ?>" role="status"><?php echo esc_html( $notices[ $state ][1] ); ?></div>
		<?php endif; ?>

		<?php if ( 'wypisz' === $state && $token ) : ?>
			<section class="notice-box notice-question">
				<h2>Wypisać się z newslettera?</h2>
				<p>Po wypisaniu nie będziesz otrzymywać kolejnych wydań ani alertów RiskBrief na e-mail.</p>
				<form method="post" action="<?php echo esc_url( add_query_arg( array( 'tsl_nl' => 'unsubscribe', 'token' => $token ), home_url( '/newsletter/' ) ) ); ?>">
					<button class="btn btn-primary" type="submit">Tak, wypisz mnie</button>
					<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Zostaję</a>
				</form>
			</section>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php get_template_part( 'template-parts/newsletter' ); ?>

<div class="wrap page-split">
	<div class="page-split-main">
		<section class="promise-list">
			<h2 class="list-heading">Co dostaniesz</h2>
			<ol class="numbered">
				<li><span><strong>RiskBrief TSL</strong> — sygnały ryzyka z oceną poziomu i działaniem „co zrobić teraz”</span></li>
				<li><span><strong>Nowe analizy i poradniki</strong> z sześciu filarów</span></li>
				<li><span><strong>Checklisty, kalkulatory i wzory dokumentów</strong></span></li>
				<li><span><strong>Zaproszenia</strong> na webinary i debaty</span></li>
			</ol>
		</section>
		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( wp_strip_all_tags( get_the_content() ) ) && false === strpos( get_the_content(), 'Do uzupełnienia' ) ) :
				?>
				<div class="entry-content newsletter-intro"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>
	</div>
	<aside class="page-split-aside">
		<?php if ( $issues ) : ?>
			<section class="margin-block">
				<p class="margin-heading">Ostatnie wydania</p>
				<ul class="margin-list">
					<?php foreach ( $issues as $issue ) : ?>
						<li><span class="margin-kind"><?php echo esc_html( get_the_date( '', $issue ) ); ?></span><a href="<?php echo esc_url( get_permalink( $issue ) ); ?>"><?php echo esc_html( get_the_title( $issue ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'wydanie' ) ); ?>">Archiwum wydań<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</section>
		<?php endif; ?>
	</aside>
</div>

<?php
get_footer();
