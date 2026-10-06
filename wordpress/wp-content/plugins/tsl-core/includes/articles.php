<?php
/**
 * Artykuły redakcji: import tekstów z Dysku (data/artykuly.json, generowane
 * przez tools/build-articles.py), automatyczne ilustracje po numerze ID
 * oraz CTA biznesowe (metadane SEO: includes/seo.php).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Numer ID materiału z nazwy pliku, np. „ZF-03-1_ilustracja.png” → „ZF-03-1”.
 *
 * @param string $name Nazwa pliku lub tytuł załącznika.
 * @return string Pusty, gdy nazwa nie zaczyna się od ID.
 */
function tsl_article_code_from_name( $name ) {
	return preg_match( '/^([A-Za-z]{2})[-_ ](\d{2})[-_ ](\d{1,2})(?![0-9])/', basename( (string) $name ), $m )
		? strtoupper( $m[1] ) . '-' . $m[2] . '-' . $m[3]
		: '';
}

/**
 * Artykuł o danym ID (pole „ID materiału”).
 *
 * @param string $code Np. „ZF-03-1”.
 * @return int ID wpisu lub 0.
 */
function tsl_article_by_code( $code ) {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'tsl_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'meta_value'     => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Ilustracja wgrana do biblioteki mediów z ID w nazwie pliku staje się
 * obrazkiem wyróżniającym artykułu o tym ID.
 *
 * @param int $attachment_id Załącznik.
 * @return bool Czy przypisano.
 */
function tsl_article_attach_image( $attachment_id ) {
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}
	$code = tsl_article_code_from_name( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) );
	if ( ! $code ) {
		$code = tsl_article_code_from_name( get_the_title( $attachment_id ) );
	}
	$post_id = $code ? tsl_article_by_code( $code ) : 0;
	if ( ! $post_id ) {
		return false;
	}
	set_post_thumbnail( $post_id, $attachment_id );
	if ( ! get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', get_the_title( $post_id ) );
	}
	return true;
}
add_action( 'add_attachment', 'tsl_article_attach_image' );

/**
 * Po imporcie: ilustracje wgrane wcześniej niż artykuły.
 *
 * @return int Liczba przypisanych.
 */
function tsl_articles_attach_existing_images() {
	$count = 0;
	$ids   = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	foreach ( $ids as $id ) {
		$count += tsl_article_attach_image( $id ) ? 1 : 0;
	}
	return $count;
}

/**
 * Teksty redakcji przygotowane do importu.
 *
 * @return array<int, array>
 */
function tsl_articles_data() {
	$file = TSL_CORE_DIR . 'includes/data/artykuly.json';
	$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return $data['articles'] ?? array();
}

/**
 * Import artykułów redakcji.
 *
 * Artykuły rozpoznawane są po polu „ID materiału” (tsl_code), więc ponowny import
 * nie tworzy duplikatów. Bez $overwrite istniejące artykuły zostają nietknięte
 * (zachowują poprawki wprowadzone w panelu).
 *
 * Nowe artykuły trafiają do szkiców. Publikacja od razu jest możliwa tylko
 * w trybie demonstracyjnym (prezentacja) i tylko na wyraźne żądanie.
 *
 * @param bool $overwrite   Nadpisz artykuły wczytane wcześniej.
 * @param bool $remove_demo Usuń przykładowe artykuły (demo), które zastępują teksty redakcji.
 * @param bool $publish     Opublikuj od razu (ignorowane poza trybem demonstracyjnym).
 * @return array{created:int, updated:int, skipped:int, denied:int, images:int, demo:int, status:string}
 */
function tsl_articles_import( $overwrite = false, $remove_demo = false, $publish = false ) {
	$status = $publish && tsl_is_demo_mode() ? 'publish' : 'draft';
	$stats  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'denied' => 0, 'images' => 0, 'demo' => 0, 'status' => $status );
	$cli    = defined( 'WP_CLI' ) && WP_CLI;
	$items = tsl_articles_data();
	if ( ! $items ) {
		return $stats;
	}

	if ( $remove_demo ) {
		$demo = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_tsl_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);
		foreach ( $demo as $id ) {
			wp_delete_post( $id, true );
		}
		$stats['demo'] = count( $demo );
	}

	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$author = get_current_user_id() ? get_current_user_id() : (int) ( $admins[0] ?? 0 );
	$fresh  = array();

	foreach ( $items as $item ) {
		$existing = tsl_article_by_code( $item['code'] );
		if ( $existing && ! $overwrite ) {
			++$stats['skipped'];
			continue;
		}
		if ( $existing && ! $cli && ! current_user_can( 'edit_post', $existing ) ) {
			++$stats['denied'];
			continue;
		}
		$id = tsl_article_save( $item, $status, $existing, $author );
		if ( ! $id ) {
			continue;
		}
		$fresh[] = $id;
		++$stats[ $existing ? 'updated' : 'created' ];
	}

	// Linki między artykułami („{{tsl:ID}}”) — gdy wszystkie już istnieją.
	tsl_articles_resolve_links( $fresh );

	$stats['images'] = tsl_articles_attach_existing_images();
	return $stats;
}

