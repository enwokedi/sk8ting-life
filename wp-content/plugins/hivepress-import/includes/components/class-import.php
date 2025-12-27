<?php
/**
 * Import component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Emails;
use HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Import component class.
 *
 * @class Import
 */
final class Import extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Delete attachments.
		add_action( 'hivepress/v1/events/daily', [ $this, 'delete_old_attachments' ] );

		// Alter forms.
		add_filter( 'hivepress/v1/forms/listing_import_process', [ $this, 'alter_listing_import_process_form' ] );
		add_filter( 'hivepress/v1/forms/listing_export', [ $this, 'alter_listing_export_form' ] );

		// Validate forms.
		add_filter( 'hivepress/v1/forms/listing_import_process/errors', [ $this, 'validate_listing_import_process_form' ], 10, 2 );

		// Alter menus.
		add_filter( 'hivepress/v1/menus/user_account', [ $this, 'alter_user_account_menu' ] );

		// Alter templates.
		add_filter( 'hivepress/v1/templates/listings_edit_page', [ $this, 'alter_listings_edit_page' ] );

		parent::__construct( $args );
	}

	/**
	 * Check if import is allowed.
	 *
	 * @return bool
	 */
	public function is_allowed() {
		return current_user_can( 'import' ) || ( current_user_can( 'edit_posts' ) && get_option( 'hp_listing_allow_import' ) );
	}

	/**
	 * Gets CSV delimiter.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function get_delimiter( $attachment_id ) {
		$delimiter = get_post_meta( $attachment_id, 'hp_import_delimiter', true );

		if ( ! $delimiter ) {
			$delimiter = ',';
		}

		return $delimiter;
	}

	/**
	 * Gets CSV columns.
	 *
	 * @param resource $file File pointer.
	 * @param string   $delimiter CSV delimiter.
	 * @return array
	 */
	public function get_columns( $file, $delimiter ) {
		$columns = fgetcsv( $file, 0, $delimiter );

		if ( $columns ) {
			$columns = array_map( 'trim', $columns );
		} else {
			$columns = [];
		}

		return $columns;
	}

	/**
	 * Gets import fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields  = [];
		$listing = null;

		// Get attachment.
		$attachment = $this->get_attachment();

		if ( $attachment ) {
			if ( get_post_meta( $attachment->get_id(), 'hp_import_mode', true ) ) {

				// Add ID field.
				$fields['id'] = [
					'label'       => esc_html__( 'ID', 'hivepress-import' ),
					'description' => esc_html__( 'Select the ID column for updating existing listings.', 'hivepress-import' ),
					'type'        => 'id',
					'required'    => true,
					'_order'      => 1,
				];
			}

			// Get category ID.
			$category_id = absint( get_post_meta( $attachment->get_id(), 'hp_import_category', true ) );

			if ( $category_id ) {

				// Create listing.
				$listing = ( new Models\Listing() )->set_categories( [ $category_id ] );

				// @todo remove temporary fix when updated.
				$listing->set_id( null );
			} else {

				// Add category field.
				$fields['categories'] = [
					'required' => true,
					'_order'   => 5,
				];
			}
		}

		if ( current_user_can( 'import' ) ) {

			// Add admin fields.
			$fields = array_merge(
				$fields,
				[
					'featured' => [
						'_order' => 1000,
					],

					'verified' => [
						'_order' => 1010,
					],

					'user'     => [
						'label'       => hivepress()->translator->get_string( 'user' ),
						'description' => esc_html__( 'Accepts usernames or email addresses.', 'hivepress-import' ),
						'required'    => false,
						'_order'      => 2,
					],
				]
			);
		}

		return array_filter(
			( new Forms\Listing_Update(
				[
					'fields' => $fields,
					'model'  => $listing,
				]
			) )->get_fields(),
			function( $field ) {
				return $field->get_label();
			}
		);
	}

	/**
	 * Gets import attachment.
	 *
	 * @return object
	 */
	public function get_attachment() {
		$attachment = null;

		if ( is_user_logged_in() ) {
			$attachment = hivepress()->request->get_context( 'import_attachment' );

			if ( is_null( $attachment ) ) {
				$attachment = Models\Attachment::query()->filter(
					[
						'user' => get_current_user_id(),
					]
				)->set_args(
					[
						'meta_key' => 'hp_imported',
					]
				)->get_first();

				if ( $attachment ) {
					hivepress()->request->set_context( 'import_attachment', $attachment );
				} else {
					hivepress()->request->set_context( 'import_attachment', false );
				}
			}
		}

		return $attachment;
	}

	/**
	 * Deletes old attachments.
	 */
	public function delete_old_attachments() {
		Models\Attachment::query()->set_args(
			[
				'meta_query' => [
					'relation' => 'OR',

					[
						'key'     => 'hp_imported',
						'compare' => 'EXISTS',
					],

					[
						'key'     => 'hp_exported',
						'compare' => 'EXISTS',
					],
				],

				'date_query' => [
					'before' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				],
			]
		)->delete();
	}

	/**
	 * Alters listing import process form.
	 *
	 * @param array $form Form arguments.
	 * @return array
	 */
	public function alter_listing_import_process_form( $form ) {

		// Get attachment.
		$attachment = $this->get_attachment();

		if ( ! $attachment ) {
			return $form;
		}

		// Open file.
		$file = fopen( $attachment->get_path(), 'r' );

		if ( ! $file ) {
			return $form;
		}

		// Get columns.
		$columns = array_filter( $this->get_columns( $file, $this->get_delimiter( $attachment->get_id() ) ) );

		fclose( $file );

		if ( ! $columns ) {
			return $form;
		}

		// Get field options.
		$field_options = [];

		foreach ( $columns as $column ) {
			$field_options[ hp\sanitize_key( $column ) ] = $column;
		}

		asort( $field_options );

		// Add fields.
		foreach ( $this->get_fields() as $field ) {

			// Get field type.
			$field_type = $field::get_meta( 'name' );

			// Get field arguments.
			$field_args = [
				'label'    => $field->get_label(),
				'type'     => 'select',
				'options'  => $field_options,
				'default'  => $field->get_name(),
				'required' => $field->is_required(),
			];

			if ( in_array( $field->get_name(), [ 'id', 'user' ] ) ) {
				$field_args['description'] = $field->get_description();
			} elseif ( is_array( $field->get_arg( 'options' ) ) ) {
				$field_labels = array_map(
					function( $option ) {
						return hp\get_array_value( $option, 'label', $option );
					},
					$field->get_arg( 'options' )
				);

				/* translators: %s: option labels. */
				$field_args['description'] = sprintf( esc_html__( 'Accepts any of these values: %s.', 'hivepress-import' ), implode( ', ', $field_labels ) );
			} elseif ( 'attachment_upload' === $field_type ) {

				/* translators: %s: file formats. */
				$field_args['description'] = sprintf( esc_html__( 'Accepts URLs of these file formats: %s.', 'hivepress-import' ), strtoupper( implode( ', ', $field->get_formats() ) ) );
			} elseif ( 'date' === $field_type ) {

				// @todo Remove duplication after updating the core.
				$field_format = 'Y-m-d';

				if ( $field->get_arg( 'format' ) ) {
					$field_format = $field->get_arg( 'format' );
				} elseif ( $field->get_arg( 'time' ) ) {
					$field_format .= ' H:i:s';
				}

				/* translators: %s: date format. */
				$field_args['description'] = sprintf( esc_html__( 'Accepts dates in "%s" format.', 'hivepress-import' ), $field_format );
			} elseif ( 'embed' === $field_type ) {
				$field_args['description'] = esc_html__( 'Accepts the embed URLs.', 'hivepress-import' );
			} elseif ( 'checkbox' === $field_type ) {

				/* translators: %s: allowed value. */
				$field_args['description'] = sprintf( esc_html__( 'Accepts "%s" or empty values.', 'hivepress-import' ), 1 );
			} elseif ( 'time' === $field_type ) {
				$field_args['description'] = esc_html__( 'Accepts times in seconds.', 'hivepress-import' );
			} elseif ( 'url' === $field_type ) {
				$field_args['description'] = esc_html__( 'Accepts URLs.', 'hivepress-import' );
			}

			// Add field.
			$form['fields'][ $field->get_name() ] = $field_args;
		}

		return $form;
	}

	/**
	 * Alters listing export form.
	 *
	 * @param array $form Form arguments.
	 * @return array
	 */
	public function alter_listing_export_form( $form ) {
		if ( current_user_can( 'import' ) ) {
			$form['fields']['user'] = [
				'label'       => hivepress()->translator->get_string( 'user' ),
				'placeholder' => hivepress()->translator->get_string( 'all_users' ),
				'type'        => 'select',
				'options'     => 'users',
				'source'      => hivepress()->router->get_url( 'users_resource' ),
				'_order'      => 1,
			];
		}

		return $form;
	}

	/**
	 * Validate import proccess form.
	 *
	 * @param array  $errors Form errors.
	 * @param object $form Form object.
	 * @return array
	 */
	public function validate_listing_import_process_form( $errors, $form ) {

		// Check duplicate values.
		$values = array_filter( $form->get_values() );

		if ( count( $values ) > count( array_unique( $values ) ) ) {
			$errors[] = esc_html__( "The same field can't be mapped to multiple columns.", 'hivepress-import' );
		}

		return $errors;
	}

	/**
	 * Alters user account menu.
	 *
	 * @param array $menu Menu arguments.
	 * @return array
	 */
	public function alter_user_account_menu( $menu ) {
		if ( $this->is_allowed() && ! hivepress()->request->get_context( 'listing_count' ) ) {
			$menu['items']['listing_import'] = [
				'label'  => hivepress()->translator->get_string( 'import_listings' ),
				'route'  => 'listing_import_page',
				'_order' => 60,
			];
		}

		return $menu;
	}

	/**
	 * Alters listings edit page.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listings_edit_page( $template ) {
		if ( $this->is_allowed() ) {
			$template = hp\merge_trees(
				$template,
				[
					'blocks' => [
						'page_content' => [
							'blocks' => [
								'listing_import_link' => [
									'type'   => 'part',
									'path'   => 'listing/import/listing-import-link',
									'_label' => esc_html__( 'Import Button', 'hivepress-import' ),
									'_order' => 20,
								],

								'listing_export_link' => [
									'type'   => 'part',
									'path'   => 'listing/import/listing-export-link',
									'_label' => esc_html__( 'Export Button', 'hivepress-import' ),
									'_order' => 30,
								],
							],
						],
					],
				]
			);
		}

		return $template;
	}
}
