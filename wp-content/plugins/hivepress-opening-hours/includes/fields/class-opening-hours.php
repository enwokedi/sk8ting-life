<?php
/**
 * Opening hours field.
 *
 * @package HivePress\Fields
 */

namespace HivePress\Fields;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Opening hours field class.
 *
 * @class Opening_Hours
 */
class Opening_Hours extends Repeater {

	/**
	 * Class constructor.
	 *
	 * @param array $args Field arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			$args,
			[
				'display_type' => 'repeater',
			]
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps field properties.
	 */
	protected function boot() {

		// Set days.
		$days = [
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
		];

		// Set fields.
		$this->fields = hp\merge_arrays(
			$this->fields,
			[
				'day'  => [
					'placeholder' => esc_html__( 'Day', 'hivepress-opening-hours' ),
					'type'        => 'select',
					'required'    => true,
					'_order'      => 10,

					'options'     => array_combine(
						$days,
						array_map(
							function( $day ) {
								return date_i18n( 'l', strtotime( $day ) );
							},
							$days
						)
					),
				],

				'from' => [
					'placeholder' => esc_html__( 'From', 'hivepress-opening-hours' ),
					'type'        => 'time',
					'required'    => true,
					'_order'      => 20,
				],

				'to'   => [
					'placeholder' => esc_html__( 'To', 'hivepress-opening-hours' ),
					'type'        => 'time',
					'required'    => true,
					'_order'      => 30,
				],
			]
		);

		// Set attributes.
		$this->attributes = hp\merge_arrays(
			$this->attributes,
			[
				// @todo change when implemented in the core.
				'data-reverse-parent' => ( is_admin() ? hp\prefix( '' ) : '' ) . 'always_open',
			]
		);

		parent::boot();
	}

	/**
	 * Validates field value.
	 *
	 * @return bool
	 */
	public function validate() {
		if ( parent::validate() && ! is_null( $this->value ) ) {
			foreach ( $this->value as $value ) {
				if ( $value['from'] === $value['to'] ) {
					$this->add_errors( sprintf( hivepress()->translator->get_string( 'field_contains_invalid_value' ), $this->get_label( true ) ) );

					break;
				}
			}
		}

		return ! $this->errors;
	}
}
