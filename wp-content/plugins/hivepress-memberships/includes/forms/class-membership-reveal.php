<?php
/**
 * Membership reveal form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership reveal form class.
 *
 * @class Membership_Reveal
 */
class Membership_Reveal extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'action'      => hivepress()->router->get_url( 'membership_reveal_action' ),
				'description' => esc_html__( 'Are you sure you want to reveal these details?', 'hivepress-memberships' ),
				'redirect'    => true,

				'fields'      => [
					'id'    => [
						'type'         => 'id',
						'display_type' => 'hidden',
						'required'     => true,
					],

					'model' => [
						'type'         => 'text',
						'max_length'   => 256,
						'display_type' => 'hidden',
						'required'     => true,
					],
				],

				'button'      => [
					'label' => esc_html__( 'Reveal Details', 'hivepress-memberships' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
