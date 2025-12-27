<?php
/**
 * Membership reveal form block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership reveal form block class.
 *
 * @class Membership_Reveal_Form
 */
class Membership_Reveal_Form extends Form {

	/**
	 * Model name.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Class constructor.
	 *
	 * @param array $args Block arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'form' => 'membership_reveal',
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps block properties.
	 */
	protected function boot() {

		// Get model ID.
		$id = 0;

		if ( $this->model ) {
			$object = $this->get_context( $this->model );

			if ( hp\is_class_instance( $object, '\HivePress\Models\\' . $this->model ) ) {
				$id = $object->get_id();
			}
		}

		// Set form values.
		$this->values['id']    = $id;
		$this->values['model'] = $this->model;

		parent::boot();
	}
}
