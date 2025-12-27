<?php
/**
 * Booking component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Emails;
use ICal\ICal;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking component class.
 *
 * @class Booking
 */
final class Booking extends Component {

	/**
	 * Array of attributes.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Add taxonomies.
		add_filter( 'hivepress/v1/taxonomies', [ $this, 'add_taxonomies' ] );

		// Add calendar assets.
		add_filter( 'hivepress/v1/scripts', [ $this, 'add_calendar_scripts' ], 1 );
		add_filter( 'hivepress/v1/styles', [ $this, 'add_calendar_styles' ], 1 );

		// Add attribute models.
		add_filter( 'hivepress/v1/components/attribute/models', [ $this, 'add_attribute_models' ] );

		// Add listing attributes.
		add_action( 'init', [ $this, 'set_listing_attributes' ], 1 );

		add_filter( 'hivepress/v1/models/listing/attributes', [ $this, 'add_listing_attributes' ], 200 );
		add_filter( 'hivepress/v1/models/vendor/attributes', [ $this, 'add_listing_attributes' ], 200 );

		// Add booking attributes.
		add_filter( 'hivepress/v1/models/booking/attributes', [ $this, 'add_booking_attributes' ], 100 );

		// Add listing fields.
		add_filter( 'hivepress/v1/models/listing/fields', [ $this, 'add_listing_fields' ], 200, 2 );
		add_filter( 'hivepress/v1/forms/listing_update', [ $this, 'add_listing_fields' ], 200, 2 );
		add_filter( 'hivepress/v1/meta_boxes/listing_attributes', [ $this, 'add_listing_fields' ], 200 );

		add_filter( 'hivepress/v1/models/vendor/fields', [ $this, 'add_listing_fields' ], 200, 2 );
		add_filter( 'hivepress/v1/forms/vendor_update', [ $this, 'add_listing_fields' ], 200, 2 );
		add_filter( 'hivepress/v1/meta_boxes/vendor_attributes', [ $this, 'add_listing_fields' ], 200 );

		// Create listing.
		add_action( 'hivepress/v1/models/listing/create', [ $this, 'create_listing' ] );
		add_action( 'hivepress/v1/models/listing/update_categories', [ $this, 'create_listing' ] );

		// Update listing status.
		add_action( 'hivepress/v1/models/listing/update_status', [ $this, 'update_listing_status' ], 100, 4 );

		// Update vendor.
		add_action( 'hivepress/v1/models/vendor/update', [ $this, 'update_vendor' ], 10, 2 );

		// Update booking.
		add_action( 'hivepress/v1/models/booking/create', [ $this, 'update_booking' ], 10, 2 );
		add_action( 'hivepress/v1/models/booking/update', [ $this, 'update_booking' ], 10, 2 );

		add_action( 'hivepress/v1/models/booking/update_status', [ $this, 'update_booking_status' ], 10, 4 );

		add_action( 'hivepress/v1/models/booking/update_start_time', [ $this, 'update_booking_start_time' ] );

		add_action( 'hivepress/v1/models/listing/update_booking_days', [ $this, 'update_booking_time_meta' ], 200 );
		add_action( 'hivepress/v1/models/listing/update_booking_min_time', [ $this, 'update_booking_time_meta' ], 200 );
		add_action( 'hivepress/v1/models/listing/update_booking_max_time', [ $this, 'update_booking_time_meta' ], 200 );
		add_action( 'hivepress/v1/models/listing/update_booking_slot_duration', [ $this, 'update_booking_time_meta' ], 200 );
		add_action( 'hivepress/v1/models/listing/update_booking_slot_interval', [ $this, 'update_booking_time_meta' ], 200 );

		// Remind booking.
		add_action( 'hivepress/v1/models/booking/remind', [ $this, 'remind_booking' ] );

		// Expire bookings.
		add_action( 'hivepress/v1/events/hourly', [ $this, 'expire_bookings' ] );

		// Complete bookings.
		add_action( 'hivepress/v1/models/booking/update_end_time', [ $this, 'schedule_booking_completion' ] );
		add_action( 'hivepress/v1/models/booking/complete', [ $this, 'complete_booking' ] );

		// Import bookings.
		add_action( 'hivepress/v1/models/listing/update', [ $this, 'schedule_booking_import' ], 10, 2 );
		add_action( 'hivepress/v1/models/vendor/update', [ $this, 'schedule_booking_import' ], 10, 2 );
		add_action( 'hivepress/v1/activate', [ $this, 'upgrade_booking_import' ], 100 );
		add_action( 'hivepress/v1/models/booking/sync', [ $this, 'import_bookings' ], 10, 2 );

		// Clear cache.
		add_action( 'hivepress/v1/models/booking/delete', [ $this, 'clear_vendor_cache' ] );
		add_action( 'hivepress/v1/models/booking/update', [ $this, 'clear_category_cache' ], 100 );

		// Alter strings.
		add_filter( 'hivepress/v1/strings', [ $this, 'alter_strings' ] );

		// Alter forms.
		add_filter( 'hivepress/v1/forms/booking_make', [ $this, 'alter_booking_make_form' ], 10, 2 );
		add_filter( 'hivepress/v1/forms/booking_confirm', [ $this, 'alter_booking_confirm_form' ], 10, 2 );

		add_filter( 'hivepress/v1/forms/listing_search', [ $this, 'alter_listing_search_form' ], 10, 2 );
		add_filter( 'hivepress/v1/forms/listing_filter', [ $this, 'alter_listing_search_form' ], 10, 2 );
		add_filter( 'hivepress/v1/forms/listing_sort', [ $this, 'alter_listing_search_form' ], 10, 2 );

		if ( hivepress()->get_version( 'marketplace' ) ) {

			// Update cart.
			add_filter( 'hivepress/v1/models/listing/cart', [ $this, 'update_cart' ], 10, 2 );

			// Create order.
			add_action( 'woocommerce_new_order', [ $this, 'create_order' ], 10, 2 );

			// Update order status.
			add_action( 'woocommerce_order_status_changed', [ $this, 'update_order_status' ], 10, 4 );

			// Update subscription status.
			add_action( 'woocommerce_subscription_status_updated', [ $this, 'update_subscription_status' ], 10, 2 );

			// Redirect order page.
			add_action( 'template_redirect', [ $this, 'redirect_order_page' ] );
		}

		if ( is_admin() ) {

			// Alter settings.
			add_filter( 'hivepress/v1/settings', [ $this, 'alter_settings' ] );

			// Hide private bookings.
			add_filter( 'posts_where', [ $this, 'hide_private_bookings' ], 10, 2 );
			add_filter( 'views_edit-hp_booking', [ $this, 'hide_private_bookings_view' ] );

			// Alter meta boxes.
			add_filter( 'hivepress/v1/meta_boxes/booking_settings', [ $this, 'alter_booking_settings_meta_box' ] );

			add_filter( 'hivepress/v1/meta_boxes/listing_calendar', [ $this, 'alter_listing_calendar_meta_box' ] );
			add_filter( 'hivepress/v1/meta_boxes/vendor_calendar', [ $this, 'alter_listing_calendar_meta_box' ] );

			// Manage admin columns.
			add_filter( 'manage_hp_booking_posts_columns', [ $this, 'add_booking_admin_columns' ] );
			add_action( 'manage_hp_booking_posts_custom_column', [ $this, 'render_booking_admin_columns' ], 10, 2 );
		} else {

			// Set request context.
			add_filter( 'hivepress/v1/components/request/context', [ $this, 'set_request_context' ], 100 );

			// Set search query.
			add_action( 'hivepress/v1/models/listing/search', [ $this, 'set_search_query' ] );
			add_filter( 'posts_where', [ $this, 'set_search_clauses' ], 100, 2 );

			// Set booking order.
			add_filter( 'posts_orderby', [ $this, 'set_booking_order' ], 10, 2 );

			// Alter menus.
			add_filter( 'hivepress/v1/menus/listing_manage/items', [ $this, 'alter_listing_manage_menu' ], 10, 2 );
			add_filter( 'hivepress/v1/menus/user_account', [ $this, 'alter_user_account_menu' ] );
		}

		// Alter templates.
		add_filter( 'hivepress/v1/templates/listing_edit_block/blocks', [ $this, 'alter_listing_edit_block' ], 100, 2 );

		add_filter( 'hivepress/v1/templates/listing_view_page', [ $this, 'alter_listing_view_page' ] );
		add_filter( 'hivepress/v1/templates/listing_view_page/blocks', [ $this, 'alter_listing_view_page_blocks' ], 100, 2 );

		parent::__construct( $args );
	}

	/**
	 * Adds taxonomies.
	 *
	 * @param array $taxonomies Taxonomies.
	 * @return array
	 */
	public function add_taxonomies( $taxonomies ) {
		if ( get_option( 'hp_booking_enable_time' ) ) {
			$taxonomies['listing_booking_days'] = [
				'post_type'          => [ 'listing' ],
				'public'             => false,
				'meta_box_cb'        => false,
				'show_in_quick_edit' => false,
				'show_in_menu'       => false,
				'rewrite'            => false,
			];
		}

		return $taxonomies;
	}

	/**
	 * Gets calendar key.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return string
	 */
	public function get_calendar_key( $vendor_id ) {
		return md5( 'vendor_calendar_' . $vendor_id . wp_salt() );
	}

	/**
	 * Gets shifted time.
	 *
	 * @param object $listing Listing object.
	 * @param int    $time Timestamp.
	 * @param string $old_timezone Old timezone.
	 * @return int
	 */
	public function get_shifted_time( $listing, $time, $old_timezone = null ) {

		// Check settings.
		if ( ! $this->is_time_enabled( $listing ) ) {
			return $time;
		}

		// Get timezones.
		$old_timezone = new \DateTimeZone( is_null( $old_timezone ) ? date_default_timezone_get() : $old_timezone );
		$new_timezone = new \DateTimeZone( $listing->get_booking_timezone() ? $listing->get_booking_timezone() : wp_timezone_string() );

		if ( $new_timezone ) {

			// Shift time.
			$time = strtotime( ( new \DateTime( '@' . $time, $old_timezone ) )->setTimezone( $new_timezone )->format( 'Y-m-d H:i:s' ) );
		}

		return $time;
	}

	/**
	 * Get pricing options.
	 *
	 * @return array
	 */
	protected function get_price_options() {
		$options = [];

		if ( get_option( 'hp_booking_enable_quantity' ) ) {
			$options = [
				''             => esc_html_x( 'per place per day', 'pricing', 'hivepress-bookings' ),
				'per_quantity' => esc_html_x( 'per place', 'pricing', 'hivepress-bookings' ),
				'per_item'     => esc_html_x( 'per day', 'pricing', 'hivepress-bookings' ),
			];
		} else {
			$options[''] = esc_html_x( 'per day', 'pricing', 'hivepress-bookings' );
		}

		$options['per_order'] = esc_html_x( 'per booking', 'pricing', 'hivepress-bookings' );

		return $options;
	}

	/**
	 * Gets average price.
	 *
	 * @param int    $start_time Start time.
	 * @param int    $end_time End time.
	 * @param object $listing Listing object.
	 * @param float  $default Default price.
	 * @return float
	 */
	public function get_average_price( $start_time, $end_time, $listing, $default = null ) {

		// Set default.
		if ( is_null( $default ) ) {
			$default = $listing->get_price();
		}

		if ( get_option( 'hp_booking_enable_price' ) ) {

			// Get quantity.
			$quantity = max( 1, round( ( $end_time - $start_time ) / DAY_IN_SECONDS ) );

			// Get ranges.
			$ranges = [];

			if ( $listing->get_booking_ranges() ) {

				// Get prices.
				$prices = [];

				foreach ( $listing->get_booking_ranges() as $range ) {
					$price = hp\get_array_value( $range, 'price', 0 );

					foreach ( hp\get_array_value( $range, 'days', [] ) as $day ) {
						$prices[ $day ] = $price;
					}
				}

				// Get period.
				$period = new \DatePeriod(
					new \DateTime( '@' . $start_time ),
					new \DateInterval( 'P1D' ),
					new \DateTime( '@' . $end_time )
				);

				foreach ( $period as $date ) {

					// Get price.
					$price = hp\get_array_value( $prices, $date->format( 'w' ) );

					if ( is_null( $price ) ) {
						continue;
					}

					// Add range.
					$ranges[] = ( new Models\Booking_Range() )->fill(
						[
							'start_time' => $date->getTimestamp(),
							'end_time'   => $date->getTimestamp() + DAY_IN_SECONDS,
							'price'      => $price,
						]
					);
				}
			}

			// Query ranges.
			$ranges = array_merge( $ranges, $this->get_overlapping_query( $start_time, $end_time, $listing, true )->get()->serialize() );

			if ( $ranges ) {

				// Filter ranges.
				foreach ( $ranges as $next_index => $next_range ) {
					foreach ( $ranges as $prev_index => $prev_range ) {
						if ( $prev_index === $next_index ) {
							break;
						}

						if ( ( $prev_range->get_start_time() >= $next_range->get_start_time() && $prev_range->get_start_time() < $next_range->get_end_time() ) || ( $prev_range->get_end_time() > $next_range->get_start_time() && $prev_range->get_end_time() <= $next_range->get_end_time() ) || ( $prev_range->get_start_time() < $next_range->get_start_time() && $prev_range->get_end_time() > $next_range->get_end_time() ) ) {
							unset( $ranges[ $prev_index ] );
						}
					}
				}

				// Get prices.
				$prices = [];

				foreach ( $ranges as $range ) {
					if ( $range->get_start_time() < $start_time ) {
						$range->set_start_time( $start_time );
					}

					if ( $range->get_end_time() > $end_time ) {
						$range->set_end_time( $end_time );
					}

					// Add price.
					$prices[] = [
						'quantity' => round( ( $range->get_end_time() - $range->get_start_time() ) / DAY_IN_SECONDS ),
						'price'    => $range->get_price(),
					];
				}

				return round(
					( ( $quantity - array_sum( array_column( $prices, 'quantity' ) ) ) * $default + array_sum(
						array_map(
							function( $price ) {
								return $price['quantity'] * $price['price'];
							},
							$prices
						)
					) ) / $quantity,
					4
				);
			}
		}

		return $default;
	}

