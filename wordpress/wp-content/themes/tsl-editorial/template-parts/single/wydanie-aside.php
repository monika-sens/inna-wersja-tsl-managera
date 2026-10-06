<?php
/**
 * Wydanie newslettera: zachęta do zapisu.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;
?>
<a class="next-step" href="<?php echo esc_url( tsl_page_url( 'newsletter' ) ); ?>">
	<span class="next-step-label">Newsletter TSL Manager</span>
	<span class="tool-title">Otrzymuj kolejne wydania na e-mail</span>
	<span class="tool-cta">Zapisz się<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
</a>
<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'wydanie' ) ); ?>">Archiwum wydań<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
