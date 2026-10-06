<?php
/**
 * Newsletter: panel administracyjny (Newsletter → Subskrybenci, Ustawienia).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		// Dane osobowe subskrybentów: tylko administrator albo osobne uprawnienie.
		add_submenu_page( 'edit.php?post_type=wydanie', 'Subskrybenci', 'Subskrybenci', 'tsl_manage_subscribers', 'tsl-subscribers', 'tsl_nl_render_subscribers' );
		add_submenu_page( 'edit.php?post_type=wydanie', 'Ustawienia newslettera', 'Ustawienia', 'manage_options', 'tsl-newsletter-settings', 'tsl_nl_render_settings' );
	}
);

/**
 * Akcje: eksport CSV, usunięcie subskrybenta, e-mail testowy ustawień.
 * Wyłącznie POST, z nonce i kontrolą uprawnień.
 */
add_action(
	'admin_init',
	function () {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['tsl_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['tsl_action'] ) );
		if ( ! in_array( $action, array( 'export', 'delete', 'testmail' ), true ) ) {
			return;
		}
		check_admin_referer( 'tsl_nl_' . $action );
		$needed = 'testmail' === $action ? 'manage_options' : 'tsl_manage_subscribers';
		if ( ! current_user_can( $needed ) ) {
			wp_die( 'Brak uprawnień.', 403 );
		}

		if ( 'export' === $action ) {
			$list = tsl_nl_query(
				array(
					'per_page' => 1000000,
					'status'   => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '',
				)
			);
			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=subskrybenci-' . gmdate( 'Y-m-d' ) . '.csv' );
			$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM: polskie znaki w Excelu.
			fputcsv( $out, array( 'email', 'status', 'zapis (UTC)', 'potwierdzenie (UTC)', 'wypisanie (UTC)', 'źródło', 'treść zgody', 'IP' ), ';' );
			foreach ( $list['items'] as $s ) {
				fputcsv( $out, array( $s->email, $s->status, $s->created_at, $s->confirmed_at, $s->unsubscribed_at, $s->source, $s->consent_text, $s->consent_ip ), ';' );
			}
			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}

		if ( 'delete' === $action && isset( $_POST['id'] ) ) {
			tsl_nl_delete( absint( $_POST['id'] ) );
			wp_safe_redirect( admin_url( 'edit.php?post_type=wydanie&page=tsl-subscribers&deleted=1' ) );
			exit;
		}

		if ( 'testmail' === $action ) {
			$to   = wp_get_current_user()->user_email;
			$ok   = tsl_nl_mail(
				$to,
				'Test poczty — TSL Manager',
				tsl_nl_layout(
					array(
						'title'  => 'Poczta działa',
						'body'   => '<p>To jest wiadomość testowa z serwisu TSL Manager. Jeśli ją widzisz, ustawienia poczty wychodzącej są poprawne.</p>',
						'footer' => esc_html( tsl_nl_setting( 'publisher' ) ),
					)
				)
			);
			$args = $ok ? array( 'testmail' => 'ok' ) : array( 'testmail' => 'blad', 'msg' => rawurlencode( $GLOBALS['tsl_nl_last_error'] ?? '' ) );
			wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=wydanie&page=tsl-newsletter-settings' ) ) );
			exit;
		}
	}
);

/**
 * Lista subskrybentów.
 */
function tsl_nl_render_subscribers() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	// phpcs:enable
	$list   = tsl_nl_query(
		array(
			'status' => $status,
			'search' => $search,
			'page'   => $paged,
		)
	);
	$counts = tsl_nl_counts();
	$labels = array(
		'active'       => 'Aktywni',
		'pending'      => 'Niepotwierdzeni',
		'unsubscribed' => 'Wypisani',
	);
	$base   = admin_url( 'edit.php?post_type=wydanie&page=tsl-subscribers' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Subskrybenci</h1>
		<form method="post" style="display:inline">
			<?php wp_nonce_field( 'tsl_nl_export' ); ?>
			<input type="hidden" name="tsl_action" value="export">
			<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
			<button class="page-title-action">Eksport CSV</button>
		</form>
		<hr class="wp-header-end">

		<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p>Usunięto subskrybenta wraz z danymi zgody.</p></div>
		<?php endif; ?>

		<p>Newsletter wysyłamy tylko do osób <strong>aktywnych</strong>, czyli takich, które potwierdziły zapis linkiem z e-maila. Niepotwierdzeni nie otrzymują żadnych wiadomości poza prośbą o potwierdzenie.</p>

		<ul class="subsubsub">
			<li><a href="<?php echo esc_url( $base ); ?>" <?php echo $status ? '' : 'class="current"'; ?>>Wszyscy <span class="count">(<?php echo esc_html( array_sum( $counts ) ); ?>)</span></a> |</li>
			<?php foreach ( $labels as $key => $label ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( 'status', $key, $base ) ); ?>" <?php echo $status === $key ? 'class="current"' : ''; ?>><?php echo esc_html( $label ); ?> <span class="count">(<?php echo esc_html( $counts[ $key ] ?? 0 ); ?>)</span></a><?php echo 'unsubscribed' === $key ? '' : ' |'; ?></li>
			<?php endforeach; ?>
		</ul>

		<form method="get" class="search-box">
			<input type="hidden" name="post_type" value="wydanie">
			<input type="hidden" name="page" value="tsl-subscribers">
			<label class="screen-reader-text" for="tsl-sub-search">Szukaj adresu</label>
			<input type="search" id="tsl-sub-search" name="s" value="<?php echo esc_attr( $search ); ?>">
			<button class="button">Szukaj</button>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>E-mail</th><th>Status</th><th>Zapis</th><th>Potwierdzenie</th><th>Źródło</th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $list['items'] ) : ?>
				<tr><td colspan="6">Brak subskrybentów.</td></tr>
			<?php endif; ?>
			<?php foreach ( $list['items'] as $s ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $s->email ); ?></strong></td>
					<td><?php echo esc_html( $labels[ $s->status ] ?? $s->status ); ?></td>
					<td><?php echo esc_html( $s->created_at ? get_date_from_gmt( $s->created_at, 'Y-m-d H:i' ) : '—' ); ?></td>
					<td><?php echo esc_html( $s->confirmed_at ? get_date_from_gmt( $s->confirmed_at, 'Y-m-d H:i' ) : '—' ); ?></td>
					<td><?php echo $s->source ? '<a href="' . esc_url( $s->source ) . '">' . esc_html( wp_parse_url( $s->source, PHP_URL_PATH ) ? wp_parse_url( $s->source, PHP_URL_PATH ) : '/' ) . '</a>' : '—'; ?></td>
					<td>
						<form method="post" onsubmit="return confirm('Usunąć trwale ten adres i dowód zgody? Użyj tylko przy żądaniu usunięcia danych — zwykłe wypisanie zachowuje historię zgody.');">
							<?php wp_nonce_field( 'tsl_nl_delete' ); ?>
							<input type="hidden" name="tsl_action" value="delete">
							<input type="hidden" name="id" value="<?php echo (int) $s->id; ?>">
							<button class="button-link submitdelete" style="color:#b32d2e">Usuń dane</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php
		$pages = (int) ceil( $list['total'] / 50 );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			echo '</div></div>';
		}
		?>
	</div>
	<?php
}

