<?php
/**
 * Newsletter: integracja z narzędziami prywatności WordPressa
 * (Narzędzia → Eksport / Usuwanie danych osobowych).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['tsl-newsletter'] = array(
			'exporter_friendly_name' => 'Newsletter TSL Manager',
			'callback'               => function ( $email ) {
				$s = tsl_nl_get_by_email( $email );
				if ( ! $s ) {
					return array(
						'data' => array(),
						'done' => true,
					);
				}
				return array(
					'data' => array(
						array(
							'group_id'    => 'tsl-newsletter',
							'group_label' => 'Newsletter',
							'item_id'     => 'subscriber-' . $s->id,
							'data'        => array(
								array( 'name' => 'E-mail', 'value' => $s->email ),
								array( 'name' => 'Status', 'value' => $s->status ),
								array( 'name' => 'Data zapisu (UTC)', 'value' => $s->created_at ),
								array( 'name' => 'Data potwierdzenia (UTC)', 'value' => (string) $s->confirmed_at ),
								array( 'name' => 'Data wypisania (UTC)', 'value' => (string) $s->unsubscribed_at ),
								array( 'name' => 'Treść zgody', 'value' => $s->consent_text ),
								array( 'name' => 'Adres IP przy zapisie', 'value' => $s->consent_ip ),
							),
						),
					),
					'done' => true,
				);
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $erasers ) {
		$erasers['tsl-newsletter'] = array(
			'eraser_friendly_name' => 'Newsletter TSL Manager',
			'callback'             => function ( $email ) {
				$s = tsl_nl_get_by_email( $email );
				if ( $s ) {
					tsl_nl_delete( $s->id );
				}
				return array(
					'items_removed'  => (bool) $s,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			},
		);
		return $erasers;
	}
);
