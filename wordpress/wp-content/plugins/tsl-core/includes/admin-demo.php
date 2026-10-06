<?php
/**
 * Narzędzia → TSL Manager: tryb demonstracyjny, materiały przykładowe,
 * artykuły redakcji, ilustracje i CTA — z panelu, na hostingu bez WP-CLI
 * (np. home.pl).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_management_page( 'TSL Manager — treści', 'TSL Manager', 'manage_options', 'tsl-demo', 'tsl_render_demo_page' );
	}
);

/**
 * Obsługa przycisków (przed wysłaniem nagłówków strony). Tylko POST + nonce.
 */
add_action(
	'admin_init',
	function () {
		if ( empty( $_POST['tsl_demo_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'tsl_demo' );
		$action = sanitize_key( wp_unslash( $_POST['tsl_demo_action'] ) );
		if ( 'mode' === $action ) {
			update_option( 'tsl_demo_mode', empty( $_POST['tsl_demo_mode'] ) ? '0' : '1' );
			$done = 'tryb';
		} elseif ( 'load' === $action ) {
			if ( ! tsl_is_demo_mode() ) {
				$done = 'tylko-demo';
			} else {
				TSL_Seeder::run();
				TSL_Seeder::demo();
				$done = 'wczytano';
			}
		} elseif ( 'remove' === $action ) {
			$done = 'usunieto-' . TSL_Seeder::remove_demo();
		} elseif ( 'articles' === $action ) {
			if ( ! current_user_can( 'tsl_import_articles' ) ) {
				wp_die( 'Brak uprawnień do importu artykułów.', 403 );
			}
			// Publikacja tylko w trybie demonstracyjnym i tylko po świadomym zaznaczeniu.
			$publish = tsl_is_demo_mode() && ! empty( $_POST['tsl_publish'] ) && current_user_can( 'publish_posts' );
			TSL_Seeder::run();
			$stats = tsl_articles_import( ! empty( $_POST['tsl_overwrite'] ), ! empty( $_POST['tsl_remove_demo'] ), $publish );
			set_transient( 'tsl_articles_stats', $stats, 300 );
			$done = 'artykuly';
		} elseif ( 'images' === $action ) {
			set_transient( 'tsl_articles_stats', array( 'images' => tsl_articles_attach_existing_images() ), 300 );
			$done = 'ilustracje';
		} elseif ( 'cta' === $action ) {
			update_option( 'tsl_show_biz_cta', empty( $_POST['tsl_show_biz_cta'] ) ? '0' : '1' );
			$done = 'cta';
		} else {
			return;
		}
		flush_rewrite_rules( false );
		delete_option( 'tsl_flush_rewrite' );
		wp_safe_redirect( add_query_arg( array( 'page' => 'tsl-demo', 'gotowe' => $done ), admin_url( 'tools.php' ) ) );
		exit;
	}
);

/**
 * Strona Narzędzia → TSL Manager.
 */
function tsl_render_demo_page() {
	$done = isset( $_GET['gotowe'] ) ? sanitize_key( wp_unslash( $_GET['gotowe'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$demo = tsl_is_demo_mode();
	?>
	<div class="wrap">
		<h1>TSL Manager — treści</h1>

		<?php if ( 'tryb' === $done ) : ?>
			<div class="notice notice-success"><p>Zapisano tryb serwisu: <strong><?php echo $demo ? 'demonstracyjny' : 'produkcyjny'; ?></strong>.</p></div>
		<?php elseif ( 'tylko-demo' === $done ) : ?>
			<div class="notice notice-warning"><p>Materiały przykładowe można wczytać tylko w trybie demonstracyjnym.</p></div>
		<?php elseif ( 'wczytano' === $done ) : ?>
			<div class="notice notice-success"><p>Materiały przykładowe są wczytane. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Zobacz serwis</a>.</p></div>
		<?php elseif ( 'artykuly' === $done || 'ilustracje' === $done ) : ?>
			<?php $stats = (array) get_transient( 'tsl_articles_stats' ); ?>
			<div class="notice notice-success"><p>
				<?php if ( 'artykuly' === $done ) : ?>
					Artykuły redakcji: nowe <?php echo (int) ( $stats['created'] ?? 0 ); ?> (<?php echo esc_html( 'publish' === ( $stats['status'] ?? '' ) ? 'opublikowane' : 'szkice' ); ?>), zaktualizowane <?php echo (int) ( $stats['updated'] ?? 0 ); ?>, pominięte (już były) <?php echo (int) ( $stats['skipped'] ?? 0 ); ?>.
					<?php if ( ! empty( $stats['denied'] ) ) : ?>Bez uprawnień do nadpisania: <?php echo (int) $stats['denied']; ?>.<?php endif; ?>
					<?php if ( ! empty( $stats['demo'] ) ) : ?>Usunięte przykładowe artykuły: <?php echo (int) $stats['demo']; ?>.<?php endif; ?>
				<?php endif; ?>
				Przypisane ilustracje: <?php echo (int) ( $stats['images'] ?? 0 ); ?>.
				<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">Zobacz wpisy</a>.
			</p></div>
		<?php elseif ( 'cta' === $done ) : ?>
			<div class="notice notice-success"><p>Zapisano ustawienie CTA biznesowego.</p></div>
		<?php elseif ( str_starts_with( $done, 'usunieto-' ) ) : ?>
			<div class="notice notice-success"><p>Usunięto materiały przykładowe: <?php echo (int) substr( $done, 9 ); ?>.</p></div>
		<?php endif; ?>

		<h2>Tryb serwisu</h2>
		<form method="post">
			<?php wp_nonce_field( 'tsl_demo' ); ?>
			<p><label><input type="checkbox" name="tsl_demo_mode" value="1" <?php checked( $demo ); ?>> <strong>Tryb demonstracyjny</strong> (prezentacja dla inwestorów)</label></p>
			<ul style="list-style:disc;margin-left:20px;max-width:780px">
				<li><strong>Włączony:</strong> materiały przykładowe są widoczne, strony informacyjne „Do uzupełnienia” są publikowane, puste działy pokazują „Sekcja w przygotowaniu”, a formularze bez skonfigurowanej poczty nie wysyłają danych i informują, że to wersja demonstracyjna.</li>
				<li><strong>Wyłączony (produkcja):</strong> materiały przykładowe są ukryte na stronie (zostają w bazie), nie da się ich wczytać, nowe strony z treścią zastępczą i importowane artykuły trafiają do szkiców, a puste podkategorie nie są pokazywane w nawigacji.</li>
			</ul>
			<button class="button button-primary" name="tsl_demo_action" value="mode">Zapisz tryb</button>
		</form>

		<hr style="margin:32px 0">
		<h2>Materiały przykładowe</h2>
		<p>Materiały przykładowe to artykuły, RiskBriefy, narzędzia (w tym checklisty i kalkulator), wydarzenia, raport, eksperci i wydanie newslettera z treścią zastępczą. Pokazują, jak serwis wygląda z treścią, zanim redakcja napisze własne teksty. Każdy jest oznaczony i można go usunąć jednym przyciskiem; materiały redakcji zostają.</p>
		<form method="post" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px">
			<?php wp_nonce_field( 'tsl_demo' ); ?>
			<?php if ( $demo ) : ?>
				<button class="button button-primary button-hero" name="tsl_demo_action" value="load">Wczytaj materiały przykładowe</button>
			<?php else : ?>
				<button class="button button-hero" disabled>Wczytaj materiały przykładowe</button>
			<?php endif; ?>
			<button class="button button-hero" name="tsl_demo_action" value="remove" onclick="return confirm('Usunąć trwale wszystkie materiały przykładowe? Będą potrzebne do prezentacji demo.');">Usuń materiały przykładowe</button>
		</form>
		<p class="description" style="margin-top:16px"><?php echo $demo ? 'Wczytanie zajmuje kilkanaście sekund. Ponowne kliknięcie nie tworzy duplikatów.' : 'Wczytanie jest dostępne tylko w trybie demonstracyjnym.'; ?></p>

		<?php if ( current_user_can( 'tsl_import_articles' ) ) : ?>
			<hr style="margin:32px 0">
			<h2>Artykuły redakcji</h2>
			<p>Gotowe teksty redakcji (<?php echo (int) count( tsl_articles_data() ); ?> artykułów z Dysku Google) — z kategoriami, formatami, źródłami, tytułami SEO i zajawkami do newslettera. Artykuły rozpoznawane są po numerze ID (np. ZF-03-1), więc ponowne kliknięcie nie tworzy duplikatów. Domyślnie trafiają do <strong>szkiców</strong>.</p>
			<form method="post" onsubmit="return !this.tsl_overwrite.checked || confirm('Nadpisać artykuły wczytane wcześniej? Poprawki zrobione w panelu w tych artykułach zostaną utracone.');">
				<?php wp_nonce_field( 'tsl_demo' ); ?>
				<?php if ( $demo ) : ?>
					<p><label><input type="checkbox" name="tsl_publish" value="1"> Opublikuj od razu (tylko na potrzeby prezentacji w trybie demonstracyjnym)</label></p>
				<?php else : ?>
					<p class="description">Tryb produkcyjny: artykuły zostaną zapisane jako szkice. Opublikujesz je po przeczytaniu.</p>
				<?php endif; ?>
				<p><label><input type="checkbox" name="tsl_remove_demo" value="1"> Usuń przykładowe artykuły (demo), które zastępują teksty redakcji</label></p>
				<p><label><input type="checkbox" name="tsl_overwrite" value="1"> <strong>Nadpisz</strong> artykuły wczytane wcześniej — poprawki zrobione w panelu zostaną utracone</label></p>
				<p><button class="button button-primary button-hero" name="tsl_demo_action" value="articles">Wczytaj artykuły redakcji</button></p>
			</form>
		<?php endif; ?>

		<h3 style="margin-top:28px">Ilustracje</h3>
		<p>Wgraj ilustracje w <a href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>">Media → Dodaj nowy</a> (można przeciągnąć wszystkie pliki naraz). Plik, którego nazwa zaczyna się od ID artykułu (np. <code>ZF-03-1.png</code> albo <code>ZF-03-1_stawka.png</code>), sam zostanie obrazkiem wyróżniającym tego artykułu.</p>
		<form method="post">
			<?php wp_nonce_field( 'tsl_demo' ); ?>
			<button class="button" name="tsl_demo_action" value="images">Przypisz ilustracje ponownie</button>
			<span class="description">— gdy ilustracje były wgrane przed artykułami.</span>
		</form>

		<h3 style="margin-top:28px">CTA biznesowe pod artykułami</h3>
		<form method="post">
			<?php wp_nonce_field( 'tsl_demo' ); ?>
			<p><label><input type="checkbox" name="tsl_show_biz_cta" value="1" <?php checked( tsl_show_biz_cta() ); ?>> Pokazuj ramkę „CTA biznesowe” pod artykułami (treść w polu „CTA biznesowe” każdego artykułu). Domyślnie wyłączone.</label></p>
			<button class="button" name="tsl_demo_action" value="cta">Zapisz</button>
		</form>
	</div>
	<?php
}
