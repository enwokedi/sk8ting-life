<?php
/**
 * Listing import email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sent to admins when new listings are imported.
 */
class Listing_Import extends Email {

	/**
	 * Class constructor.
	 *
	 * @param array $args Email arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'subject' => hivepress()->translator->get_string( 'listings_imported' ),
				'body'    => hp\sanitize_html( __( 'New listings have been imported, click on the following link to view them: %listings_url%', 'hivepress-import' ) ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
