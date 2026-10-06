<?php
/**
 * Nagłówek: pasek wydania, winieta (znak słowny), pasek działów,
 * wyszukiwarka, CTA RiskBrief na e-mail, menu pełnoekranowe.
 *
 * Po przewinięciu winieta chowa się, a pasek działów zostaje u góry
 * ekranu z małym znakiem słownym.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Przejdź do treści</a>

<?php tsl_render_topbar(); ?>

<header class="masthead" id="site-header">
	<div class="masthead-main wrap">
		<div class="mh-side mh-left">
			<button class="mh-button menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav">
				<span class="icon-open"><?php echo tsl_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="icon-close"><?php echo tsl_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="mh-button-label">Menu</span>
			</button>
			<p class="mh-tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>

		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — strona główna">
			<?php echo tsl_logo( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>

		<div class="mh-side mh-right">
			<button class="mh-button search-toggle" type="button" aria-expanded="false" aria-controls="search-panel">
				<?php echo tsl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="mh-button-label">Szukaj</span>
			</button>
			<a class="btn btn-accent header-cta" data-track="CTA|nagłówek|RiskBrief na e-mail" href="<?php echo esc_url( tsl_page_url( 'newsletter' ) ); ?>">RiskBrief na e-mail</a>
		</div>
	</div>

	<?php tsl_render_primary_nav(); ?>

	<div class="search-panel" id="search-panel" hidden>
		<div class="wrap search-panel-inner">
			<p class="search-panel-title">Czego szukasz?</p>
			<?php get_search_form(); ?>
			<p class="search-hints"><span>Często szukane:</span> <a href="<?php echo esc_url( home_url( '/?s=fałszywy+przewoźnik' ) ); ?>">fałszywy przewoźnik</a> <a href="<?php echo esc_url( home_url( '/?s=CMR' ) ); ?>">CMR</a> <a href="<?php echo esc_url( home_url( '/?s=płynność' ) ); ?>">płynność</a> <a href="<?php echo esc_url( home_url( '/?s=TMS' ) ); ?>">TMS</a></p>
		</div>
	</div>

	<?php tsl_render_mobile_nav(); ?>
</header>

<main id="main" class="site-main" tabindex="-1">