	/**
	 * Gets cart meta.
	 *
	 * @param object $booking Booking object.
	 * @param object $listing Listing object.
	 * @return array
	 */
	public function get_cart_meta( $booking, $listing ) {
		$meta = [
			'booking' => $booking->get_id(),
		];

		// Set quantity.
		if ( get_option( 'hp_booking_enable_quantity' ) && $booking->get_quantity() ) {
			$meta['quantity'] = $booking->get_quantity();
		}

		// Set tier.
		$meta['price'] = null;

		if ( get_option( 'hp_listing_allow_price_tiers' ) && $booking->get_price_tiers() ) {
			$tier = hp\get_first_array_value( $booking->get_price_tiers() );

			if ( $tier ) {

				/* translators: 1: tier name, 2: tier price. */
				$meta['price_tier'] = esc_html( sprintf( _x( '%1$s (%2$s)', 'pricing tier format', 'hivepress-bookings' ), $tier['name'], hivepress()->woocommerce->format_price( $tier['price'] ) ) );
				$meta['price']      = floatval( $tier['price'] );
			}
		}

		// Set price.
		$meta['price'] = $this->get_average_price( $booking->get_start_time(), $booking->get_end_time(), $listing, $meta['price'] );

		// Add extras.
		if ( get_option( 'hp_listing_allow_price_extras' ) && $booking->get_price_extras() ) {
			$meta['price_extras'] = $booking->get_price_extras();
		}

		if ( $listing->get_booking_deposit() ) {

			// Get vendor.
			$vendor = $listing->get_vendor();

			if ( $vendor ) {

				// Add deposit.
				$meta['fees'][] = [
					'name'   => hivepress()->translator->get_string( 'security_deposit' ),
					'amount' => $listing->get_booking_deposit(),
				];

				// Add commission.
				$meta['commission_fee'] = round( $listing->get_booking_deposit() * hivepress()->marketplace->get_commission_rate( $vendor ), 2 );
			}
		}

		if ( $booking->get_status() === 'pending' ) {

			// @todo add fee label when available in strings.
			$meta['fees']['direct_payment'] = [
				'name'   => '',
				'amount' => 0,
			];
		}

		return $meta;
	}

	/**
	 * Checks if booking is recurring.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	public function is_recurring( $booking ) {
		return $booking->get_end_time() - $booking->get_start_time() > 99 * YEAR_IN_SECONDS;
	}

	/**
	 * Checks if booking is enabled.
	 *
	 * @param object $listing Listing object.
	 * @return bool
	 */
	public function is_booking_enabled( $listing ) {
		$enabled = true;

		// Get category IDs.
		$category_ids = array_filter( (array) get_option( 'hp_booking_categories' ) );

		if ( $category_ids ) {

			// Get child category IDs.
			foreach ( $category_ids as $category_id ) {
				$category_ids = array_merge( $category_ids, get_term_children( $category_id, 'hp_listing_category' ) );
			}

			// Get listing category IDs.
			$listing_category_ids = [];

			if ( is_object( $listing ) ) {
				$listing_category_ids = (array) $listing->get_categories__id();
			} else {
				$listing_category_ids = wp_get_post_terms( $listing, 'hp_listing_category', [ 'fields' => 'ids' ] );
			}

			// Check listing.
			if ( ! array_intersect( $listing_category_ids, $category_ids ) ) {
				$enabled = false;
			}
		}

		return $enabled;
	}

	/**
	 * Check if time slots are enabled.
	 *
	 * @param object $listing Listing object.
	 * @return bool
	 */
	public function is_time_enabled( $listing ) {
		$enabled = false;

		if ( get_option( 'hp_booking_enable_time' ) ) {
			$enabled = true;

			// Get category IDs.
			$category_ids = array_filter( (array) get_option( 'hp_booking_time_categories' ) );

			if ( $category_ids ) {

				// Get child category IDs.
				foreach ( $category_ids as $category_id ) {
					$category_ids = array_merge( $category_ids, get_term_children( $category_id, 'hp_listing_category' ) );
				}

				// Get listing category IDs.
				$listing_category_ids = [];

				if ( is_object( $listing ) ) {
					$listing_category_ids = (array) $listing->get_categories__id();
				} else {
					$listing_category_ids = wp_get_post_terms( $listing, 'hp_listing_category', [ 'fields' => 'ids' ] );
				}

				// Check listing.
				if ( ! array_intersect( $listing_category_ids, $category_ids ) ) {
					$enabled = false;
				}
			}
		}

		return $enabled;
	}

	/**
	 * Gets blocked statuses.
	 *
	 * @return array
	 */
	public function get_blocked_statuses() {
		return array_filter(
			array_merge(
				[ 'publish', 'private' ],
				(array) get_option( 'hp_booking_blocked_statuses' )
			)
		);
	}

