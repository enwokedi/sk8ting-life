<?php
/**
 * Membership plans block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership plans block class.
 *
 * @class Membership_Plans
 */
class Membership_Plans extends Block {

	/**
	 * Columns number.
	 *
	 * @var int
	 */
	protected $columns = 4;

	/**
	 * Plans number.
	 *
	 * @var int
	 */
	protected $number;

	/**
	 * Class initializer.
	 *
	 * @param array $meta Block meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'    => esc_html__( 'Membership Plans', 'hivepress-memberships' ),

				'settings' => [
					'columns' => [
						'label'    => hivepress()->translator->get_string( 'columns_number' ),
						'type'     => 'select',
						'default'  => 4,
						'required' => true,
						'_order'   => 10,

						'options'  => [
							2 => '2',
							3 => '3',
							4 => '4',
						],
					],

					'number'  => [
						'label'     => hivepress()->translator->get_string( 'items_number' ),
						'type'      => 'number',
						'min_value' => 1,
						'default'   => 4,
						'required'  => true,
						'_order'    => 20,
					],
				],
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		// Get restriction.
		$restriction = sanitize_key( hp\get_array_value( $_GET, 'restriction' ) );

		if ( ! in_array( hp\get_last_array_value( explode( '_', $restriction ) ), [ 'pages','attributes', 'limit' ] ) ) {
			$restriction = null;
		}

		// Get plans.
		$plans = Models\Membership_Plan::query()->filter(
			[
				'status' => 'publish',
			]
		)->order( [ 'sort_order' => 'asc' ] );

		if ( in_array( $restriction, array_keys( ( new Models\Membership_Plan() )->_get_fields() ) ) ) {
			$plans->filter( [ $restriction . '__exists' => true ] );
		}

		$plans->get();

		if ( ! $plans->count() ) {
			return $output;
		}

		// Get column width.
		$column_width = hp\get_column_width( $this->columns );

		if ( isset( $this->context['membership_plans'] ) ) {
			$columns = absint( $plans->count() );

			if ( $columns < 4 ) {
				$column_width = round( 12 / $columns );
			}
		}

		// Get memberships.
		$memberships = [];

		if ( is_user_logged_in() ) {
			$memberships = Models\Membership::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
					'user'       => get_current_user_id(),
				]
			)->get()
			->serialize();

			$memberships = array_combine(
				array_map(
					function ( $membership ) {
						return $membership->get_plan__id();
					},
					$memberships
				),
				$memberships
			);
		}

		// Render plans.
		$output  = '<div class="hp-membership-plans hp-grid hp-block">';
		$output .= '<div class="hp-row">';

		foreach ( $plans as $plan ) {

			// Render plan.
			$output .= '<div class="hp-grid__item hp-col-sm-' . esc_attr( $column_width ) . ' hp-col-xs-12">';

			$output .= ( new Template(
				[
					'template' => 'membership_plan_view_block',

					'context'  => [
						'membership_plan' => $plan,
						'membership'      => hp\get_array_value( $memberships, $plan->get_id() ),
					],
				]
			) )->render();

			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}
}
