<?php
/**
 * Search alerts block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alerts block class.
 *
 * @class Search_Alerts
 */
class Search_Alerts extends Block {

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		if ( is_user_logged_in() ) {

			// Get alerts.
			$alerts = Models\Search_Alert::query()->filter(
				[
					'user' => get_current_user_id(),
				]
			)->order( [ 'id' => 'desc' ] )
			->get()
			->serialize();

			// Render alerts.
			if ( $alerts ) {
				$output .= '<table class="hp-search-alerts hp-table hp-block">';

				foreach ( $alerts as $alert ) {
					$output .= ( new Template(
						[
							'template' => 'search_alert_view_block',

							'context'  => [
								'search_alert' => $alert,
							],
						]
					) )->render();
				}

				$output .= '</table>';
			}
		}

		return $output;
	}
}
