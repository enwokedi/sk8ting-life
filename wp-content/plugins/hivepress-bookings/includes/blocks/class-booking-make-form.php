<?php
/**
 * Booking make form block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking make form block class.
 *
 * @class Booking_Make_Form
 */
class Booking_Make_Form extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Block arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'form' => 'booking_make',
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps block properties.
	 */
	protected function boot() {

		// Get listing.
		$listing = $this->get_context( 'listing' );

		if ( $listing ) {

			// Set listing ID.
			$this->values['listing'] = $listing->get_id();

			if ( hivepress()->get_version( 'marketplace' ) ) {

				// Set rendering.
				$this->attributes['data-render'] = wp_json_encode(
					[
						'url'   => hivepress()->router->get_url( 'listing_buy_action', [ 'listing_id' => $listing->get_id() ] ),
						'block' => 'listing_attributes_primary',
					]
				);
			}
		}

		parent::boot();
	}
}
