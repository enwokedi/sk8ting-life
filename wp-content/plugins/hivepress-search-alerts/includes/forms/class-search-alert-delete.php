<?php
/**
 * Search alert delete form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert delete form class.
 *
 * @class Search_Alert_Delete
 */
class Search_Alert_Delete extends Model_Form {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'model' => 'search_alert',
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'description' => esc_html__( 'Are you sure you want to delete this search?', 'hivepress-search-alerts' ),
				'method'      => 'DELETE',
				'redirect'    => true,

				'button'      => [
					'label' => esc_html__( 'Delete Search', 'hivepress-search-alerts' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps form properties.
	 */
	protected function boot() {

		// Set action.
		if ( $this->model->get_id() ) {
			$this->action = hivepress()->router->get_url(
				'search_alert_delete_action',
				[
					'search_alert_id' => $this->model->get_id(),
				]
			);
		}

		parent::boot();
	}
}
