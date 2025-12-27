<?php
/**
 * Membership renew email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership renew email class.
 *
 * @class Membership_Renew
 */
class Membership_Renew extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'       => esc_html__( 'Membership Renewed', 'hivepress-memberships' ),
				'description' => esc_html__( 'This email is sent to users when a membership is renewed.', 'hivepress-memberships' ),
				'recipient'   => hivepress()->translator->get_string( 'user' ),
				'tokens'      => [ 'user_name', 'membership_plan', 'memberships_url', 'user', 'membership' ],
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
				'subject' => hp\sanitize_html( __( 'Membership Renewed', 'hivepress-memberships' ) ),
				'body'    => hp\sanitize_html( __( 'Hi, %user_name%! Your "%membership_plan%" membership has been renewed, click on the following link to view it: %memberships_url%', 'hivepress-memberships' ) ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
