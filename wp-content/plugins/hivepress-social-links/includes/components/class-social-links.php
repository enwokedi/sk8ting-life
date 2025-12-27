<?php
/**
 * Social links component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Social links component class.
 *
 * @class Social_Links
 */
final class Social_Links extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Add attributes.
		add_filter( 'hivepress/v1/models/listing/attributes', [ $this, 'add_attributes' ], 100 );
		add_filter( 'hivepress/v1/models/vendor/attributes', [ $this, 'add_attributes' ], 100 );

		// Alter templates.
		add_filter( 'hivepress/v1/templates/listing_view_page', [ $this, 'alter_listing_view_page' ] );
		add_filter( 'hivepress/v1/templates/vendor_view_page', [ $this, 'alter_vendor_view_page' ] );

		parent::__construct( $args );
	}

	/**
	 * Gets links.
	 *
	 * @param string $model Model name.
	 * @return array
	 */
	public function get_links( $model ) {
		$links = (array) get_option( 'hp_' . $model . '_social_links' );

		return array_filter(
			hivepress()->get_config( 'social_links' ),
			function( $link ) use ( $links ) {
				return in_array( $link, $links, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Adds attributes.
	 *
	 * @param array $attributes Attribute arguments.
	 * @return array
	 */
	public function add_attributes( $attributes ) {

		// Get model.
		$model = explode( '/', current_filter() )[3];

		// Add attributes.
		$index = 0;

		foreach ( $this->get_links( $model ) as $name => $args ) {
			$attributes[ $name ] = [
				'editable'   => true,

				'edit_field' => [
					'label'  => $args['label'],
					'type'   => hp\get_array_value( $args, 'type', 'url' ),
					'_order' => 150 + $index,
				],
			];

			$index++;
		}

		return $attributes;
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
							'listing_social_links' => [
								'type'   => 'social_links',
								'model'  => 'listing',
								'_label' => esc_html__( 'Social Links', 'hivepress-social-links' ),
								'_order' => 15,
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Alters vendor view page.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_vendor_view_page( $template ) {
		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'page_sidebar' => [
						'blocks' => [
							'vendor_social_links' => [
								'type'   => 'social_links',
								'model'  => 'vendor',
								'_label' => esc_html__( 'Social Links', 'hivepress-social-links' ),
								'_order' => 25,
							],
						],
					],
				],
			]
		);
	}
}
