<?php
/**
 * Stopka: indeks serwisu (działy, korzystaj, projekt), CTA newslettera,
 * dolna belka z dokumentami i znak słowny na całą szerokość.
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="site-footer">
	<div class="wrap footer-top">
		<div class="footer-intro">
			<p class="footer-claim"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<a class="btn btn-primary" href="<?php echo esc_url( tsl_page_url( 'newsletter' ) ); ?>">Zapisz się do newslettera<?php echo tsl_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>

		<nav class="footer-col" aria-label="Filary">
			<p class="footer-heading">Działy</p>
			<ol class="footer-pillars">
				<?php foreach ( tsl_get_pillars() as $pillar ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $pillar ) ); ?>"><?php echo esc_html( $pillar->name ); ?></a></li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<nav class="footer-col" aria-label="Korzystaj">
			<p class="footer-heading">Korzystaj</p>
			<ul>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'narzedzie' ) ); ?>">Narzędzia</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'riskbrief' ) ); ?>">RiskBrief TSL</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'raport' ) ); ?>">Raporty i dane</a></li>
				<li><a href="<?php echo esc_url( tsl_events_url() ); ?>">Wydarzenia</a></li>
				<li><a href="<?php echo esc_url( tsl_page_url( 'newsletter' ) ); ?>">Newsletter</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'ekspert' ) ); ?>">Eksperci</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'partner' ) ); ?>">Partnerzy</a></li>
			</ul>
		</nav>

		<nav class="footer-col" aria-label="Projekt">
			<p class="footer-heading">Projekt</p>
			<?php
			if ( has_nav_menu( 'footer-project' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer-project',
						'container'      => false,
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul>';
				foreach ( array( 'o-projekcie' => 'O projekcie', 'redakcja' => 'Redakcja', 'zasady-redakcyjne' => 'Zasady redakcyjne', 'wspolpraca-partnerska' => 'Współpraca partnerska', 'kontakt' => 'Kontakt' ) as $path => $label ) {
					echo '<li><a href="' . esc_url( tsl_page_url( $path ) ) . '">' . esc_html( $label ) . '</a></li>';
				}
				echo '</ul>';
			}
			?>
		</nav>
	</div>

	<div class="wrap footer-sign">
		<a class="footer-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — strona główna">
			<?php echo tsl_logo( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>

	<div class="wrap footer-bottom">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> TSL Manager</p>
		<?php
		if ( has_nav_menu( 'footer-legal' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'footer-legal',
					'container'      => false,
					'depth'          => 1,
					'menu_class'     => 'legal-links',
				)
			);
		} else {
			echo '<ul class="legal-links">';
			foreach ( array( 'polityka-prywatnosci' => 'Polityka prywatności', 'polityka-cookies' => 'Cookies', 'dostepnosc' => 'Dostępność', 'regulaminy' => 'Regulaminy', 'korekty-aktualizacje' => 'Korekty i aktualizacje' ) as $path => $label ) {
				echo '<li><a href="' . esc_url( tsl_page_url( $path ) ) . '">' . esc_html( $label ) . '</a></li>';
			}
			echo '</ul>';
		}
		// Ponowne otwarcie banera zgody — gdy któreś narzędzie analityczne wymaga zgody.
		if ( function_exists( 'tsl_analytics_needs_consent' ) && tsl_analytics_needs_consent() ) {
			echo '<button type="button" class="footer-privacy" data-tsl-privacy aria-controls="tsl-consent" hidden>Ustawienia prywatności</button>';
		}
		?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
