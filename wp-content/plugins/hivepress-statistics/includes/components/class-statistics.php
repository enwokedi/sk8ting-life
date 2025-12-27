<?php
/**
 * Statistics component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Statistics component class.
 *
 * @class Statistics
 */
final class Statistics extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Check settings.
		if ( ! get_option( 'hp_ganalytics_property_id' ) && ! get_option( 'hp_ganalytics_view_id' ) ) {
			return;
		}

		// Add listing attributes.
		add_filter( 'hivepress/v1/models/listing/attributes', [ $this, 'add_listing_attributes' ] );

		if ( is_admin() ) {

			// Add admin notices.
			add_filter( 'hivepress/v1/admin_notices', [ $this, 'add_admin_notices' ] );
		} else {

			// Enqueue scripts.
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Alter menus.
			add_filter( 'hivepress/v1/menus/listing_manage/items', [ $this, 'alter_listing_manage_menu' ], 100, 2 );

			// Alter templates.
			add_filter( 'hivepress/v1/templates/listing_edit_block', [ $this, 'alter_listing_edit_block' ] );
		}

		parent::__construct( $args );
	}

	/**
	 * Adds admin notices.
	 *
	 * @param array $notices Admin notices.
	 * @return array
	 */
	public function add_admin_notices( $notices ) {
		if ( ! get_option( 'hp_ganalytics_property_id' ) && get_option( 'hp_ganalytics_view_id' ) ) {
			$notices['ga3_deprecated'] = [
				'type' => 'error',
				/* translators: 1: settings link, 2: extension name. */
				'text' => sprintf( hp\sanitize_html( __( 'Universal Analytics is deprecated, please <a href="%1$s">set up</a> Google Analytics 4 for %2$s to work.', 'hivepress-statistics' ) ), admin_url( 'admin.php?page=hp_settings&tab=integrations' ), hivepress()->get_name( 'statistics' ) ),
			];
		}

		return $notices;
	}

	/**
	 * Enqueues scripts.
	 */
	public function enqueue_scripts() {

		// Check tracking ID.
		if ( ! get_option( 'hp_ganalytics_measurement_id' ) ) {
			return;
		}

		// Enqueue Google Analytics.
		wp_enqueue_script(
			'google-analytics',
			'https://www.googletagmanager.com/gtag/js?' . http_build_query(
				[
					'id' => get_option( 'hp_ganalytics_measurement_id' ),
				]
			),
			[],
			null,
			false
		);

		wp_script_add_data( 'google-analytics', 'async', true );

		// Add tracking code.
		wp_add_inline_script(
			'google-analytics',
			"
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', '" . esc_js( get_option( 'hp_ganalytics_measurement_id' ) ) . "');
			"
		);
	}

	/**
	 * Alters listing manage menu.
	 *
	 * @param array  $items Menu items.
	 * @param object $menu Menu object.
	 * @return array
	 */
	public function alter_listing_manage_menu( $items, $menu ) {
		if ( isset( $items['listing_edit'] ) ) {

			// Get listing.
			$listing = $menu->get_context( 'listing' );

			if ( hp\is_class_instance( $listing, '\HivePress\Models\Listing' ) && $listing->get_status() === 'publish' ) {
				$items['listing_statistics'] = [
					'label'  => esc_html__( 'Statistics', 'hivepress-statistics' ),
					'url'    => hivepress()->router->get_url( 'listing_statistics_page', [ 'listing_id' => $listing->get_id() ] ),
					'_order' => 50,
				];
			}
		}

		return $items;
	}

	/**
	 * Alters listing edit block.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_edit_block( $template ) {
		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'listing_actions_primary' => [
						'blocks' => [
							'listing_statistics_link' => [
								'type'   => 'part',
								'path'   => 'listing/edit/block/listing-statistics-link',
								'_order' => 20,
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Adds listing attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return array
	 */
	public function add_listing_attributes( $attributes ) {
		$attributes['view_count'] = [
			'sortable'   => true,
			'protected'  => true,

			'edit_field' => [
				'label'     => esc_html__( 'Popularity', 'hivepress-statistics' ),
				'type'      => 'number',
				'min_value' => 0,
			],
		];

		return $attributes;
	}
}
