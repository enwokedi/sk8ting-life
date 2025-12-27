<?php
/**
 * Membership view block template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership view block template class.
 *
 * @class Membership_View_Block
 */
class Membership_View_Block extends Template {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'membership_container' => [
						'type'       => 'container',
						'_order'     => 10,

						'attributes' => [
							'class' => [ 'hp-membership', 'hp-membership--view-block' ],
						],

						'blocks'     => [
							'membership_content' => [
								'type'       => 'container',
								'_order'     => 10,

								'attributes' => [
									'class' => [ 'hp-membership__content' ],
								],

								'blocks'     => [
									'membership_name'   => [
										'type'   => 'part',
										'path'   => 'membership/view/membership-name',
										'_order' => 10,
									],

									'membership_details_primary' => [
										'type'       => 'container',
										'_order'     => 20,

										'attributes' => [
											'class' => [ 'hp-membership__details', 'hp-membership__details--primary' ],
										],

										'blocks'     => [
											'membership_expired_date' => [
												'type'   => 'part',
												'path'   => 'membership/view/membership-expired-date',
												'_order' => 10,
											],

											'membership_status' => [
												'type'   => 'part',
												'path'   => 'membership/view/membership-status',
												'_order' => 20,
											],
										],
									],

									'membership_limits' => [
										'type'   => 'part',
										'path'   => 'membership/view/membership-limits',
										'_order' => 30,
									],
								],
							],
						],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