/**
 * Ustawienia newslettera i poczty wychodzącej.
 */
function tsl_nl_render_settings() {
	$s = wp_parse_args( (array) get_option( 'tsl_newsletter', array() ), tsl_nl_defaults() );
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$test = isset( $_GET['testmail'] ) ? sanitize_key( wp_unslash( $_GET['testmail'] ) ) : '';
	$msg  = isset( $_GET['msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['msg'] ) ) ) : '';
	// phpcs:enable
	$field = function ( $key, $label, $type = 'text', $help = '' ) use ( $s ) {
		$value = 'smtp_password' === $key ? '' : $s[ $key ];
		echo '<tr><th scope="row"><label for="tsl-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="3" id="tsl-' . esc_attr( $key ) . '" name="tsl_newsletter[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'checkbox' === $type ) {
			echo '<input type="checkbox" id="tsl-' . esc_attr( $key ) . '" name="tsl_newsletter[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, 1, false ) . '>';
		} else {
			$placeholder = ( 'smtp_password' === $key && ( $s['smtp_password'] || defined( 'TSL_SMTP_PASSWORD' ) ) ) ? 'zapisane — wpisz, aby zmienić' : '';
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="tsl-' . esc_attr( $key ) . '" name="tsl_newsletter[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">';
		}
		if ( $help ) {
			echo '<p class="description">' . wp_kses( $help, array( 'code' => array(), 'strong' => array() ) ) . '</p>';
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1>Ustawienia newslettera</h1>

		<?php if ( 'ok' === $test ) : ?>
			<div class="notice notice-success"><p>Wiadomość testowa wysłana. Sprawdź skrzynkę (również folder spam).</p></div>
		<?php elseif ( 'blad' === $test ) : ?>
			<div class="notice notice-error"><p>Nie udało się wysłać wiadomości. <?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'tsl_newsletter' ); ?>

			<h2>Nadawca i treść</h2>
			<table class="form-table" role="presentation">
				<?php
				$field( 'from_name', 'Nazwa nadawcy' );
				$field( 'from_email', 'Adres nadawcy', 'email', 'Musi to być skrzynka w Waszej domenie (np. <code>newsletter@tslmanager.pl</code>) — ta sama, której dane SMTP wpisujesz niżej.' );
				$field( 'reply_to', 'Adres do odpowiedzi', 'email', 'Opcjonalnie, np. adres redakcji.' );
				$field( 'publisher', 'Dane wydawcy w stopce', 'textarea', 'Nazwa i adres wydawcy — wymagane w stopce każdej wiadomości.' );
				$field( 'consent_text', 'Treść zgody przy zapisie', 'textarea', 'Wyświetlana przy formularzu i zapisywana jako dowód zgody. <strong>Treść powinien zatwierdzić prawnik.</strong>' );
				?>
			</table>

			<h2>Wysyłka</h2>
			<table class="form-table" role="presentation">
				<?php
				$field( 'hourly_limit', 'Limit wiadomości na godzinę', 'number', 'Ustaw z zapasem poniżej limitu serwera pocztowego. Dla poczty home.pl bezpiecznie: 300.' );
				$field( 'smtp_enabled', 'Wysyłaj przez SMTP', 'checkbox', 'Zalecane. Bez SMTP serwis wysyła pocztę funkcją serwera, co częściej kończy się w spamie.' );
				$field( 'smtp_host', 'Serwer SMTP', 'text', 'Dane z panelu home.pl: Poczta → wybrana skrzynka → konfiguracja programu pocztowego (serwer poczty wychodzącej).' );
				$field( 'smtp_port', 'Port', 'number', 'Zwykle 465 (SSL) albo 587 (TLS).' );
				?>
				<tr><th scope="row"><label for="tsl-smtp_secure">Szyfrowanie</label></th><td>
					<select id="tsl-smtp_secure" name="tsl_newsletter[smtp_secure]">
						<?php foreach ( array( 'ssl' => 'SSL (port 465)', 'tls' => 'TLS / STARTTLS (port 587)', '' => 'Brak' ) as $v => $l ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s['smtp_secure'], $v ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</td></tr>
				<?php
				$field( 'smtp_user', 'Login SMTP', 'text', 'Zwykle pełny adres skrzynki.' );
				$field( 'smtp_password', 'Hasło SMTP', 'password', defined( 'TSL_SMTP_PASSWORD' ) ? 'Hasło jest ustawione w pliku wp-config.php.' : 'Bezpieczniej: dopisz w wp-config.php <code>define( \'TSL_SMTP_PASSWORD\', \'…\' );</code> — wtedy hasło nie jest przechowywane w bazie.' );
				?>
			</table>
			<?php submit_button( 'Zapisz ustawienia' ); ?>
		</form>

		<h2>Test poczty</h2>
		<p>Po zapisaniu ustawień wyślij wiadomość testową na swój adres (<?php echo esc_html( wp_get_current_user()->user_email ); ?>).</p>
		<form method="post">
			<?php wp_nonce_field( 'tsl_nl_testmail' ); ?>
			<input type="hidden" name="tsl_action" value="testmail">
			<p><button class="button">Wyślij wiadomość testową</button></p>
		</form>

		<h2>Dostarczalność — do zrobienia raz, w panelu domeny</h2>
		<p>Aby wiadomości nie trafiały do spamu, domena nadawcy musi mieć rekordy <strong>SPF</strong>, <strong>DKIM</strong> i <strong>DMARC</strong>. Szczegóły: <code>docs/NEWSLETTER.md</code> w repozytorium.</p>
	</div>
	<?php
}

/**
 * Kolumna „Wysyłka” na liście wydań.
 */
add_filter(
	'manage_wydanie_posts_columns',
	function ( $columns ) {
		$columns['tsl_nl'] = 'Wysyłka';
		return $columns;
	}
);
add_action(
	'manage_wydanie_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'tsl_nl' !== $column ) {
			return;
		}
		$st     = tsl_nl_issue_status( $post_id );
		$labels = array(
			'draft'   => 'Nie wysłano',
			'sending' => sprintf( 'W trakcie: %d / %d', $st['sent'], $st['total'] ),
			'sent'    => sprintf( 'Wysłano: %d', $st['sent'] ),
		);
		echo esc_html( $labels[ $st['status'] ] ?? $st['status'] );
	},
	10,
	2
);
