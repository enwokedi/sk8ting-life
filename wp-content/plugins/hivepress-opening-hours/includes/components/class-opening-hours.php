<?php
/**
 * Opening hours component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Opening hours component class.
 *
 * @class Opening_Hours
 */
final class Opening_Hours extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Update listings.
		add_action( 'hivepress/v1/activate', [ $this, 'upgrade_listings' ] );
		add_action( 'hivepress/v1/models/listing/update', [ $this, 'update_listing' ], 10, 2 );

		// Add attributes.
		add_filter( 'hivepress/v1/models/listing/attributes', [ $this, 'add_attributes' ] );

		// Add search fields.
		add_filter( 'hivepress/v1/forms/listing_filter', [ $this, 'add_search_fields' ], 10, 2 );
		add_filter( 'hivepress/v1/forms/listing_sort', [ $this, 'add_search_fields' ], 10, 2 );

		// Set search query.
		add_action( 'hivepress/v1/models/listing/search', [ $this, 'set_search_query' ] );

		// Alter templates.
		add_filter( 'hivepress/v1/templates/listing_view_page', [ $this, 'alter_listing_view_page' ] );

		parent::__construct( $args );
	}

	/**
	 * Upgrades listings.
	 */
	public function upgrade_listings() {
		global $wpdb;

		// @deprecated since version 1.1.0
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key IN (
					'hp_monday_from',
					'hp_tuesday_from',
					'hp_wednesday_from',
					'hp_thursday_from',
					'hp_friday_from',
					'hp_saturday_from',
					'hp_sunday_from',
					'hp_monday_to',
					'hp_tuesday_to',
					'hp_wednesday_to',
					'hp_thursday_to',
					'hp_friday_to',
					'hp_saturday_to',
					'hp_sunday_to'
				) AND CAST(meta_value AS SIGNED) <= %d;",
				1440
			),
			ARRAY_A
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				update_post_meta( $result['post_id'], $result['meta_key'], $result['meta_value'] * 60 );
			}
		}

		// @deprecated since version 1.2.0
		$row = $wpdb->get_row(
			"SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key = 'hp_opening_hours';"
		);

		if ( ! $row ) {
			$days = [
				'monday',
				'tuesday',
				'wednesday',
				'thursday',
				'friday',
				'saturday',
				'sunday',
			];

			foreach ( Models\Listing::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
				]
			)->get_ids() as $listing_id ) {
				$ranges = [];

				foreach ( $days as $day ) {
					$from = get_post_meta( $listing_id, hp\prefix( $day . '_from' ), true );
					$to   = get_post_meta( $listing_id, hp\prefix( $day . '_to' ), true );

					if ( ! strlen( $from ) || ! strlen( $to ) ) {
						continue;
					}

					$ranges[] = [
						'day'  => $day,
						'from' => $from,
						'to'   => $to,
					];
				}

				if ( ! $ranges ) {
					continue;
				}

				update_post_meta( $listing_id, 'hp_opening_hours', $ranges );
			}
		}
	}

	/**
	 * Updates listing.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param object $listing Listing object.
	 */
	public function update_listing( $listing_id, $listing ) {

		// Set days.
		$days = [
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
		];

		// Delete ranges.
		foreach ( $days as $day ) {
			delete_post_meta( $listing_id, hp\prefix( $day . '_from' ) );
			delete_post_meta( $listing_id, hp\prefix( $day . '_to' ) );
		}

		// Get ranges.
		$ranges = $listing->get_opening_hours();

		if ( $listing->is_always_open() ) {
			$ranges = [];

			foreach ( $days as $day ) {
				$ranges[] = [
					'day'  => $day,
					'from' => 0,
					'to'   => 86340,
				];
			}

			// Delete ranges.
			delete_post_meta( $listing_id, hp\prefix( 'opening_hours' ) );
		}

		if ( ! $ranges ) {
			return;
		}

		// Add ranges.
		foreach ( $ranges as $range ) {
			add_post_meta( $listing_id, hp\prefix( $range['day'] . '_from' ), $range['from'] );

			if ( $range['from'] > $range['to'] ) {
				add_post_meta( $listing_id, hp\prefix( $range['day'] . '_to' ), 86340 );

				// Get next day.
				$next_day = $days[ ( array_search( $range['day'], $days ) + 1 ) % 7 ];

				// Add next day range.
				add_post_meta( $listing_id, hp\prefix( $next_day . '_from' ), 0 );
				add_post_meta( $listing_id, hp\prefix( $next_day . '_to' ), $range['to'] );
			} else {
				add_post_meta( $listing_id, hp\prefix( $range['day'] . '_to' ), $range['to'] );
			}
		}
	}

	/**
	 * Adds attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return array
	 */
	public function add_attributes( $attributes ) {
		$attributes['opening_hours'] = [
			'editable'   => true,

			'edit_field' => [
				'label'  => esc_html__( 'Opening Hours', 'hivepress-opening-hours' ),
				'type'   => 'opening_hours',
				'_order' => 190,
			],
		];

		$attributes['always_open'] = [
			'editable'   => true,

			'edit_field' => [
				'label'   => ' ',
				'caption' => esc_html__( 'Open 24/7', 'hivepress-opening-hours' ),
				'type'    => 'checkbox',
				'_order'  => 190,
			],
		];

		return $attributes;
	}

	/**
	 * Adds search fields.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function add_search_fields( $form_args, $form ) {

		// Set field arguments.
		$field_args = [
			'caption' => esc_html__( 'Open now', 'hivepress-opening-hours' ),
			'type'    => 'checkbox',
			'_order'  => 1000,
		];

		if ( $form::get_meta( 'name' ) === 'listing_sort' ) {
			$field_args['display_type'] = 'hidden';
		}

		// Add field.
		$form_args['fields']['_open'] = $field_args;

		return $form_args;
	}

	/**
	 * Sets search query.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function set_search_query( $query ) {

		// Check filter.
		if ( ! hp\get_array_value( $_GET, '_open' ) ) {
			return;
		}

		// Get day and time.
		$day  = strtolower( current_time( 'l' ) );
		$time = current_time( 'timestamp' ) - strtotime( 'today' );

		// Get meta query.
		$meta_query = array_filter( (array) $query->get( 'meta_query' ) );

		// Add meta clause.
		$meta_query[] = [
			'relation' => 'AND',

			[
				'key'     => hp\prefix( $day . '_from' ),
				'value'   => $time,
				'compare' => '<',
				'type'    => 'NUMERIC',
			],
			[
				'key'     => hp\prefix( $day . '_to' ),
				'value'   => $time,
				'compare' => '>',
				'type'    => 'NUMERIC',
			],
		];

		// Set meta query.
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Alters listing view page.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_view_page( $template ) {
		return hivepress()->template->merge_blocks(
			$template,
			[
				'page_sidebar' => [
					'blocks' => [
						'listing_opening_hours' => [
							'type'   => 'opening_hours',
							'model'  => 'listing',
							'_label' => esc_html__( 'Opening Hours', 'hivepress-opening-hours' ),
							'_order' => 23,
						],
					],
				],
			]
		);
	}
}