	/**
	 * Gets listing IDs.
	 *
	 * @param object $listing Listing object.
	 * @param bool   $common Common flag.
	 * @return array
	 */
	public function get_listing_ids( $listing, $common = false ) {
		$listing_ids = [];

		if ( $common || get_option( 'hp_booking_per_vendor' ) ) {
			$listing_ids = Models\Listing::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
					'vendor'     => $listing->get_vendor__id(),
				]
			)->get_ids();
		} else {
			$listing_ids = [ $listing->get_id() ];
		}

		return $listing_ids;
	}

	/**
	 * Gets overlapping query.
	 *
	 * @param int    $start_time Start time.
	 * @param int    $end_time End time.
	 * @param object $listing Listing object.
	 * @param bool   $range Range flag.
	 * @return object
	 */
	public function get_overlapping_query( $start_time, $end_time, $listing = null, $range = false ) {

		// Create query.
		$query = null;

		if ( $range ) {
			$query = Models\Booking_Range::query();
		} else {
			$query = Models\Booking::query()->filter(
				[
					'status__in' => $this->get_blocked_statuses(),
				]
			);
		}

		// Set listing IDs.
		if ( $listing ) {
			$query->filter( [ 'listing__in' => $this->get_listing_ids( $listing ) ] );
		}

		// Set time query.
		$query->set_args(
			[
				'meta_query' => [
					'relation' => 'OR',

					[
						'key'     => 'hp_start_time',
						'value'   => [ $start_time, $end_time - 1 ],
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
					],

					[
						'key'     => 'hp_end_time',
						'value'   => [ $start_time + 1, $end_time ],
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
					],

					[
						'relation' => 'AND',

						[
							'key'     => 'hp_start_time',
							'value'   => $start_time,
							'type'    => 'NUMERIC',
							'compare' => '<',
						],

						[
							'key'     => 'hp_end_time',
							'value'   => $end_time,
							'type'    => 'NUMERIC',
							'compare' => '>',
						],
					],
				],
			]
		);

		return $query;
	}

	/**
	 * Gets booking times.
	 *
	 * @param array  $args Request arguments.
	 * @param object $listing Listing object.
	 * @return array
	 */
	public function get_booking_times( $args, $listing ) {
		$start_time = null;
		$end_time   = null;

		// Get dates.
		$dates = hp\get_array_value( $args, '_dates' );

		if ( $this->is_time_enabled( $listing ) ) {

			// Get date.
			$date = sanitize_text_field( $dates );

			if ( $date ) {

				// Get time.
				$time  = hp\get_array_value( $args, '_time' );
				$count = 1;

				if ( get_option( 'hp_booking_multiple_time' ) && is_array( $time ) ) {
					$count = count( $time );
					$time  = hp\get_first_array_value( $time );
				}

				$time = absint( $time );

				// Set time range.
				$start_time = strtotime( $date ) + $time;
				$end_time   = $start_time + ( $listing->get_booking_slot_duration() * $count + $listing->get_booking_slot_interval() * ( $count - 1 ) ) * 60;
			}
		} elseif ( hivepress()->get_version( 'marketplace' ) && hivepress()->marketplace->is_recurring( $listing ) ) {

			// Get date.
			$date = sanitize_text_field( $dates );

			if ( $date ) {

				// Get time range.
				$start_time = strtotime( $date );

				if ( $start_time ) {
					$end_time = $start_time + 100 * YEAR_IN_SECONDS;
				}
			}
		} else {

			// Get settings.
			$is_daily = get_option( 'hp_booking_enable_daily' );

			if ( $is_daily && $listing->get_booking_max_length() === 1 ) {

				// Get dates.
				$dates = array_map( 'sanitize_text_field', [ $dates, $dates ] );
			}

			if ( is_array( $dates ) && count( $dates ) === 2 ) {

				// Set time range.
				$start_time = strtotime( hp\get_first_array_value( $dates ) );
				$end_time   = strtotime( hp\get_last_array_value( $dates ) );

				if ( $end_time && $is_daily ) {
					$end_time += DAY_IN_SECONDS - 1;
				}
			}
		}

		return [ $start_time, $end_time ];
	}

	/**
	 * Gets booking quantity.
	 *
	 * @param int    $start_time Start time.
	 * @param int    $end_time End time.
	 * @param object $listing Listing object.
	 * @return int
	 */
	public function get_booking_quantity( $start_time, $end_time, $listing ) {

		// Check listing.
		if ( hivepress()->get_version( 'marketplace' ) && hivepress()->marketplace->is_recurring( $listing ) ) {
			return 1;
		}

		// Calculate quantity.
		$diff_time     = $end_time - $start_time;
		$diff_interval = DAY_IN_SECONDS;

		if ( $this->is_time_enabled( $listing ) ) {
			$diff_time    += $listing->get_booking_slot_interval() * 60;
			$diff_interval = ( $listing->get_booking_slot_duration() + $listing->get_booking_slot_interval() ) * 60;

			if ( ! $diff_interval ) {
				return 1;
			}
		}

		return max( 1, round( $diff_time / $diff_interval ) );
	}

	/**
	 * Adds calendar scripts.
	 *
	 * @param array $scripts Scripts.
	 * @return array
	 */
	public function add_calendar_scripts( $scripts ) {
		if ( is_admin() || in_array( hivepress()->router->get_current_route_name(), [ 'listing_calendar_page', 'vendor_calendar_page' ], true ) ) {

			// Add calendar.
			$scripts['fullcalendar'] = [
				'handle'  => 'fullcalendar',
				'src'     => hivepress()->get_url( 'bookings' ) . '/node_modules/fullcalendar/main.min.js',
				'version' => hivepress()->get_version( 'bookings' ),
				'scope'   => [ 'frontend', 'backend' ],
			];

			// Add locales.
			$scripts['fullcalendar_locales'] = [
				'handle'  => 'fullcalendar-locales',
				'src'     => hivepress()->get_url( 'bookings' ) . '/node_modules/fullcalendar/locales-all.min.js',
				'version' => hivepress()->get_version( 'bookings' ),
				'deps'    => [ 'fullcalendar' ],
				'scope'   => [ 'frontend', 'backend' ],
			];

			// Add dependencies.
			$scripts['bookings']['deps'][] = 'fullcalendar';
		}

		return $scripts;
	}

	/**
	 * Adds calendar styles.
	 *
	 * @param array $styles Styles.
	 * @return array
	 */
	public function add_calendar_styles( $styles ) {
		if ( is_admin() || in_array( hivepress()->router->get_current_route_name(), [ 'listing_calendar_page', 'vendor_calendar_page' ], true ) ) {
			$styles['fullcalendar'] = [
				'handle'  => 'fullcalendar',
				'src'     => hivepress()->get_url( 'bookings' ) . '/node_modules/fullcalendar/main.min.css',
				'version' => hivepress()->get_version( 'bookings' ),
				'scope'   => [ 'frontend', 'backend' ],
			];
		}

		return $styles;
	}

	/**
	 * Adds attribute models.
	 *
	 * @param array $models Model names.
	 * @return array
	 */
	public function add_attribute_models( $models ) {
		$models['booking'] = [ 'category_model' => 'listing' ];

		return $models;
	}

	/**
	 * Adds booking attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return array
	 */
	public function add_booking_attributes( $attributes ) {
		if ( get_option( 'hp_booking_enable_quantity' ) ) {

			// Add quantity.
			$attributes['quantity'] = [
				'editable'       => true,
				'display_format' => sprintf( hivepress()->translator->get_string( 'places_n' ), '%value%' ),

				'display_areas'  => [
					'view_block_primary',
					'view_page_primary',
				],

				'edit_field'     => [
					'label'     => hivepress()->translator->get_string( 'places' ),
					'type'      => 'number',
					'min_value' => 1,
					'default'   => 1,
					'required'  => true,
					'_order'    => 30,
				],
			];
		}

		if ( get_option( 'hp_listing_allow_price_tiers' ) ) {

			// Add price tiers.
			$attributes['price_tiers'] = [
				'edit_field' => [
					'label'  => esc_html__( 'Tiers', 'hivepress-bookings' ),
					'type'   => 'repeater',
					'_order' => 197,

					'fields' => [
						'name'        => [
							'placeholder' => hivepress()->translator->get_string( 'title' ),
							'type'        => 'text',
							'max_length'  => 256,
							'required'    => true,
							'_order'      => 10,
						],

						'price'       => [
							'placeholder' => hivepress()->translator->get_string( 'price' ),
							'type'        => 'currency',
							'min_value'   => 0,
							'required'    => true,
							'_order'      => 20,
						],

						'description' => [
							'placeholder' => hivepress()->translator->get_string( 'description' ),
							'type'        => 'text',
							'max_length'  => 512,
							'_order'      => 30,
						],
					],
				],
			];
		}

		if ( get_option( 'hp_listing_allow_price_extras' ) ) {

			// Add price extras.
			$attributes['price_extras'] = [
				'edit_field' => [
					'label'  => esc_html__( 'Extras', 'hivepress-bookings' ),
					'type'   => 'repeater',
					'_order' => 199,

					'fields' => [
						'name'  => [
							'placeholder' => hivepress()->translator->get_string( 'title' ),
							'type'        => 'text',
							'max_length'  => 256,
							'required'    => true,
							'_order'      => 10,
						],

						'price' => [
							'placeholder' => hivepress()->translator->get_string( 'price' ),
							'type'        => 'currency',
							'min_value'   => 0,
							'required'    => true,
							'_order'      => 20,
						],

						'type'  => [
							'type'    => 'select',
							'options' => $this->get_price_options(),
							'_order'  => 30,
						],
					],
				],
			];
		}

		if ( get_option( 'hp_booking_allow_sync' ) ) {

			// Add import URL.
			$attributes['import_url'] = [
				'protected'  => true,

				'edit_field' => [
					'type' => 'url',
				],
			];
		}

		return $attributes;
	}

	/**
	 * Sets listing attributes.
	 */
	public function set_listing_attributes() {
		$this->attributes = [
			'booking_offset'        => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Offset', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set how many days are required prior to the booking date.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 1,
					'_order'      => 210,
				],
			],

			'booking_window'        => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Window', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set how many days in advance a booking can be made.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 1,
					'_order'      => 220,
				],
			],

			'booking_min_time'      => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Available From', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the earliest time available for booking.', 'hivepress-bookings' ),
					'type'        => 'time',
					'required'    => true,
					'_timeonly'   => true,
					'_order'      => 230,
				],
			],

			'booking_max_time'      => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Available To', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the latest time available for booking.', 'hivepress-bookings' ),
					'type'        => 'time',
					'required'    => true,
					'_timeonly'   => true,
					'_order'      => 240,
				],
			],

			'booking_min_length'    => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Minimum Booking Duration', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the minimum booking duration in days.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 1,
					'_timeonly'   => false,
					'_order'      => 227,
				],
			],

			'booking_max_length'    => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Maximum Booking Duration', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the maximum booking duration in days.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 1,
					'_timeonly'   => false,
					'_order'      => 228,
				],
			],

			'booking_slot_interval' => [
				'editable'   => true,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Slot Interval', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the interval between time slots in minutes.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 5,
					'max_value'   => 720,
					'_timeonly'   => true,
					'_order'      => 260,
				],
			],

			'booking_moderated'     => [
				'editable'   => true,

				'edit_field' => [
					'label'   => esc_html__( 'Booking Requests', 'hivepress-bookings' ),
					'caption' => esc_html__( 'Manually accept new bookings', 'hivepress-bookings' ),
					'type'    => 'checkbox',
					'_order'  => 290,
				],
			],
		];

		if ( get_option( 'hp_booking_allow_sync' ) ) {
			$this->attributes = array_merge(
				$this->attributes,
				[
					'booking_import_urls' => [
						'editable'   => true,

						'edit_field' => [
							'label'       => esc_html__( 'Booking Import URLs', 'hivepress-bookings' ),
							'description' => esc_html__( 'Add external ICS calendar URLs to sync the availability.', 'hivepress-bookings' ),
							'type'        => 'repeater',
							'_order'      => 270,

							'fields'      => [
								'url' => [
									'type'     => 'url',
									'required' => true,
									'_order'   => 10,
								],
							],
						],
					],

					'booking_export_url'  => [
						'editable'   => true,

						'edit_field' => [
							'label'       => esc_html__( 'Booking Export URL', 'hivepress-bookings' ),
							'description' => esc_html__( 'Use this URL to sync the availability of external ICS calendars.', 'hivepress-bookings' ),
							'type'        => 'url',
							'readonly'    => true,
							'_separate'   => true,
							'_order'      => 280,
						],
					],
				]
			);
		}
	}

	/**
	 * Adds listing attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return array
	 */
	public function add_listing_attributes( $attributes ) {

		// Check settings.
		$is_listing = strpos( current_filter(), 'listing' );
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		if ( ! $is_listing && ! $per_vendor ) {
			return $attributes;
		}

		// Get category IDs.
		$category_ids = array_filter( (array) get_option( 'hp_booking_categories' ) );

		// Get new attributes.
		$new_attributes = array_map(
			function( $attribute ) use ( $category_ids, $per_vendor ) {
				if ( ! $per_vendor ) {
					$attribute['categories'] = $category_ids;
				}

				return $attribute;
			},
			$this->attributes
		);

		if ( $is_listing ) {
			if ( $per_vendor ) {
				$new_attributes = array_map(
					function( $attribute ) {
						$attribute['editable']  = false;
						$attribute['protected'] = true;

						return $attribute;
					},
					$new_attributes
				);
			}

			$attributes['booking_slot_duration'] = [
				'editable'   => true,
				'categories' => $category_ids,

				'edit_field' => [
					'label'       => esc_html__( 'Booking Slot Duration', 'hivepress-bookings' ),
					'description' => esc_html__( 'Set the time slot duration in minutes.', 'hivepress-bookings' ),
					'type'        => 'number',
					'min_value'   => 5,
					'max_value'   => 720,
					'required'    => true,
					'_timeonly'   => true,
					'_order'      => 250,
				],
			];

			if ( get_option( 'hp_booking_enable_quantity' ) ) {

				// Add quantity attributes.
				$attributes = array_merge(
					$attributes,
					[
						'booking_min_quantity' => [
							'editable'   => true,
							'categories' => $category_ids,

							'edit_field' => [
								'label'     => hivepress()->translator->get_string( 'min_places_per_booking' ),
								'type'      => 'number',
								'min_value' => 1,
								'_order'    => 243,
							],
						],

						'booking_max_quantity' => [
							'editable'   => true,
							'categories' => $category_ids,

							'edit_field' => [
								'label'     => hivepress()->translator->get_string( 'max_places_per_booking' ),
								'type'      => 'number',
								'min_value' => 1,
								'_order'    => 247,
							],
						],
					]
				);
			}

			if ( get_option( 'hp_listing_allow_purchase_note' ) ) {

				// Add booking note.
				$attributes['purchase_note'] = [
					'editable'   => true,
					'categories' => $category_ids,

					'edit_field' => [
						'label'       => esc_html__( 'Booking Note', 'hivepress-bookings' ),
						'description' => esc_html__( 'Add a note that will be revealed to the customer upon booking.', 'hivepress-bookings' ),
						'type'        => 'textarea',
						'max_length'  => 10240,
						'_order'      => 205,
					],
				];
			}

			if ( get_option( 'hp_booking_enable_price' ) ) {
				$attributes['booking_ranges'] = [
					'editable'   => true,
					'categories' => $category_ids,

					'edit_field' => [
						'label'  => esc_html__( 'Daily Prices', 'hivepress-bookings' ),
						'type'   => 'repeater',
						'_order' => 31,

						'fields' => [
							'days'  => [
								'placeholder' => esc_html__( 'Days', 'hivepress-bookings' ),
								'type'        => 'select',
								'options'     => 'days',
								'multiple'    => true,
								'required'    => true,
								'_order'      => 10,
							],

							'price' => [
								'placeholder' => hivepress()->translator->get_string( 'price' ),
								'type'        => 'currency',
								'min_value'   => 0,
								'required'    => true,
								'_order'      => 20,
							],
						],
					],
				];
			}

			if ( get_option( 'hp_booking_enable_deposit' ) ) {
				$attributes['booking_deposit'] = [
					/* translators: %s: amount. */
					'display_format' => sprintf( esc_html__( 'Security Deposit: %s', 'hivepress-bookings' ), '%value%' ),
					'display_areas'  => [ 'view_page_primary' ],
					'editable'       => true,

					'edit_field'     => [
						'label'  => hivepress()->translator->get_string( 'security_deposit' ),
						'type'   => 'currency',
						'_order' => 32,
					],
				];
			}
		}

		return array_merge( $attributes, $new_attributes );
	}

	/**
	 * Adds listing fields.
	 *
	 * @param array  $form Form arguments.
	 * @param object $model Model object.
	 * @return array
	 */
	public function add_listing_fields( $form, $model = null ) {

		// Get settings.
		$is_form    = strpos( current_filter(), 'form' );
		$is_model   = strpos( current_filter(), 'model' );
		$is_listing = strpos( current_filter(), 'listing' );
		$is_vendor  = strpos( current_filter(), 'vendor' );
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		if ( ! $is_listing && ! $per_vendor ) {
			return $form;
		}

		// Get fields.
		$fields = [];

		if ( $is_model ) {
			$fields = $form;
		} else {
			$fields = $form['fields'];
		}

		// Get listing ID.
		$listing_id = null;

		if ( $is_listing ) {
			if ( $is_model ) {
				$listing_id = $model->get_id();
			} elseif ( $is_form ) {
				$listing_id = $model->get_model()->get_id();
			} else {
				$listing_id = get_the_ID();
			}

			if ( ! $listing_id || ! $this->is_booking_enabled( $listing_id ) ) {
				return $form;
			}
		}

		if ( get_option( 'hp_booking_allow_sync' ) ) {
			if ( $is_model ) {
				unset( $fields['booking_export_url'] );
			} elseif ( $is_listing && ! $per_vendor ) {

				// Get vendor ID.
				$vendor_id = null;

				if ( $is_form ) {
					$vendor_id = $model->get_model()->get_vendor__id();
				} else {
					$vendor_id = get_post_field( 'post_parent' );
				}

				// Set export URL.
				$fields['booking_export_url']['default'] = hivepress()->router->get_url(
					'listing_calendar_file',
					[
						'listing_id' => $listing_id,
						'access_key' => $this->get_calendar_key( $vendor_id ),
					]
				);
			} elseif ( $is_vendor ) {

				// Get vendor ID.
				$vendor_id = null;

				if ( $is_form ) {
					$vendor_id = $model->get_model()->get_id();
				} else {
					$vendor_id = get_the_ID();
				}

				// Set export URL.
				$fields['booking_export_url']['default'] = hivepress()->router->get_url(
					'vendor_calendar_file',
					[
						'vendor_id'  => $vendor_id,
						'access_key' => $this->get_calendar_key( $vendor_id ),
					]
				);
			}
		}

		// Filter fields.
		$time_enabled = ( $listing_id && $this->is_time_enabled( $listing_id ) ) || ( ! $is_listing && get_option( 'hp_booking_enable_time' ) );

		if ( $is_listing || ! get_option( 'hp_booking_time_categories' ) ) {
			foreach ( $fields as $field_name => $field_args ) {
				if ( isset( $field_args['_timeonly'] ) && $field_args['_timeonly'] !== $time_enabled ) {
					unset( $fields[ $field_name ] );
				}
			}
		}

		if ( $is_listing && hivepress()->get_version( 'marketplace' ) ) {

			// Set price format.
			if ( ! $time_enabled ) {
				if ( get_option( 'hp_booking_enable_daily' ) ) {

					/* translators: %s: price. */
					$fields['price']['display_template'] = sprintf( esc_html__( '%s / day', 'hivepress-bookings' ), '%value%' );
				} else {

					/* translators: %s: price. */
					$fields['price']['display_template'] = sprintf( esc_html__( '%s / night', 'hivepress-bookings' ), '%value%' );
				}
			} else {

				// @todo remove when recurring support added.
				unset( $fields['subscription_period'] );
			}

			// Set price options.
			if ( get_option( 'hp_listing_allow_price_extras' ) ) {

				// @todo remove when added to marketplace.
				$fields['price_extras']['fields']['type'] = [
					'type'    => 'select',
					'options' => $this->get_price_options(),
					'_order'  => 30,
				];
			}

			// Set discount label.
			if ( get_option( 'hp_listing_allow_discounts' ) ) {
				$fields['discounts']['fields']['quantity']['placeholder'] = esc_html__( 'Days', 'hivepress-bookings' );
			}
		}

		// Add fields.
		if ( $time_enabled && ( ! $is_listing || ! $per_vendor || $is_model ) ) {
			$fields['booking_days'] = [
				'label'       => esc_html__( 'Booking Days', 'hivepress-bookings' ),
				'description' => esc_html__( 'Select the days of the week available for booking.', 'hivepress-bookings' ),
				'type'        => 'checkboxes',
				'options'     => 'days',
				'_external'   => true,
				'_order'      => 225,

				'attributes'  => [
					'class' => [ 'hp-field--days' ],
				],
			];

			if ( get_option( 'hp_booking_enable_timezone' ) ) {
				$fields['booking_timezone'] = [
					'label'       => esc_html__( 'Booking Timezone', 'hivepress-bookings' ),
					'description' => esc_html__( 'Select the timezone for bookings.', 'hivepress-bookings' ),
					'type'        => 'select',
					'options'     => 'timezones',
					'default'     => get_option( 'timezone_string' ),
					'required'    => true,
					'_external'   => true,
					'_order'      => 245,
				];
			}
		}

		// Set fields.
		if ( $is_model ) {
			$form = $fields;
		} else {
			$form['fields'] = $fields;
		}

		return $form;
	}

	/**
	 * Creates listing.
	 *
	 * @param int $listing_id Listing ID.
	 */
	public function create_listing( $listing_id ) {

		// Check settings.
		if ( ! get_option( 'hp_booking_per_vendor' ) ) {
			return;
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $listing_id );

		if ( ! $listing ) {
			return;
		}

		// Get vendor.
		$vendor = $listing->get_vendor();

		if ( ! $vendor ) {
			return;
		}

		// Get attributes.
		$attributes = $this->attributes;

		if ( $this->is_time_enabled( $listing ) ) {
			$attributes['booking_days'] = [];

			if ( get_option( 'hp_booking_enable_timezone' ) ) {
				$attributes['booking_timezone'] = [];
			}
		}

		// Get values.
		$values = array_intersect_key( $vendor->serialize(), $attributes );

		// Update listing.
		$listing->fill( $values )->save( array_keys( $values ) );
	}

	/**
	 * Updates listing status.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $listing Listing.
	 */
	public function update_listing_status( $listing_id, $new_status, $old_status, $listing ) {

		// Check settings.
		if ( ! get_option( 'hp_booking_per_vendor' ) || 'trash' !== $new_status ) {
			return;
		}

		// Get first listing.
		$new_listing_id = Models\Listing::query()->filter(
			[
				'user'       => $listing->get_user__id(),
				'status__in' => [ 'publish', 'pending', 'draft' ],
			]
		)->get_first_id();

		if ( ! $new_listing_id ) {
			return;
		}

		// Get blocked dates.
		$bookings = Models\Booking::query()->filter(
			[
				'listing' => $listing_id,
				'status'  => 'private',
			]
		)->get();

		// Move blocked dates.
		foreach ( $bookings as $booking ) {
			$booking->set_listing( $new_listing_id )->save_listing();
		}

		// Check settings.
		if ( ! get_option( 'hp_booking_enable_price' ) ) {
			return;
		}

		// Get booking ranges.
		$booking_ranges = Models\Booking_Range::query()->filter(
			[
				'listing' => $listing_id,
			]
		)->get();

		// Move booking ranges.
		foreach ( $booking_ranges as $booking_range ) {
			$booking_range->set_listing( $new_listing_id )->save_listing();
		}
	}

	/**
	 * Updates vendor.
	 *
	 * @param int    $vendor_id Vendor ID.
	 * @param object $vendor Vendor object.
	 */
	public function update_vendor( $vendor_id, $vendor ) {

		// Remove action.
		remove_action( 'hivepress/v1/models/vendor/update', [ $this, 'update_vendor' ] );

		// Check settings.
		if ( ! get_option( 'hp_booking_per_vendor' ) ) {
			return;
		}

		// Get attributes.
		$attributes = $this->attributes;

		if ( get_option( 'hp_booking_enable_time' ) ) {
			$attributes['booking_days'] = [];

			if ( get_option( 'hp_booking_enable_timezone' ) ) {
				$attributes['booking_timezone'] = [];
			}
		}

		// Get values.
		$values = array_intersect_key( $vendor->serialize(), $attributes );

		// Get listings.
		$listings = Models\Listing::query()->filter(
			[
				'status__in' => [ 'auto-draft', 'draft', 'pending', 'publish' ],
				'user'       => $vendor->get_user__id(),
			]
		)->get();

		// Update listings.
		foreach ( $listings as $listing ) {
			if ( array_intersect_key( $listing->serialize(), $attributes ) !== $values ) {
				$listing->fill( $values )->save( array_keys( $values ) );
			}
		}
	}

	/**
	 * Updates booking.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param object $booking Booking object.
	 */
	public function update_booking( $booking_id, $booking ) {
		if ( ! $booking->get_title() && ! in_array( $booking->get_status(), [ 'auto-draft', 'private' ], true ) ) {

			// Update title.
			$booking->set_title( '#' . $booking->get_id() )->save_title();
		}
	}

	/**
	 * Updates booking status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $booking Booking object.
	 */
	public function update_booking_status( $booking_id, $new_status, $old_status, $booking ) {
		if ( in_array( $new_status, [ 'publish', 'pending', 'trash' ], true ) || 'pending' === $old_status ) {

			// Get listing.
			$listing = $booking->get_listing();

			if ( $listing ) {

				// Get users.
				$user   = $booking->get_user();
				$vendor = $listing->get_user();

				if ( $user && $vendor ) {

					// Set email arguments.
					$email_args = [
						'tokens' => [
							'listing'       => $listing,
							'booking'       => $booking,
							'listing_title' => $listing->get_title(),
							'booking_dates' => $booking->display_dates(),
							'booking_url'   => hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking_id ] ),
						],
					];

					$user_email_args = hp\merge_arrays(
						$email_args,
						[
							'recipient' => $user->get_email(),

							'tokens'    => [
								'user'      => $user,
								'user_name' => $user->get_display_name(),
								'vendor'    => $listing->get_vendor(),
							],
						]
					);

					$vendor_email_args = hp\merge_arrays(
						$email_args,
						[
							'recipient' => $vendor->get_email(),

							'tokens'    => [
								'user'      => $vendor,
								'user_name' => $vendor->get_display_name(),
							],
						]
					);

					// Send emails.
					if ( 'publish' === $new_status ) {
						( new Emails\Booking_Confirm_User( $user_email_args ) )->send();

						if ( $vendor->get_id() !== $user->get_id() && 'pending' !== $old_status ) {
							( new Emails\Booking_Confirm_Vendor( $vendor_email_args ) )->send();
						}
					} elseif ( 'pending' === $new_status ) {
						( new Emails\Booking_Request( $vendor_email_args ) )->send();
					} elseif ( 'trash' === $new_status && 'pending' !== $old_status ) {
						( new Emails\Booking_Cancel_User( $user_email_args ) )->send();

						// @todo don't send if canceled by vendor.
						if ( $vendor->get_id() !== $user->get_id() ) {
							( new Emails\Booking_Cancel_Vendor( $vendor_email_args ) )->send();
						}
					}
				}
			}
		}

		// @todo set date also when published immediately.
		if ( in_array( $new_status, [ 'draft', 'pending' ], true ) ) {

			// Get date.
			$date = current_time( 'mysql' );

			// Update date.
			$booking->fill(
				[
					'created_date'     => $date,
					'created_date_gmt' => get_gmt_from_date( $date ),
				]
			)->save( [ 'created_date', 'created_date_gmt' ] );
		}

		if ( in_array( $old_status, [ 'auto-draft', 'pending' ], true ) && 'private' !== $new_status ) {

			// Clear cache.
			$this->clear_vendor_cache( $booking_id );
		}

		if ( 'publish' !== $new_status ) {

			// Unschedule actions.
			hivepress()->scheduler->remove_action( 'hivepress/v1/models/booking/remind', [ $booking_id ] );
			hivepress()->scheduler->remove_action( 'hivepress/v1/models/booking/complete', [ $booking->get_id() ] );
		} elseif ( $booking->get_end_time() ) {

			// Schedule actions.
			hivepress()->scheduler->add_action( 'hivepress/v1/models/booking/remind', [ $booking_id ], strtotime( '-1 day', $booking->get_start_time() ) );
			hivepress()->scheduler->add_action( 'hivepress/v1/models/booking/complete', [ $booking->get_id() ], $booking->get_end_time() + DAY_IN_SECONDS / 2 );
		}
	}

	/**
	 * Updates booking start time.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function update_booking_start_time( $booking_id ) {

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $booking_id );

		if ( ! $booking || ! $booking->get_start_time() ) {
			return;
		}

		// Reschedule action.
		hivepress()->scheduler->remove_action( 'hivepress/v1/models/booking/remind', [ $booking_id ] );

		if ( 'publish' === $booking->get_status() ) {
			hivepress()->scheduler->add_action( 'hivepress/v1/models/booking/remind', [ $booking_id ], strtotime( '-1 day', $booking->get_start_time() ) );
		}
	}

	/**
	 * Updates booking time meta.
	 *
	 * @param int $listing_id Listing ID.
	 */
	public function update_booking_time_meta( $listing_id ) {

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $listing_id );

		if ( ! $listing ) {
			return;
		}

		// Get days.
		$days = __( 'Uncategorized' );

		if ( $listing->get_booking_days() ) {
			$days = $listing->display_booking_days();
		}

		// Update days.
		wp_set_post_terms( $listing_id, $days, 'hp_listing_booking_days' );

		// Update slots.
		update_post_meta( $listing_id, 'hp_booking_slot_quantity', $this->get_booking_quantity( $listing->get_booking_min_time(), $listing->get_booking_max_time(), $listing ) );
	}

	/**
	 * Remind about the booking.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function remind_booking( $booking_id ) {

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $booking_id );

		if ( ! $booking || 'publish' !== $booking->get_status() ) {
			return;
		}

		// Get user.
		$user = $booking->get_user();

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $user || ! $listing ) {
			return;
		}

		// Send email.
		( new Emails\Booking_Remind(
			[
				'recipient' => $user->get_email(),

				'tokens'    => [
					'user'           => $user,
					'listing'        => $listing,
					'booking'        => $booking,
					'vendor'         => $listing->get_vendor(),
					'user_name'      => $user->get_display_name(),
					'booking_number' => '#' . $booking->get_id(),
					'booking_dates'  => $booking->display_dates(),
					'booking_url'    => hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ),
				],
			]
		) )->send();
	}

	/**
	 * Expires bookings.
	 */
	public function expire_bookings() {

		// Delete private bookings.
		Models\Booking::query()->filter(
			[
				'status'       => 'private',
				'end_time__lt' => time(),
			]
		)->limit( 10 )
		->delete();

		// Get expiration period.
		$expiration_period = absint( get_option( 'hp_booking_expiration_period' ) );

		if ( $expiration_period ) {

			// Get expiration time.
			$expiration_time = time() - $expiration_period * DAY_IN_SECONDS;

			// Cancel pending bookings.
			Models\Booking::query()->filter(
				[
					'status__in' => [ 'draft', 'pending' ],
				]
			)->limit( 10 )
			->set_args(
				[
					'date_query' => [
						[
							'before' => date( 'Y-m-d H:i:s', $expiration_time ),
						],
					],
				]
			)->trash();
		}

		// Get storage period.
		$storage_period = absint( get_option( 'hp_booking_storage_period' ) );

		if ( $storage_period ) {

			// Get storage time.
			$storage_time = time() - $storage_period * DAY_IN_SECONDS;

			// Delete past bookings.
			Models\Booking::query()->filter(
				[
					'status'       => 'publish',
					'end_time__lt' => $storage_time,
				]
			)->limit( 10 )
			->delete();

			// Delete canceled bookings.
			Models\Booking::query()->filter(
				[
					'status' => 'trash',
				]
			)->set_args(
				[
					'date_query' => [
						[
							'before' => date( 'Y-m-d H:i:s', $storage_time ),
						],
					],
				]
			)->limit( 10 )
			->delete();
		}
	}

	/**
	 * Schedules booking completion.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function schedule_booking_completion( $booking_id ) {

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $booking_id );

		// Unschedule completion.
		hivepress()->scheduler->remove_action( 'hivepress/v1/models/booking/complete', [ $booking->get_id() ] );

		if ( ! $booking->get_end_time() ) {
			return;
		}

		if ( get_option( 'hp_booking_enable_daily' ) && $booking->get_status() !== 'private' && $booking->get_end_time() % DAY_IN_SECONDS === 0 ) {

			// Get listing.
			$listing = $booking->get_listing();

			if ( $listing && ! $this->is_time_enabled( $listing ) ) {

				// Adjust end time.
				$booking->set_end_time( $booking->get_end_time() + DAY_IN_SECONDS - 1 )->save_end_time();

				return;
			}
		}

		if ( 'publish' !== $booking->get_status() ) {
			return;
		}

		// Schedule completion.
		hivepress()->scheduler->add_action( 'hivepress/v1/models/booking/complete', [ $booking->get_id() ], $booking->get_end_time() + DAY_IN_SECONDS / 2 );
	}

	/**
	 * Completes booking.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function complete_booking( $booking_id ) {

		// Check Marketplace status.
		if ( ! hivepress()->get_version( 'marketplace' ) ) {
			return;
		}

		// Get order.
		$order = hp\get_first_array_value(
			wc_get_orders(
				[
					'limit'      => 1,
					'meta_key'   => 'hp_booking',
					'meta_value' => $booking_id,
				]
			)
		);

		if ( ! $order || $order->get_status() !== 'processing' ) {
			return;
		}

		// Complete order.
		$order->update_status( 'completed' );
	}

	/**
	 * Schedules booking import.
	 *
	 * @param int    $id Object ID.
	 * @param object $object Model object.
	 */
	public function schedule_booking_import( $id, $object ) {

		// Check version.
		if ( version_compare( hivepress()->get_version(), '1.6.9', '<' ) ) {
			return;
		}

		// Get model name.
		$model = $object::_get_meta( 'name' );

		// Get settings.
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		if ( ! get_option( 'hp_booking_allow_sync' ) || ( $per_vendor && 'listing' === $model ) || ( ! $per_vendor && 'vendor' === $model ) ) {
			return;
		}

		// Schedule actions.
		$action      = 'hivepress/v1/models/booking/sync';
		$action_args = [ $model, $id ];

		if ( $object->get_booking_import_urls() ) {
			hivepress()->scheduler->add_action( $action, $action_args, null, HOUR_IN_SECONDS * 2 );
		} else {
			hivepress()->scheduler->remove_action( $action, $action_args );
		}
	}

	/**
	 * Upgrades booking import.
	 *
	 * @deprecated Since version 1.5.1
	 */
	public function upgrade_booking_import() {
		$listings = get_posts(
			[
				'post_type'      => 'hp_listing',
				'post_status'    => [ 'draft', 'pending', 'publish' ],
				'meta_key'       => 'hp_booking_import_url',
				'posts_per_page' => -1,
			]
		);

		foreach ( $listings as $listing ) {
			update_post_meta( $listing->ID, 'hp_booking_import_urls', [ [ 'url' => $listing->hp_booking_import_url ] ] );
			delete_post_meta( $listing->ID, 'hp_booking_import_url' );
		}
	}

	/**
	 * Imports bookings.
	 *
	 * @param string $model Model name.
	 * @param int    $id Object ID.
	 */
	public function import_bookings( $model, $id ) {

		// Set action arguments.
		$action      = current_action();
		$action_args = [ $model, $id ];

		// Check settings.
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		if ( ! get_option( 'hp_booking_allow_sync' ) || ( $per_vendor && 'listing' === $model ) || ( ! $per_vendor && 'vendor' === $model ) ) {
			hivepress()->scheduler->remove_action( $action, $action_args );

			return;
		}

		// Get model object.
		$object = hivepress()->model->get_model_object( $model, $id );

		if ( ! $object || ! in_array( $object->get_status(), [ 'draft', 'pending', 'publish' ] ) ) {
			hivepress()->scheduler->remove_action( $action, $action_args );

			return;
		}

		// Get listing.
		$listing = null;

		if ( 'listing' === $model ) {
			$listing = $object;
		} elseif ( 'vendor' === $model ) {
			$listing = Models\Listing::query()->filter(
				[
					'user'       => $object->get_user__id(),
					'status__in' => [ 'draft', 'pending', 'publish' ],
				]
			)->get_first();
		}

		if ( ! $listing || ! $listing->get_booking_import_urls() ) {
			hivepress()->scheduler->remove_action( $action, $action_args );

			return;
		}

		foreach ( $listing->get_booking_import_urls() as $url ) {

			// Get calendar URL.
			$url = hp\get_array_value( $url, 'url' );

			if ( ! $url ) {
				continue;
			}

			// Cleanup bookings.
			Models\Booking::query()->filter(
				[
					'status'  => 'private',
					'listing' => $listing->get_id(),
				]
			)->set_args(
				[
					// @todo remove temporary fix when updated.
					'meta_key'   => 'hp_import_url',
					'meta_value' => $url,
				]
			)->delete();

			// Create calendar.
			try {
				$calendar = @new ICal( $url );
			} catch ( \Exception $exception ) {
				continue;
			}

			if ( ! $calendar->hasEvents() ) {
				continue;
			}

			// Get events.
			$events = $calendar->eventsFromRange( date( 'Y-m-d H:i:s', strtotime( 'today' ) ) );

			foreach ( $events as $event ) {

				// Get timezone.
				$timezone = hp\get_array_value( $event->dtstart_array[0], 'TZID', $calendar->calendarTimeZone() );

				if ( 'GMT Standard Time' === $timezone ) {
					$timezone = 'UTC';
				}

				// Get time range.
				$start_time = hivepress()->booking->get_shifted_time( $listing, absint( $event->dtstart_array[2] ), $timezone );
				$end_time   = hivepress()->booking->get_shifted_time( $listing, absint( $event->dtend_array[2] ), $timezone );

				// Check availability.
				if ( hivepress()->booking->get_overlapping_query( $start_time, $end_time, $listing )->get_first_id() ) {
					continue;
				}

				// Add booking.
				$booking = new Models\Booking();

				// @todo remove temporary fix when updated.
				$booking->set_id( null );

				$booking->fill(
					[
						'start_time' => $start_time,
						'end_time'   => $end_time,
						'status'     => 'private',
						'user'       => $listing->get_user__id(),
						'listing'    => $listing->get_id(),
						'import_url' => $url,
					]
				);

				if ( ! $booking->save( [ 'start_time', 'end_time', 'status', 'user', 'listing', 'import_url' ] ) ) {
					continue;
				}
			}
		}
	}

	/**
	 * Clears vendor cache.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function clear_vendor_cache( $booking_id ) {

		// Get listing ID.
		$listing_id = get_post_field( 'post_parent', $booking_id );

		if ( $listing_id ) {

			// Get user ID.
			$user_id = absint( get_post_field( 'post_author', $listing_id ) );

			if ( $user_id ) {

				// Delete cache.
				hivepress()->cache->delete_user_cache( $user_id, 'booking_count', 'models/booking' );
				hivepress()->cache->delete_user_cache( $user_id, 'booking_request_count', 'models/booking' );
			}
		}
	}

	/**
	 * Clears category cache.
	 *
	 * @param int $booking_id Booking ID.
	 * @todo Remove when fixed in the core.
	 */
	public function clear_category_cache( $booking_id ) {
		hivepress()->cache->delete_post_cache( $booking_id, [ 'fields' => 'ids' ], 'models/listing_category' );
	}

	/**
	 * Alters strings.
	 *
	 * @param array $strings Strings.
	 * @return array
	 */
	public function alter_strings( $strings ) {
		if ( get_option( 'hp_booking_enable_time' ) ) {
			if ( get_option( 'hp_booking_time_categories' ) ) {
				$strings['start_date'] = esc_html__( 'Start', 'hivepress-bookings' );
				$strings['end_date']   = esc_html__( 'End', 'hivepress-bookings' );
			} else {
				$strings['start_date'] = esc_html__( 'Start Time', 'hivepress-bookings' );
				$strings['end_date']   = esc_html__( 'End Time', 'hivepress-bookings' );
			}
		}

		return $strings;
	}

	/**
	 * Updates cart.
	 *
	 * @param array  $cart Cart arguments.
	 * @param object $listing Listing object.
	 * @return array
	 */
	public function update_cart( $cart, $listing ) {
		if ( ! isset( $cart['meta']['booking'] ) && hivepress()->booking->is_booking_enabled( $listing ) ) {

			// Set quantity.
			if ( isset( $cart['args']['_quantity'] ) && get_option( 'hp_booking_enable_quantity' ) ) {
				$cart['meta']['quantity'] = absint( $cart['args']['_quantity'] );

				unset( $cart['args']['_quantity'] );
			}

			// Get time range.
			list($start_time, $end_time) = $this->get_booking_times( $cart['args'], $listing );

			if ( $start_time && $end_time ) {

				// Set quantity.
				$cart['args']['_quantity'] = $this->get_booking_quantity( $start_time, $end_time, $listing );

				// Set tier.
				$cart['meta']['price'] = null;

				if ( get_option( 'hp_listing_allow_price_tiers' ) && $listing->get_price_tiers() ) {
					$tier = hp\get_array_value( $listing->get_price_tiers(), absint( hp\get_array_value( $cart['args'], '_tier' ) ) );

					if ( $tier ) {

						/* translators: 1: tier name, 2: tier price. */
						$cart['meta']['price_tier'] = esc_html( sprintf( _x( '%1$s (%2$s)', 'pricing tier format', 'hivepress-bookings' ), $tier['name'], hivepress()->woocommerce->format_price( $tier['price'] ) ) );
						$cart['meta']['price']      = floatval( $tier['price'] );
					}
				}

				// Set price.
				$cart['meta']['price'] = $this->get_average_price( $start_time, $end_time, $listing, $cart['meta']['price'] );
			}
		}

		return $cart;
	}

	/**
	 * Creates order.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function create_order( $order_id, $order ) {

		// Check order.
		if ( ! $order || $order->get_meta( 'hp_booking' ) ) {
			return;
		}

		// Get item.
		$item = hp\get_first_array_value( $order->get_items() );

		if ( ! $item || ! $item->get_meta( 'hp_booking' ) ) {
			return;
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $item->get_meta( 'hp_booking' ) );

		if ( ! $booking ) {
			return;
		}

		// Update order.
		update_post_meta( $order->get_id(), 'hp_booking', $booking->get_id() );

		// Update item.
		wc_delete_order_item_meta( $item->get_id(), 'hp_booking' );
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

		// Check order.
		if ( ! $order->get_meta( 'hp_booking' ) ) {
			return;
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $order->get_meta( 'hp_booking' ) );

		if ( ! $booking ) {
			return;
		}

		// Update status.
		if ( in_array( $booking->get_status(), [ 'draft', 'publish' ] ) ) {
			if ( in_array( $new_status, [ 'processing', 'completed' ] ) ) {
				$booking->set_status( 'publish' )->save_status();
			} elseif ( in_array( $new_status, [ 'failed', 'cancelled' ] ) ) {
				$booking->set_status( 'draft' )->save_status();
			} elseif ( 'refunded' === $new_status ) {
				$booking->trash();
			}
		}

		if ( 'completed' === $new_status && ! $order->get_total_refunded() ) {

			// Get deposit.
			$deposit_amount = null;
			$deposit_label  = hivepress()->translator->get_string( 'security_deposit' );

			foreach ( $order->get_fees() as $fee ) {
				if ( $fee->get_name( 'edit' ) === $deposit_label ) {
					$deposit_amount = $fee->get_amount( 'edit' );

					break;
				}
			}

			if ( $deposit_amount ) {

				// Get commission.
				$commission_rate = floatval( $order->get_meta( 'hp_commission_rate' ) );
				$commission_fee  = floatval( $order->get_meta( 'hp_commission_fee' ) );

				if ( $commission_fee ) {

					// Update commission.
					$commission_fee -= round( $deposit_amount * $commission_rate, 2 );

					update_post_meta( $order->get_id(), 'hp_commission_fee', $commission_fee );
				}

				// Refund deposit.
				hivepress()->marketplace->refund_order( $order->get_id(), $deposit_amount, $deposit_label );
			}
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

		if ( ! $order || ! $order->get_meta( 'hp_booking' ) ) {
			return;
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $order->get_meta( 'hp_booking' ) );

		if ( ! $booking ) {
			return;
		}

		if ( 'active' === $new_status ) {

			// Confirm booking.
			$booking->set_status( 'publish' )->save_status();
		} elseif ( in_array( $new_status, [ 'cancelled', 'expired' ] ) ) {

			// Cancel booking.
			$booking->trash();
		}
	}

	/**
	 * Redirects order page.
	 */
	public function redirect_order_page() {

		// Check authentication.
		if ( ! is_user_logged_in() || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Get order.
		$order = wc_get_order( get_query_var( 'order-received' ) );

		if ( ! $order || ! $order->get_meta( 'hp_booking' ) ) {
			return;
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $order->get_meta( 'hp_booking' ) );

		if ( ! $booking ) {
			return;
		}

		// Redirect page.
		wp_safe_redirect( hivepress()->router->get_url( 'booking_pay_complete_page', [ 'booking_id' => $booking->get_id() ] ) );

		exit;
	}

	/**
	 * Alters settings.
	 *
	 * @param array $settings Settings configuration.
	 * @return array
	 */
	public function alter_settings( $settings ) {
		if ( hivepress()->get_version( 'marketplace' ) ) {
			unset( $settings['listings']['sections']['selling']['fields']['listing_allow_discounts']['_parent'] );
		} else {
			unset( $settings['bookings']['sections']['pricing'] );
		}

		if ( ! get_option( 'hp_booking_enable_capacity' ) ) {
			$settings['listings']['sections']['search']['fields']['listing_search_fields']['options']['dates'] = esc_html__( 'Dates', 'hivepress-bookings' );
		}

		if ( get_option( 'hp_booking_enable_quantity' ) ) {
			$settings['listings']['sections']['search']['fields']['listing_search_fields']['options']['quantity'] = hivepress()->translator->get_string( 'places' );
		}

		return $settings;
	}

	/**
	 * Hides private bookings.
	 *
	 * @param string   $where Where clause.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function hide_private_bookings( $where, $query ) {
		if ( $query->is_main_query() && $query->get( 'post_type' ) === 'hp_booking' ) {
			$where .= " AND post_status != 'private'";
		}

		return $where;
	}

	/**
	 * Hides private bookings view.
	 *
	 * @param array $views Views.
	 * @return array
	 */
	public function hide_private_bookings_view( $views ) {
		unset( $views['private'] );

		return $views;
	}

	/**
	 * Alters booking settings meta box.
	 *
	 * @param array $meta_box Meta box arguments.
	 * @return array
	 */
	public function alter_booking_settings_meta_box( $meta_box ) {

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( get_post() );

		if ( ! $booking ) {
			return $meta_box;
		}

		if ( $this->is_recurring( $booking ) ) {
			$meta_box['fields']['end_time']['readonly']     = true;
			$meta_box['fields']['end_time']['display_type'] = 'hidden';
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( $listing && $this->is_time_enabled( $listing ) ) {
			$meta_box['fields']['start_time']['time'] = true;
			$meta_box['fields']['end_time']['time']   = true;
		}

		if ( hivepress()->get_version( 'marketplace' ) ) {

			// Get order.
			$order = hp\get_first_array_value(
				wc_get_orders(
					[
						'limit'      => 1,
						'meta_key'   => 'hp_booking',
						'meta_value' => get_the_ID(),
					]
				)
			);

			if ( $order ) {

				// Add link.
				$meta_box['fields']['order'] = [
					'label'      => hivepress()->translator->get_string( 'order' ),
					'caption'    => hivepress()->translator->get_string( 'view_order' ),
					'type'       => 'button',
					'_order'     => 50,

					'attributes' => [
						'data-component' => 'link',
						'data-url'       => hivepress()->router->get_admin_url( 'post', $order->get_id() ),
					],
				];
			}
		}

		return $meta_box;
	}

	/**
	 * Alters listing calendar meta box.
	 *
	 * @param array $meta_box Meta box arguments.
	 * @return array
	 */
	public function alter_listing_calendar_meta_box( $meta_box ) {

		// Get settings.
		$is_listing = strpos( current_filter(), 'listing' );
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		// Get listing.
		$listing = null;

		if ( $is_listing ) {
			$listing = Models\Listing::query()->get_by_id( get_post() );
		}

		if ( ( $is_listing && ( $per_vendor || ! $listing || ! $this->is_booking_enabled( $listing ) ) ) || ( ! $is_listing && ! $per_vendor ) ) {

			// Remove fields.
			$meta_box['fields'] = [];
			$meta_box['blocks'] = [];
		}

		return $meta_box;
	}

	/**
	 * Adds booking admin columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_booking_admin_columns( $columns ) {
		unset( $columns['date'] );

		$columns['title'] = hivepress()->translator->get_string( 'booking' );

		return array_merge(
			$columns,
			[
				'listing' => hivepress()->translator->get_string( 'listing' ),
				'user'    => hivepress()->translator->get_string( 'user' ),
				'dates'   => esc_html__( 'Dates', 'hivepress-bookings' ),
				'status'  => hivepress()->translator->get_string( 'status' ),
			]
		);
	}

	/**
	 * Renders booking admin columns.
	 *
	 * @param string $column Column name.
	 * @param int    $booking_id Booking ID.
	 */
	public function render_booking_admin_columns( $column, $booking_id ) {
		$output = '';

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		if ( ! $booking || $booking->get_id() !== $booking_id ) {
			$booking = Models\Booking::query()->get_by_id( get_post() );

			hivepress()->request->set_context( 'booking', $booking );
		}

		// Render column.
		switch ( $column ) {
			case 'listing':
				$output = '<a href="' . esc_url( hivepress()->router->get_admin_url( 'post', $booking->get_listing__id() ) ) . '">' . esc_html( $booking->get_listing__title() ) . '</a>';
				break;

			case 'user':
				$output = '<a href="' . esc_url( hivepress()->router->get_admin_url( 'user', $booking->get_user__id() ) ) . '">' . esc_html( $booking->get_user__display_name() ) . '</a>';
				break;

			case 'dates':
				$output = $booking->display_dates();
				break;

			case 'status':
				$output = '<div class="hp-status hp-status--' . esc_attr( $booking->get_status() ) . '"><span>' . esc_html( $booking->display_status() ) . '</span></div>';
				break;
		}

		if ( $output ) {
			echo wp_kses_post( $output );
		}
	}

	/**
	 * Sets request context.
	 *
	 * @param array $context Request context.
	 * @return array
	 */
	public function set_request_context( $context ) {

		// Get listing count.
		$listing_count = hp\get_array_value( $context, 'listing_count' );

		// Get cached booking count.
		$booking_count = hivepress()->cache->get_user_cache( get_current_user_id(), 'booking_count', 'models/booking' );

		if ( $listing_count ) {
			$booking_request_count = hivepress()->cache->get_user_cache( get_current_user_id(), 'booking_request_count', 'models/booking' );
		}

		if ( is_null( $booking_count ) ) {

			// Get booking count.
			$booking_count = Models\Booking::query()->filter(
				[
					'status__in' => [ 'trash', 'draft', 'pending', 'publish' ],
					'user'       => get_current_user_id(),
				]
			)->get_count();

			if ( $listing_count ) {
				$booking_request_count = 0;

				// Get listing IDs.
				$listing_ids = Models\Listing::query()->filter(
					[
						'status__in' => [ 'draft', 'pending', 'publish' ],
						'user'       => get_current_user_id(),
					]
				)->get_ids();

				if ( $listing_ids ) {

					// Add booking count.
					$booking_count += Models\Booking::query()->filter(
						[
							'status__in'  => [ 'trash', 'draft', 'pending', 'publish' ],
							'listing__in' => $listing_ids,
						]
					)->get_count();

					$booking_request_count = Models\Booking::query()->filter(
						[
							'status'      => 'pending',
							'listing__in' => $listing_ids,
						]
					)->get_count();
				}

				// Cache booking count.
				hivepress()->cache->set_user_cache( get_current_user_id(), 'booking_request_count', 'models/booking', $booking_request_count );
			}

			hivepress()->cache->set_user_cache( get_current_user_id(), 'booking_count', 'models/booking', $booking_count );
		}

		if ( hivepress()->get_version( 'marketplace' ) ) {

			// Get cached booking count.
			$booking_unpaid_count = hivepress()->cache->get_user_cache( get_current_user_id(), 'booking_unpaid_count', 'models/booking' );

			if ( is_null( $booking_unpaid_count ) ) {
				$booking_unpaid_count = Models\Booking::query()->filter(
					[
						'status' => 'draft',
						'user'   => get_current_user_id(),
					]
				)->get_count();

				// Cache booking count.
				hivepress()->cache->set_user_cache( get_current_user_id(), 'booking_unpaid_count', 'models/booking', $booking_unpaid_count );
			}

			// Set request context.
			if ( $booking_unpaid_count ) {
				$context['booking_unpaid_count'] = $booking_unpaid_count;

				$context['notice_count'] = hp\get_array_value( $context, 'notice_count', 0 ) + $booking_unpaid_count;
			}
		}

		// Set request context.
		$context['booking_count'] = $booking_count;

		if ( $listing_count ) {
			$context['booking_request_count'] = $booking_request_count;

			$context['notice_count'] = hp\get_array_value( $context, 'notice_count', 0 ) + $booking_request_count;
		}

		return $context;
	}

	/**
	 * Sets search query.
	 *
	 * @param WP_Query $query Search query.
	 */
	public function set_search_query( $query ) {

		// Get meta and taxonomy queries.
		$meta_query = array_filter( (array) $query->get( 'meta_query' ) );
		$tax_query  = array_filter( (array) $query->get( 'tax_query' ) );

		// Get fields.
		$fields = (array) get_option( 'hp_listing_search_fields' );

		if ( in_array( 'quantity', $fields ) ) {

			// Get quantity.
			$quantity = absint( hp\get_array_value( $_GET, '_quantity' ) );

			if ( $quantity >= 1 && $quantity <= 100 ) {

				// Set meta filter.
				$meta_query[] = [
					'key'     => 'hp_booking_max_quantity',
					'value'   => $quantity,
					'type'    => 'NUMERIC',
					'compare' => '>=',
				];
			}
		}

		if ( in_array( 'dates', $fields ) ) {

			// Get dates.
			$dates = hp\get_array_value( $_GET, '_dates' );

			if ( is_array( $dates ) && count( $dates ) === 2 ) {

				// Get time range.
				$start_time = strtotime( hp\get_first_array_value( $dates ) );
				$end_time   = strtotime( hp\get_last_array_value( $dates ) );

				$start_period = round( ( $start_time - strtotime( 'today' ) ) / DAY_IN_SECONDS );
				$end_period   = round( ( $end_time - strtotime( 'today' ) ) / DAY_IN_SECONDS );

				if ( $start_time && $end_time ) {
					if ( get_option( 'hp_booking_enable_daily' ) || get_option( 'hp_booking_enable_time' ) ) {
						$end_time += DAY_IN_SECONDS - 1;
					}

					// Set request context.
					hivepress()->request->set_context( 'booking_times', [ $start_time, $end_time ] );

					// Get quantity.
					$quantity = max( 1, round( ( $end_time - $start_time ) / DAY_IN_SECONDS ) );

					if ( ! get_option( 'hp_listing_optimize_date_search' ) ) {

						// Set meta filter.
						$meta_query = array_merge(
							$meta_query,
							[
								[
									'relation' => 'OR',

									[
										'key'     => 'hp_booking_min_length',
										'value'   => $quantity,
										'type'    => 'NUMERIC',
										'compare' => '<=',
									],

									[
										'key'     => 'hp_booking_min_length',
										'compare' => 'NOT EXISTS',
									],
								],

								[
									'relation' => 'OR',

									[
										'key'     => 'hp_booking_offset',
										'value'   => $start_period,
										'type'    => 'NUMERIC',
										'compare' => '<=',
									],

									[
										'key'     => 'hp_booking_offset',
										'compare' => 'NOT EXISTS',
									],
								],

								[
									'relation' => 'OR',

									[
										'key'     => 'hp_booking_window',
										'value'   => $end_period,
										'type'    => 'NUMERIC',
										'compare' => '>=',
									],

									[
										'key'     => 'hp_booking_window',
										'compare' => 'NOT EXISTS',
									],
								],
							]
						);
					}

					if ( get_option( 'hp_booking_enable_time' ) ) {

						// Get period.
						$period = new \DatePeriod(
							new \DateTime( '@' . $start_time ),
							new \DateInterval( 'P1D' ),
							new \DateTime( '@' . $end_time )
						);

						// Get days.
						$days = array_unique(
							array_map(
								function ( $date ) {
									return $date->format( 'D' );
								},
								iterator_to_array( $period )
							)
						);

						$days[] = __( 'Uncategorized' );

						// Add filters.
						$tax_query[] = [
							'taxonomy' => 'hp_listing_booking_days',
							'field'    => 'name',
							'terms'    => $days,
						];

						$meta_query[] = [
							'key'     => 'hp_booking_slot_quantity',
							'compare' => 'EXISTS',
						];
					}
				}
			}
		}

		// Set meta and taxonomy queries.
		$query->set( 'meta_query', $meta_query );
		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Sets search clauses.
	 *
	 * @param string   $where Where clause.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function set_search_clauses( $where, $query ) {
		global $wpdb;

		// Check query.
		if ( ! $query->is_search() || $query->get( 'post_type' ) !== 'hp_listing' ) {
			return $where;
		}

		if ( ! $query->is_main_query() && $query->get( 'meta_key' ) !== 'hp_featured' ) {
			return $where;
		}

		// Check request context.
		if ( ! hivepress()->request->get_context( 'booking_times' ) ) {
			return $where;
		}

		// Get time range.
		list($start_time, $end_time) = hivepress()->request->get_context( 'booking_times' );

		if ( ! $start_time || ! $end_time ) {
			return $where;
		}

		// Get subquery.
		$placeholder = implode( ', ', array_fill( 0, count( $this->get_blocked_statuses() ), '%s' ) );

		$subquery = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} AS bookings
			INNER JOIN {$wpdb->postmeta} AS start_times ON ( bookings.ID = start_times.post_id )
			INNER JOIN {$wpdb->postmeta} AS end_times ON ( bookings.ID = end_times.post_id )
			WHERE bookings.post_status IN ( {$placeholder} ) AND bookings.post_type = %s
			AND bookings.post_parent = {$wpdb->posts}.ID
			AND start_times.meta_key = %s AND end_times.meta_key = %s
			AND (
				( CAST( start_times.meta_value AS SIGNED ) BETWEEN %d and %d )
				OR ( CAST( end_times.meta_value AS SIGNED ) BETWEEN %d and %d )
				OR (
					( CAST( start_times.meta_value AS SIGNED ) < %d )
					AND ( CAST( end_times.meta_value AS SIGNED ) > %d )
				)
			)",
			array_merge(
				$this->get_blocked_statuses(),
				[
					'hp_booking',
					'hp_start_time',
					'hp_end_time',
					$start_time,
					$end_time - 1,
					$start_time + 1,
					$end_time,
					$start_time,
					$end_time,
				]
			)
		);

		// Add clauses.
		if ( get_option( 'hp_booking_enable_time' ) ) {

			// Get quantity.
			$quantity = max( 1, round( ( $end_time - $start_time ) / DAY_IN_SECONDS ) );

			// Get alias.
			$alias = null;

			foreach ( $query->meta_query->get_clauses() as $clause ) {
				if ( 'hp_booking_slot_quantity' === $clause['key'] && 'EXISTS' === $clause['compare'] ) {
					$alias = $clause['alias'];

					break;
				}
			}

			if ( $alias ) {
				$where .= " AND ( CAST( {$alias}.meta_value AS SIGNED ) * {$quantity} > ( {$subquery} ) )";
			}
		} else {
			$where .= " AND ( {$subquery} ) = 0";
		}

		return $where;
	}

	/**
	 * Sets booking order.
	 *
	 * @param string   $orderby ORDER BY clause.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function set_booking_order( $orderby, $query ) {
		if ( $query->get( 'post_type' ) === 'hp_booking' && $query->get( 'hp_sort' ) ) {
			$orderby = 'post_status ASC, ' . $orderby;
		}

		return $orderby;
	}

	/**
	 * Alters booking make form.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function alter_booking_make_form( $form_args, $form ) {

		// Get booking.
		$booking = $form->get_model();

		if ( $booking ) {

			// Get listing.
			$listing = $booking->get_listing();

			if ( $listing ) {
				$field_args = [];

				if ( $this->is_time_enabled( $listing ) || ( hivepress()->get_version( 'marketplace' ) && hivepress()->marketplace->is_recurring( $listing ) ) ) {
					$field_args['label'] = $this->is_time_enabled( $listing ) ? esc_html__( 'Date', 'hivepress-bookings' ) : esc_html__( 'Start Date', 'hivepress-bookings' );
					$field_args['type']  = 'date';
				}

				if ( $this->is_time_enabled( $listing ) ) {
					$field_args['min_date'] = date( 'Y-m-d', $this->get_shifted_time( $listing, time() ) + $listing->get_booking_offset() * DAY_IN_SECONDS );

					$form_args['fields']['_time'] = [
						'label'      => esc_html__( 'Time', 'hivepress-bookings' ),
						'type'       => 'select',
						'options'    => [],
						'source'     => hivepress()->router->get_url( 'listing_slots_resource', [ 'listing_id' => $listing->get_id() ] ),
						'required'   => true,
						'_separate'  => true,
						'_order'     => 20,

						'attributes' => [
							'data-parent'  => '_dates',

							'data-options' => wp_json_encode(
								[
									'disableSearch' => true,
									'closeOnSelect' => ! (bool) get_option( 'hp_booking_multiple_time' ),
								]
							),
						],
					];

					if ( get_option( 'hp_booking_multiple_time' ) ) {
						$form_args['fields']['_time']['description'] = esc_html__( 'Please select one or more subsequent time slots.', 'hivepress-bookings' );
						$form_args['fields']['_time']['multiple']    = true;
					}

					if ( get_option( 'hp_booking_enable_timezone' ) && $listing->get_booking_timezone() ) {
						$form_args['fields']['_timezone'] = [
							'label'     => esc_html__( 'Timezone', 'hivepress-bookings' ),
							'type'      => 'text',
							'default'   => $listing->display_booking_timezone(),
							'disabled'  => true,
							'_separate' => true,
							'_order'    => 30,

							'statuses'  => [
								'optional' => null,
							],
						];
					}
				} else {

					// Get listing IDs.
					$listing_ids = $this->get_listing_ids( $listing );

					// Get settings.
					$per_vendor  = get_option( 'hp_booking_per_vendor' );
					$is_multiple = get_option( 'hp_booking_enable_capacity' ) && $listing->get_booking_max_quantity();
					$is_daily    = get_option( 'hp_booking_enable_daily' );

					// Set minimum length.
					if ( $listing->get_booking_min_length() ) {
						$field_args['min_length'] = $listing->get_booking_min_length();
					}

					if ( $is_daily ) {
						if ( isset( $field_args['min_length'] ) ) {
							$field_args['min_length'] -= 1;
						} else {
							$field_args['min_length'] = 0;
						}
					}

					// Set maximum length.
					if ( $listing->get_booking_max_length() ) {
						$field_args['max_length'] = $listing->get_booking_max_length();

						if ( $is_daily ) {
							$field_args['max_length'] -= 1;

							if ( $listing->get_booking_max_length() === 1 ) {
								$field_args['label'] = esc_html__( 'Date', 'hivepress-bookings' );
								$field_args['type']  = 'date';
							}
						}
					}

					// Get ranges.
					$ranges = [];

					if ( get_option( 'hp_booking_enable_price' ) ) {

						// Get cached ranges.
						$ranges = null;

						if ( $per_vendor ) {
							$ranges = hivepress()->cache->get_user_cache( $listing->get_user__id(), 'date_ranges', 'models/booking_range' );
						} else {
							$ranges = hivepress()->cache->get_post_cache( $listing->get_id(), 'date_ranges', 'models/booking_range' );
						}

						if ( is_null( $ranges ) ) {
							$ranges = [];

							// Get ranges.
							$booking_ranges = Models\Booking_Range::query()->filter(
								[
									'listing__in' => $listing_ids,
								]
							)->get();

							// Add ranges.
							foreach ( $booking_ranges as $range ) {
								$ranges[] = [
									'start'  => $range->get_start_time(),
									'end'    => $range->get_end_time(),
									'label'  => $range->display_price(),
									'status' => $range->get_price() <= $listing->get_price() ? 'success' : 'warning',
								];
							}

							// Cache ranges.
							if ( $per_vendor ) {
								hivepress()->cache->set_user_cache( $listing->get_user__id(), 'date_ranges', 'models/booking_range', $ranges );
							} else {
								hivepress()->cache->set_post_cache( $listing->get_id(), 'date_ranges', 'models/booking_range', $ranges );
							}
						}
					}

					// Get cached dates.
					$disabled_dates = null;

					if ( $per_vendor ) {
						$disabled_dates = hivepress()->cache->get_user_cache( $listing->get_user__id(), 'disabled_dates', 'models/booking' );
					} else {
						$disabled_dates = hivepress()->cache->get_post_cache( $listing->get_id(), 'disabled_dates', 'models/booking' );
					}

					if ( is_null( $disabled_dates ) ) {
						$disabled_dates = [];

						// Get bookings.
						$bookings = Models\Booking::query()->filter(
							[
								'status__in'  => $this->get_blocked_statuses(),
								'listing__in' => $listing_ids,
							]
						)->order( [ 'start_time' => 'asc' ] )
						->get();

						// Get booking counts.
						$booking_counts = [];

						$prev_booking = null;

						foreach ( $bookings as $booking ) {
							$start_time = $booking->get_start_time();

							if ( ! $is_daily && ( ! $prev_booking || $prev_booking->get_end_time() < $start_time ) ) {
								$start_time += DAY_IN_SECONDS;
							}

							$booking_time     = $start_time . '-' . $booking->get_end_time();
							$booking_quantity = $is_multiple ? $booking->get_quantity() : 1;

							if ( $booking->get_status() === 'private' ) {
								$booking_counts[ $booking_time ] = 1000000;
							} else {
								$booking_counts[ $booking_time ] = hp\get_array_value( $booking_counts, $booking_time ) + $booking_quantity;
							}

							$prev_booking = $booking;
						}

						foreach ( $booking_counts as $booking_time => $booking_count ) {

							// Check availability.
							$is_booked = (bool) $booking_count;

							if ( $is_multiple ) {
								$is_booked = $booking_count >= $listing->get_booking_max_quantity();
							}

							// Add dates.
							if ( $is_booked ) {
								list($start_time, $end_time) = array_map( 'absint', explode( '-', $booking_time ) );

								if ( ! $is_daily && $start_time === $end_time ) {
									$disabled_dates[] = date( 'Y-m-d', $start_time );
								} else {
									if ( $start_time < $end_time ) {
										--$end_time;
									}

									$disabled_dates[] = [ date( 'Y-m-d', $start_time ), date( 'Y-m-d', $end_time ) ];
								}
							}
						}

						// Cache dates.
						if ( $per_vendor ) {
							hivepress()->cache->set_user_cache( $listing->get_user__id(), 'disabled_dates', 'models/booking', $disabled_dates );
						} else {
							hivepress()->cache->set_post_cache( $listing->get_id(), 'disabled_dates', 'models/booking', $disabled_dates );
						}
					}

					// Set disabled dates.
					if ( $disabled_dates ) {
						foreach ( $disabled_dates as $index => $disabled_date ) {
							if ( ! is_array( $disabled_date ) ) {
								$disabled_time = strtotime( $disabled_date );

								$ranges[] = [
									'start'  => $disabled_time - DAY_IN_SECONDS,
									'end'    => $disabled_time,
									'label'  => esc_html__( 'End date only', 'hivepress-bookings' ),
									'status' => 'error',
								];

								unset( $disabled_dates[ $index ] );
							}
						}

						$field_args['disabled_dates'] = array_values( $disabled_dates );
					}

					// Set ranges.
					if ( $ranges ) {
						$field_args['ranges'] = $ranges;
					}
				}

				// Set date offset.
				if ( $listing->get_booking_offset() ) {
					$field_args['offset'] = $listing->get_booking_offset();
				}

				// Set date window.
				if ( $listing->get_booking_window() ) {
					$field_args['window'] = $listing->get_booking_window();
				}

				// Set disabled days.
				if ( $listing->get_booking_days() ) {
					$field_args['disabled_days'] = array_diff( [ 0, 1, 2, 3, 4, 5, 6 ], $listing->get_booking_days() );
				}

				// Set field arguments.
				$form_args['fields']['_dates'] = array_merge( $form_args['fields']['_dates'], $field_args );

				// Set button label.
				if ( $listing->is_booking_moderated() ) {
					$form_args['button']['label'] = esc_html__( 'Request to Book', 'hivepress-bookings' );
				}

				if ( get_option( 'hp_booking_enable_quantity' ) ) {

					// Get minimum quantity.
					$min_quantity = 1;

					if ( $listing->get_booking_min_quantity() ) {
						$min_quantity = $listing->get_booking_min_quantity();
					}

					// Add quantity field.
					$form_args['fields']['_quantity'] = [
						'label'     => hivepress()->translator->get_string( 'places' ),
						'type'      => 'number',
						'max_value' => $listing->get_booking_max_quantity(),
						'min_value' => $min_quantity,
						'default'   => $min_quantity,
						'required'  => true,
						'_separate' => true,
						'_order'    => 30,
					];
				}

				if ( get_option( 'hp_listing_allow_price_tiers' ) && $listing->get_price_tiers() ) {

					// Set field arguments.
					$field_args = [
						'type'      => 'radio',
						'options'   => [],
						'default'   => 0,
						'required'  => true,
						'_separate' => true,
						'_order'    => 100,
					];

					if ( get_option( 'hp_listing_allow_price_extras' ) && $listing->get_price_extras() ) {
						$field_args['label'] = esc_html__( 'Tiers', 'hivepress-bookings' );
					}

					foreach ( $listing->get_price_tiers() as $index => $item ) {
						$field_args['options'][ $index ] = [
							/* translators: 1: tier name, 2: tier price. */
							'label'       => esc_html( sprintf( _x( '%1$s (%2$s)', 'pricing tier format', 'hivepress-bookings' ), $item['name'], hivepress()->woocommerce->format_price( $item['price'] ) ) ),
							'description' => hp\get_array_value( $item, 'description' ),
						];
					}

					// Add field.
					$form_args['fields']['_tier'] = $field_args;
				}

				if ( get_option( 'hp_listing_allow_price_extras' ) && $listing->get_price_extras() ) {

					// Set field arguments.
					$field_args = [
						'type'      => 'checkboxes',
						'options'   => [],
						'default'   => [],
						'_separate' => true,
						'_order'    => 110,
					];

					if ( get_option( 'hp_listing_allow_price_tiers' ) && $listing->get_price_tiers() ) {
						$field_args['label'] = esc_html__( 'Extras', 'hivepress-bookings' );
					}

					foreach ( $listing->get_price_extras() as $index => $item ) {
						$field_args['options'][ $index ] = [
							/* translators: 1: extra name, 2: extra price. */
							'label' => esc_html(
								sprintf(
									_x( '%1$s (%2$s %3$s)', 'price extra format', 'hivepress-bookings' ),
									$item['name'],
									hivepress()->woocommerce->format_price( $item['price'] ),
									hp\get_array_value( $this->get_price_options(), hp\get_array_value( $item, 'type' ) )
								)
							),
						];

						// @todo remove when added to marketplace (also styles).
						if ( hp\get_array_value( $item, 'required' ) ) {
							$field_args['options'][ $index ]['attributes'] = [
								'class' => [ 'hp-field--readonly' ],
							];

							$field_args['default'][] = $index;
						}
					}

					// Add extras field.
					$form_args['fields']['_extras'] = $field_args;
				}
			}
		}

		return $form_args;
	}

	/**
	 * Alters booking confirm form.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function alter_booking_confirm_form( $form_args, $form ) {

		// Get booking.
		$booking = $form->get_model();

		if ( $booking ) {

			// Get listing.
			$listing = $booking->get_listing();

			if ( $listing ) {
				$field_args = [];

				// Get settings.
				$is_payment = hivepress()->get_version( 'marketplace' ) && ! is_null( $listing->get_price() );

				// Set date offset.
				if ( $listing->get_booking_offset() ) {
					$field_args['offset'] = $listing->get_booking_offset();
				}

				// Set date window.
				if ( $listing->get_booking_window() ) {
					$field_args['window'] = $listing->get_booking_window();
				}

				if ( $this->is_time_enabled( $listing ) ) {

					// Allow setting time.
					$field_args['time'] = true;

					if ( get_option( 'hp_booking_enable_timezone' ) && $listing->get_booking_timezone() ) {
						$form_args['fields']['_timezone'] = [
							'label'     => esc_html__( 'Timezone', 'hivepress-bookings' ),
							'type'      => 'text',
							'default'   => $listing->display_booking_timezone(),
							'disabled'  => true,
							'_separate' => true,
							'_order'    => 30,

							'statuses'  => [
								'optional' => null,
							],
						];
					}
				}

				// Set field arguments.
				$form_args['fields']['start_time'] = array_merge( $form_args['fields']['start_time'], $field_args );
				$form_args['fields']['end_time']   = array_merge( $form_args['fields']['end_time'], $field_args );

				if ( $this->is_recurring( $booking ) ) {
					$form_args['fields']['end_time']['display_type'] = 'hidden';
				}

				// Disable quantity field.
				if ( get_option( 'hp_booking_enable_quantity' ) ) {
					$form_args['fields']['quantity']['disabled'] = true;
				}

				// Set button label.
				if ( $listing->is_booking_moderated() ) {
					$form_args['button']['label'] = esc_html__( 'Send Request', 'hivepress-bookings' );
				} elseif ( $is_payment ) {
					$form_args['button']['label'] = esc_html__( 'Proceed to Payment', 'hivepress-bookings' );
				}

				// Add price field.
				if ( $is_payment && isset( WC()->cart ) && ! WC()->cart->is_empty() ) {
					$price = WC()->cart->get_total();

					// @todo move to Marketplace for consistency.
					if ( hivepress()->marketplace->is_recurring( $listing ) ) {
						$price .= ' / ' . $listing->display_subscription_period();
					}

					$form_args['fields']['_price'] = [
						'label'     => hivepress()->translator->get_string( 'price' ),
						'type'      => 'text',
						'value'     => $price,
						'disabled'  => true,
						'_separate' => true,
						'_order'    => 30,

						'statuses'  => [
							'optional' => null,
						],
					];
				}
			}
		}

		// Get terms page ID.
		$page_id = absint( get_option( 'hp_page_booking_terms' ) );

		if ( $page_id ) {

			// Get terms page URL.
			$page_url = get_permalink( $page_id );

			if ( $page_url ) {

				// Add terms field.
				$form_args['fields']['_terms'] = [
					'caption'   => sprintf( hivepress()->translator->get_string( 'i_agree_to_terms_and_conditions' ), esc_url( $page_url ) ),
					'type'      => 'checkbox',
					'required'  => true,
					'_separate' => true,
					'_order'    => 1000,
				];
			}
		}

		return $form_args;
	}

	/**
	 * Alters listing search form.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function alter_listing_search_form( $form_args, $form ) {
		$field_names = (array) get_option( 'hp_listing_search_fields' );

		if ( in_array( 'dates', $field_names ) ) {
			$field_args = [
				'placeholder' => esc_html__( 'Dates', 'hivepress-bookings' ),
				'type'        => 'date_range',
				'offset'      => 0,
				'min_length'  => 1,
				'_order'      => 25,
			];

			if ( get_option( 'hp_booking_enable_daily' ) || get_option( 'hp_booking_enable_time' ) ) {
				$field_args['min_length'] = 0;
			}

			if ( $form::get_meta( 'name' ) !== 'listing_search' ) {
				$field_args['display_type'] = 'hidden';
			}

			$form_args['fields']['_dates'] = $field_args;
		}

		if ( in_array( 'quantity', $field_names ) ) {
			$field_args = [
				'placeholder' => hivepress()->translator->get_string( 'places' ),
				'type'        => 'number',
				'min_value'   => 1,
				'max_value'   => 100,
				'_order'      => 27,
			];

			if ( $form::get_meta( 'name' ) !== 'listing_search' ) {
				$field_args['display_type'] = 'hidden';
			}

			$form_args['fields']['_quantity'] = $field_args;
		}

		return $form_args;
	}

	/**
	 * Alters listing manage menu.
	 *
	 * @param array  $items Menu items.
	 * @param object $menu Menu object.
	 * @return array
	 */
	public function alter_listing_manage_menu( $items, $menu ) {

		// Get listing.
		$listing = $menu->get_context( 'listing' );

		if ( $listing && get_current_user_id() === $listing->get_user__id() ) {

			// Get cached booking count.
			$booking_count = hivepress()->cache->get_post_cache( $listing->get_id(), 'booking_count', 'models/booking' );

			if ( is_null( $booking_count ) ) {

				// Get booking count.
				$booking_count = Models\Booking::query()->filter(
					[
						'status__in' => [ 'trash', 'draft', 'pending', 'publish' ],
						'listing'    => $listing->get_id(),
					]
				)->get_count();

				// Cache booking count.
				hivepress()->cache->set_post_cache( $listing->get_id(), 'booking_count', 'models/booking', $booking_count );
			}

			// Add menu items.
			if ( $booking_count ) {
				$items['listing_bookings'] = [
					'label'  => esc_html__( 'Bookings', 'hivepress-bookings' ),
					'url'    => hivepress()->router->get_url( 'listing_bookings_page', [ 'listing_id' => $listing->get_id() ] ),
					'_order' => 50,
				];
			}

			if ( ! get_option( 'hp_booking_per_vendor' ) && $this->is_booking_enabled( $listing ) ) {
				$items['listing_calendar'] = [
					'label'  => esc_html__( 'Calendar', 'hivepress-bookings' ),
					'url'    => hivepress()->router->get_url( 'listing_calendar_page', [ 'listing_id' => $listing->get_id() ] ),
					'_order' => 40,
				];
			}
		}

		return $items;
	}

	/**
	 * Alters user account menu.
	 *
	 * @param array $menu Menu arguments.
	 * @return array
	 */
	public function alter_user_account_menu( $menu ) {

		// Add calendar page.
		if ( hivepress()->request->get_context( 'listing_count' ) ) {
			$menu['items']['vendor_calendar'] = [
				'route'  => 'vendor_calendar_page',
				'_order' => 25,
			];
		}

		// Add bookings page.
		if ( hivepress()->request->get_context( 'booking_count' ) ) {
			$item_args = [
				'route'  => 'bookings_view_page',
				'_order' => 27,
			];

			$item_meta = hivepress()->request->get_context( 'booking_request_count' ) + hivepress()->request->get_context( 'booking_unpaid_count' );

			if ( $item_meta ) {
				$item_args['meta'] = $item_meta;
			}

			$menu['items']['bookings_view'] = $item_args;
		}

		return $menu;
	}

	/**
	 * Alters listing edit block.
	 *
	 * @param array  $blocks Block arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_listing_edit_block( $blocks, $template ) {

		// Check settings.
		if ( get_option( 'hp_booking_per_vendor' ) ) {
			return $blocks;
		}

		// Get listing.
		$listing = $template->get_context( 'listing' );

		if ( $listing && $this->is_booking_enabled( $listing ) ) {
			$blocks = hp\merge_trees(
				[ 'blocks' => $blocks ],
				[
					'blocks' => [
						'listing_actions_primary' => [
							'blocks' => [
								'listing_calendar_link' => [
									'type'   => 'part',
									'path'   => 'listing/edit/block/listing-calendar-link',
									'_order' => 20,
								],
							],
						],
					],
				]
			)['blocks'];
		}

		return $blocks;
	}

	/**
	 * Alters listing view page.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_view_page( $template ) {
		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'page_sidebar' => [
						'blocks' => [
							'booking_make_form' => [
								'type'       => 'booking_make_form',
								'_label'     => esc_html__( 'Booking Form', 'hivepress-bookings' ),
								'_order'     => 15,

								'attributes' => [
									'class' => [ 'hp-form--narrow', 'hp-widget', 'widget', 'widget--sidebar' ],
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Alters listing view page blocks.
	 *
	 * @param array  $blocks Block arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_listing_view_page_blocks( $blocks, $template ) {

		// Get listing.
		$listing = $template->get_context( 'listing' );

		// Get block name.
		$block_name = 'booking_make_form';

		if ( $listing && $this->is_booking_enabled( $listing ) ) {
			$block_name = 'listing_buy_form';
		}

		return hp\merge_trees(
			[ 'blocks' => $blocks ],
			[
				'blocks' => [
					$block_name => [
						'type' => 'content',
					],
				],
			]
		)['blocks'];
	}
}
