<?php
/**
 * Wczytanie struktury serwisu: drzewo filarów, słowniki, strony informacyjne
 * i ustawienia. Operacja jest idempotentna — można ją uruchamiać wielokrotnie,
 * istniejące terminy i strony nie są nadpisywane ani duplikowane.
 *
 * Materiały demonstracyjne (TSL_Seeder::demo) są opcjonalne i oznaczone
 * meta `_tsl_demo`, więc można je w całości usunąć jednym poleceniem.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seeder struktury serwisu.
 */
class TSL_Seeder {

	/**
	 * Dziennik operacji (dla WP-CLI).
	 *
	 * @var string[]
	 */
	public static $log = array();

	/**
	 * Pełne wczytanie struktury (bez treści demonstracyjnych).
	 */
	public static function run() {
		self::options();
		self::categories();
		self::dictionaries();
		self::pages();
		if ( function_exists( 'tsl_ccb_install' ) ) {
			tsl_ccb_install();
		}
		update_option( 'tsl_flush_rewrite', 1, false );
	}

	/**
	 * Podstawowe ustawienia serwisu.
	 */
	public static function options() {
		$options = array(
			'blogname'            => 'TSL Manager',
			'blogdescription'     => 'Niezależny portal ekspercki dla menedżerów transportu, spedycji, logistyki i łańcucha dostaw.',
			'permalink_structure' => '/%category%/%postname%/',
			'timezone_string'     => 'Europe/Warsaw',
			'date_format'         => 'j F Y',
			'time_format'         => 'H:i',
			'start_of_week'       => 1,
			'WPLANG'              => 'pl_PL',
			'default_comment_status' => 'closed', // MVP: bez otwartych komentarzy.
			'default_ping_status'    => 'closed',
		);
		foreach ( $options as $key => $value ) {
			update_option( $key, $value );
		}

		// Domyślna kategoria sygnalizuje materiał bez filaru — nie jest filarem.
		$default = get_term( (int) get_option( 'default_category' ), 'category' );
		if ( $default instanceof WP_Term && 'do-przypisania' !== $default->slug ) {
			wp_update_term(
				$default->term_id,
				'category',
				array(
					'name' => 'Do przypisania',
					'slug' => 'do-przypisania',
				)
			);
		}
		// Domyślne treści instalacji WordPressa („Witaj, świecie!”, „Przykładowa strona”),
		// o ile nikt ich nie edytował — inaczej trafiałyby do serwisu i newslettera.
		foreach ( array( 'post' => array( 'hello-world', 'witaj-swiecie' ), 'page' => array( 'sample-page', 'przykladowa-strona' ) ) as $type => $slugs ) {
			foreach ( $slugs as $slug ) {
				$default_post = get_page_by_path( $slug, OBJECT, $type );
				if ( $default_post && $default_post->post_modified === $default_post->post_date ) {
					wp_delete_post( $default_post->ID, true );
				}
			}
		}

		if ( function_exists( 'tsl_events_configure_tec' ) ) {
			tsl_events_configure_tec();
		}

		self::$log[] = 'Ustawienia serwisu zapisane.';
	}

	/**
	 * Drzewo sześciu filarów.
	 */
	public static function categories() {
		$tree  = require TSL_CORE_DIR . 'includes/data/structure.php';
		$count = 0;

		foreach ( $tree as $order => $pillar ) {
			$pillar_id = self::ensure_term( $pillar, 'category', 0, $order + 1, $count );
			update_term_meta( $pillar_id, 'tsl_pillar', '1' );
			update_term_meta( $pillar_id, 'tsl_short', $pillar['short'] );
			update_term_meta( $pillar_id, 'tsl_promise', $pillar['promise'] );
			self::children( $pillar['children'] ?? array(), $pillar_id, $count );
		}
		self::$log[] = sprintf( 'Drzewo filarów: %d nowych terminów.', $count );
	}

	/**
	 * Rekurencyjnie tworzy podkategorie.
	 *
	 * @param array $children  Węzły.
	 * @param int   $parent_id Rodzic.
	 * @param int   $count     Licznik nowych terminów.
	 */
	private static function children( $children, $parent_id, &$count ) {
		foreach ( $children as $order => $child ) {
			$term_id = self::ensure_term( $child, 'category', $parent_id, $order + 1, $count );
			if ( ! empty( $child['mega'] ) ) {
				update_term_meta( $term_id, 'tsl_mega', '1' );
			}
			self::children( $child['children'] ?? array(), $term_id, $count );
		}
	}

