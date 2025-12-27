<?php
/**
 * Booking range model.
 *
 * @package HivePress\Models
 */

namespace HivePress\Models;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking range model class.
 *
 * @class Booking_Range
 */
class Booking_Range extends Comment {

	/**
	 * Class constructor.
	 *
	 * @param array $args Model arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'fields' => [
					'start_time' => [
						'type'      => 'number',
						'min_value' => 0,
						'required'  => true,
						'_external' => true,
					],

					'end_time'   => [
						'type'      => 'number',
						'min_value' => 0,
						'required'  => true,
						'_external' => true,
					],

					'price'      => [
						'type'      => 'currency',
						'min_value' => 0,
						'required'  => true,
						'_external' => true,
					],

					'user'       => [
						'type'     => 'id',
						'required' => true,
						'_alias'   => 'user_id',
						'_model'   => 'user',
					],

					'listing'    => [
						'type'     => 'id',
						'required' => true,
						'_alias'   => 'comment_post_ID',
						'_model'   => 'listing',
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
