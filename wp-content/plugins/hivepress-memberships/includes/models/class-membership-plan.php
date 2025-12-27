<?php
/**
 * Membership plan model.
 *
 * @package HivePress\Models
 */

namespace HivePress\Models;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership plan model class.
 *
 * @class Membership_Plan
 */
class Membership_Plan extends Post {

	/**
	 * Class constructor.
	 *
	 * @param array $args Model arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'fields' => [
					'name'          => [
						'type'       => 'text',
						'max_length' => 256,
						'required'   => true,
						'_alias'     => 'post_title',
					],

					'description'   => [
						'type'       => 'textarea',
						'max_length' => 10240,
						'html'       => true,
						'_alias'     => 'post_content',
					],

					'status'        => [
						'type'       => 'text',
						'max_length' => 128,
						'_alias'     => 'post_status',
					],

					'product'       => [
						'type'   => 'id',
						'_alias' => 'post_parent',
					],

					'sort_order'    => [
						'type'      => 'number',
						'min_value' => 0,
						'_alias'    => 'menu_order',
					],

					'expire_period' => [
						'type'      => 'number',
						'min_value' => 1,
						'_external' => true,
					],

					'primary'       => [
						'type'      => 'checkbox',
						'_external' => true,
					],

					'view_pages'    => [
						'type'        => 'select',
						'options'     => 'posts',
						'option_args' => [ 'post_type' => 'page' ],
						'multiple'    => true,
						'_external'   => true,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Gets product price.
	 *
	 * @return string
	 */
	final public function display_product__price() {
		$price = esc_html( hivepress()->translator->get_string( 'free' ) );

		if ( hp\is_plugin_active( 'woocommerce' ) && $this->get_product__id() ) {

			// Get product.
			$product = wc_get_product( $this->get_product__id() );

			if ( $product ) {

				// Get price.
				$price = $product->get_price_html();
			}
		}

		return $price;
	}
}
