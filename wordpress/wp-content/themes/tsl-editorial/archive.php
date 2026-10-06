<?php
/**
 * Archiwa kolekcji: Narzędzia (i typy narzędzi), RiskBrief TSL, Wydarzenia,
 * Raporty i dane, Eksperci, Partnerzy, Wydania newslettera.
 *
 * Filtry i zapytania jak w motywie 0.10.0; każda kolekcja ma własną formę
 * listy: dziennik sygnałów, indeks narzędzi, kalendarium, okładki raportów,
 * lista osób, lista partnerów.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

$filters = array(
	'narzedzie'  => 'tsl_typ_narzedzia',
	'riskbrief'  => 'tsl_poziom_ryzyka',
	'wydarzenie' => 'tsl_typ_wydarzenia',
	'raport'     => 'tsl_typ_raportu',
	'partner'    => 'tsl_poziom_partnerstwa',
);

if ( is_tax( 'tsl_typ_narzedzia' ) ) {
	$post_type   = 'narzedzie';
	$title       = single_term_title( '', false );
	$description = term_description();
	$kicker      = 'Narzędzia';
} else {
	$post_type   = get_query_var( 'post_type' );
	$post_type   = is_array( $post_type ) ? reset( $post_type ) : $post_type;
	$object      = get_post_type_object( $post_type );
	$title       = $object ? $object->labels->name : get_the_archive_title();
	$description = $object ? $object->description : '';
	$kicker      = 'Kolekcja';
}

$filter_tax = $filters[ $post_type ] ?? '';
$tax_object = $filter_tax ? get_taxonomy( $filter_tax ) : null;
$active     = $tax_object ? get_query_var( $tax_object->query_var ) : '';
$base_url   = get_post_type_archive_link( $post_type );
$arrow      = tsl_icon( 'arrow' );

$status_filters = array(
	'wydarzenie' => array(
		'var'     => 'status-wydarzenia',
		'label'   => 'Status wydarzenia',
		'options' => array(
			''           => 'Nadchodzące',
			'zakonczone' => 'Zakończone',
		),
	),
	'riskbrief'  => array(
		'var'     => 'status-sygnalu',
		'label'   => 'Status sygnału',
		'options' => array(
			''             => 'Wszystkie statusy',
			'nowe'         => 'Nowe',
			'aktualizacja' => 'Aktualizacja',
			'stabilna'     => 'Sytuacja stabilna',
			'wygaslo'      => 'Wygasło',
		),
	),
);
$status_filter  = $status_filters[ $post_type ] ?? null;

/**
 * Lista materiałów kolekcji w formie właściwej dla typu.
 *
 * @param WP_Post[] $items     Materiały.
 * @param string    $post_type Typ.
 */
