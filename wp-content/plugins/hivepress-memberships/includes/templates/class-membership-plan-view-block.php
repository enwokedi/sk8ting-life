<?php
/**
 * Membership plan view block template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership plan view block template class.
 *
 * @class Membership_Plan_View_Block
 */
class Membership_Plan_View_Block extends Template {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'membership_plan_container' => [
						'type'       => 'container',
						'_order'     => 10,

						'attributes' => [
							'class' => [ 'hp-membership-plan', 'hp-membership-plan--view-block' ],
						],

						'blocks'     => [
							'membership_plan_header'  => [
								'type'       => 'container',
								'tag'        => 'header',
								'_order'     => 10,

								'attributes' => [
									'class' => [ 'hp-membership-plan__header' ],
								],

								'blocks'     => [
									'membership_plan_name' => [
										'type'       => 'container',
										'tag'        => 'h3',
										'_order'     => 10,

										'attributes' => [
											'class' => [ 'hp-membership-plan__name' ],
										],

										'blocks'     => [
											'membership_plan_name_text'     => [
												'type'   => 'part',
												'path'   => 'membership-plan/view/membership-plan-name',
												'_order' => 10,
											],

											'membership_plan_primary_badge' => [
												'type'   => 'part',
												'path'   => 'membership-plan/view/membership-plan-primary-badge',
												'_order' => 20,
											],
										],
									],

									'membership_plan_price' => [
										'type'   => 'part',
										'path'   => 'membership-plan/view/membership-plan-price',
										'_order' => 20,
									],
								],
							],

							'membership_plan_content' => [
								'type'       => 'container',
								'optional'   => true,
								'_order'     => 20,

								'attributes' => [
									'class' => [ 'hp-membership-plan__content' ],
								],

								'blocks'     => [
									'membership_plan_description' => [
										'type'   => 'part',
										'path'   => 'membership-plan/view/membership-plan-description',
										'_order' => 10,
									],
								],
							],

							'membership_plan_footer'  => [
								'type'       => 'container',
								'tag'        => 'footer',
								'_order'     => 30,

								'attributes' => [
									'class' => [ 'hp-membership-plan__footer' ],
								],

								'blocks'     => [
									'membership_plan_select_button' => [
										'type'   => 'part',
										'path'   => 'membership-plan/view/membership-plan-select-button',
										'_order' => 10,
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
