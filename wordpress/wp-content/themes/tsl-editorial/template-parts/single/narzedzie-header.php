<?php
/**
 * Narzędzie: wersja i data aktualizacji (Załącznik B — każde narzędzie ma wersję i datę).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$version = tsl_field( 'tsl_version', $post_obj );
$updated = tsl_field( 'tsl_version_date', $post_obj );
?>
<?php if ( $version || $updated ) : ?>
	<p class="post-meta">
		<?php if ( $version ) : ?>
			<span>Wersja <?php echo esc_html( $version ); ?></span>
		<?php endif; ?>
		<?php if ( $version && $updated ) : ?>
			<span class="sep" aria-hidden="true">·</span>
		<?php endif; ?>
		<?php if ( $updated ) : ?>
			<span>Aktualizacja: <?php echo esc_html( tsl_format_date( $updated ) ); ?></span>
		<?php endif; ?>
	</p>
<?php endif; ?>
