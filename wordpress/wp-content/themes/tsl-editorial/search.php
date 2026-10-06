<?php
/**
 * Wyszukiwarka: duże pole, filtry (filar, format, rodzaj transportu),
 * liczba wyników, wyniki jako numerowana lista, podpowiedzi przy braku wyników.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;

$selected = array(
	'category_name' => get_query_var( 'category_name' ),
	'format'        => get_query_var( 'format' ),
	'transport'     => get_query_var( 'transport' ),
);

$selects = array(
	'category_name' => array( 'Filar', tsl_get_pillars() ),
	'format'        => array( 'Format', get_terms( array( 'taxonomy' => 'tsl_format', 'hide_empty' => false ) ) ),
	'transport'     => array( 'Rodzaj transportu', get_terms( array( 'taxonomy' => 'tsl_rodzaj_transportu', 'hide_empty' => false ) ) ),
);
?>

<header class="page-head page-head-search">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<h1 class="page-title">Wyszukiwarka</h1>
		<form class="search-filters" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div class="search-row">
				<label class="screen-reader-text" for="search-page-input">Szukaj</label>
				<input id="search-page-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Wpisz problem, np. fałszywy przewoźnik">
				<button class="btn btn-primary" type="submit"><?php echo tsl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Szukaj</button>
			</div>
			<div class="filter-row">
				<?php foreach ( $selects as $name => $select ) : ?>
					<label class="select-field">
						<span class="field-label"><?php echo esc_html( $select[0] ); ?></span>
						<select name="<?php echo esc_attr( $name ); ?>">
							<option value="">Wszystkie</option>
							<?php foreach ( (array) $select[1] as $t ) : ?>
								<?php if ( $t instanceof WP_Term ) : ?>
									<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $selected[ $name ], $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endforeach; ?>
			</div>
		</form>
	</div>
</header>

<div class="wrap page-body page-body-narrow" data-search-term="<?php echo esc_attr( get_search_query() ); ?>" data-search-count="<?php echo esc_attr( (int) $wp_query->found_posts ); ?>">
	<?php if ( have_posts() ) : ?>
		<p class="result-count" role="status">
			<?php echo esc_html( 'Znaleziono ' . (int) $wp_query->found_posts . ' ' . tsl_plural( $wp_query->found_posts, 'materiał', 'materiały', 'materiałów' ) ); ?>
		</p>
		<h2 class="screen-reader-text">Wyniki wyszukiwania</h2>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				tsl_card( get_post(), 'river' );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'prev_text' => 'Poprzednie',
				'next_text' => 'Następne',
			)
		);
		?>
	<?php else : ?>
		<div class="empty-state">
			<p class="empty-state-title">Brak wyników dla tego zapytania</p>
			<p>Spróbuj innego słowa lub zajrzyj do:</p>
			<ul class="inline-links">
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">Narzędzia</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'riskbrief' ) ); ?>">RiskBrief TSL</a></li>
				<?php foreach ( tsl_get_pillars() as $pillar ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $pillar ) ); ?>"><?php echo esc_html( $pillar->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<p>Nie znalazłeś tematu? <a href="<?php echo esc_url( tsl_page_url( 'kontakt' ) ); ?>">Zgłoś go redakcji</a>.</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
