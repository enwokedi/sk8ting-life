<?php
/**
 * Booking model.
 *
 * @package HivePress\Models
 */

namespace HivePress\Models;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking model class.
 *
 * @class Booking
 */
class Booking extends Post {

	/**
	 * Class constructor.
	 *
	 * @param array $args Model arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'fields' => [
					'title'            => [
						'type'       => 'text',
						'max_length' => 256,
						'_alias'     => 'post_title',
					],

					'note'             => [
						'label'      => esc_html__( 'Notes', 'hivepress-bookings' ),
						'type'       => 'textarea',
						'max_length' => 10240,
						'html'       => true,
						'_alias'     => 'post_content',
					],

					'status'           => [
						'type'    => 'select',
						'_alias'  => 'post_status',

						'options' => [
							'publish'    => esc_html_x( 'Confirmed', 'booking', 'hivepress-bookings' ),
							'future'     => '',
							'draft'      => esc_html_x( 'Unpaid', 'booking', 'hivepress-bookings' ),
							'pending'    => esc_html_x( 'Pending', 'booking', 'hivepress-bookings' ),
							'private'    => '',
							'trash'      => esc_html_x( 'Canceled', 'booking', 'hivepress-bookings' ),
							'auto-draft' => '',
							'inherit'    => '',
						],
					],

					'drafted'          => [
						'type'      => 'checkbox',
						'_external' => true,
					],

					'canceled'         => [
						'type'      => 'checkbox',
						'_external' => true,
					],

					'created_date'     => [
						'type'   => 'date',
						'format' => 'Y-m-d H:i:s',
						'_alias' => 'post_date',
					],

					'created_date_gmt' => [
						'type'   => 'date',
						'format' => 'Y-m-d H:i:s',
						'_alias' => 'post_date_gmt',
					],

					'modified_date'    => [
						'type'   => 'date',
						'format' => 'Y-m-d H:i:s',
						'_alias' => 'post_modified',
					],

					'start_time'       => [
						'label'     => hivepress()->translator->get_string( 'start_date' ),
						'type'      => 'number',
						'min_value' => 0,
						'required'  => true,
						'_external' => true,
					],

					'end_time'         => [
						'label'     => hivepress()->translator->get_string( 'end_date' ),
						'type'      => 'number',
						'min_value' => 0,
						'required'  => true,
						'_external' => true,
					],

					'listing'          => [
						'type'     => 'id',
						'required' => true,
						'_alias'   => 'post_parent',
						'_model'   => 'listing',
					],

					'user'             => [
						'type'     => 'id',
						'required' => true,
						'_alias'   => 'post_author',
						'_model'   => 'user',
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Gets model fields.
	 *
	 * @param string $area Display area.
	 * @return array
	 */
	final public function _get_fields( $area = null ) {
		return array_filter(
			$this->fields,
			function( $field ) use ( $area ) {
				return ! $area || in_array( $area, (array) $field->get_arg( '_display_areas' ), true );
			}
		);
	}

	/**
	 * Displays booking dates.
	 *
	 * @return string
	 */
	final public function display_dates() {
		$output = '';

		if ( $this->get_start_time() && $this->get_end_time() ) {
			$date_format = get_option( 'date_format' );

			if ( hivepress()->booking->is_recurring( $this ) ) {

				/* translators: %s: date. */
				$output = sprintf( esc_html__( 'from %s', 'hivepress-bookings' ), date_i18n( $date_format, $this->get_start_time() ) );
			} elseif ( hivepress()->booking->is_time_enabled( $this->get_listing() ) ) {
				$time_format = get_option( 'time_format' );

				$output = date_i18n( $date_format . ' ' . $time_format, $this->get_start_time() ) . ' - ' . date_i18n( $time_format, $this->get_end_time() );
			} else {
				$output = implode(
					' - ',
					array_unique(
						[
							date_i18n( $date_format, $this->get_start_time() ),
							date_i18n( $date_format, $this->get_end_time() ),
						]
					)
				);
			}
		}

		return $output;
	}
}
