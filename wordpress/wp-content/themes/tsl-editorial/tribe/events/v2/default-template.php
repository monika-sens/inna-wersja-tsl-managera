<?php
/**
 * Szablon widoków kalendarza The Events Calendar (lista, miesiąc, dzień)
 * w układzie serwisu: otwarcie strony nad kalendarzem, widok TEC poniżej.
 *
 * Nadpisuje: the-events-calendar/src/views/v2/default-template.php.
 * Pojedyncze wydarzenie korzysta z single.php motywu (wtyczka tsl-core).
 *
 * @package TSL_Editorial
 */

use Tribe\Events\Views\V2\Template_Bootstrap;

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-head">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<p class="page-kicker">Kalendarium</p>
		<h1 class="page-title">Wydarzenia</h1>
		<p class="page-lead">Webinary, debaty, briefingi i konferencje dla menedżerów TSL. Dodaj wydarzenie do swojego kalendarza jednym kliknięciem.</p>
	</div>
</header>
<div class="wrap tec-wrap">
	<?php echo tribe( Template_Bootstrap::class )->get_view_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php
get_footer();
