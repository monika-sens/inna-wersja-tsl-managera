<?php
/**
 * Elementy pojedynczego materiału zależne od typu treści (rozdz. 5, 7, 16, 17).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Data i godzina pola datetime w formacie serwisu.
 *
 * @param string $value   Wartość Y-m-d\TH:i.
 * @param bool   $with_time Z godziną.
 */
function tsl_format_datetime( $value, $with_time = true ) {
	if ( ! $value ) {
		return '';
	}
	$date = date_create_immutable( $value, wp_timezone() );
	if ( ! $date ) {
		return '';
	}
	return wp_date( $with_time ? get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) : get_option( 'date_format' ), $date->getTimestamp() );
}

/**
 * Data z pola date.
 *
 * @param string $value Wartość Y-m-d.
 */
function tsl_format_date( $value ) {
	return tsl_format_datetime( $value ? $value . 'T12:00' : '', false );
}

/**
 * Lista linii jako HTML; adresy URL są podlinkowane.
 *
 * @param string[] $lines Linie.
 * @param string   $tag   ul | ol.
 * @param string   $class Klasa listy.
 */
function tsl_lines_html( $lines, $tag = 'ul', $class = '' ) {
	if ( ! $lines ) {
		return '';
	}
	$html = '<' . $tag . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>';
	foreach ( $lines as $line ) {
		$html .= '<li>' . make_clickable( function_exists( 'tsl_md_links' ) ? tsl_md_links( $line ) : esc_html( $line ) ) . '</li>';
	}
	return $html . '</' . $tag . '>';
}

/**
 * Główny następny krok materiału (rozdz. 16: jedno główne CTA).
 *
 * Kolejność: wybór redakcji (pole „Główny następny krok”), powiązane
 * narzędzie, narzędzie z tej samej podkategorii, narzędzie z filaru.
 *
 * @param WP_Post $post Materiał.
 * @return array{label: string, kind: string, title: string, url: string, cta: string}|null
 */
