<?php
/**
 * Membership component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership component class.
 *
 * @class Membership
 */
final class Membership extends Component {

	/**
	 * Model names.
	 *
	 * @var array
	 */
	protected $models = [ 'user', 'vendor', 'listing', 'request' ];

	/**
	 * Current membership.
	 *
	 * @var object
	 */
	protected $membership;

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Set models.
		$this->models = array_intersect( $this->models, (array) get_option( 'hp_membership_models', [ 'listing' ] ) );

		// Upgrade memberships.
		add_action( 'hivepress/v1/activate', [ $this, 'upgrade_memberships' ], 100 );
		add_action( 'hivepress/v1/update', [ $this, 'upgrade_memberships' ], 100 );

		// Expire memberships.
		add_action( 'hivepress/v1/events/hourly', [ $this, 'expire_memberships' ] );

		// Update membership.
		add_action( 'hivepress/v1/models/membership/update', [ $this, 'update_membership' ], 10, 2 );

		// Update membership status.
		add_action( 'hivepress/v1/models/membership/update_status', [ $this, 'update_membership_status' ], 10, 4 );

		if ( hp\is_plugin_active( 'woocommerce' ) ) {

			// Update order status.
			add_action( 'woocommerce_order_status_changed', [ $this, 'update_order_status' ], 10, 4 );

			// Update subscription status.
			add_action( 'woocommerce_subscription_status_updated', [ $this, 'update_subscription_status' ], 10, 2 );

			// Renew subscription.
			add_action( 'woocommerce_subscription_renewal_payment_complete', [ $this, 'renew_subscription' ] );
		}

		// Add routes.
		add_filter( 'hivepress/v1/routes', [ $this, 'add_routes' ] );

		// Add plan fields.
		add_filter( 'hivepress/v1/models/membership_plan', [ $this, 'add_plan_fields' ] );

		foreach ( $this->models as $model ) {

			// Manage model fields.
			add_filter( 'hivepress/v1/forms/' . $model . '_update', [ $this, 'remove_model_fields' ], 150, 2 );
			add_filter( 'hivepress/v1/models/' . $model . '/fields', [ $this, 'hide_model_fields' ], 150, 2 );

			if ( ! in_array( $model, [ 'user', 'vendor' ] ) ) {

				// Manage model actions.
				add_action( 'hivepress/v1/models/' . $model . '/update_status', [ $this, 'submit_model' ], 1, 4 );
				add_action( 'hivepress/v1/models/' . $model . '/update_expired_time', [ $this, 'renew_model' ], 10, 2 );

				if ( 'listing' === $model ) {
					add_action( 'hivepress/v1/models/' . $model . '/update_featured', [ $this, 'feature_model' ], 10, 2 );
				}
			}
		}

		// Validate models.
		add_filter( 'hivepress/v1/models/message/errors', [ $this, 'validate_message' ], 10, 2 );
		add_filter( 'hivepress/v1/models/offer/errors', [ $this, 'validate_offer' ] );

		// Create models.
		add_action( 'hivepress/v1/models/message/create', [ $this, 'create_message' ], 10, 2 );
		add_action( 'hivepress/v1/models/offer/create', [ $this, 'create_offer' ], 10, 2 );

		// Manage meta boxes.
		add_filter( 'hivepress/v1/meta_boxes', [ $this, 'add_meta_boxes' ], 1 );
		add_filter( 'hivepress/v1/meta_boxes/membership_settings', [ $this, 'alter_meta_box' ] );

		foreach ( array_merge( $this->models, [ 'page' ] ) as $model ) {
			add_filter( 'hivepress/v1/meta_boxes/membership_' . $model . '_restrictions', [ $this, 'alter_meta_box' ] );
		}

		if ( is_admin() ) {

			// Manage admin columns.
			add_filter( 'manage_hp_membership_posts_columns', [ $this, 'add_membership_admin_columns' ] );
			add_action( 'manage_hp_membership_posts_custom_column', [ $this, 'render_membership_admin_columns' ], 10, 2 );

			// Alter settings.
			add_filter( 'hivepress/v1/settings', [ $this, 'alter_settings' ] );
		} else {

			// Set request context.
			add_action( 'init', [ $this, 'set_request_context' ], 200 );

			// Redirect pages.
			add_action( 'template_redirect', [ $this, 'redirect_pages' ] );

			// Alter menus.
			add_filter( 'hivepress/v1/menus/user_account', [ $this, 'alter_user_account_menu' ] );

			// Alter templates.
			add_filter( 'hivepress/v1/templates/membership_plan_view_block/blocks', [ $this, 'alter_membership_plan_view_blocks' ], 10, 2 );

			add_filter( 'hivepress/v1/templates/listing_edit_block/blocks', [ $this, 'alter_listing_edit_blocks' ], 10, 2 );
			add_filter( 'hivepress/v1/templates/listing_edit_page/blocks', [ $this, 'alter_listing_edit_blocks' ], 10, 2 );

			foreach ( $this->models as $model ) {
				add_filter( 'hivepress/v1/templates/' . $model . '_view_block/blocks', [ $this, 'alter_reveal_view_blocks' ], 10, 2 );
				add_filter( 'hivepress/v1/templates/' . $model . '_view_page/blocks', [ $this, 'alter_reveal_view_blocks' ], 10, 2 );
			}
		}

