<?php
/**
 * Strona główna — „wydanie”: okładka, pas sygnałów RiskBrief, spis działów,
 * najnowsze (różna gęstość), dossier „Decyzja pod lupą”, warsztat narzędzi,
 * koszt, newsletter, raport i kalendarium, głosy ekspertów, partnerzy.
 *
 * Zakres i logika modułów są takie same jak w motywie 0.10.0:
 * moduł bez treści nie jest wyświetlany, moduły włącza się w
 * Wygląd → Strona główna (tsl_home_on()), materiały się nie powtarzają,
 * a strona ma zawsze dokładnie jeden H1 (temat tygodnia albo nazwa serwisu).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hero     = tsl_hero_post();
$exclude  = $hero ? array( $hero->ID ) : array();
$briefs   = tsl_posts( array( 'post_type' => 'riskbrief', 'posts_per_page' => 5 ) );
$critical = tsl_posts(
	array(
		'post_type'      => 'riskbrief',
		'posts_per_page' => 1,
		'tax_query'      => tsl_in_term( 'poziom_ryzyka', 'krytyczne' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
		'date_query'     => array( array( 'after' => '7 days ago' ) ),
	)
);
$arrow    = tsl_icon( 'arrow' );
?>

<?php /* Pilny sygnał — tylko gdy istnieje realny alert (krytyczny RiskBrief z 7 dni). */ ?>
<?php if ( $critical && tsl_home_on( 'pilny' ) ) : ?>
	<div class="urgent" data-track-module="pilny-sygnal">
		<div class="wrap urgent-inner">
			<span class="urgent-flag">Pilny sygnał</span>
			<a href="<?php echo esc_url( get_permalink( $critical[0] ) ); ?>"><?php echo esc_html( get_the_title( $critical[0] ) ); ?><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>
	</div>
<?php endif; ?>