/**
 * Zapis jednego artykułu (import z danych wtyczki lub z pliku .docx).
 *
 * @param array  $item     Artykuł: code, title, slug, category, format, lead, date, content, sources, meta.
 * @param string $status   publish | draft.
 * @param int    $existing ID istniejącego wpisu do nadpisania (0 = nowy).
 * @param int    $author   Autor nowego wpisu.
 * @return int ID wpisu albo 0 przy błędzie.
 */
function tsl_article_save( array $item, $status = 'draft', $existing = 0, $author = 0 ) {
	$postarr = array(
		'post_type'    => 'post',
		'post_status'  => $status,
		'post_title'   => $item['title'],
		'post_name'    => $item['slug'],
		'post_excerpt' => $item['lead'],
		'post_content' => $item['content'],
		'post_author'  => $author ? $author : get_current_user_id(),
	);
	if ( ! empty( $item['date'] ) ) { // Data redakcyjna (stan tekstu) także dla szkiców.
		$postarr['post_date']     = $item['date'];
		$postarr['post_date_gmt'] = get_gmt_from_date( $item['date'] );
	}
	if ( $existing ) {
		$postarr['ID'] = $existing;
		unset( $postarr['post_author'] );
		if ( 'publish' !== $status ) {
			unset( $postarr['post_status'] ); // Nadpisanie treści nie cofa publikacji.
		}
	}
	// Treść jest już gotowym HTML-em bloków — bez filtrów, które usuwają atrybuty.
	kses_remove_filters();
	$id = $existing ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
	kses_init_filters();
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	$category = ! empty( $item['category'] ) ? get_category_by_path( $item['category'], true ) : null;
	if ( $category ) {
		wp_set_object_terms( $id, array( $category->term_id ), 'category' );
	}
	if ( ! empty( $item['format'] ) ) {
		wp_set_object_terms( $id, array( $item['format'] ), 'tsl_format' );
	}
	update_post_meta( $id, 'tsl_code', $item['code'] );
	update_post_meta( $id, 'tsl_sources', implode( "\n", $item['sources'] ) );
	foreach ( $item['meta'] as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	// Ta sama treść dla popularnych wtyczek SEO, jeśli zostaną zainstalowane.
	if ( ! empty( $item['meta']['tsl_seo_title'] ) ) {
		update_post_meta( $id, '_yoast_wpseo_title', $item['meta']['tsl_seo_title'] . ' %%sep%% %%sitename%%' );
		update_post_meta( $id, 'rank_math_title', $item['meta']['tsl_seo_title'] . ' %sep% %sitename%' );
	}
	if ( ! empty( $item['meta']['tsl_seo_desc'] ) ) {
		update_post_meta( $id, '_yoast_wpseo_metadesc', $item['meta']['tsl_seo_desc'] );
		update_post_meta( $id, 'rank_math_description', $item['meta']['tsl_seo_desc'] );
	}
	if ( ! empty( $item['meta']['tsl_keyphrase'] ) ) {
		update_post_meta( $id, '_yoast_wpseo_focuskw', $item['meta']['tsl_keyphrase'] );
		update_post_meta( $id, 'rank_math_focus_keyword', $item['meta']['tsl_keyphrase'] );
	}
	return (int) $id;
}

/**
 * Zamienia znaczniki „{{tsl:ID}}” w treści na adresy artykułów o tych ID.
 *
 * @param int[] $ids Wpisy do przejrzenia.
 */
function tsl_articles_resolve_links( array $ids ) {
	foreach ( $ids as $id ) {
		$content = get_post_field( 'post_content', $id );
		$linked  = preg_replace_callback(
			'/\{\{tsl:([A-Z]{2}-\d{2}-\d+)\}\}/',
			function ( $m ) {
				$target = tsl_article_by_code( $m[1] );
				return $target ? get_permalink( $target ) : home_url( '/' );
			},
			$content
		);
		if ( $linked !== $content ) {
			kses_remove_filters();
			wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $linked ) ) );
			kses_init_filters();
		}
	}
}

/**
 * Markdown-owe linki „[tekst](https://…)” w polach tekstowych → HTML.
 *
 * @param string $text Tekst (nieescapowany).
 * @return string Bezpieczny HTML.
 */
function tsl_md_links( $text ) {
	return preg_replace_callback(
		'/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/',
		function ( $m ) {
			return '<a href="' . esc_url( html_entity_decode( $m[2] ) ) . '">' . $m[1] . '</a>';
		},
		esc_html( $text )
	);
}

/**
 * Czy pokazywać CTA biznesowe pod artykułami (Narzędzia → TSL Manager).
 */
function tsl_show_biz_cta() {
	return '1' === get_option( 'tsl_show_biz_cta', '0' ); // Domyślnie wyłączone — włącza administrator.
}
