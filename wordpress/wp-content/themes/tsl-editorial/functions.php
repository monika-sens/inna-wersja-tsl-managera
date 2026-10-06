<?php
/**
 * Motyw TSL Manager Editorial.
 *
 * Model treści (typy, taksonomie, drzewo filarów, adresy URL, formularze,
 * newsletter, analityka, SEO) jest we wtyczce tsl-core. Motyw odpowiada
 * wyłącznie za prezentację — ten sam zakres funkcji co motyw „TSL Manager”
 * 0.10.0, inna koncepcja wizualna.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

define( 'TSL_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/navigation.php';
require_once get_template_directory() . '/inc/queries.php';
require_once get_template_directory() . '/inc/single.php';
require_once get_template_directory() . '/inc/home-modules.php';
require_once get_template_directory() . '/inc/calculator.php';