function tsl_next_step( $post ) {
	$choice = tsl_field( 'tsl_cta', $post );

	if ( 'wydarzenie' === $choice ) {
		$event = tsl_related( 'wydarzenie', $post, 1 );
		if ( $event ) {
			return array(
				'label' => 'Następny krok',
				'kind'  => tsl_kind_label( $event[0] ),
				'title' => get_the_title( $event[0] ),
				'url'   => get_permalink( $event[0] ),
				'cta'   => tsl_event_is_past( $event[0] ) ? 'Zobacz materiały' : 'Zarejestruj się',
			);
		}
	}
	if ( 'ekspert' === $choice ) {
		$expert = tsl_related( 'ekspert', $post, 1 );
		if ( $expert ) {
			return array(
				'label' => 'Porozmawiaj z ekspertem',
				'kind'  => (string) tsl_field( 'tsl_position', $expert[0] ),
				'title' => get_the_title( $expert[0] ),
				'url'   => get_permalink( $expert[0] ),
				'cta'   => 'Zobacz profil',
			);
		}
	}
	if ( 'newsletter' === $choice ) {
		return array(
			'label' => 'Następny krok',
			'kind'  => 'Newsletter',
			'title' => 'RiskBrief TSL i najważniejsze analizy co tydzień',
			'url'   => tsl_page_url( 'newsletter' ),
			'cta'   => 'Zapisz się',
		);
	}
	if ( 'kontakt' === $choice ) {
		return array(
			'label' => 'Następny krok',
			'kind'  => 'Kontakt',
			'title' => 'Masz pytanie do redakcji lub eksperta?',
			'url'   => tsl_page_url( 'kontakt' ),
			'cta'   => 'Napisz do nas',
		);
	}

	// Narzędzie jest samo celem; wydarzenie ma rejestrację w nagłówku; profile osób i partnerów nie mają CTA narzędziowego.
	if ( in_array( $post->post_type, array( 'narzedzie', tsl_event_post_type(), 'ekspert', 'partner' ), true ) ) {
		return null;
	}

	$tool = tsl_related( 'narzedzie', $post, 1 );
	if ( ! $tool ) {
		$primary = tsl_get_primary_term( $post );
		foreach ( $primary ? array( $primary, tsl_get_pillar_for_term( $primary ) ) : array() as $scope ) {
			if ( $scope && ! $tool ) {
				$tool = tsl_posts(
					array(
						'post_type'      => 'narzedzie',
						'posts_per_page' => 1,
						'tax_query'      => tsl_in_category( $scope ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
					)
				);
			}
		}
	}
	if ( ! $tool ) {
		return null;
	}
	return array(
		'label' => 'Następny krok',
		'kind'  => tsl_kind_label( $tool[0] ),
		'title' => get_the_title( $tool[0] ),
		'url'   => get_permalink( $tool[0] ),
		'cta'   => 'Użyj narzędzia',
	);
}

/**
 * Odznaka materiału partnerskiego / sponsorowanego (zasady redakcyjne).
 *
 * @param WP_Post $post Materiał.
 */
function tsl_sponsor_badge( $post ) {
	$label = tsl_field_label( 'tsl_label', $post );
	if ( ! $label || ! tsl_field( 'tsl_label', $post ) ) {
		return '';
	}
	$partner = tsl_related( 'partner', $post, 1 );
	$suffix  = $partner ? ' · <a href="' . esc_url( get_permalink( $partner[0] ) ) . '">' . esc_html( get_the_title( $partner[0] ) ) . '</a>' : '';
	return '<span class="sponsor-badge">' . esc_html( $label ) . $suffix . '</span>';
}

/**
 * Plik do pobrania (narzędzie, raport).
 *
 * @param WP_Post $post  Materiał.
 * @param string  $label Etykieta przycisku.
 */
function tsl_download_button( $post, $label ) {
	$file_id = (int) tsl_field( 'tsl_file', $post );
	$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
	if ( ! $url ) {
		return '';
	}
	$path = get_attached_file( $file_id );
	$size = $path && file_exists( $path ) ? size_format( filesize( $path ) ) : '';
	$ext  = strtoupper( pathinfo( $url, PATHINFO_EXTENSION ) );

	// Dostęp „po podaniu e-maila”: link przychodzi e-mailem (formularz pod przyciskiem).
	if ( tsl_download_is_gated( $post ) ) {
		return '<a class="btn btn-primary download-button" href="#pobierz" data-track="Pobranie|otwarcie formularza|' . esc_attr( get_the_title( $post ) ) . '">' . tsl_icon( 'download' ) . esc_html( $label ) . ' <span class="download-meta">' . esc_html( trim( $ext . ' ' . $size ) ) . '</span></a><span class="download-note">Link wyślemy na Twój adres e-mail.</span>';
	}

	return '<a class="btn btn-primary download-button" href="' . esc_url( $url ) . '" download>' . tsl_icon( 'download' ) . esc_html( $label ) . ' <span class="download-meta">' . esc_html( trim( $ext . ' ' . $size ) ) . '</span></a>';
}

/**
 * Czy pełny plik wymaga podania e-maila.
 *
 * @param WP_Post $post Materiał.
 */
function tsl_download_is_gated( $post ) {
	return 'e-mail' === tsl_field( 'tsl_access', $post ) && (int) tsl_field( 'tsl_file', $post ) && wp_get_attachment_url( (int) tsl_field( 'tsl_file', $post ) );
}

/**
 * Formularz pobrania (dla plików z dostępem po podaniu e-maila).
 *
 * @param WP_Post $post Materiał.
 */
function tsl_download_form( $post ) {
	if ( ! tsl_download_is_gated( $post ) ) {
		return;
	}
	echo '<section class="form-section" id="pobierz">';
	get_template_part(
		'template-parts/form',
		null,
		array(
			'type'  => 'pobranie',
			'post'  => $post,
			'title' => 'raport' === $post->post_type ? 'Pobierz pełny raport' : 'Pobierz narzędzie',
		)
	);
	echo '</section>';
}

/**
 * Karty osób (eksperci, prelegenci).
 *
 * @param WP_Post[] $people  Eksperci.
 * @param string    $heading Nagłówek.
 */
function tsl_people_box( $people, $heading ) {
	if ( ! $people ) {
		return;
	}
	echo '<section class="margin-block people-box">';
	if ( $heading ) {
		echo '<p class="margin-heading">' . esc_html( $heading ) . '</p>';
	}
	echo '<ul class="people-list">';
	foreach ( $people as $person ) {
		echo '<li>';
		get_template_part( 'template-parts/person', null, array( 'post' => $person ) );
		echo '</li>';
	}
	echo '</ul></section>';
}

/**
 * Inicjały (zastępcze zdjęcie osoby).
 *
 * @param string $name Imię i nazwisko.
 */
function tsl_initials( $name ) {
	$words = preg_split( '/\s+/u', preg_replace( '/[—–-].*$/u', '', $name ) );
	$init  = '';
	foreach ( array_slice( array_filter( $words ), 0, 2 ) as $word ) {
		$init .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
	}
	return $init;
}

/**
 * Prosta lista powiązanych materiałów.
 *
 * @param WP_Post[] $posts   Materiały.
 * @param string    $heading Nagłówek.
 */
function tsl_link_list( $posts, $heading ) {
	if ( ! $posts ) {
		return;
	}
	echo '<section class="margin-block"><p class="margin-heading">' . esc_html( $heading ) . '</p><ul class="margin-list">';
	foreach ( $posts as $item ) {
		echo '<li><span class="margin-kind">' . esc_html( tsl_kind_label( $item ) ) . '</span><a href="' . esc_url( get_permalink( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a></li>';
	}
	echo '</ul></section>';
}

/**
 * Checklista z pola „Punkty checklisty” — struktura sekcji i punktów.
 *
 * @param WP_Post $post Narzędzie.
 * @return array<int, array{title: string, items: array<int, array{text: string, critical: bool}>}>
 */
function tsl_checklist_sections( $post ) {
	$sections = array();
	$current  = -1;
	foreach ( tsl_field_lines( 'tsl_checklist', $post ) as $line ) {
		if ( str_starts_with( $line, '##' ) ) {
			$sections[] = array(
				'title' => trim( ltrim( $line, '#' ) ),
				'items' => array(),
			);
			$current    = count( $sections ) - 1;
			continue;
		}
		if ( $current < 0 ) {
			$sections[] = array(
				'title' => '',
				'items' => array(),
			);
			$current    = 0;
		}
		$critical                         = str_starts_with( $line, '!' );
		$sections[ $current ]['items'][] = array(
			'text'     => trim( ltrim( $line, '!' ) ),
			'critical' => $critical,
		);
	}
	return array_values(
		array_filter(
			$sections,
			function ( $section ) {
				return $section['items'];
			}
		)
	);
}

/**
 * Interaktywna checklista: odhaczanie, postęp, punkty krytyczne, wydruk / PDF.
 * Stan zapisuje się tylko w przeglądarce czytelnika (localStorage).
 *
 * @param WP_Post $post Narzędzie.
 */
function tsl_checklist( $post ) {
	$sections = tsl_checklist_sections( $post );
	if ( ! $sections ) {
		return;
	}
	$version  = (string) tsl_field( 'tsl_version', $post );
	$updated  = (string) tsl_field( 'tsl_version_date', $post );
	$alert    = (string) tsl_field( 'tsl_checklist_alert', $post );
	$alert    = $alert ? $alert : 'Co najmniej jeden punkt krytyczny nie jest spełniony. Wstrzymaj działanie i skonsultuj sprawę z przełożonym lub działem bezpieczeństwa.';
	$total    = array_sum(
		array_map(
			function ( $s ) {
				return count( $s['items'] );
			},
			$sections
		)
	);
	$n        = 0;
	?>
	<section class="checklist" id="checklista" data-checklist="<?php echo esc_attr( 'tsl-checklist-' . $post->ID . '-' . ( $version ? $version : '0' ) ); ?>" data-title="<?php echo esc_attr( get_the_title( $post ) ); ?>" aria-labelledby="checklist-title-<?php echo esc_attr( $post->ID ); ?>">
		<div class="checklist-print-head" aria-hidden="true">
			<p class="checklist-brand"><strong>TSL</strong> Manager</p>
			<p class="checklist-meta"><?php echo esc_html( trim( ( $version ? 'Wersja ' . $version : '' ) . ( $updated ? ' · aktualizacja ' . tsl_format_date( $updated ) : '' ) ) ); ?></p>
			<div class="checklist-fill">
				<span>Firma / kontrahent: ______________________________</span>
				<span>Osoba sprawdzająca: ______________________________</span>
				<span>Data: ____________</span>
			</div>
		</div>

		<header class="checklist-head">
			<div>
				<p class="checklist-kicker">Checklista<?php echo $version ? ' · wersja ' . esc_html( $version ) : ''; ?></p>
				<h2 class="checklist-title" id="checklist-title-<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
			</div>
			<div class="checklist-progress" aria-live="polite">
				<span class="checklist-count"><strong data-done>0</strong><span class="checklist-of"> z <?php echo esc_html( $total ); ?></span><span class="screen-reader-text"> punktów sprawdzonych</span></span>
				<span class="checklist-bar" aria-hidden="true"><span data-bar></span></span>
			</div>
		</header>

		<?php foreach ( $sections as $section ) : ?>
			<fieldset class="checklist-section">
				<?php if ( $section['title'] ) : ?>
					<legend><?php echo esc_html( $section['title'] ); ?></legend>
				<?php endif; ?>
				<ul>
					<?php foreach ( $section['items'] as $item ) : ?>
						<?php $id = 'cl-' . $post->ID . '-' . ( ++$n ); ?>
						<li class="checklist-item<?php echo $item['critical'] ? ' is-critical' : ''; ?>">
							<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" data-item="<?php echo esc_attr( $n ); ?>" <?php echo $item['critical'] ? 'data-critical' : ''; ?>>
							<label for="<?php echo esc_attr( $id ); ?>">
								<span class="checklist-box" aria-hidden="true"></span>
								<span class="checklist-text"><?php echo esc_html( $item['text'] ); ?></span>
								<?php if ( $item['critical'] ) : ?>
									<span class="checklist-flag">Krytyczne</span>
								<?php endif; ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</fieldset>
		<?php endforeach; ?>

		<div class="checklist-notes">
			<label for="cl-notes-<?php echo esc_attr( $post->ID ); ?>">Uwagi</label>
			<textarea id="cl-notes-<?php echo esc_attr( $post->ID ); ?>" rows="3" data-notes placeholder="Np. numer zlecenia, ustalenia, osoby kontaktowe"></textarea>
		</div>

		<p class="checklist-alert" data-alert hidden role="status"><?php echo esc_html( $alert ); ?></p>
		<p class="checklist-done" data-complete hidden role="status">Wszystkie punkty sprawdzone.</p>

		<div class="checklist-actions">
			<button type="button" class="btn btn-primary" data-print><?php echo tsl_icon( 'print' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Drukuj / zapisz jako PDF</button>
			<button type="button" class="btn btn-ghost" data-reset>Wyczyść zaznaczenia</button>
		</div>
		<p class="checklist-privacy">Zaznaczenia i uwagi zapisują się tylko w tej przeglądarce — nie wysyłamy ich do serwisu.</p>

		<p class="checklist-print-foot" aria-hidden="true">Źródło: <?php echo esc_html( get_permalink( $post ) ); ?> · Narzędzie jest wzorem — dostosuj je do procesów i umów swojej firmy.</p>
	</section>
	<?php
}
