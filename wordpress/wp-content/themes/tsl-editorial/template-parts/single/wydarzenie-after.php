<?php
/**
 * Wydarzenie: agenda, prelegenci; po terminie — materiały (rozdz. 7.3).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

$post_obj = $args['post'];
?>
<?php
$agenda   = tsl_field_lines( 'tsl_agenda', $post_obj );
$speakers = tsl_related( 'ekspert', $post_obj );
$past     = tsl_event_is_past( $post_obj );
?>
<?php if ( $agenda ) : ?>
	<section class="agenda">
		<h2>Agenda</h2>
		<ol class="agenda-list">
			<?php foreach ( $agenda as $line ) : ?>
				<?php $parts = preg_split( '/\s+[—–-]\s+/u', $line, 2 ) ?: array( $line ); ?>
				<li>
					<?php if ( count( $parts ) === 2 ) : ?>
						<span class="agenda-time"><?php echo esc_html( $parts[0] ); ?></span><span><?php echo esc_html( $parts[1] ); ?></span>
					<?php else : ?>
						<span><?php echo esc_html( $line ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<?php if ( $speakers ) : ?>
	<section class="speakers">
		<h2>Prelegenci</h2>
		<?php tsl_people_box( $speakers, '' ); ?>
	</section>
<?php endif; ?>

<?php if ( ! $past && ! tsl_field( 'tsl_registration_url', $post_obj ) ) : ?>
	<section class="form-section" id="rejestracja">
		<?php get_template_part( 'template-parts/form', null, array( 'type' => 'wydarzenie', 'post' => $post_obj, 'title' => 'Rejestracja' ) ); ?>
	</section>
<?php endif; ?>

<?php if ( $past ) : ?>
	<?php
	$materials = array_merge( tsl_related( 'post', $post_obj ), tsl_related( 'narzedzie', $post_obj ), tsl_related( 'raport', $post_obj ) );
	?>
	<section class="after-event">
		<h2>Materiały po wydarzeniu</h2>
		<?php if ( $materials ) : ?>
			<div class="post-list">
				<?php foreach ( $materials as $item ) : ?>
					<?php tsl_card( $item, 'row' ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p>Wnioski i materiały z wydarzenia opublikujemy wkrótce.</p>
		<?php endif; ?>
	</section>
<?php endif; ?>
