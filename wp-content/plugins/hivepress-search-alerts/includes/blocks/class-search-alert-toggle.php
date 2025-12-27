<?php
/**
 * Search alert toggle block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;
use HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert toggle block class.
 *
 * @class Search_Alert_Toggle
 */
class Search_Alert_Toggle extends Toggle {

	/**
	 * Class constructor.
	 *
	 * @param array $args Block arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'states' => [
					[
						'icon'    => 'bell',
						'caption' => esc_html__( 'Set Search Alert', 'hivepress-search-alerts' ),
					],
					[
						'icon'    => 'bell-slash',
						'caption' => esc_html__( 'Delete Search Alert', 'hivepress-search-alerts' ),
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps block properties.
	 */
	protected function boot() {

		// Get form.
		$form = hivepress()->search_alert->get_model_form( $_GET );

		if ( $form ) {

			// Get parameters.
			$params = array_filter(
				$form->get_values(),
				function( $param ) {
					return ! is_null( $param );
				}
			);

			ksort( $params );

			// Get key.
			$key = hivepress()->search_alert->get_alert_key( $params );

			// Set URL.
			$this->url = add_query_arg( $params, hivepress()->router->get_url( 'search_alert_update_action' ) );

			// Set active flag.
			if ( in_array( $key, hivepress()->request->get_context( 'search_alert_keys', [] ), true ) ) {
				$this->active = true;
			}
		}

		parent::boot();
	}

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		if ( is_search() && ( $this->active || count( hivepress()->request->get_context( 'search_alert_keys', [] ) ) < 10 ) ) {
			$output = parent::render();
		}

		return $output;
	}
}