$render_list = function ( $items, $post_type ) {
	if ( 'riskbrief' === $post_type ) {
		echo '<ol class="signal-log">';
		foreach ( $items as $item ) {
			echo '<li class="log-row">';
			echo '<span class="log-level">' . tsl_risk_badge( $item ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="log-main"><a class="log-title card-title" href="' . esc_url( get_permalink( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a>';
			if ( has_excerpt( $item ) ) {
				echo '<span class="log-lead">' . esc_html( get_the_excerpt( $item ) ) . '</span>';
			}
			echo tsl_pillar_label( $item ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="log-status">' . tsl_status_badge( $item ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<time class="log-date" datetime="' . esc_attr( get_the_date( 'c', $item ) ) . '">' . esc_html( get_the_date( '', $item ) ) . '</time>';
			echo '</li>';
		}
		echo '</ol>';
		return;
	}
	if ( 'narzedzie' === $post_type ) {
		echo '<ol class="tool-index">';
		foreach ( $items as $item ) {
			echo '<li><a class="tool-row" href="' . esc_url( get_permalink( $item ) ) . '">';
			echo '<span class="tool-type">' . esc_html( tsl_kind_label( $item ) ) . '</span>';
			echo '<span class="tool-title">' . esc_html( get_the_title( $item ) ) . '</span>';
			if ( has_excerpt( $item ) ) {
				echo '<span class="tool-lead">' . esc_html( get_the_excerpt( $item ) ) . '</span>';
			}
			echo '<span class="tool-cta">Użyj narzędzia' . tsl_icon( 'arrow' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</a></li>';
		}
		echo '</ol>';
		return;
	}
	if ( in_array( $post_type, array( 'wydarzenie', 'tribe_events' ), true ) ) {
		echo '<ol class="calendar calendar-wide">';
		foreach ( $items as $item ) {
			get_template_part( 'template-parts/event-row', null, array( 'post' => $item ) );
		}
		echo '</ol>';
		return;
	}
	if ( 'ekspert' === $post_type ) {
		echo '<ul class="voices voices-wide">';
		foreach ( $items as $item ) {
			echo '<li>';
			get_template_part( 'template-parts/person', null, array( 'post' => $item ) );
			if ( has_excerpt( $item ) ) {
				echo '<p class="person-lead">' . esc_html( get_the_excerpt( $item ) ) . '</p>';
			}
			echo '</li>';
		}
		echo '</ul>';
		return;
	}
	if ( 'raport' === $post_type ) {
		echo '<div class="report-shelf">';
		foreach ( $items as $item ) {
			echo '<a class="report-feature" href="' . esc_url( get_permalink( $item ) ) . '">';
			echo '<span class="report-cover" aria-hidden="true"><span class="rc-brand">TSL Manager · ' . esc_html( tsl_kind_label( $item ) ) . '</span><span class="rc-title">' . esc_html( get_the_title( $item ) ) . '</span><span class="rc-year">' . esc_html( get_the_date( 'Y', $item ) ) . '</span></span>';
			echo '<span class="report-copy"><span class="tool-type">' . esc_html( tsl_kind_label( $item ) ) . '</span><span class="report-title">' . esc_html( get_the_title( $item ) ) . '</span>';
			if ( has_excerpt( $item ) ) {
				echo '<span class="tool-lead">' . esc_html( get_the_excerpt( $item ) ) . '</span>';
			}
			echo '<span class="tool-cta">Najważniejsze wnioski' . tsl_icon( 'arrow' ) . '</span></span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		return;
	}
	if ( 'partner' === $post_type ) {
		echo '<ul class="partner-roster">';
		foreach ( $items as $item ) {
			$level = get_the_terms( $item, 'tsl_poziom_partnerstwa' );
			echo '<li><span class="partner-role">' . esc_html( $level && ! is_wp_error( $level ) ? $level[0]->name : 'Partner' ) . '</span>';
			echo '<a class="partner-name card-title" href="' . esc_url( get_permalink( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a>';
			if ( tsl_field( 'tsl_area', $item ) ) {
				echo '<span class="partner-area">' . esc_html( tsl_field( 'tsl_area', $item ) ) . '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
		return;
	}
	echo '<div class="post-list">';
	foreach ( $items as $item ) {
		tsl_card( $item, 'river' );
	}
	echo '</div>';
};
?>

<header class="page-head<?php echo 'riskbrief' === $post_type ? ' page-head-signal' : ''; ?>">
	<div class="wrap">
		<?php tsl_breadcrumbs(); ?>
		<p class="page-kicker"><?php echo esc_html( $kicker ); ?></p>
		<h1 class="page-title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $description ) : ?>
			<p class="page-lead"><?php echo esc_html( wp_strip_all_tags( $description ) ); ?></p>
		<?php endif; ?>

		<?php if ( $tax_object ) : ?>
			<?php
			$terms = get_terms(
				array(
					'taxonomy'   => $filter_tax,
					'hide_empty' => false,
				)
			);
			if ( 'tsl_poziom_ryzyka' === $filter_tax || 'tsl_poziom_partnerstwa' === $filter_tax ) {
				$terms = tsl_sort_terms( $terms );
			}
			?>
			<nav class="filter-tabs" aria-label="Filtr: <?php echo esc_attr( $tax_object->labels->singular_name ); ?>">
				<span class="filter-label" aria-hidden="true"><?php echo esc_html( $tax_object->labels->singular_name ); ?></span>
				<a href="<?php echo esc_url( $base_url ); ?>" <?php echo $active ? '' : 'aria-current="true"'; ?>>Wszystkie</a>
				<?php foreach ( $terms as $t ) : ?>
					<?php $url = 'tsl_typ_narzedzia' === $filter_tax ? get_term_link( $t ) : add_query_arg( $tax_object->query_var, $t->slug, $base_url ); ?>
					<a href="<?php echo esc_url( $url ); ?>" <?php echo $active === $t->slug ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $status_filter ) : ?>
			<?php $current_status = (string) get_query_var( $status_filter['var'] ); ?>
			<nav class="filter-tabs filter-tabs-status" aria-label="<?php echo esc_attr( $status_filter['label'] ); ?>">
				<span class="filter-label" aria-hidden="true"><?php echo esc_html( $status_filter['label'] ); ?></span>
				<?php foreach ( $status_filter['options'] as $value => $label ) : ?>
					<a href="<?php echo esc_url( $value ? add_query_arg( $status_filter['var'], $value, $base_url ) : remove_query_arg( $status_filter['var'], add_query_arg( array() ) ) ); ?>" <?php echo $current_status === $value ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</header>

<div class="wrap page-body">
	<?php if ( 'wydarzenie' === $post_type && ! have_posts() && 'zakonczone' !== get_query_var( 'status-wydarzenia' ) ) : ?>
		<?php $finished = tsl_posts( array_merge( array( 'post_type' => 'wydarzenie', 'posts_per_page' => 6 ), tsl_event_query_args( 'zakonczone' ) ) ); ?>
		<div class="empty-state">
			<p><strong>Kolejne wydarzenia ogłosimy wkrótce.</strong> Zapisz się do newslettera, aby otrzymać zaproszenie. Poniżej materiały z ostatnich spotkań.</p>
		</div>
		<?php if ( $finished ) : ?>
			<h2 class="list-heading">Ostatnie wydarzenia</h2>
			<?php $render_list( $finished, 'wydarzenie' ); ?>
		<?php endif; ?>
	<?php elseif ( have_posts() ) : ?>
		<h2 class="screen-reader-text">Lista materiałów</h2>
		<?php
		$items = array();
		while ( have_posts() ) {
			the_post();
			$items[] = get_post();
		}
		$render_list( $items, $post_type );
		the_posts_pagination(
			array(
				'prev_text' => 'Poprzednie',
				'next_text' => 'Następne',
			)
		);
		?>
	<?php else : ?>
		<?php $tsl_obj = get_queried_object(); ?>
		<?php tsl_empty_state( ( $tsl_obj instanceof WP_Post_Type || $tsl_obj instanceof WP_Term ) ? (string) $tsl_obj->description : '' ); ?>
	<?php endif; ?>
</div>

<?php get_template_part( 'template-parts/newsletter' ); ?>

<?php
get_footer();
