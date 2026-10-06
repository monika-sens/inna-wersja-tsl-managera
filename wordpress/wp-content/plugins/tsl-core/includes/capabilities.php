<?php
/**
 * Uprawnienia TSL Manager.
 *
 *  - tsl_import_articles     import artykułów redakcji i plików .docx
 *  - tsl_manage_subscribers  lista subskrybentów, eksport i usuwanie danych
 *
 * Oba ma administrator. Rola „Redaktor naczelny” = Redaktor + import artykułów.
 * Zwykły Redaktor i Autor nie mają żadnego z nich.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

const TSL_CAPS_VERSION = 1;

/**
 * Nadaje uprawnienia i tworzy rolę „Redaktor naczelny” (bez zmian w innych rolach).
 */
function tsl_caps_install() {
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'tsl_import_articles' );
		$admin->add_cap( 'tsl_manage_subscribers' );
	}
	$editor = get_role( 'editor' );
	if ( $editor && ! get_role( 'tsl_redaktor_naczelny' ) ) {
		add_role(
			'tsl_redaktor_naczelny',
			'Redaktor naczelny',
			array_merge( $editor->capabilities, array( 'tsl_import_articles' => true ) )
		);
	}
	update_option( 'tsl_caps_version', TSL_CAPS_VERSION );
}

add_action(
	'init',
	function () {
		if ( (int) get_option( 'tsl_caps_version' ) < TSL_CAPS_VERSION ) {
			tsl_caps_install();
		}
	},
	1
);
