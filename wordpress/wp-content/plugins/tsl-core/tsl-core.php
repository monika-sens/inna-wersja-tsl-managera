<?php
/**
 * Plugin Name:       TSL Manager — model treści
 * Description:       Typy treści, taksonomie, drzewo sześciu filarów i struktura adresów URL portalu TSL Manager. Wtyczka jest niezależna od motywu: zmiana wyglądu nie usuwa treści ani klasyfikacji.
 * Version:           0.10.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            TSL Manager
 * Text Domain:       tsl-core
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'TSL_CORE_VERSION', '0.10.0' );
define( 'TSL_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once TSL_CORE_DIR . 'includes/capabilities.php';
require_once TSL_CORE_DIR . 'includes/modes.php';
require_once TSL_CORE_DIR . 'includes/taxonomies.php';
require_once TSL_CORE_DIR . 'includes/post-types.php';
require_once TSL_CORE_DIR . 'includes/classification.php';
require_once TSL_CORE_DIR . 'includes/permalinks.php';
require_once TSL_CORE_DIR . 'includes/pillars.php';
require_once TSL_CORE_DIR . 'includes/fields.php';
require_once TSL_CORE_DIR . 'includes/events.php';
require_once TSL_CORE_DIR . 'includes/collections.php';
require_once TSL_CORE_DIR . 'includes/seeder.php';
require_once TSL_CORE_DIR . 'includes/newsletter/settings.php';
require_once TSL_CORE_DIR . 'includes/newsletter/subscribers.php';
require_once TSL_CORE_DIR . 'includes/newsletter/mailer.php';
require_once TSL_CORE_DIR . 'includes/newsletter/sending.php';
require_once TSL_CORE_DIR . 'includes/newsletter/public.php';
require_once TSL_CORE_DIR . 'includes/newsletter/rest.php';
require_once TSL_CORE_DIR . 'includes/newsletter/privacy.php';
require_once TSL_CORE_DIR . 'includes/forms/forms.php';
require_once TSL_CORE_DIR . 'includes/analytics.php';
require_once TSL_CORE_DIR . 'includes/widgets.php';
require_once TSL_CORE_DIR . 'includes/articles.php';
require_once TSL_CORE_DIR . 'includes/seo.php';
if ( is_admin() ) {
	require_once TSL_CORE_DIR . 'includes/newsletter/admin.php';
	require_once TSL_CORE_DIR . 'includes/admin-demo.php';
	require_once TSL_CORE_DIR . 'includes/docx-import.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once TSL_CORE_DIR . 'includes/cli.php';
}

/**
 * Aktywacja: rejestracja modelu treści, wczytanie drzewa kategorii i słowników,
 * odświeżenie reguł adresów URL.
 */
function tsl_core_activate() {
	tsl_register_taxonomies();
	tsl_register_post_types();
	tsl_nl_install();
	tsl_caps_install();
	TSL_Seeder::run();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tsl_core_activate' );

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'tsl_nl_process' );
		flush_rewrite_rules();
	}
);
