<?php
/**
 * Search alert view block template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert view block template class.
 *
 * @class Search_Alert_View_Block
 */
class Search_Alert_View_Block extends Template {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'search_alert_container' => [
						'type'       => 'container',
						'tag'        => 'tr',
						'_order'     => 10,

						'attributes' => [
							'class' => [ 'hp-search-alert', 'hp-search-alert--view-block' ],
						],

						'blocks'     => [
							'search_alert_query'           => [
								'type'   => 'part',
								'path'   => 'search-alert/view/search-alert-query',
								'_order' => 10,
							],

							'search_alert_category'        => [
								'type'   => 'part',
								'path'   => 'search-alert/view/search-alert-category',
								'_order' => 20,
							],

							'search_alert_params'          => [
								'type'   => 'part',
								'path'   => 'search-alert/view/search-alert-params',
								'_order' => 30,
							],

							'search_alert_actions_primary' => [
								'type'       => 'container',
								'tag'        => 'td',
								'_order'     => 100,

								'attributes' => [
									'class' => [ 'hp-search-alert__actions', 'hp-search-alert__actions--primary' ],
								],

								'blocks'     => [
									'search_alert_delete_modal' => [
										'type'        => 'modal',
										'model'       => 'search_alert',
										'title'       => esc_html__( 'Delete Search', 'hivepress-search-alerts' ),
										'_capability' => 'read',
										'_order'      => 5,

										'blocks'      => [
											'search_alert_delete_form' => [
												'type'   => 'form',
												'form'   => 'search_alert_delete',
												'_order' => 10,
											],
										],
									],

									'search_alert_view_link'  => [
										'type'   => 'part',
										'path'   => 'search-alert/view/search-alert-view-link',
										'_order' => 10,
									],

									'search_alert_delete_link'  => [
										'type'   => 'part',
										'path'   => 'search-alert/view/search-alert-delete-link',
										'_order' => 20,
									],
								],
							],
						],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
