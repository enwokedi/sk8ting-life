<?php
/**
 * Search alert model.
 *
 * @package HivePress\Models
 */

namespace HivePress\Models;

use HivePress\Helpers as hp;
use HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert model class.
 *
 * @class Search_Alert
 */
class Search_Alert extends Comment {

	/**
	 * Class constructor.
	 *
	 * @param array $args Model arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'fields' => [
					'query'        => [
						'type'       => 'text',
						'max_length' => 256,
						'_alias'     => 'comment_author',
					],

					'key'          => [
						'type'       => 'text',
						'max_length' => 64,
						'required'   => true,
						'_alias'     => 'comment_author_url',
					],

					'params'       => [
						'type'       => 'text',
						'max_length' => 10240,
						'required'   => true,
						'_alias'     => 'comment_content',
					],

					'checked_date' => [
						'type'   => 'date',
						'format' => 'Y-m-d H:i:s',
						'_alias' => 'comment_date',
					],

					'found_time'   => [
						'type'      => 'number',
						'min_value' => 0,
						'_external' => true,
					],

					'category'     => [
						'type'   => 'id',
						'_alias' => 'comment_karma',

						// @todo add support for request categories.
						'_model' => 'listing_category',
					],

					'user'         => [
						'type'     => 'id',
						'required' => true,
						'_alias'   => 'user_id',
						'_model'   => 'user',
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Gets category name.
	 *
	 * @return string
	 */
	public function get_category__name() {

		// Check category.
		if ( ! $this->get_category__id() ) {
			return;
		}

		// Get model name.
		$model = hivepress()->search_alert->get_model_name( $this->get_params() );

		if ( ! $model ) {
			return;
		}

		// Get category.
		$category = hivepress()->model->get_model_object( $model . '_category', $this->get_category__id() );

		if ( ! $category ) {
			return;
		}

		return $category->get_name();
	}

	/**
	 * Sets parameters.
	 *
	 * @param mixed $params Parameters.
	 */
	public function set_params( $params ) {
		if ( is_array( $params ) ) {
			$params = wp_json_encode( $params, JSON_UNESCAPED_UNICODE );
		}

		$this->fields['params']->set_value( $params );
	}

	/**
	 * Gets parameters.
	 *
	 * @return array
	 */
	public function get_params() {
		$params = json_decode( $this->fields['params']->get_value(), true );

		if ( ! is_array( $params ) ) {
			$params = [];
		} elseif ( ! isset( $params['s'] ) ) {
			$params['s'] = '';
		}

		return $params;
	}

	/**
	 * Displays parameters.
	 *
	 * @return string
	 */
	public function display_params() {
		$output = '';

		// Get parameters.
		$params = $this->get_params();

		// Create forms.
		$filter_form = hivepress()->search_alert->get_model_form( $params );
		$search_form = hivepress()->search_alert->get_model_form( $params, 'search' );

		if ( ! $filter_form || ! $search_form ) {
			return $output;
		}

		// Get fields.
		$fields = array_merge(
			$filter_form->get_fields(),
			$search_form->get_fields()
		);

		unset( $fields['s'] );
		unset( $fields['_category'] );

		// Render fields.
		foreach ( $fields as $field ) {

			// Get field label.
			$label = $field->get_label() ? $field->get_label() : $field->get_arg( 'placeholder' );

			if ( $label ) {

				// Get field value.
				$value = $field->get_display_value();

				if ( ! is_null( $value ) ) {
					$output .= '<div><strong>' . esc_html( $label ) . ':</strong> <span>' . $value . '</span></div>';
				}
			}
		}

		return $output;
	}
}
