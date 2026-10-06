<?php
/**
 * Podstawowe SEO, gdy nie działa wtyczka SEO (Yoast, Rank Math, AIOSEO, SEOPress):
 * tytuł, opis, adres kanoniczny i znaczniki Open Graph dla wszystkich typów treści.
 *
 * Gdy wtyczka SEO jest aktywna, nic tu nie jest wypisywane — bez duplikatów.
 * Noindex dla wyszukiwarki, filtrów i taksonomii przekrojowych: includes/permalinks.php.
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Czy działa wtyczka SEO, która sama wypisuje metadane.
 */
function tsl_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Archiwum wydań newslettera ma inną nazwę niż strona „Newsletter” (zapis).
 */
add_filter(
	'post_type_archive_title',
	function ( $title, $post_type ) {
		return 'wydanie' === $post_type ? 'Archiwum newslettera' : $title;
	},
	10,
	2
);

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( tsl_seo_plugin_active() || ! is_singular() ) {
			return $title;
		}
		$seo = (string) get_post_meta( get_queried_object_id(), 'tsl_seo_title', true );
		return '' !== $seo ? $seo . ' – ' . get_bloginfo( 'name' ) : $title;
	}
);

/**
 * Metadane bieżącego widoku: tytuł, opis, adres, typ, obraz.
 *
 * @return array{title:string, desc:string, url:string, type:string, image:string}|null Null dla widoków bez metadanych (404, wyszukiwarka).
 */
function tsl_seo_meta() {
	$site = get_bloginfo( 'name' );
	$meta = array(
		'title' => $site,
		'desc'  => (string) get_bloginfo( 'description' ),
		'url'   => '',
		'type'  => 'website',
		'image' => '',
	);

	if ( is_404() || is_search() ) {
		return null;
	}

	if ( is_front_page() ) {
		$meta['url'] = home_url( '/' );
	} elseif ( is_singular() ) {
		$id            = get_queried_object_id();
		$post          = get_post( $id );
		$title         = (string) get_post_meta( $id, 'tsl_seo_title', true );
		$desc          = (string) get_post_meta( $id, 'tsl_seo_desc', true );
		$meta['title'] = '' !== $title ? $title : wp_strip_all_tags( get_the_title( $id ) );
		if ( '' === $desc ) {
			$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( (string) $post->post_content ) ) ), 30, '…' );
		}
		// Strony z treścią zastępczą: w opisie tylko zakres strony, bez „Do uzupełnienia”.
		$desc         = trim( preg_replace( '/^Do uzupełnienia\.?\s*(Zakres strony:\s*)?/u', '', trim( $desc ) ) );
		$meta['desc'] = '' !== $desc ? $desc : $meta['desc'];
		$meta['url']  = (string) get_permalink( $id );
		$meta['type'] = in_array( $post->post_type, array( 'post', 'riskbrief', 'raport', 'wydanie' ), true ) ? 'article' : 'website';
		if ( has_post_thumbnail( $id ) ) {
			$meta['image'] = (string) get_the_post_thumbnail_url( $id, 'large' );
		}
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$term          = get_queried_object();
		$meta['title'] = $term->name;
		$meta['desc']  = '' !== trim( (string) $term->description ) ? wp_strip_all_tags( $term->description ) : $meta['desc'];
		$meta['url']   = (string) get_term_link( $term );
	} elseif ( is_post_type_archive() ) {
		$type          = get_queried_object();
		$meta['title'] = post_type_archive_title( '', false );
		$meta['desc']  = '' !== trim( (string) $type->description ) ? $type->description : $meta['desc'];
		$meta['url']   = (string) get_post_type_archive_link( $type->name );
	} elseif ( is_home() ) {
		$meta['title'] = wp_strip_all_tags( get_the_title( (int) get_option( 'page_for_posts' ) ) );
		$meta['url']   = (string) get_permalink( (int) get_option( 'page_for_posts' ) );
	} else {
		return null;
	}

	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 && '' !== $meta['url'] && ! is_singular() ) {
		$meta['url'] = user_trailingslashit( trailingslashit( $meta['url'] ) . 'page/' . $paged );
	}
	if ( '' === $meta['image'] ) {
		/**
		 * Domyślny obraz udostępniania (1200×630), gdy materiał nie ma ilustracji.
		 *
		 * @param string $url Adres obrazu.
		 */
		$meta['image'] = (string) apply_filters( 'tsl_seo_default_image', plugins_url( 'assets/og-default.jpg', TSL_CORE_DIR . 'tsl-core.php' ) );
	}
	$meta['desc'] = trim( preg_replace( '/\s+/', ' ', $meta['desc'] ) );
	return $meta;
}

add_action(
	'wp_head',
	function () {
		if ( tsl_seo_plugin_active() ) {
			return;
		}
		$meta = tsl_seo_meta();
		if ( ! $meta ) {
			return;
		}
		if ( '' !== $meta['desc'] ) {
			echo '<meta name="description" content="' . esc_attr( $meta['desc'] ) . '">' . "\n";
		}
		// Adres kanoniczny wpisów i stron wypisuje WordPress (rel_canonical); tu pozostałe widoki.
		if ( ! is_singular() && '' !== $meta['url'] && ! tsl_seo_is_filtered_view() ) {
			echo '<link rel="canonical" href="' . esc_url( $meta['url'] ) . '">' . "\n";
		}
		$tags = array(
			'og:locale'      => 'pl_PL',
			'og:type'        => $meta['type'],
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:title'       => $meta['title'],
			'og:description' => $meta['desc'],
			'og:url'         => $meta['url'],
			'og:image'       => $meta['image'],
		);
		foreach ( array_filter( $tags ) as $property => $content ) {
			$value = in_array( $property, array( 'og:url', 'og:image' ), true ) ? esc_url( $content ) : esc_attr( $content );
			echo '<meta property="' . esc_attr( $property ) . '" content="' . $value . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	},
	5
);
