<?php
/**
 * Formularz wyszukiwania (panel w nagłówku, menu, 404).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$uid = wp_unique_id( 'search-' );
?>
<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>">Szukaj w serwisie</label>
	<input id="<?php echo esc_attr( $uid ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Problem, temat, narzędzie…" autocomplete="off">
	<button class="btn btn-primary" type="submit"><?php echo tsl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Szukaj</span></button>
</form>
