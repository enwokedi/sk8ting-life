<?php
/**
 * Listing find email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing find email class.
 *
 * @class Listing_Find
 */
class Listing_Find extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'       => hivepress()->translator->get_string( 'listings_found' ),
				'description' => esc_html__( 'This email is sent to users when new listings match their search criteria.', 'hivepress-search-alerts' ),
				'recipient'   => hivepress()->translator->get_string( 'user' ),
				'tokens'      => [ 'user_name', 'listings_url', 'user' ],
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Email arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'subject' => hivepress()->translator->get_string( 'listings_found' ),
				'body'    => hp\sanitize_html( __( 'Hi, %user_name%! There are new listings matching your search criteria, click on the following link to view them: %listings_url%', 'hivepress-search-alerts' ) ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