<?php
$show_hero = $hero && tsl_home_on( 'hero' );
$show_rb   = $briefs && tsl_home_on( 'riskbrief' );
?>
<?php /* Okładka: temat tygodnia. */ ?>
<section class="cover<?php echo $show_hero ? '' : ' cover-intro'; ?>" data-track-module="hero">
	<div class="wrap cover-grid">
		<?php if ( ! $show_hero ) : ?>
			<p class="cover-kicker"><span class="cover-flag">Portal dla menedżerów TSL</span></p>
			<h1 class="cover-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<div class="cover-side">
				<?php if ( get_bloginfo( 'description' ) ) : ?>
					<p class="cover-lead"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
				<?php endif; ?>
				<div class="cover-actions">
					<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">Narzędzia dla menedżera<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
			</div>
		<?php else : ?>
			<p class="cover-kicker">
				<span class="cover-flag">Temat tygodnia</span>
				<span class="cover-kind"><?php echo esc_html( tsl_kind_label( $hero ) ); ?></span>
				<?php echo tsl_pillar_label( $hero ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo tsl_sponsor_label( $hero ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
			<h1 class="cover-title"><a href="<?php echo esc_url( get_permalink( $hero ) ); ?>"><?php echo esc_html( get_the_title( $hero ) ); ?></a></h1>
			<div class="cover-side">
				<?php if ( has_excerpt( $hero ) ) : ?>
					<p class="cover-lead"><?php echo esc_html( get_the_excerpt( $hero ) ); ?></p>
				<?php endif; ?>
				<?php echo tsl_post_meta( $hero, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="cover-actions">
					<a class="btn btn-primary" href="<?php echo esc_url( get_permalink( $hero ) ); ?>">Czytaj analizę<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">Narzędzia dla menedżera<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
			</div>
			<a class="cover-media" href="<?php echo esc_url( get_permalink( $hero ) ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo tsl_media( $hero, 'tsl-hero', 'eager', '(max-width: 900px) 100vw, 66vw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
	</div>
</section>

<?php /* Pas sygnałów RiskBrief. */ ?>
<?php if ( $show_rb ) : ?>
	<section class="signals" aria-labelledby="rb-title" data-track-module="riskbrief">
		<div class="wrap">
			<header class="signals-head">
				<p class="signals-brand"><span class="signal-dot" aria-hidden="true"></span>RiskBrief TSL</p>
				<h2 id="rb-title" class="signals-title">Bieżące ryzyka i ich wpływ na biznes</h2>
				<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'riskbrief' ) ); ?>">Archiwum sygnałów<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</header>
			<ol class="signals-list">
				<?php foreach ( $briefs as $i => $brief ) : ?>
					<li class="signal">
						<span class="signal-num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<?php echo tsl_risk_badge( $brief ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a class="signal-title" href="<?php echo esc_url( get_permalink( $brief ) ); ?>"><?php echo esc_html( get_the_title( $brief ) ); ?></a>
						<time datetime="<?php echo esc_attr( get_the_date( 'c', $brief ) ); ?>"><?php echo esc_html( get_the_date( '', $brief ) ); ?></time>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<?php /* Spis działów (sześć filarów). */ ?>
<?php if ( tsl_home_on( 'filary' ) ) : ?>
	<section class="section section-index" data-track-module="filary">
		<div class="wrap">
			<?php tsl_section_header( 'Sześć filarów wiedzy dla liderów TSL', '', '', 'Obszary decyzji — spis działów serwisu' ); ?>
			<ol class="pillar-index">
				<?php foreach ( tsl_get_pillars() as $i => $pillar ) : ?>
					<li>
						<a class="pillar-row" href="<?php echo esc_url( get_term_link( $pillar ) ); ?>">
							<span class="pi-num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="pi-name pillar-name"><?php echo esc_html( $pillar->name ); ?></span>
							<span class="pi-promise"><?php echo esc_html( tsl_term_promise( $pillar ) ); ?></span>
							<span class="pi-subs"><?php echo esc_html( implode( ' · ', wp_list_pluck( array_slice( tsl_get_subcategories( $pillar, true ), 0, 3 ), 'name' ) ) ); ?></span>
							<span class="pi-arrow"><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<?php /* Najnowsze: po jednym materiale z każdego filaru. */ ?>
<?php
$latest = array();
foreach ( tsl_get_pillars() as $pillar ) {
	$found = tsl_posts(
		array(
			'posts_per_page' => 1,
			'post__not_in'   => $exclude,
			'tax_query'      => tsl_in_category( $pillar ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
		)
	);
	if ( $found ) {
		$latest[] = $found[0];
	}
}
// Materiały pokazane tutaj nie powtarzają się w kolejnych modułach.
$exclude = array_merge( $exclude, wp_list_pluck( $latest, 'ID' ) );
?>
<?php if ( $latest && tsl_home_on( 'najnowsze' ) ) : ?>
	<section class="section section-latest" data-track-module="najnowsze">
		<div class="wrap">
			<?php tsl_section_header( 'Najnowsze analizy', home_url( '/?s=' ), 'Wszystkie materiały', 'Po jednym z każdego filaru' ); ?>
			<div class="latest-grid">
				<div class="latest-lead"><?php tsl_card( array_shift( $latest ), 'lead' ); ?></div>
				<?php if ( $latest ) : ?>
					<div class="latest-mid">
						<?php foreach ( array_splice( $latest, 0, 2 ) as $item ) : ?>
							<?php tsl_card( $item, 'medium' ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( $latest ) : ?>
					<div class="latest-list">
						<?php foreach ( $latest as $n => $item ) : ?>
							<?php tsl_card( $item, 'text', array( 'num' => str_pad( (string) ( $n + 1 ), 2, '0', STR_PAD_LEFT ) ) ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* Dossier: Decyzja pod lupą — materiał flagowy + 2. */ ?>
<?php
$decisions = tsl_posts(
	array(
		'posts_per_page' => 3,
		'post__not_in'   => $exclude,
		'tax_query'      => tsl_in_term( 'format', 'decyzja-pod-lupa' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
?>
<?php if ( $decisions && tsl_home_on( 'decyzja' ) ) : ?>
	<section class="section dossier" aria-labelledby="dossier-title" data-track-module="decyzja-pod-lupa">
		<div class="wrap dossier-grid">
			<header class="dossier-head">
				<p class="dossier-kicker">Warianty, koszty, ryzyka</p>
				<h2 class="dossier-title" id="dossier-title">Decyzja pod lupą</h2>
				<p class="dossier-desc">Porównujemy warianty tak, jak robi to zarząd: pełny koszt, czas, ryzyko i to, co trzeba sprawdzić przed podpisem.</p>
				<a class="link-more" href="<?php echo esc_url( home_url( '/?s=&format=decyzja-pod-lupa' ) ); ?>">Więcej porównań<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</header>
			<div class="dossier-feature"><?php tsl_card( array_shift( $decisions ), 'lead' ); ?></div>
			<?php if ( $decisions ) : ?>
				<div class="dossier-more">
					<?php foreach ( $decisions as $n => $item ) : ?>
						<?php tsl_card( $item, 'text', array( 'num' => str_pad( (string) ( $n + 2 ), 2, '0', STR_PAD_LEFT ) ) ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php /* Warsztat: narzędzia dla menedżera. */ ?>
<?php $tools = tsl_posts( array( 'post_type' => 'narzedzie', 'posts_per_page' => 6 ) ); ?>
<?php if ( $tools && tsl_home_on( 'narzedzia' ) ) : ?>
	<section class="section section-tools" data-track-module="narzedzia">
		<div class="wrap">
			<?php tsl_section_header( 'Narzędzia dla menedżera', get_post_type_archive_link( 'narzedzie' ), 'Wszystkie narzędzia', 'Checklisty, formularze, kalkulatory, procedury — gotowe do użycia' ); ?>
			<ol class="tool-index">
				<?php foreach ( $tools as $i => $tool ) : ?>
					<li>
						<a class="tool-row<?php echo 0 === $i ? ' is-featured' : ''; ?>" href="<?php echo esc_url( get_permalink( $tool ) ); ?>">
							<span class="tool-type"><?php echo esc_html( tsl_kind_label( $tool ) ); ?></span>
							<span class="tool-title"><?php echo esc_html( get_the_title( $tool ) ); ?></span>
							<?php if ( has_excerpt( $tool ) ) : ?>
								<span class="tool-lead"><?php echo esc_html( get_the_excerpt( $tool ) ); ?></span>
							<?php endif; ?>
							<span class="tool-cta">Użyj narzędzia<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<?php /* Ile to naprawdę kosztuje? + kalkulator. */ ?>
<?php
$costs      = tsl_posts(
	array(
		'posts_per_page' => 1,
		'post__not_in'   => $exclude,
		'tax_query'      => tsl_in_term( 'format', 'ile-to-kosztuje' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
$calculator = tsl_posts(
	array(
		'post_type'      => 'narzedzie',
		'posts_per_page' => 1,
		'tax_query'      => tsl_in_term( 'typ_narzedzia', 'kalkulatory' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
?>
<?php if ( ( $costs || $calculator ) && tsl_home_on( 'kalkulator' ) ) : ?>
	<section class="section cost" data-track-module="ile-to-kosztuje">
		<div class="wrap cost-grid">
			<p class="cost-mark" aria-hidden="true"><span>zł</span><span>/km</span></p>
			<div class="cost-copy">
				<p class="cost-kicker">Ile to naprawdę kosztuje?</p>
				<?php if ( $costs ) : ?>
					<h2 class="cost-title"><a href="<?php echo esc_url( get_permalink( $costs[0] ) ); ?>"><?php echo esc_html( get_the_title( $costs[0] ) ); ?></a></h2>
					<?php if ( has_excerpt( $costs[0] ) ) : ?>
						<p class="cost-lead"><?php echo esc_html( get_the_excerpt( $costs[0] ) ); ?></p>
					<?php endif; ?>
					<a class="link-more" href="<?php echo esc_url( get_permalink( $costs[0] ) ); ?>">Czytaj kalkulację<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php else : ?>
					<h2 class="cost-title">Kalkulator kosztu kilometra</h2>
					<p class="cost-lead">Policz koszt kilometra, próg opłacalności i stawkę z narzutem na podstawie własnych kosztów floty.</p>
				<?php endif; ?>
			</div>
			<?php if ( $calculator ) : ?>
				<a class="cost-tool" href="<?php echo esc_url( get_permalink( $calculator[0] ) ); ?>">
					<span class="tool-type">Kalkulator</span>
					<span class="tool-title"><?php echo esc_html( get_the_title( $calculator[0] ) ); ?></span>
					<span class="tool-cta">Policz<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php /* Transport i operacje — rodzaje transportu (zakładki). */ ?>
<?php
$transport_tabs = array();
foreach ( array( 'drogowy' => 'Drogowy', 'morski' => 'Morski', 'kolejowy' => 'Kolejowy', 'lotniczy' => 'Lotniczy' ) as $mode => $label ) {
	$transport_tabs[ $mode ] = array(
		'label' => $label,
		'posts' => tsl_posts(
			array(
				'posts_per_page' => 3,
				'tax_query'      => tsl_in_term( 'rodzaj_transportu', $mode ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
			)
		),
		'term'  => get_category_by_path( 'transport-operacje/transport/' . $mode, true ),
	);
}
$transport_tabs = array_filter(
	$transport_tabs,
	function ( $tab ) {
		return $tab['posts'];
	}
);
$transport      = get_category_by_path( 'transport-operacje', true );
$spedycja       = get_category_by_path( 'transport-operacje/spedycja', true );
?>
<?php if ( $transport_tabs && $transport && tsl_home_on( 'transport' ) ) : ?>
	<section class="section section-transport" data-track-module="transport">
		<div class="wrap">
			<?php tsl_section_header( 'Transport i operacje', get_term_link( $transport ), 'Cały dział', 'Wszystkie gałęzie TSL' ); ?>
			<div class="tabs" data-tabs>
				<div class="tab-list" role="tablist" aria-label="Rodzaje transportu">
					<?php $first = true; ?>
					<?php foreach ( $transport_tabs as $mode => $tab ) : ?>
						<button class="tab" role="tab" type="button" id="tab-<?php echo esc_attr( $mode ); ?>" aria-controls="panel-<?php echo esc_attr( $mode ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" <?php echo $first ? '' : 'tabindex="-1"'; ?>><?php echo esc_html( $tab['label'] ); ?></button>
						<?php $first = false; ?>
					<?php endforeach; ?>
					<?php if ( $spedycja ) : ?>
						<a class="tab-link" href="<?php echo esc_url( get_term_link( $spedycja ) ); ?>">Spedycja<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				</div>
				<?php $first = true; ?>
				<?php foreach ( $transport_tabs as $mode => $tab ) : ?>
					<div class="tab-panel" role="tabpanel" id="panel-<?php echo esc_attr( $mode ); ?>" aria-labelledby="tab-<?php echo esc_attr( $mode ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
						<div class="story-row">
							<?php foreach ( $tab['posts'] as $item ) : ?>
								<?php tsl_card( $item, 'medium' ); ?>
							<?php endforeach; ?>
						</div>
						<?php if ( $tab['term'] ) : ?>
							<a class="link-more" href="<?php echo esc_url( get_term_link( $tab['term'] ) ); ?>">Transport <?php echo esc_html( mb_strtolower( $tab['label'] ) ); ?><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endif; ?>
					</div>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* Szkoda pod lupą / Pierwsze 24 godziny + powiązana procedura. */ ?>
<?php
$incident  = tsl_posts(
	array(
		'posts_per_page' => 1,
		'tax_query'      => tsl_in_term( 'format', array( 'szkoda-pod-lupa', 'pierwsze-24-godziny' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
$procedure = tsl_posts(
	array(
		'post_type'      => 'narzedzie',
		'posts_per_page' => 1,
		'tax_query'      => tsl_in_term( 'typ_narzedzia', 'procedury' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
	)
);
?>
<?php if ( $incident && tsl_home_on( 'pierwsze24' ) ) : ?>
	<section class="incident" data-track-module="pierwsze-24h">
		<div class="wrap incident-grid">
			<div class="incident-main">
				<p class="incident-kicker"><?php echo esc_html( tsl_kind_label( $incident[0] ) ); ?></p>
				<h2 class="incident-title"><a href="<?php echo esc_url( get_permalink( $incident[0] ) ); ?>"><?php echo esc_html( get_the_title( $incident[0] ) ); ?></a></h2>
				<?php if ( has_excerpt( $incident[0] ) ) : ?>
					<p class="incident-lead"><?php echo esc_html( get_the_excerpt( $incident[0] ) ); ?></p>
				<?php endif; ?>
				<a class="btn btn-accent" href="<?php echo esc_url( get_permalink( $incident[0] ) ); ?>">Zobacz procedurę krok po kroku</a>
			</div>
			<?php if ( $procedure ) : ?>
				<a class="incident-tool" href="<?php echo esc_url( get_permalink( $procedure[0] ) ); ?>">
					<span class="tool-type">Procedura do pobrania</span>
					<span class="tool-title"><?php echo esc_html( get_the_title( $procedure[0] ) ); ?></span>
					<span class="tool-cta">Otwórz<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php /* Newsletter / RiskBrief na e-mail. */ ?>
<?php if ( tsl_home_on( 'newsletter' ) ) : ?>
	<div data-track-module="newsletter">
		<?php get_template_part( 'template-parts/newsletter' ); ?>
	</div>
<?php endif; ?>

<?php /* Raporty i dane + Wydarzenia (kalendarium). */ ?>
<?php
$reports = tsl_home_on( 'raporty' ) ? tsl_posts( array( 'post_type' => 'raport', 'posts_per_page' => 1 ) ) : array();
$events  = array();
if ( tsl_home_on( 'wydarzenia' ) ) {
	$events = tsl_get_events( 'nadchodzace', 3 );
	if ( count( $events ) < 3 ) {
		$events = array_merge( $events, tsl_get_events( 'zakonczone', 3 - count( $events ) ) );
	}
}
?>
<?php if ( $reports || $events ) : ?>
	<section class="section section-data" data-track-module="raporty-wydarzenia">
		<div class="wrap data-grid<?php echo ( $reports && $events ) ? '' : ' is-single'; ?>">
			<?php if ( $reports ) : ?>
				<div class="data-reports">
					<?php tsl_section_header( 'Raporty i dane', get_post_type_archive_link( 'raport' ), 'Wszystkie raporty', 'Dane, trendy, benchmarki' ); ?>
					<a class="report-feature" href="<?php echo esc_url( get_permalink( $reports[0] ) ); ?>">
						<span class="report-cover" aria-hidden="true">
							<span class="rc-brand">TSL Manager · <?php echo esc_html( tsl_kind_label( $reports[0] ) ); ?></span>
							<span class="rc-title"><?php echo esc_html( get_the_title( $reports[0] ) ); ?></span>
							<span class="rc-year"><?php echo esc_html( get_the_date( 'Y', $reports[0] ) ); ?></span>
						</span>
						<span class="report-copy">
							<span class="tool-type"><?php echo esc_html( tsl_kind_label( $reports[0] ) ); ?></span>
							<span class="report-title"><?php echo esc_html( get_the_title( $reports[0] ) ); ?></span>
							<?php if ( has_excerpt( $reports[0] ) ) : ?>
								<span class="tool-lead"><?php echo esc_html( get_the_excerpt( $reports[0] ) ); ?></span>
							<?php endif; ?>
							<span class="tool-cta">Najważniejsze wnioski<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</span>
					</a>
				</div>
			<?php endif; ?>
			<?php if ( $events ) : ?>
				<div class="data-events">
					<?php tsl_section_header( 'Wydarzenia', tsl_events_url(), 'Kalendarz', 'Webinary, debaty, konferencje' ); ?>
					<ol class="calendar">
						<?php foreach ( $events as $event ) : ?>
							<?php get_template_part( 'template-parts/event-row', null, array( 'post' => $event ) ); ?>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php /* Eksperci — głosy praktyków. */ ?>
<?php $experts = tsl_posts( array( 'post_type' => 'ekspert', 'posts_per_page' => 4 ) ); ?>
<?php if ( $experts && tsl_home_on( 'eksperci' ) ) : ?>
	<section class="section section-voices" data-track-module="eksperci">
		<div class="wrap">
			<?php tsl_section_header( 'Eksperci', get_post_type_archive_link( 'ekspert' ), 'Wszyscy eksperci', 'Wiedza praktyków' ); ?>
			<ul class="voices">
				<?php foreach ( $experts as $expert ) : ?>
					<li><?php get_template_part( 'template-parts/person', null, array( 'post' => $expert ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php /* Partnerzy — role są jawne, logotypy nie dominują nad treścią. */ ?>
<?php $partners = tsl_posts( array( 'post_type' => 'partner', 'posts_per_page' => 8 ) ); ?>
<?php if ( $partners && tsl_home_on( 'partnerzy' ) ) : ?>
	<section class="section section-partners" data-track-module="partnerzy">
		<div class="wrap partners-grid">
			<?php tsl_section_header( 'Partnerzy projektu', get_post_type_archive_link( 'partner' ), 'Zasady partnerstwa' ); ?>
			<ul class="partner-list">
				<?php foreach ( $partners as $partner ) : ?>
					<?php $level = get_the_terms( $partner, 'tsl_poziom_partnerstwa' ); ?>
					<li>
						<?php if ( $level && ! is_wp_error( $level ) ) : ?>
							<span class="partner-role"><?php echo esc_html( $level[0]->name ); ?></span>
						<?php endif; ?>
						<a href="<?php echo esc_url( get_permalink( $partner ) ); ?>">
							<?php echo has_post_thumbnail( $partner ) ? get_the_post_thumbnail( $partner, 'medium', array( 'alt' => get_the_title( $partner ) ) ) : esc_html( get_the_title( $partner ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
