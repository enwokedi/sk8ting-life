<?php
/**
 * Social links block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Social links block class.
 *
 * @class Social_Links
 */
class Social_Links extends Block {

	/**
	 * Model name.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		if ( $this->model ) {

			// Get object.
			$object = $this->get_context( $this->model );

			if ( hp\is_class_instance( $object, '\HivePress\Models\\' . $this->model ) ) {

				// Get class.
				$class = '';

				if ( get_option( 'hp_' . $this->model . '_social_links_display' ) ) {
					$class = 'button button--large button--primary alt';
				}

				// Render links.
				foreach ( hivepress()->social_links->get_links( $this->model ) as $name => $args ) {

					// Get value.
					$value = call_user_func( [ $object, 'get_' . $name ] );

					if ( $value ) {

						// Format value.
						$value = esc_url( hp\get_array_value( $args, 'prefix' ) . $value );

						if ( 'viber' === $name ) {
							$value = str_replace( [ 'http:', 'https:' ], 'viber:', $value );
							$value = str_replace( '+', '', $value );
						}

						// Get slug.
						$slug = hp\sanitize_slug( $name );

						// Get icon.
						$icon = hp\get_array_value( $args, 'icon', hivepress()->get_url( 'social_links' ) . '/assets/images/icons/' . $slug . '.svg' );

						// Render link.
						$output .= '<a href="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . ' hp-social-links__item hp-social-links__item--' . esc_attr( $slug ) . '" target="_blank" rel="nofollow">';

						$output .= '<img src="' . esc_url( $icon ) . '" title="' . esc_attr( $args['label'] ) . '" alt="' . esc_attr( $args['label'] ) . '" />';
						$output .= '<span>' . esc_html( $args['label'] ) . '</span>';

						$output .= '</a>';
					}
				}

				// Add wrapper.
				if ( $output ) {
					$output = '<div class="hp-' . esc_attr( hp\sanitize_slug( $this->model ) ) . '__social-links hp-widget widget"><nav class="hp-social-links">' . $output . '</nav></div>';
				}
			}
		}

		return $output;
	}
}
