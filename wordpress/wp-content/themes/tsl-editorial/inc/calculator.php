<?php
/**
 * Kalkulator [tsl_kalkulator klucz="…"] bez wtyczki Cost Calculator Builder.
 *
 * Wtyczka tsl-core wstawia kalkulator przez Cost Calculator Builder (CCB);
 * bez CCB czytelnik nie widzi nic. Motyw rysuje wtedy ten sam kalkulator
 * z tej samej definicji (includes/data/ccb-kalkulatory.json: pola, limity,
 * wzory) — lekko, bez zewnętrznych skryptów. Gdy CCB jest włączony,
 * działa kalkulator wtyczki (bez zmian).
 *
 * @package TSL_Editorial
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wzór CCB („if( a ) { b } else { c }”, aliasy pól) → wyrażenie JS na obiekcie v.
 * Zwraca pusty tekst, gdy wzór zawiera coś poza liczbami, aliasami,
 * Math.min/max i operatorami (wtedy kalkulator się nie rysuje).
 *
 * @param string $formula Wzór.
 */
function tsl_calc_js_formula( $formula ) {
	$f = (string) $formula;
	do {
		$prev = $f;
		$f    = preg_replace( '/if\s*\((.*?)\)\s*\{([^{}]*)\}\s*else\s*\{([^{}]*)\}/s', '(($1) ? ($2) : ($3))', $f, 1 );
	} while ( $f !== $prev );
	$f = preg_replace( '/\b((?:quantity|range|total)_field_id_\d+)\b/', 'v.$1', $f );
	$check = preg_replace( '/\bMath\.(min|max)\b|\bv\.(quantity|range|total)_field_id_\d+\b/', '', $f );
	if ( preg_match( '/[^\d\s.+\-*\/()<>=!?:,&|]/', $check ) ) {
		return '';
	}
	return trim( $f );
}

/**
 * HTML kalkulatora z definicji CCB.
 *
 * @param array $calc Definicja kalkulatora.
 */
function tsl_calc_render( $calc ) {
	$fields = array();
	$totals = array();
	$html   = '';
	$uid    = wp_unique_id( 'calc-' );

	foreach ( $calc['ccb_fields'][0]['groupElements'] ?? array() as $section ) {
		$items = $section['fields'] ?? array();
		if ( $items && count( array_filter( $items, fn( $f ) => 'cost-total' === $f['_tag'] ) ) === count( $items ) ) {
			$totals = array_merge( $totals, $items );
			continue;
		}
		$html .= '<fieldset class="calc-section"><legend>' . esc_html( $section['label'] ?? '' ) . '</legend>';
		foreach ( $items as $f ) {
			$id    = $uid . '-' . $f['alias'];
			$desc  = ! empty( $f['description'] ) ? '<p class="calc-desc" id="' . esc_attr( $id ) . '-d">' . esc_html( $f['description'] ) . '</p>' : '';
			$aria  = $desc ? ' aria-describedby="' . esc_attr( $id ) . '-d"' : '';
			if ( 'cost-range' === $f['_tag'] ) {
				$fields[] = array( 'a' => $f['alias'], 'min' => (float) $f['minValue'], 'max' => (float) $f['maxValue'] );
				$html    .= '<div class="calc-field calc-field-range"><label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label>'
					. '<div class="calc-range"><input type="range" id="' . esc_attr( $id ) . '" data-alias="' . esc_attr( $f['alias'] ) . '" min="' . esc_attr( $f['minValue'] ) . '" max="' . esc_attr( $f['maxValue'] ) . '" step="' . esc_attr( $f['step'] ) . '" value="' . esc_attr( $f['default'] ) . '"' . $aria . '>'
					. '<output for="' . esc_attr( $id ) . '" data-sign="' . esc_attr( $f['sign'] ?? '' ) . '">' . esc_html( $f['default'] . ' ' . ( $f['sign'] ?? '' ) ) . '</output></div>' . $desc . '</div>';
			} elseif ( 'cost-quantity' === $f['_tag'] ) {
				$fields[] = array( 'a' => $f['alias'], 'min' => (float) $f['min'], 'max' => (float) $f['max'] );
				$html    .= '<div class="calc-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label>'
					. '<input type="text" inputmode="decimal" id="' . esc_attr( $id ) . '" data-alias="' . esc_attr( $f['alias'] ) . '" value="' . esc_attr( str_replace( '.', ',', (string) $f['default'] ) ) . '" autocomplete="off"' . $aria . '>' . $desc . '</div>';
			}
		}
		$html .= '</fieldset>';
	}

	$spec_totals = array();
	$rows        = '';
	foreach ( $totals as $t ) {
		$js = tsl_calc_js_formula( $t['costCalcFormula'] ?? '' );
		if ( '' === $js ) {
			return '';
		}
		$spec_totals[] = array( 'a' => $t['alias'], 'f' => $js );
		$rows         .= '<div class="calc-row"><dt>' . esc_html( $t['label'] ) . '</dt><dd data-total="' . esc_attr( $t['alias'] ) . '">—</dd></div>';
	}
	if ( ! $spec_totals ) {
		return '';
	}

	$spec = wp_json_encode( array( 'fields' => $fields, 'totals' => $spec_totals ) );
	return '<div class="calc" data-tsl-calc="' . esc_attr( $spec ) . '">'
		. '<div class="calc-fields"><p class="calc-title">' . esc_html( $calc['ccb_name'] ?? '' ) . '</p>' . $html . '</div>'
		. '<aside class="calc-result" aria-live="polite"><p class="calc-result-title">Wynik</p><dl>' . $rows . '</dl></aside>'
		. '</div>';
}

add_action(
	'init',
	function () {
		if ( ! function_exists( 'tsl_ccb_definitions' ) || ! function_exists( 'tsl_ccb_active' ) || tsl_ccb_active() ) {
			return;
		}
		remove_shortcode( 'tsl_kalkulator' );
		add_shortcode(
			'tsl_kalkulator',
			function ( $atts ) {
				$atts = shortcode_atts( array( 'klucz' => '' ), $atts, 'tsl_kalkulator' );
				$key  = sanitize_key( $atts['klucz'] );
				foreach ( tsl_ccb_definitions() as $calc ) {
					if ( ( $calc['tsl_key'] ?? '' ) === $key ) {
						$html = tsl_calc_render( $calc );
						if ( $html ) {
							return '<div class="tsl-calc" data-track-module="Kalkulator: ' . esc_attr( $key ) . '">' . $html
								. '<p class="tsl-widget-note tsl-calc-note"><strong>Wartości domyślne są przykładowe.</strong> Wynik jest szacunkiem i nie zastępuje kalkulacji controllingowej w Twojej firmie.</p></div>';
						}
					}
				}
				return current_user_can( 'edit_posts' ) ? '<p class="tsl-demo-note">Nie znaleziono kalkulatora „' . esc_html( $key ) . '”. Ten komunikat widzi tylko redakcja.</p>' : '';
			}
		);
	},
	20
);