		parent::__construct( $args );
	}

	/**
	 * Gets plan fields.
	 *
	 * @param string $model Model name.
	 * @return array
	 */
	protected function get_plan_fields( $model ) {
		$fields = [];

		// Get display format.
		$display_format = '%label%: %value%';

		if ( count( $this->models ) > 1 ) {
			$display_format .= ' (' . hivepress()->translator->get_string( $model . 's' ) . ')';
		}

		// Add pages field.
		$fields[ $model . '_view_pages' ] = [
			'label'       => esc_html__( 'Pages', 'hivepress-memberships' ),
			'description' => esc_html__( 'Select restricted entry pages that can be viewed with this membership.', 'hivepress-memberships' ),
			'type'        => 'select',
			'_external'   => true,
			'_order'      => 10,

			'options'     => [
				'single' => esc_html__( 'Single Pages', 'hivepress-memberships' ),
				'all'    => esc_html__( 'All Pages', 'hivepress-memberships' ),
			],
		];

		// Get attributes.
		$view_attributes = [];
		$edit_attributes = [];

		foreach ( hivepress()->attribute->get_attributes( $model ) as $attribute_name => $attribute ) {
			$attribute_label = hp\get_array_value( $attribute, 'label' );

			if ( ! $attribute_label ) {
				continue;
			}

			if ( $attribute['display_areas'] ) {
				$view_attributes[ $attribute_name ] = $attribute_label;
			}

			if ( $attribute['editable'] ) {
				$edit_attributes[ $attribute_name ] = $attribute_label;
			}
		}

		// Add attribute fields.
		if ( $view_attributes ) {
			$fields = array_merge(
				$fields,
				[
					$model . '_view_attributes' => [
						'label'       => hivepress()->translator->get_string( 'attributes' ) . ' (' . hivepress()->translator->get_string( 'display_noun' ) . ')',
						'description' => esc_html__( 'Select restricted attributes that can be viewed with this membership.', 'hivepress-memberships' ),
						'type'        => 'select',
						'options'     => $view_attributes,
						'multiple'    => true,
						'_external'   => true,
						'_order'      => 20,
					],

					$model . '_view_limit'      => [
						'label'            => esc_html__( 'View Limit', 'hivepress-memberships' ),
						'description'      => esc_html__( 'Set the number of restricted entries that can be viewed.', 'hivepress-memberships' ),
						'type'             => 'number',
						'min_value'        => 0,
						'display_template' => $display_format,
						'_display_areas'   => [ 'view_block_secondary' ],
						'_external'        => true,
						'_parent'          => $model . '_view_attributes',
						'_order'           => 40,
					],
				]
			);
		}

		if ( $edit_attributes ) {
			$fields[ $model . '_edit_attributes' ] = [
				'label'       => hivepress()->translator->get_string( 'attributes' ) . ' (' . hivepress()->translator->get_string( 'editing' ) . ')',
				'description' => esc_html__( 'Select restricted attributes that can be edited with this membership.', 'hivepress-memberships' ),
				'type'        => 'select',
				'options'     => $edit_attributes,
				'multiple'    => true,
				'_external'   => true,
				'_order'      => 30,
			];
		}

		if ( ! in_array( $model, [ 'user', 'vendor' ] ) ) {

			// Add submission fields.
			$fields = array_merge(
				$fields,
				[
					$model . '_submit_limit'  => [
						'label'            => esc_html__( 'Submission Limit', 'hivepress-memberships' ),
						'description'      => esc_html__( 'Set the number of entries that can be added or renewed.', 'hivepress-memberships' ),
						'type'             => 'number',
						'min_value'        => 0,
						'display_template' => $display_format,
						'_display_areas'   => [ 'view_block_secondary' ],
						'_external'        => true,
						'_order'           => 50,
					],

					$model . '_expire_period' => [
						'label'       => hivepress()->translator->get_string( 'expiration_period' ),
						'description' => esc_html__( 'Set the number of days after which an entry expires.', 'hivepress-memberships' ),
						'type'        => 'number',
						'min_value'   => 1,
						'_external'   => true,
						'_parent'     => $model . '_submit_limit',
						'_order'      => 60,
					],
				]
			);
		} else {

			// Change display format.
			$display_format = hp\get_first_array_value( explode( ' (', $display_format ) );

			if ( hivepress()->get_version( 'messages' ) ) {

				// Add message limit.
				$fields[ $model . '_message_limit' ] = [
					'label'            => esc_html__( 'Message Limit', 'hivepress-memberships' ),
					'description'      => esc_html__( 'Set the number of first messages that can be sent.', 'hivepress-memberships' ),
					'type'             => 'number',
					'min_value'        => 0,
					'display_template' => $display_format,
					'_display_areas'   => [ 'view_block_secondary' ],
					'_external'        => true,
					'_order'           => 50,
				];
			}

			if ( hivepress()->get_version( 'requests' ) && 'vendor' === $model ) {

				// Add offer limit.
				$fields[ $model . '_offer_limit' ] = [
					'label'            => esc_html__( 'Offer Limit', 'hivepress-memberships' ),
					'description'      => esc_html__( 'Set the number of request offers that can be made.', 'hivepress-memberships' ),
					'type'             => 'number',
					'min_value'        => 0,
					'display_template' => $display_format,
					'_display_areas'   => [ 'view_block_secondary' ],
					'_external'        => true,
					'_order'           => 60,
				];
			}
		}

		if ( 'listing' === $model ) {

			// Add featuring fields.
			$fields = array_merge(
				$fields,
				[
					$model . '_feature_limit'  => [
						'label'            => esc_html__( 'Featuring Limit', 'hivepress-memberships' ),
						'description'      => esc_html__( 'Set the number of entries that can be featured.', 'hivepress-memberships' ),
						'type'             => 'number',
						'min_value'        => 0,
						'display_template' => $display_format,
						'_display_areas'   => [ 'view_block_secondary' ],
						'_external'        => true,
						'_order'           => 70,
					],

					$model . '_feature_period' => [
						'label'       => hivepress()->translator->get_string( 'featuring_period' ),
						'description' => esc_html__( 'Set the number of days after which an entry loses featured status.', 'hivepress-memberships' ),
						'type'        => 'number',
						'min_value'   => 1,
						'_external'   => true,
						'_parent'     => $model . '_feature_limit',
						'_order'      => 80,
					],
				]
			);
		}

		return $fields;
	}

	/**
	 * Gets plan arguments.
	 *
	 * @param array $plans Plans or memberships.
	 * @return array
	 */
	protected function get_plan_args( $plans ) {
		$plan_args = [];

		if ( ! $plans ) {
			return $plan_args;
		}

		foreach ( $this->models as $model ) {

			// Get pages.
			$plan_args[ $model . '_view_pages' ] = null;

			foreach ( $plans as $plan ) {
				$pages = call_user_func( [ $plan, 'get_' . $model . '_view_pages' ] );

				if ( $pages && 'all' !== $plan_args[ $model . '_view_pages' ] ) {
					$plan_args[ $model . '_view_pages' ] = $pages;
				}
			}

			// Get attributes.
			foreach ( [ 'edit', 'view' ] as $attribute_type ) {
				$field_name = $model . '_' . $attribute_type . '_attributes';

				$plan_args[ $field_name ] = [];

				foreach ( $plans as $plan ) {
					$attributes = call_user_func( [ $plan, 'get_' . $field_name ] );

					if ( $attributes ) {
						$plan_args[ $field_name ] = array_merge( $plan_args[ $field_name ], $attributes );
					}
				}

				$plan_args[ $field_name ] = array_unique( $plan_args[ $field_name ] );
			}

			// Get limits.
			$limit_types = [ 'view', 'submit', 'feature' ];

			if ( hivepress()->get_version( 'messages' ) ) {
				$limit_types[] = 'message';
			}

			if ( hivepress()->get_version( 'requests' ) ) {
				$limit_types[] = 'offer';
			}

			foreach ( $limit_types as $limit_type ) {
				$field_name = $model . '_' . $limit_type . '_limit';

				$plan_args[ $field_name ] = null;

				foreach ( $plans as $plan ) {
					$limit = call_user_func( [ $plan, 'get_' . $field_name ] );

					if ( ! is_null( $limit ) ) {
						if ( $plan::_get_meta( 'name' ) === 'membership' ) {
							$plan_args[ $field_name ] += $limit;
						} elseif ( is_null( $plan_args[ $field_name ] ) || $limit < $plan_args[ $field_name ] ) {
							$plan_args[ $field_name ] = $limit;
						}
					}
				}
			}
		}

		// Get pages.
		$plan_args['view_pages'] = [];

		foreach ( $plans as $plan ) {
			if ( $plan->get_view_pages__id() ) {
				$plan_args['view_pages'] = array_merge( $plan_args['view_pages'], $plan->get_view_pages__id() );
			}
		}

		$plan_args['view_pages'] = array_unique( $plan_args['view_pages'] );

		return $plan_args;
	}

	/**
	 * Gets plan product IDs.
	 *
	 * @return array
	 */
	protected function get_plan_product_ids() {
		return array_filter(
			array_map(
				function ( $plan ) {
					return $plan->get_product__id();
				},
				Models\Membership_Plan::query()->filter(
					[
						'status' => 'publish',
					]
				)->get()
				->serialize()
			)
		);
	}

	/**
	 * Adds membership for user.
	 *
	 * @param int    $user_id User ID.
	 * @param object $plan Plan object.
	 * @return mixed
	 */
	public function add_membership( $user_id, $plan ) {

		// Get membership.
		$membership = Models\Membership::query()->filter(
			[
				'status__in' => [ 'draft', 'pending', 'publish' ],
				'user'       => $user_id,
				'plan'       => $plan->get_id(),
			]
		)->get_first();

		if ( ! $membership ) {
			$membership = ( new Models\Membership() )->fill(
				[
					'user' => $user_id,
					'plan' => $plan->get_id(),
				]
			);
		} elseif ( ! $plan->get_product__id() && ( $membership->get_status() !== 'draft' || ! get_option( 'hp_membership_renew_free' ) ) ) {
			return false;
		}

		// Get expiration.
		$expiration = null;

		if ( $plan->get_expire_period() ) {
			$expiration = time() + $plan->get_expire_period() * DAY_IN_SECONDS;
		}

		// Add membership.
		$membership->fill(
			array_merge(
				$plan->serialize(),
				[
					'status'       => 'publish',
					'expired_time' => $expiration,
				]
			)
		);

		if ( ! $membership->save() ) {
			return false;
		}

		return $membership;
	}

	/**
	 * Reduces membership limit.
	 *
	 * @param int    $user_id User ID.
	 * @param string $limit Limit name.
	 * @return mixed
	 */
	public function reduce_membership_limit( $user_id, $limit ) {

		// Get membership.
		$this->membership = Models\Membership::query()->filter(
			[
				'status'        => 'publish',
				'user'          => $user_id,
				$limit . '__gt' => 0,
			]
		)->order( [ $limit => 'desc' ] )
		->get_first();

		if ( ! $this->membership ) {
			return null;
		}

		// Get value.
		$value = call_user_func( [ $this->membership, 'get_' . $limit ] );

		if ( ! $value ) {
			return false;
		}

		// Reduce value.
		--$value;

		// Update membership.
		if ( ! $this->membership->fill( [ $limit => $value ] )->save( [ $limit ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets reveal IDs.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_reveal_ids( $user_id ) {
		$reveal_ids = (array) get_user_meta( $user_id, 'hp_membership_reveal_ids', true );

		foreach ( $this->models as $model ) {
			$reveal_ids[ $model ] = hp\get_array_value( $reveal_ids, $model, [] );
		}

		return $reveal_ids;
	}

	/**
	 * Sets reveal IDs.
	 *
	 * @param int   $user_id User ID.
	 * @param array $reveal_ids Reveal IDs.
	 */
	public function set_reveal_ids( $user_id, $reveal_ids ) {
		update_user_meta( $user_id, 'hp_membership_reveal_ids', $reveal_ids );
	}

	/**
	 * Upgrades memberships.
	 */
	public function upgrade_memberships() {
		$models = [];

		// Get restrictions.
		$restrictions = [];

		foreach ( [ 'listing', 'vendor', 'request', 'message', 'offer' ] as $model ) {
			$restriction = get_option( 'hp_membership_' . $model . '_restriction' );

			if ( ! $restriction ) {
				continue;
			}

			if ( in_array( $model, [ 'message', 'offer' ] ) ) {
				$restrictions[ $model ] = true;

				if ( ! in_array( 'vendor', $models ) ) {
					$models[] = 'vendor';
				}
			} else {
				$restrictions[ $model ] = hp\get_first_array_value( explode( '_', $restriction ) );

				$models[] = $model;
			}

			delete_option( 'hp_membership_' . $model . '_restriction' );
		}

		if ( ! $restrictions ) {
			return;
		}

		// Set models.
		update_option( 'hp_membership_models', $models );

		// Get memberships.
		$memberships = array_merge(
			Models\Membership_Plan::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
				]
			)->get()
			->serialize(),
			Models\Membership::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
				]
			)->get()
			->serialize()
		);

		// Upgrade memberships.
		foreach ( $memberships as $membership ) {
			$pages = get_post_meta( $membership->get_id(), 'hp_pages', true );

			if ( $pages ) {
				$membership->set_view_pages( $pages )->save_view_pages();
			}

			foreach ( $restrictions as $model => $restriction ) {
				if ( in_array( $model, [ 'offer', 'message' ] ) ) {
					$limit = get_post_meta( $membership->get_id(), 'hp_' . $model, true );

					if ( $limit ) {
						update_post_meta( $membership->get_id(), 'hp_vendor_' . $model . '_limit', 1000000 );
					}
				} else {
					$attributes = get_post_meta( $membership->get_id(), 'hp_' . $model . '_attributes', true );

					if ( $attributes ) {
						$attributes = array_filter(
							array_map(
								function ( $attribute_id ) {
									return hivepress()->attribute->get_attribute_name( get_post_field( 'post_name', $attribute_id ) );
								},
								(array) $attributes
							)
						);

						if ( $attributes ) {
							update_post_meta( $membership->get_id(), 'hp_' . $model . '_view_attributes', $attributes );
						}
					}

					if ( 'attributes' !== $restriction ) {
						update_post_meta( $membership->get_id(), 'hp_' . $model . '_view_pages', $restriction );
					}

					if ( get_option( 'hp_membership_limit_views' ) ) {
						$limit = get_post_meta( $membership->get_id(), 'hp_view_limit', true );

						if ( $limit ) {
							update_post_meta( $membership->get_id(), 'hp_' . $model . '_view_limit', $limit );
						}
					}
				}
			}
		}

		delete_option( 'hp_membership_limit_views' );
	}

	/**
	 * Expires memberships.
	 */
	public function expire_memberships() {

		// Get memberships.
		$memberships = Models\Membership::query()->filter(
			[
				'status'            => 'publish',
				'expired_time__lte' => time(),
			]
		)->limit( 10 )
		->get();

		foreach ( $memberships as $membership ) {

			// Get user.
			$user = $membership->get_user();

			if ( $user ) {

				// Send email.
				( new Emails\Membership_Expire(
					[
						'recipient' => $user->get_email(),

						'tokens'    => [
							'user'                 => $user,
							'membership'           => $membership,
							'user_name'            => $user->get_display_name(),
							'membership_plan'      => $membership->get_name(),
							'membership_plans_url' => hivepress()->router->get_url( 'membership_plans_view_page' ),
						],
					]
				) )->send();
			}

			if ( ! $membership->get_plan__product() ) {

				// Update membership.
				$membership->set_status( 'draft' )->save_status();
			} else {

				// Delete membership.
				$membership->trash();
			}
		}
	}

	/**
	 * Updates membership.
	 *
	 * @param int    $membership_id Membership ID.
	 * @param object $membership Membership object.
	 */
	public function update_membership( $membership_id, $membership ) {

		// Check membership.
		if ( $membership->validate() ) {
			return;
		}

		// Get plan.
		$plan = $membership->get_plan();

		if ( ! $plan ) {
			return;
		}

		// Get expiration.
		$expiration = $membership->get_expired_time();

		if ( ! $expiration && $plan->get_expire_period() ) {
			$expiration = time() + $plan->get_expire_period() * DAY_IN_SECONDS;
		}

		// Remove action.
		remove_action( 'hivepress/v1/models/membership/update', [ $this, 'update_membership' ] );

		// Update membership.
		$membership->fill(
			array_merge(
				$plan->serialize(),
				[
					'status'       => $membership->get_status(),
					'expired_time' => $expiration,
				]
			)
		)->save();
	}

	/**
	 * Updates membership status.
	 *
	 * @param int    $membership_id Membership ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $membership Membership object.
	 */
	public function update_membership_status( $membership_id, $new_status, $old_status, $membership ) {

		// Check status.
		if ( 'publish' !== $new_status ) {
			return;
		}

		// Send email.
		$user = $membership->get_user();

		if ( $user ) {
			( new Emails\Membership_Activate(
				[
					'recipient' => $user->get_email(),

					'tokens'    => [
						'user'            => $user,
						'membership'      => $membership,
						'user_name'       => $user->get_display_name(),
						'membership_plan' => $membership->get_name(),
						'memberships_url' => hivepress()->router->get_url( 'memberships_view_page' ),
					],
				]
			) )->send();
		}
	}

	/**
	 * Updates order status.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order Order object.
	 */
	public function update_order_status( $order_id, $old_status, $new_status, $order ) {

		// Check user.
		if ( ! $order->get_user_id() ) {
			return;
		}

		// Get product IDs.
		$product_ids = array_intersect( $this->get_plan_product_ids(), hivepress()->woocommerce->get_order_product_ids( $order ) );

		if ( ! $product_ids ) {
			return;
		}

		// Get products.
		$products = wc_get_products( [ 'include' => $product_ids ] );

		foreach ( $products as $product ) {
			if ( $product->get_type() === 'subscription' ) {
				unset( $product_ids[ array_search( $product->get_id(), $product_ids ) ] );
			}
		}

		if ( ! $product_ids ) {
			return;
		}

		// Get plans.
		$plans = Models\Membership_Plan::query()->filter(
			[
				'status'      => 'publish',
				'product__in' => $product_ids,
			]
		)->get();

		if ( ! $plans->count() ) {
			return;
		}

		if ( in_array( $new_status, [ 'processing', 'completed' ] ) && 'processing' !== $old_status ) {
			foreach ( $plans as $plan ) {

				// Add membership.
				$this->add_membership( $order->get_user_id(), $plan );
			}
		} elseif ( in_array( $new_status, [ 'failed', 'cancelled', 'refunded' ] ) ) {

			// Delete memberships.
			Models\Membership::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
					'user'       => $order->get_user_id(),
					'plan__in'   => $plans->get_ids(),
				]
			)->trash();
		}
	}

	/**
	 * Updates subscription status.
	 *
	 * @param object $subscription Subscription object.
	 * @param string $new_status New status.
	 */
	public function update_subscription_status( $subscription, $new_status ) {

		// Get order.
		$order = $subscription->get_parent();

		if ( ! $order || ! $order->get_user_id() ) {
			return;
		}

		// Get product IDs.
		$product_ids = array_intersect( $this->get_plan_product_ids(), hivepress()->woocommerce->get_order_product_ids( $order ) );

		if ( ! $product_ids ) {
			return;
		}

		// Get products.
		$products = wc_get_products( [ 'include' => $product_ids ] );

		foreach ( $products as $product ) {
			if ( $product->get_type() !== 'subscription' ) {
				unset( $product_ids[ array_search( $product->get_id(), $product_ids ) ] );
			}
		}

		if ( ! $product_ids ) {
			return;
		}

		// Get plan.
		$plan = Models\Membership_Plan::query()->filter(
			[
				'status'      => 'publish',
				'product__in' => $product_ids,
			]
		)->get_first();

		if ( ! $plan ) {
			return;
		}

		// Get membership.
		$membership = Models\Membership::query()->filter(
			[
				'status__in' => [ 'draft', 'pending', 'publish' ],
				'user'       => $order->get_user_id(),
				'plan'       => $plan->get_id(),
			]
		)->get_first();

		if ( 'active' === $new_status ) {
			if ( $membership ) {

				// Update membership.
				$membership->set_status( 'publish' )->save_status();
			} else {

				// Add membership.
				$membership = $this->add_membership( $order->get_user_id(), $plan );

				if ( $membership ) {

					// Update membership.
					$membership->set_expired_time( null )->save_expired_time();

					// Set membership ID.
					$subscription->update_meta_data( 'hp_membership', $membership->get_id() );
					$subscription->save_meta_data();
				}
			}
		} elseif ( in_array( $new_status, [ 'cancelled', 'expired' ] ) && $membership ) {

			// Delete membership.
			$membership->trash();
		}
	}

	/**
	 * Renews subscription.
	 *
	 * @param object $subscription Subscription object.
	 */
	public function renew_subscription( $subscription ) {

		// Get membership ID.
		$membership_id = absint( $subscription->get_meta( 'hp_membership' ) );

		if ( ! $membership_id ) {
			return;
		}

		// Get membership.
		$membership = Models\Membership::query()->get_by_id( $membership_id );

		if ( ! $membership ) {
			return;
		}

		// Get plan.
		$plan = $membership->get_plan();

		if ( ! $plan ) {
			return;
		}

		// Update membership.
		$membership->fill(
			array_merge(
				$plan->serialize(),
				[
					'status' => $membership->get_status(),
				]
			)
		)->save();

		// Send email.
		$user = $membership->get_user();

		if ( $user ) {
			( new Emails\Membership_Renew(
				[
					'recipient' => $user->get_email(),

					'tokens'    => [
						'user'            => $user,
						'membership'      => $membership,
						'user_name'       => $user->get_display_name(),
						'membership_plan' => $membership->get_name(),
						'memberships_url' => hivepress()->router->get_url( 'memberships_view_page' ),
					],
				]
			) )->send();
		}
	}

	/**
	 * Adds URL routes.
	 *
	 * @param array $routes URL routes.
	 * @return array
	 */
	public function add_routes( $routes ) {

		// Get actions.
		$actions = [ 'submit', 'renew' ];

		if ( hivepress()->get_version( 'paid_listings' ) && hp\is_plugin_active( 'woocommerce' ) && get_option( 'hp_product_listing_feature' ) ) {

			// @todo remove when Paid Listings extension is deprecated.
			$controller = hp\get_array_value( hivepress()->get_controllers(), 'listing_package' );

			if ( $controller ) {
				$routes['listing_feature_page']['redirect']          = [ $controller, 'redirect_listing_feature_page' ];
				$routes['listing_feature_complete_page']['redirect'] = [ $controller, 'redirect_listing_feature_complete_page' ];
				$routes['listing_feature_complete_page']['action']   = [ $controller, 'render_listing_feature_complete_page' ];
			}
		} else {
			$actions[] = 'feature';
		}

		// Add redirects.
		foreach ( $this->models as $model ) {
			foreach ( [ $model . '_view_page', $model . 's_view_page' ] as $route ) {
				if ( isset( $routes[ $route ] ) ) {
					$routes[ $route ]['redirect']['membership'] = [
						'callback' => [ $this, 'redirect_model_view_page' ],
						'_order'   => 100,
					];
				}
			}

			if ( ! in_array( $model, [ 'user', 'vendor' ] ) ) {
				foreach ( $actions as $action ) {
					$route = $model . '_' . $action . '_page';

					if ( isset( $routes[ $route ] ) ) {
						$routes[ $route ]['redirect']['membership'] = [
							'callback' => [ $this, 'redirect_model_edit_page' ],
							'_order'   => 1,
						];
					}
				}
			}
		}

		return $routes;
	}

	/**
	 * Redirects model view page.
	 *
	 * @return mixed
	 */
	public function redirect_model_view_page() {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan ) {
			return;
		}

		// Get model name.
		$model = rtrim( hp\get_first_array_value( explode( '_', hivepress()->router->get_current_route_name() ) ), 's' );

		if ( ! $model ) {
			return;
		}

		// Get limit name.
		$limit = $model . '_view_pages';

		// Get pages.
		$pages = call_user_func( [ $plan, 'get_' . $limit ] );

		if ( ! $pages ) {
			return;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( $membership && in_array( call_user_func( [ $membership, 'get_' . $limit ] ), [ $pages, 'all' ] ) ) {
			return;
		}

		if ( 'single' === $pages ) {

			// Check page.
			if ( strpos( hivepress()->router->get_current_route_name(), $model . 's' ) === 0 ) {
				return;
			}

			// Get model object.
			$object = hivepress()->request->get_context( $model );

			if ( $object && ( ( 'user' !== $model && get_current_user_id() === $object->get_user__id() ) || ( 'user' === $model && get_current_user_id() === $object->get_id() ) ) ) {
				return;
			}
		}

		return hivepress()->router->get_return_url( 'membership_plans_view_page', [ 'restriction' => $limit ] );
	}

	/**
	 * Redirects model edit page.
	 *
	 * @return mixed
	 */
	public function redirect_model_edit_page() {

		// Get model and action.
		$model  = hp\get_first_array_value( explode( '_', hivepress()->router->get_current_route_name() ) );
		$action = hp\get_array_value( explode( '_', hivepress()->router->get_current_route_name() ), 1 );

		if ( 'renew' === $action ) {
			$action = 'submit';
		}

		// Get redirect.
		$redirect = true;

		if ( ! is_user_logged_in() || 'feature' === $action ) {
			$redirect = null;
		}

		if ( ! $model || ! $action ) {
			return $redirect;
		}

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $redirect;
		}

		// Get limit name.
		$limit = $model . '_' . $action . '_limit';

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! call_user_func( [ $plan, 'get_' . $limit ] ) ) {
			return $redirect;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( $membership && call_user_func( [ $membership, 'get_' . $limit ] ) ) {
			return $redirect;
		}

		return hivepress()->router->get_return_url( 'membership_plans_view_page', [ 'restriction' => $limit ] );
	}

	/**
	 * Adds plan fields.
	 *
	 * @param array $model_args Model arguments.
	 * @return array
	 */
	public function add_plan_fields( $model_args ) {
		foreach ( $this->models as $model ) {
			$model_args['fields'] = array_merge(
				$model_args['fields'],
				$this->get_plan_fields( $model )
			);
		}

		return $model_args;
	}

	/**
	 * Removes model fields.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function remove_model_fields( $form_args, $form ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $form_args;
		}

		// Get model name.
		$model = $form::get_meta( 'model' );

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan ) {
			return $form_args;
		}

		// Get attributes.
		$attributes = call_user_func( [ $plan, 'get_' . $model . '_edit_attributes' ] );

		if ( ! $attributes ) {
			return $form_args;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		foreach ( $attributes as $attribute ) {
			if ( ! $membership || ! in_array( $attribute, (array) call_user_func( [ $membership, 'get_' . $model . '_edit_attributes' ] ) ) ) {

				// Remove field.
				unset( $form_args['fields'][ $attribute ] );
			}
		}

		return $form_args;
	}

	/**
	 * Hides model fields.
	 *
	 * @param array  $fields Model fields.
	 * @param object $model Model object.
	 * @return array
	 */
	public function hide_model_fields( $fields, $model ) {

		// Get model name.
		$model_name = $model::_get_meta( 'name' );

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan ) {
			return $fields;
		}

		// Get attributes.
		$attributes = call_user_func( [ $plan, 'get_' . $model_name . '_view_attributes' ] );

		if ( ! $attributes ) {
			return $fields;
		}

		// Get visibility.
		$revealed = true;

		if ( call_user_func( [ $plan, 'get_' . $model_name . '_view_limit' ] ) ) {
			$revealed = in_array( $model->get_id(), (array) hp\get_array_value( hivepress()->request->get_context( 'membership_reveal_ids' ), $model_name ) );
		}

		// Check permissions.
		if ( is_user_logged_in() && ( current_user_can( 'edit_others_posts' ) || get_current_user_id() === (int) get_post_field( 'post_author', $model->get_id() ) ) ) {
			return $fields;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		// Get link URL.
		$url = hivepress()->router->get_return_url( 'membership_plans_view_page', [ 'restriction' => $model_name . '_view_attributes' ] );

		if ( $membership && call_user_func( [ $membership, 'get_' . $model_name . '_view_limit' ] ) ) {
			$url = '#membership_reveal_modal_' . $model->get_id();
		}

		foreach ( $attributes as $attribute ) {

			// Get field.
			$field = hp\get_array_value( $fields, $attribute );

			if ( ! $field ) {
				continue;
			}

			// Hide field.
			if ( ! $membership || ! in_array( $attribute, (array) call_user_func( [ $membership, 'get_' . $model_name . '_view_attributes' ] ) ) || ! $revealed ) {

				/* translators: %s: attribute label. */
				$fields[ $attribute ]['display_template'] = '<a href="' . esc_url( $url ) . '" class="hp-link"><i class="hp-icon fas fa-eye"></i><span>' . sprintf( esc_html__( 'Reveal %s', 'hivepress-memberships' ), hp\get_array_value( $field, 'label' ) ) . '</span></a>';
			} elseif ( 'attachment_upload' === $field['type'] ) {

				// @todo Replace temporary fix.
				$attachment_id = absint( get_post_meta( $model->get_id(), hp\prefix( $attribute ), true ) );

				if ( $attachment_id ) {
					$fields[ $attribute ]['display_template'] = str_replace(
						'%value%',
						hivepress()->router->get_url( 'attachment_download_page', [ 'attachment_id' => $attachment_id ] ),
						$fields[ $attribute ]['display_template']
					);
				}
			}
		}

		return $fields;
	}

	/**
	 * Submits model object.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $object Model object.
	 */
	public function submit_model( $object_id, $new_status, $old_status, $object ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Check status.
		if ( 'auto-draft' !== $old_status || ! in_array( $new_status, [ 'pending', 'publish' ] ) ) {
			return;
		}

		// Update membership.
		$this->reduce_membership_limit( $object->get_user__id(), $object::_get_meta( 'name' ) . '_submit_limit' );

		// Set expiration period.
		add_filter( 'option_hp_' . $object::_get_meta( 'name' ) . '_expiration_period', [ $this, 'expire_model' ] );
	}

	/**
	 * Renews model object.
	 *
	 * @param int   $object_id Object ID.
	 * @param mixed $expired_time Expiration time.
	 */
	public function renew_model( $object_id, $expired_time ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get model and object.
		$model  = hp\get_array_value( explode( '/', current_action() ), 3 );
		$object = hivepress()->model->get_model_object( $model, $object_id );

		if ( ! $object || 'draft' !== $object->get_status() || $expired_time ) {
			return;
		}

		// Update membership.
		$this->reduce_membership_limit( $object->get_user__id(), $object::_get_meta( 'name' ) . '_submit_limit' );

		// Set expiration period.
		add_filter( 'option_hp_' . $object::_get_meta( 'name' ) . '_expiration_period', [ $this, 'expire_model' ] );
	}

	/**
	 * Features model object.
	 *
	 * @param int   $object_id Object ID.
	 * @param mixed $feature_status Feature status.
	 */
	public function feature_model( $object_id, $feature_status ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get model and object.
		$model  = hp\get_array_value( explode( '/', current_action() ), 3 );
		$object = hivepress()->model->get_model_object( $model, $object_id );

		if ( ! $object || ! $feature_status || $object->get_featured_time() ) {
			return;
		}

		// Update membership.
		$this->reduce_membership_limit( $object->get_user__id(), $object::_get_meta( 'name' ) . '_feature_limit' );

		// Check membership.
		if ( ! $this->membership ) {
			return;
		}

		// Get featuring period.
		$period = call_user_func( [ $this->membership, 'get_' . $object::_get_meta( 'name' ) . '_feature_period' ] );

		if ( $period ) {
			$object->set_featured_time( time() + $period * DAY_IN_SECONDS )->save_featured_time();
		}
	}

	/**
	 * Sets expiration period.
	 *
	 * @param mixed $default_period Default period.
	 * @return mixed
	 */
	public function expire_model( $default_period ) {

		// Check membership.
		if ( ! $this->membership ) {
			return $default_period;
		}

		// Get model name.
		$model = hp\get_array_value( explode( '_', current_filter() ), 2 );

		if ( ! $model ) {
			return $default_period;
		}

		// Get expiration period.
		$period = call_user_func( [ $this->membership, 'get_' . $model . '_expire_period' ] );

		if ( ! $period ) {
			return $default_period;
		}

		return $period;
	}

	/**
	 * Validates message.
	 *
	 * @param array  $errors Error messages.
	 * @param object $message Message object.
	 * @return array
	 */
	public function validate_message( $errors, $message ) {

		// Check errors.
		if ( $errors ) {
			return $errors;
		}

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $errors;
		}

		// Get model.
		$model = hivepress()->request->get_context( 'vendor_id' ) ? 'vendor' : 'user';

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! call_user_func( [ $plan, 'get_' . $model . '_message_limit' ] ) ) {
			return $errors;
		}

		// Check message.
		$message_id = Models\Message::query()->filter(
			[
				'sender'    => $message->get_sender__id(),
				'recipient' => $message->get_recipient__id(),
			]
		)->get_first_id();

		if ( $message_id ) {
			return $errors;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( ! $membership ) {
			$errors[] = esc_html__( 'An active membership is required for this action.', 'hivepress-memberships' );
		} elseif ( ! call_user_func( [ $membership, 'get_' . $model . '_message_limit' ] ) ) {
			$errors[] = esc_html__( 'The message limit is exceeded.', 'hivepress-memberships' );
		}

		return $errors;
	}

	/**
	 * Validates offer.
	 *
	 * @param array $errors Error messages.
	 * @return array
	 */
	public function validate_offer( $errors ) {

		// Check errors.
		if ( $errors ) {
			return $errors;
		}

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $errors;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! $plan->get_vendor_offer_limit() ) {
			return $errors;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( ! $membership ) {
			$errors[] = esc_html__( 'An active membership is required for this action.', 'hivepress-memberships' );
		} elseif ( ! $membership->get_vendor_offer_limit() ) {
			$errors[] = esc_html__( 'The offer limit is exceeded.', 'hivepress-memberships' );
		}

		return $errors;
	}

	/**
	 * Creates message.
	 *
	 * @param int    $message_id Message ID.
	 * @param object $message Message object.
	 */
	public function create_message( $message_id, $message ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get model.
		$model = hivepress()->request->get_context( 'vendor_id' ) ? 'vendor' : 'user';

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! call_user_func( [ $plan, 'get_' . $model . '_message_limit' ] ) ) {
			return;
		}

		// Check message.
		$first_message_id = Models\Message::query()->filter(
			[
				'sender'     => $message->get_sender__id(),
				'recipient'  => $message->get_recipient__id(),
				'id__not_in' => $message_id,
			]
		)->get_first_id();

		if ( $first_message_id ) {
			return;
		}

		// Update membership.
		$this->reduce_membership_limit( $message->get_sender__id(), $model . '_message_limit' );
	}

	/**
	 * Creates offer.
	 *
	 * @param int    $offer_id Offer ID.
	 * @param object $offer Offer object.
	 */
	public function create_offer( $offer_id, $offer ) {

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! $plan->get_vendor_offer_limit() ) {
			return;
		}

		// Update membership.
		$this->reduce_membership_limit( $offer->get_bidder__id(), 'vendor_offer_limit' );
	}

	/**
	 * Adds meta boxes.
	 *
	 * @param array $meta_boxes Meta box arguments.
	 * @return array
	 */
	public function add_meta_boxes( $meta_boxes ) {

		// Set screens.
		$screens = [ 'membership_plan', 'membership' ];

		foreach ( $screens as $screen ) {

			// Add meta box.
			$meta_boxes[ $screen . '_page_restrictions' ] = [
				'title'  => esc_html__( 'Restrictions', 'hivepress-memberships' ) . ' (' . esc_html__( 'General', 'hivepress-memberships' ) . ')',
				'screen' => $screen,

				'fields' => [
					'view_pages' => [
						'label'       => esc_html__( 'Pages', 'hivepress-memberships' ),
						'description' => esc_html__( 'Select restricted pages that can be viewed with this membership.', 'hivepress-memberships' ),
						'type'        => 'select',
						'options'     => 'posts',
						'option_args' => [ 'post_type' => 'page' ],
						'multiple'    => true,
						'_order'      => 10,
					],
				],
			];
		}

		foreach ( $this->models as $model ) {

			// Get plan fields.
			$fields = $this->get_plan_fields( $model );

			foreach ( $screens as $screen ) {

				// Add meta box.
				$meta_boxes[ $screen . '_' . $model . '_restrictions' ] = [
					'title'  => esc_html__( 'Restrictions', 'hivepress-memberships' ) . ' (' . hivepress()->translator->get_string( $model . 's' ) . ')',
					'screen' => $screen,
					'fields' => $fields,
				];
			}
		}

		return $meta_boxes;
	}

	/**
	 * Alters meta box.
	 *
	 * @param array $meta_box Meta box arguments.
	 * @return array
	 */
	public function alter_meta_box( $meta_box ) {

		// Get meta box name.
		$meta_box_name = hp\get_last_array_value( explode( '/', current_filter() ) );

		// Get plan ID.
		$plan_id = absint( get_post_field( 'post_parent' ) );

		if ( ! $plan_id ) {
			if ( 'membership_settings' === $meta_box_name ) {
				$meta_box['fields']['expired_time']['disabled'] = true;
			} else {
				$meta_box['fields'] = [];
			}
		} elseif ( 'membership_settings' === $meta_box_name ) {
			$meta_box['fields']['user']['disabled'] = true;
			$meta_box['fields']['plan']['disabled'] = true;
		}

		return $meta_box;
	}

	/**
	 * Adds membership admin columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_membership_admin_columns( $columns ) {
		return array_merge(
			array_slice( $columns, 0, 2, true ),
			[
				'user'         => hivepress()->translator->get_string( 'user' ),
				'expired_time' => hivepress()->translator->get_string( 'expiration_date' ),
			],
			array_slice( $columns, 2, null, true )
		);
	}

	/**
	 * Renders membership admin columns.
	 *
	 * @param string $column Column name.
	 * @param int    $membership_id Membership ID.
	 */
	public function render_membership_admin_columns( $column, $membership_id ) {
		$output = '';

		if ( 'user' === $column ) {

			// Get user ID.
			$user_id = get_post_field( 'post_author', $membership_id );

			if ( $user_id ) {

				// Get user name.
				$name = get_the_author_meta( 'user_login', $user_id );

				// Get user URL.
				$url = admin_url(
					'user-edit.php?' . http_build_query(
						[
							'user_id' => $user_id,
						]
					)
				);

				// Render user link.
				$output = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
			}
		} elseif ( 'expired_time' === $column ) {
			$output = '&mdash;';

			// Get expiration time.
			$expired_time = absint( get_post_meta( $membership_id, 'hp_expired_time', true ) );

			if ( $expired_time ) {

				// Render expiration date.
				$output = date_i18n( get_option( 'date_format' ), $expired_time );
			}
		}

		echo wp_kses_data( $output );
	}

	/**
	 * Alters settings.
	 *
	 * @param array $settings Settings configuration.
	 * @return array
	 */
	public function alter_settings( $settings ) {
		if ( hivepress()->get_version( 'requests' ) ) {
			$settings['memberships']['sections']['restrictions']['fields']['membership_models']['options']['request'] = hivepress()->translator->get_string( 'requests' );
		}

		return $settings;
	}

	/**
	 * Sets request context.
	 */
	public function set_request_context() {

		// Get cached plan.
		$plan_args = hivepress()->cache->get_cache( 'membership_plan', 'models/membership_plan' );

		if ( is_null( $plan_args ) ) {
			$plan_args = $this->get_plan_args(
				Models\Membership_Plan::query()->filter(
					[
						'status' => 'publish',
					]
				)->get()
				->serialize()
			);

			// Cache plan.
			hivepress()->cache->set_cache( 'membership_plan', 'models/membership_plan', $plan_args );
		}

		if ( $plan_args ) {

			// Set request context.
			hivepress()->request->set_context( 'membership_plan', ( new Models\Membership_Plan() )->fill( $plan_args ) );
		}

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get cached membership.
		$membership_args = hivepress()->cache->get_user_cache( get_current_user_id(), 'membership', 'models/membership' );

		if ( is_null( $membership_args ) ) {
			$membership_args = $this->get_plan_args(
				Models\Membership::query()->filter(
					[
						'status' => 'publish',
						'user'   => get_current_user_id(),
					]
				)->get()
				->serialize()
			);

			// Cache membership.
			hivepress()->cache->set_user_cache( get_current_user_id(), 'membership', 'models/membership', $membership_args );
		}

		if ( $membership_args ) {

			// Set request context.
			hivepress()->request->set_context( 'membership', ( new Models\Membership() )->fill( $membership_args ) );
		}

		// Get cached membership count.
		$membership_count = hivepress()->cache->get_user_cache( get_current_user_id(), 'membership_count', 'models/membership' );

		if ( is_null( $membership_count ) ) {

			// Get membership count.
			$membership_count = Models\Membership::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
					'user'       => get_current_user_id(),
				]
			)->get_count();

			// Cache membership count.
			hivepress()->cache->set_user_cache( get_current_user_id(), 'membership_count', 'models/membership', $membership_count );
		}

		// Set request context.
		hivepress()->request->set_context( 'membership_count', $membership_count );

		// Get reveal IDs.
		$reveal_ids = $this->get_reveal_ids( get_current_user_id() );

		// Set request context.
		hivepress()->request->set_context( 'membership_reveal_ids', $reveal_ids );
	}

	/**
	 * Redirects pages.
	 */
	public function redirect_pages() {

		// Check page.
		if ( ! is_page() || current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan ) {
			return;
		}

		// Check page ID.
		if ( ! in_array( get_the_ID(), (array) $plan->get_view_pages__id() ) ) {
			return;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( ! $membership || ! in_array( get_the_ID(), (array) $membership->get_view_pages__id() ) ) {

			// Redirect page.
			wp_safe_redirect( hivepress()->router->get_return_url( 'membership_plans_view_page', [ 'restriction' => 'view_pages' ] ) );

			exit;
		}
	}

	/**
	 * Alters user account menu.
	 *
	 * @param array $menu Menu arguments.
	 * @return array
	 */
	public function alter_user_account_menu( $menu ) {
		if ( hivepress()->request->get_context( 'membership_count' ) ) {
			$menu['items']['memberships_view'] = [
				'route'  => 'memberships_view_page',
				'_order' => 35,
			];
		}

		return $menu;
	}

	/**
	 * Alters membership plan view blocks.
	 *
	 * @param array  $blocks Block arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_membership_plan_view_blocks( $blocks, $template ) {

		// Get plan.
		$plan = $template->get_context( 'membership_plan' );

		if ( $plan && $plan->is_primary() ) {

			// Add class.
			$blocks = hivepress()->template->merge_blocks(
				$blocks,
				[
					'membership_plan_container' => [
						'attributes' => [
							'class' => [ 'hp-membership-plan--primary' ],
						],
					],
				]
			);
		}

		return $blocks;
	}

	/**
	 * Alters listing edit blocks.
	 *
	 * @param array  $blocks Block arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_listing_edit_blocks( $blocks, $template ) {

		// Check layout.
		$is_page = (bool) strpos( current_filter(), 'listing_edit_page' );

		// Get listing.
		$listing = $template->get_context( 'listing' );

		if ( ! $listing ) {
			return $blocks;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan || ! $plan->get_listing_feature_limit() ) {
			return $blocks;
		}

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		// Get featuring URL.
		$feature_url = hivepress()->router->get_url( 'listing_feature_page', [ 'listing_id' => $listing->get_id() ] );

		if ( current_user_can( 'edit_others_posts' ) || ( $membership && $membership->get_listing_feature_limit() ) ) {
			$feature_url = '#listing_feature_modal_' . $listing->get_id();
		}

		return hivepress()->template->merge_blocks(
			$blocks,
			[
				'listing_actions_' . ( $is_page ? 'secondary' : 'primary' ) => [
					'blocks' => [
						'listing_feature_button' => [
							'type'    => 'part',
							'path'    => 'listing/edit/' . ( $is_page ? 'page' : 'block' ) . '/listing-feature-button',
							'_order'  => $is_page ? 10 : 5,

							'context' => [
								'listing_feature_url' => $feature_url,
							],
						],

						'listing_feature_modal'  => [
							'type'   => 'modal',
							'model'  => 'listing',
							'title'  => hivepress()->translator->get_string( 'feature_listing' ),
							'_order' => 5,

							'blocks' => [
								'listing_feature_message' => [
									'type'   => 'part',
									'path'   => 'listing/feature/listing-feature-message',
									'_order' => 10,
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Alters reveal view blocks.
	 *
	 * @param array  $blocks Block arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_reveal_view_blocks( $blocks, $template ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return $blocks;
		}

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $blocks;
		}

		// Get model name.
		$model = $template::get_meta( 'model' );

		if ( ! $model ) {
			return $blocks;
		}

		// Get plan.
		$plan = hivepress()->request->get_context( 'membership_plan' );

		if ( ! $plan ) {
			return $blocks;
		}

		// Check visibility.
		$limit = call_user_func( [ $plan, 'get_' . $model . '_view_limit' ] );

		if ( ! $limit ) {
			return $blocks;
		}

		// Get container.
		$container = hp\get_first_array_value( array_keys( $blocks ) );

		if ( ! $container ) {
			return $blocks;
		}

		return hivepress()->template->merge_blocks(
			$blocks,
			[
				$container => [
					'blocks' => [
						'membership_reveal_modal' => [
							'type'   => 'modal',
							'model'  => $model,
							'title'  => esc_html__( 'Reveal Details', 'hivepress-memberships' ),

							'blocks' => [
								'membership_reveal_form' => [
									'type'   => 'membership_reveal_form',
									'model'  => $model,
									'_order' => 10,
								],
							],
						],
					],
				],
			]
		);
	}
}