	/**
	 * Tworzy termin, jeśli nie istnieje pod danym rodzicem.
	 *
	 * @param array  $node      Dane terminu (name, slug, description).
	 * @param string $taxonomy  Taksonomia.
	 * @param int    $parent_id Rodzic.
	 * @param int    $order     Kolejność.
	 * @param int    $count     Licznik nowych terminów.
	 * @return int ID terminu.
	 */
	private static function ensure_term( $node, $taxonomy, $parent_id, $order, &$count ) {
		$existing = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'slug'       => $node['slug'],
				'parent'     => $parent_id,
				'hide_empty' => false,
				'number'     => 1,
			)
		);

		if ( ! is_wp_error( $existing ) && $existing ) {
			$term_id = $existing[0]->term_id;
		} else {
			$result = wp_insert_term(
				$node['name'],
				$taxonomy,
				array(
					'slug'        => $node['slug'],
					'parent'      => $parent_id,
					'description' => $node['description'] ?? '',
				)
			);
			if ( is_wp_error( $result ) ) {
				self::$log[] = sprintf( 'Błąd przy „%s”: %s', $node['name'], $result->get_error_message() );
				return 0;
			}
			$term_id = $result['term_id'];
			++$count;
		}

		if ( '' === get_term_meta( $term_id, 'tsl_order', true ) ) {
			update_term_meta( $term_id, 'tsl_order', $order );
		}
		return (int) $term_id;
	}

	/**
	 * Słowniki taksonomii pomocniczych.
	 */
	public static function dictionaries() {
		$dictionaries = require TSL_CORE_DIR . 'includes/data/terms.php';
		$count        = 0;

		foreach ( $dictionaries as $key => $terms ) {
			$order = 0;
			foreach ( $terms as $slug => $data ) {
				$data    = is_array( $data ) ? $data : array( 'name' => $data );
				$term_id = self::ensure_term( array_merge( $data, array( 'slug' => $slug ) ), 'tsl_' . $key, 0, ++$order, $count );
				foreach ( $data['meta'] ?? array() as $meta_key => $meta_value ) {
					update_term_meta( $term_id, $meta_key, $meta_value );
				}
			}
		}
		self::$log[] = sprintf( 'Słowniki taksonomii: %d nowych terminów.', $count );
	}

	/**
	 * Obowiązkowe strony informacyjne (rozdz. 18).
	 */
	public static function pages() {
		$pages = array(
			'o-projekcie'          => array( 'O projekcie', 'Misja, odbiorcy, sześć filarów, geneza, partner założycielski, zasada niezależności.' ),
			'zasady-redakcyjne'    => array( 'Zasady redakcyjne', 'Oznaczanie materiałów partnerskich, korekty, źródła, konflikty interesów, odpowiedzialność ekspercka.' ),
			'redakcja'             => array( 'Redakcja', 'Role, biogramy, kontakt redakcyjny, zasady współpracy.' ),
			'wspolpraca-partnerska' => array( 'Współpraca partnerska', 'Poziomy partnerstwa, kryteria obecności, formularz zainteresowania.' ),
			'kontakt'              => array( 'Kontakt', 'Tematy redakcyjne, partnerstwa, wydarzenia, dane ogólne; rozdzielone formularze.' ),
			'newsletter'           => array( 'Newsletter', 'Zapis na newsletter i RiskBrief TSL na e-mail, archiwum wydań.' ),
			'polityka-prywatnosci' => array( 'Polityka prywatności', 'Informacje dla newslettera, formularzy, narzędzi i wydarzeń.' ),
			'polityka-cookies'     => array( 'Polityka cookies', 'Rodzaje plików cookies, cele, zarządzanie zgodą.' ),
			'regulaminy'           => array( 'Regulaminy', 'Newsletter, wydarzenia, konkursy, pobrania — jeśli wymagają odrębnych zasad.' ),
			'dostepnosc'           => array( 'Deklaracja dostępności', 'Deklaracja dostępności i sposób zgłaszania problemów.' ),
			'korekty-aktualizacje' => array( 'Korekty i aktualizacje', 'Zasada oznaczania zmian w materiałach, szczególnie prawnych i ryzykowych.' ),
		);

		$count = 0;
		foreach ( $pages as $slug => $page ) {
			if ( get_page_by_path( $slug ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					// Kontakt i Newsletter mają działające formularze; pozostałe strony mają
					// treść „Do uzupełnienia” — w trybie produkcyjnym powstają jako szkice.
					'post_status'  => in_array( $slug, array( 'kontakt', 'newsletter' ), true ) ? 'publish' : tsl_placeholder_status(),
					'post_title'   => $page[0],
					'post_name'    => $slug,
					'post_content' => "<!-- wp:paragraph -->\n<p><strong>Do uzupełnienia.</strong> Zakres strony: " . esc_html( $page[1] ) . "</p>\n<!-- /wp:paragraph -->",
				)
			);
			if ( 'polityka-prywatnosci' === $slug && $id ) {
				update_option( 'wp_page_for_privacy_policy', $id );
			}
			++$count;
		}
		self::$log[] = sprintf( 'Strony informacyjne: %d nowych.', $count );
	}

	/**
	 * Materiały demonstracyjne: pakiet startowy z banku tematów (rozdz. 10–15)
	 * z treścią zastępczą — pokazują układ serwisu przed napisaniem tekstów.
	 */
	public static function demo() {
		if ( ! tsl_is_demo_mode() ) {
			self::$log[] = 'Materiały przykładowe można wczytać tylko w trybie demonstracyjnym (Narzędzia → TSL Manager).';
			return;
		}
		$demo   = require TSL_CORE_DIR . 'includes/data/demo.php';
		$days   = 0;
		$made   = 0;
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		$author = get_current_user_id() ? get_current_user_id() : (int) ( $admins[0] ?? 0 );

		// Gdy są już artykuły redakcji (36 tekstów), przykładowe artykuły z treścią
		// zastępczą tylko dublowałyby tematy — pomijamy je.
		$has_editorial = (bool) get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'tsl_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);

		foreach ( $demo as $item ) {
			if ( $has_editorial && 'post' === $item['type'] ) {
				continue;
			}
			$item['type'] = tsl_map_event_type( $item['type'] );
			if ( get_page_by_path( $item['slug'], OBJECT, $item['type'] ) ) {
				continue;
			}

			$category = $item['category'] ? get_category_by_path( $item['category'], true ) : null;
			$id       = wp_insert_post(
				array(
					'post_type'    => $item['type'],
					'post_status'  => 'publish',
					'post_title'   => $item['title'],
					'post_name'    => $item['slug'],
					'post_author'  => $author,
					'post_excerpt' => $item['lead'],
					'post_content' => $item['content'] ?? self::demo_body( $item ),
					'post_date'    => wp_date( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * ( $days += 2 ) ),
					'meta_input'   => array( '_tsl_demo' => 1 ),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				self::$log[] = 'Błąd: ' . $id->get_error_message();
				continue;
			}
			if ( $category ) {
				wp_set_object_terms( $id, array( $category->term_id ), 'category' );
			}
			foreach ( $item['terms'] ?? array() as $taxonomy => $slugs ) {
				wp_set_object_terms( $id, (array) $slugs, 'tsl_' . $taxonomy );
			}
			$meta = $item['meta'] ?? array();
			if ( 'tribe_events' === $item['type'] ) {
				self::demo_tec_event( $id, $meta );
				unset( $meta['tsl_start'], $meta['tsl_end'], $meta['tsl_location'] );
			}
			foreach ( $meta as $key => $value ) {
				if ( 'tsl_file' === $key ) {
					$value = self::demo_file( $id );
				}
				update_post_meta( $id, $key, self::demo_value( $key, $value ) );
			}
			if ( ! empty( $item['sticky'] ) ) {
				stick_post( $id );
			}
			++$made;
		}

		// Powiązania po utworzeniu wszystkich materiałów (wskazujemy je po slugach).
		foreach ( $demo as $item ) {
			$post = get_page_by_path( $item['slug'], OBJECT, tsl_map_event_type( $item['type'] ) );
			if ( ! $post || empty( $item['rel'] ) || get_post_meta( $post->ID, '_tsl_demo_rel', true ) ) {
				continue;
			}
			foreach ( $item['rel'] as $target => $slugs ) {
				foreach ( $slugs as $slug ) {
					$related = get_page_by_path( $slug, OBJECT, tsl_map_event_type( $target ) );
					if ( $related ) {
						add_post_meta( $post->ID, 'tsl_rel_' . tsl_rel_key( $target ), $related->ID );
					}
				}
			}
			update_post_meta( $post->ID, '_tsl_demo_rel', 1 );
		}

		self::$log[] = sprintf( 'Materiały demonstracyjne: %d nowych.', $made );
	}

	/**
	 * Termin i miejsce przykładowego wydarzenia w The Events Calendar.
	 *
	 * @param int   $id   Wydarzenie.
	 * @param array $meta Dane demonstracyjne (tsl_start, tsl_end, tsl_location).
	 */
	private static function demo_tec_event( $id, $meta ) {
		if ( empty( $meta['tsl_start'] ) || ! class_exists( 'Tribe__Events__API' ) ) {
			return;
		}
		$tz   = wp_timezone();
		$s    = date_create_immutable( self::demo_value( 'tsl_start', $meta['tsl_start'] ), $tz );
		$e    = ! empty( $meta['tsl_end'] ) ? date_create_immutable( self::demo_value( 'tsl_end', $meta['tsl_end'] ), $tz ) : $s->modify( '+1 hour' );
		$args = array(
			'EventStartDate'   => $s->format( 'Y-m-d' ),
			'EventStartHour'   => $s->format( 'H' ),
			'EventStartMinute' => $s->format( 'i' ),
			'EventEndDate'     => $e->format( 'Y-m-d' ),
			'EventEndHour'     => $e->format( 'H' ),
			'EventEndMinute'   => $e->format( 'i' ),
			'EventTimezone'    => wp_timezone_string(),
		);
		Tribe__Events__API::saveEventMeta( $id, $args, get_post( $id ) );
		if ( ! empty( $meta['tsl_location'] ) && ( $meta['tsl_mode'] ?? '' ) !== 'online' ) {
			tsl_events_set_venue( $id, $meta['tsl_location'] );
		}
	}

	/**
	 * Przykładowy plik PDF jako załącznik (pokazuje pobieranie narzędzi i raportów).
	 *
	 * @param int $post_id Materiał, do którego dołączamy plik.
	 * @return int ID załącznika.
	 */
	private static function demo_file( $post_id ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_tsl_demo_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
		$source = TSL_CORE_DIR . 'assets/demo/material-przykladowy.pdf';
		$upload = wp_upload_bits( 'material-przykladowy.pdf', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'application/pdf',
				'post_title'     => 'Materiał przykładowy (demo)',
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$post_id
		);
		update_post_meta( $attachment_id, '_tsl_demo_file', 1 );
		update_post_meta( $attachment_id, '_tsl_demo', 1 );
		return (int) $attachment_id;
	}

	/**
	 * Daty względne w danych demonstracyjnych („+21 days 10:00”, „-3 days”),
	 * żeby wydarzenia i terminy zawsze wypadały sensownie względem dnia instalacji.
	 *
	 * @param string $key   Klucz pola.
	 * @param string $value Wartość.
	 */
	private static function demo_value( $key, $value ) {
		if ( ! preg_match( '/^[+-]\d+ days/', (string) $value ) ) {
			return $value;
		}
		$time = strtotime( $value, current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		return in_array( $key, array( 'tsl_start', 'tsl_end' ), true ) ? gmdate( 'Y-m-d\TH:i', $time ) : gmdate( 'Y-m-d', $time );
	}

	/**
	 * Treść zastępcza zgodna ze stałą konstrukcją materiału (test sześciu pytań, rozdz. 9).
	 *
	 * @param array $item Materiał.
	 */
	private static function demo_body( $item ) {
		$sections = array(
			'riskbrief'  => array( 'Co się stało' ),
			'ekspert'    => array( 'Doświadczenie' ),
			'wydarzenie' => array( 'O wydarzeniu', 'Dla kogo' ),
			'raport'     => array( 'Najważniejsze dane', 'Rekomendacje' ),
			'narzedzie'  => array( 'Do czego służy', 'Kiedy go użyć' ),
			'wydanie'    => array( 'W tym tygodniu' ),
		);
		$sections = $sections[ tsl_rel_key( $item['type'] ) ] ?? array( 'Co się dzieje', 'Co może pójść źle', 'Jaki będzie skutek', 'Ile może to kosztować', 'Co firma powinna zrobić' );

		$html = "<!-- wp:paragraph {\"className\":\"tsl-demo-note\"} -->\n<p class=\"tsl-demo-note\">Materiał demonstracyjny — treść zastępcza pokazująca układ strony. Do zastąpienia tekstem redakcji.</p>\n<!-- /wp:paragraph -->\n\n";
		foreach ( $sections as $heading ) {
			$html .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $heading ) . "</h2>\n<!-- /wp:heading -->\n\n";
			$html .= "<!-- wp:paragraph -->\n<p>W tym miejscu redakcja opisze odpowiedź na pytanie „" . esc_html( mb_strtolower( $heading ) ) . "” w kontekście tematu: " . esc_html( $item['title'] ) . ". Akapit ma 3–5 zdań, konkretne liczby i źródła.</p>\n<!-- /wp:paragraph -->\n\n";
		}
		return $html;
	}

	/**
	 * Usuwa wszystkie materiały demonstracyjne.
	 *
	 * @return int Liczba usuniętych.
	 */
	public static function remove_demo() {
		// Oba typy wydarzeń — także ten, którego silnik jest akurat wyłączony.
		$ids = get_posts(
			array(
				'post_type'      => array_values( array_unique( array_merge( get_post_types( array( 'public' => true ) ), array( 'wydarzenie', 'tribe_events', 'wydanie' ) ) ) ),
				'tribe_suppress_query_filters' => true,
				'eventDisplay'   => 'custom',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_tsl_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
		$files = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_tsl_demo_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);
		foreach ( $files as $file_id ) {
			wp_delete_attachment( $file_id, true );
		}
		return count( $ids );
	}
}
