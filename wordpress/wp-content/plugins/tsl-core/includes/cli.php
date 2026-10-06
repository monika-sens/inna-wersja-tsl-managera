<?php
/**
 * Polecenia WP-CLI.
 *
 *   wp tsl seed            wczytuje drzewo filarów, słowniki, strony i ustawienia
 *   wp tsl seed --demo     j.w. + materiały demonstracyjne
 *   wp tsl demo --remove   usuwa materiały demonstracyjne
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Zarządzanie strukturą serwisu TSL Manager.
 */
class TSL_CLI_Command {

	/**
	 * Wczytuje strukturę serwisu. Bezpieczne do wielokrotnego uruchamiania.
	 *
	 * ## OPTIONS
	 *
	 * [--demo]
	 * : Dodaje materiały demonstracyjne (pakiet MVP z treścią zastępczą).
	 *
	 * @param array $args       Argumenty.
	 * @param array $assoc_args Opcje.
	 */
	public function seed( $args, $assoc_args ) {
		TSL_Seeder::run();
		if ( ! empty( $assoc_args['demo'] ) && ! tsl_is_demo_mode() ) {
			WP_CLI::warning( 'Tryb produkcyjny — materiały przykładowe pominięte. Włącz tryb: wp tsl mode demo' );
		}
		if ( ! empty( $assoc_args['demo'] ) ) {
			TSL_Seeder::demo();
		}
		flush_rewrite_rules( false );
		delete_option( 'tsl_flush_rewrite' );

		foreach ( TSL_Seeder::$log as $line ) {
			WP_CLI::log( $line );
		}
		WP_CLI::success( 'Struktura serwisu gotowa.' );
	}

	/**
	 * Dodaje lub usuwa materiały demonstracyjne.
	 *
	 * ## OPTIONS
	 *
	 * [--remove]
	 * : Usuwa wszystkie materiały oznaczone jako demonstracyjne.
	 *
	 * @param array $args       Argumenty.
	 * @param array $assoc_args Opcje.
	 */
	public function demo( $args, $assoc_args ) {
		if ( empty( $assoc_args['remove'] ) && ! tsl_is_demo_mode() ) {
			WP_CLI::error( 'Serwis jest w trybie produkcyjnym. Najpierw: wp tsl mode demo' );
		}
		if ( ! empty( $assoc_args['remove'] ) ) {
			WP_CLI::success( sprintf( 'Usunięto materiały demonstracyjne: %d.', TSL_Seeder::remove_demo() ) );
			return;
		}
		TSL_Seeder::demo();
		foreach ( TSL_Seeder::$log as $line ) {
			WP_CLI::log( $line );
		}
		WP_CLI::success( 'Gotowe.' );
	}

	/**
	 * Pokazuje albo zmienia tryb serwisu.
	 *
	 * ## OPTIONS
	 *
	 * [<tryb>]
	 * : demo albo production. Bez argumentu — pokazuje bieżący tryb.
	 *
	 * @param array $args Argumenty.
	 */
	public function mode( $args ) {
		if ( ! $args ) {
			WP_CLI::log( tsl_is_demo_mode() ? 'demo' : 'production' );
			return;
		}
		if ( ! in_array( $args[0], array( 'demo', 'production' ), true ) ) {
			WP_CLI::error( 'Dozwolone wartości: demo, production.' );
		}
		update_option( 'tsl_demo_mode', 'demo' === $args[0] ? '1' : '0' );
		WP_CLI::success( 'Tryb: ' . $args[0] . '.' );
	}
}

/**
 * Wydarzenia.
 */
class TSL_CLI_Events_Command {

	/**
	 * Przenosi wydarzenia z wbudowanego modułu do The Events Calendar.
	 */
	public function migrate() {
		if ( ! tsl_events_tec() ) {
			WP_CLI::error( 'The Events Calendar nie jest włączony.' );
		}
		WP_CLI::success( sprintf( 'Przeniesiono wydarzenia: %d.', tsl_events_migrate_to_tec() ) );
	}
}

WP_CLI::add_command( 'tsl', 'TSL_CLI_Command' );
WP_CLI::add_command( 'tsl events', 'TSL_CLI_Events_Command' );
