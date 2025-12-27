<?php
/**
 * Import controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Forms;
use HivePress\Blocks;
use HivePress\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Import controller class.
 *
 * @class Import
 */
final class Import extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'listing_import_upload_action'  => [
						'base'   => 'listings_resource',
						'path'   => '/upload-import',
						'method' => 'POST',
						'action' => [ $this, 'upload_import' ],
						'rest'   => true,
					],

					'listing_import_process_action' => [
						'base'   => 'listings_resource',
						'path'   => '/process-import',
						'method' => 'POST',
						'action' => [ $this, 'process_import' ],
						'rest'   => true,
					],

					'listing_export_action'         => [
						'base'   => 'listings_resource',
						'path'   => '/export',
						'method' => 'POST',
						'action' => [ $this, 'export_listings' ],
						'rest'   => true,
					],

					'listing_import_page'           => [
						'path'     => '/import-listings',
						'redirect' => [ $this, 'redirect_listing_import_page' ],
					],

					'listing_import_upload_page'    => [
						'base'     => 'listing_import_page',
						'path'     => '/upload',
						'title'    => esc_html__( 'Upload File', 'hivepress-import' ),
						'redirect' => [ $this, 'redirect_listing_import_upload_page' ],
						'action'   => [ $this, 'render_listing_import_upload_page' ],
					],

					'listing_import_process_page'   => [
						'base'     => 'listing_import_page',
						'path'     => '/process',
						'title'    => esc_html__( 'Map Columns', 'hivepress-import' ),
						'redirect' => [ $this, 'redirect_listing_import_process_page' ],
						'action'   => [ $this, 'render_listing_import_process_page' ],
					],

					'listing_import_complete_page'  => [
						'base'   => 'listing_import_page',
						'path'   => '/complete',
						'title'  => esc_html__( 'Import Completed', 'hivepress-import' ),
						'action' => [ $this, 'render_listing_import_complete_page' ],
					],

					'listing_export_page'           => [
						'path'     => '/export-listings',
						'title'    => hivepress()->translator->get_string( 'export_listings' ),
						'redirect' => [ $this, 'redirect_listing_export_page' ],
						'action'   => [ $this, 'render_listing_export_page' ],
					],

					'listing_export_download_page'  => [
						'base'     => 'listing_export_page',
						'path'     => '/download',
						'redirect' => [ $this, 'redirect_listing_export_download_page' ],
						'action'   => [ $this, 'render_listing_export_download_page' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Upload listing import.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function upload_import( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hp\rest_error( 403 );
		}

		// Validate form.
		$form = ( new Forms\Listing_Import_Upload() )->set_values(
			array_merge(
				$request->get_params(),
				$request->get_file_params()
			)
		);

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Open file.
		$file = fopen( $form->get_value( 'file' )['tmp_name'], 'r' );

		if ( ! $file ) {
			return hp\rest_error( 400, esc_html__( 'The file could not be read.', 'hivepress-import' ) );
		}

		// Get columns.
		$columns = array_filter( hivepress()->import->get_columns( $file, $form->get_value( 'delimiter' ) ) );

		fclose( $file );

		if ( ! $columns || count( $columns ) === 1 ) {
			return hp\rest_error( 400, esc_html__( 'The file could not be read.', 'hivepress-import' ) );
		}

		// Delete previous attachment.
		$attachment = hivepress()->import->get_attachment();

		if ( $attachment ) {
			$attachment->delete();
		}

		// Upload new attachment.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return hp\rest_error( 400, $attachment_id->get_error_messages() );
		}

		// Save import settings.
		update_post_meta( $attachment_id, 'hp_imported', 1 );

		if ( $form->get_value( 'delimiter' ) !== ',' ) {
			update_post_meta( $attachment_id, 'hp_import_delimiter', $form->get_value( 'delimiter' ) );
		}

		if ( $form->get_value( 'mode' ) ) {
			update_post_meta( $attachment_id, 'hp_import_mode', $form->get_value( 'mode' ) );
		}

		if ( $form->get_value( 'category' ) ) {
			update_post_meta( $attachment_id, 'hp_import_category', $form->get_value( 'category' ) );
		}

		return hp\rest_response(
			201,
			[
				'id' => $attachment_id,
			]
		);
	}

	/**
	 * Process listing import.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function process_import( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hp\rest_error( 403 );
		}

		// Validate form.
		$form = ( new Forms\Listing_Import_Process() )->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Get attachment.
		$attachment = hivepress()->import->get_attachment();

		if ( ! $attachment ) {
			return hp\rest_error( 404, esc_html__( 'The file could not be read.', 'hivepress-import' ) );
		}

		// Open file.
		$file = fopen( $attachment->get_path(), 'r' );

		if ( ! $file ) {
			return hp\rest_error( 400, esc_html__( 'The file could not be read.', 'hivepress-import' ) );
		}

		// Include dependencies.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Get listing status.
		$status = ! current_user_can( 'import' ) && get_option( 'hp_listing_enable_moderation' ) ? 'pending' : 'publish';

		// Get category ID.
		$category_id = absint( get_post_meta( $attachment->get_id(), 'hp_import_category', true ) );

		// Get listing fields.
		$fields = hivepress()->import->get_fields();

		// Get CSV delimiter.
		$delimiter = hivepress()->import->get_delimiter( $attachment->get_id() );

		// Get CSV columns.
		$mappings = $form->get_values();

		$columns = array_map(
			function( $column ) use ( $mappings ) {
				return array_search( hp\sanitize_key( $column ), $mappings );
			},
			array_filter( hivepress()->import->get_columns( $file, $delimiter ) )
		);

		// Iterate CSV rows.
		$row = $columns;

		$import_count  = 0;
		$import_errors = [];
		$import_mode   = get_post_meta( $attachment->get_id(), 'hp_import_mode', true );

		$row_index = 0;

		while ( $row ) {
			$row_index++;

			$row = hivepress()->import->get_columns( $file, $delimiter );

			if ( ! $row || count( $row ) !== count( $columns ) ) {
				if ( $row ) {
					$import_errors[ $row_index ][] = esc_html__( 'Row is empty or invalid.', 'hivepress-import' );
				}

				continue;
			}

			// Get field values.
			$field_values   = [];
			$attachment_ids = [];

			foreach ( $row as $index => $value ) {

				// Get field name.
				$field_name = hp\get_array_value( $columns, $index );

				if ( ! $field_name ) {
					continue;
				}

				// Get field.
				$field = hp\get_array_value( $fields, $field_name );

				if ( ! $field ) {
					continue;
				}

				if ( 'attachment_upload' === $field::get_meta( 'name' ) ) {

					// Get attachment URLs.
					$attachment_urls = array_filter( array_map( 'trim', explode( ',', $value ) ) );

					if ( ! $attachment_urls ) {
						continue;
					}

					foreach ( $attachment_urls as $attachment_index => $attachment_url ) {
						$attachment_url = esc_url_raw( $attachment_url );

						/* translators: 1: file URL, 2: field name. */
						$attachment_error = sprintf( esc_html__( 'Error uploading "%1$s" file for "%2$s":', 'hivepress-import' ), $attachment_url, $field->get_label() ) . ' ';

						// Download file.
						$attachment_path = download_url( $attachment_url );

						if ( is_wp_error( $attachment_path ) ) {
							$import_errors[ $row_index ][] = $attachment_error . implode( ' ', $attachment_path->get_error_messages() );

							continue;
						}

						// Get file name.
						$attachment_name = basename( hp\get_array_value( wp_parse_url( $attachment_url ), 'path' ) );

						if ( ! $attachment_name ) {
							$import_errors[ $row_index ][] = $attachment_error . esc_html__( 'Invalid file name.', 'hivepress-import' );

							@unlink( $attachment_path );
							continue;
						}

						// Check file format.
						if ( $field->get_formats() && ! hivepress()->attachment->is_valid_file( $attachment_path, $attachment_name, $field->get_formats() ) ) {

							/* translators: %s: file extensions. */
							$import_errors[ $row_index ][] = $attachment_error . sprintf( esc_html__( 'Only %s files are allowed.', 'hivepress-import' ), strtoupper( implode( ', ', $field->get_formats() ) ) );

							@unlink( $attachment_path );
							continue;
						}

						// Upload attachment.
						$attachment_id = media_handle_sideload(
							[
								'name'     => $attachment_name,
								'tmp_name' => $attachment_path,
							]
						);

						@unlink( $attachment_path );

						if ( is_wp_error( $attachment_id ) ) {
							$import_errors[ $row_index ][] = $attachment_error . implode( ' ', $attachment_id->get_error_messages() );

							continue;
						}

						$attachment_ids[] = $attachment_id;

						// Set parent details.
						update_post_meta( $attachment_id, 'hp_parent_model', 'listing' );
						update_post_meta( $attachment_id, 'hp_parent_field', $field->get_name() );

						// Set field value.
						if ( ! $field->is_multiple() ) {
							$field_values[ $field->get_name() ] = $attachment_id;

							break;
						} elseif ( 'images' === $field->get_name() && ! $attachment_index ) {
							$field_values['image'] = $attachment_id;
						}
					}
				} elseif ( is_array( $field->get_arg( 'options' ) ) ) {
					$field_values[ $field->get_name() ] = [];

					// Get option labels.
					$option_labels = array_filter( array_map( 'trim', explode( ',', $value ) ) );

					if ( ! $option_labels ) {
						continue;
					}

					// Set option values.
					foreach ( $field->get_arg( 'options' ) as $option_name => $option_label ) {
						$option_label = html_entity_decode( hp\get_array_value( $option_label, 'label', $option_label ) );

						if ( in_array( $option_label, $option_labels, true ) ) {
							$field_values[ $field->get_name() ][] = $option_name;
						}
					}
				} elseif ( ! $field->get_arg( 'options' ) ) {

					// Set value.
					$field_values[ $field->get_name() ] = $value;
				}
			}

			if ( ! $field_values ) {
				$import_errors[ $row_index ][] = esc_html__( 'Row is empty or invalid.', 'hivepress-import' );

				continue;
			}

			// Get user ID.
			$user_id = get_current_user_id();

			if ( isset( $field_values['user'] ) ) {
				$user = null;

				if ( is_email( $field_values['user'] ) ) {
					$user = get_user_by( 'email', $field_values['user'] );
				} else {
					$user = get_user_by( 'login', $field_values['user'] );
				}

				if ( $user ) {
					$user_id = $user->ID;
				}
			}

			// Get category ID.
			if ( $category_id ) {
				$field_values['categories'] = [ $category_id ];
			}

			// Set field values.
			$field_values = array_merge(
				$field_values,
				[
					'user'   => $user_id,
					'status' => $status,
				]
			);

			// Create listing.
			$listing = null;

			if ( isset( $field_values['id'] ) ) {
				$listing = Models\Listing::query()->get_by_id( $field_values['id'] );

				if ( ! $listing && 'update' === $import_mode ) {
					$import_errors[ $row_index ][] = hivepress()->translator->get_string( 'no_listings_found' );

					continue;
				}

				unset( $field_values['id'] );
			}

			if ( ! $listing ) {
				$listing = new Models\Listing();

				if ( $category_id ) {
					$listing->set_categories( [ $category_id ] );
				}

				// @todo remove temporary fix when updated.
				$listing->set_id( null );
			}

			$listing->fill( $field_values );

			if ( ! $listing->save(
				array_merge(
					array_keys( $fields ),
					[ 'image', 'user', 'status', 'categories' ]
				)
			) ) {
				$import_errors[ $row_index ] = array_merge( hp\get_array_value( $import_errors, $row_index, [] ), $listing->_get_errors() );

				continue;
			} else {
				$import_count++;
			}

			// Update attachments.
			foreach ( $attachment_ids as $attachment_id ) {
				wp_update_post(
					[
						'ID'            => $attachment_id,
						'post_parent'   => $listing->get_id(),
						'comment_count' => $listing->get_id(),
					]
				);
			}
		}

		if ( $import_count && ! current_user_can( 'import' ) ) {

			// Send email.
			( new Emails\Listing_Import(
				[
					'recipient' => get_option( 'admin_email' ),

					'tokens'    => [
						'listings_url' => admin_url( 'edit.php?post_type=hp_listing&author=' . get_current_user_id() ),
					],
				]
			) )->send();
		}

		// Close file.
		fclose( $file );

		// Update attachment.
		update_post_meta( $attachment->get_id(), 'hp_import_count', $import_count );
		update_post_meta( $attachment->get_id(), 'hp_import_errors', $import_errors );

		return hp\rest_response( 200, [] );
	}

	/**
	 * Exports listings.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function export_listings( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hp\rest_error( 403 );
		}

		// Validate form.
		$form = ( new Forms\Listing_Export() )->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Get category ID.
		$category_id = $form->get_value( 'category' );

		// Get listings.
		$listings = Models\Listing::query();

		if ( ! current_user_can( 'import' ) ) {
			$listings->filter( [ 'user' => get_current_user_id() ] );
		} elseif ( $form->get_value( 'user' ) ) {
			$listings->filter( [ 'user' => $form->get_value( 'user' ) ] );
		}

		if ( $category_id ) {
			$listings->filter( [ 'categories__in' => [ $category_id ] ] );
		}

		if ( ! $listings->get_first_id() ) {
			return hp\rest_error( 404, hivepress()->translator->get_string( 'no_listings_found' ) );
		}

		// Get fields.
		$fields = [];

		if ( ! $category_id ) {
			$fields['categories'] = [];
		}

		if ( current_user_can( 'import' ) ) {
			if ( ! $form->get_value( 'user' ) ) {
				$fields['user'] = [
					'label' => hivepress()->translator->get_string( 'user' ),
				];
			}

			$fields = array_merge(
				$fields,
				[
					'featured' => [],
					'verified' => [],
				]
			);
		}

		$listing = new Models\Listing();

		if ( $category_id ) {
			$listing->set_categories( [ $category_id ] );
		}

		$fields = array_filter(
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

		// Get rows.
		$rows = [
			array_merge(
				[
					esc_html__( 'ID', 'hivepress-import' ),
				],
				array_values(
					array_map(
						function( $field ) {
							return $field->get_label();
						},
						$fields
					)
				)
			),
		];

		foreach ( $listings->get() as $listing ) {
			$row = [
				$listing->get_id(),
			];

			foreach ( $fields as $field ) {

				// Get field.
				$field = hp\get_array_value( $listing->_get_fields(), $field->get_name() );

				if ( ! $field ) {
					$row[] = null;

					continue;
				}

				// Add column.
				if ( 'user' === $field->get_name() ) {
					$row[] = $listing->get_user__username();
				} elseif ( 'attachment_upload' === $field::get_meta( 'name' ) ) {

					// @todo replace temporary fix.
					if ( 'images' === $field->get_name() ) {
						$listing->get_images__id();
					}

					$row[] = implode( ', ', (array) call_user_func( [ $listing, 'get_' . $field->get_name() . '__url' ] ) );
				} elseif ( is_array( $field->get_arg( 'options' ) ) ) {
					$row[] = $field->get_display_value();
				} elseif ( ! $field->get_arg( 'options' ) ) {
					$row[] = $field->get_value();
				} else {
					$row[] = null;
				}
			}

			$rows[] = $row;
		}

		// Get attachment.
		$attachment = Models\Attachment::query()->filter(
			[
				'user' => get_current_user_id(),
			]
		)->set_args(
			[
				'meta_key' => 'hp_exported',
			]
		)->get_first();

		if ( $attachment ) {

			// Delete attachment.
			$attachment->delete();
		}

		// Create file.
		$name = 'export-' . strtolower( wp_generate_password( 6, false, false ) ) . '.csv';

		$dir  = wp_upload_dir();
		$path = $dir['path'] . '/' . $name;
		$url  = $dir['url'] . '/' . $name;

		$file = fopen( $path, 'w' );

		foreach ( $rows as $row ) {
			fputcsv( $file, $row, $form->get_value( 'delimiter' ) );
		}

		fclose( $file );

		// Add attachment.
		$attachment_id = wp_insert_attachment(
			[
				'guid'           => $url,
				'post_mime_type' => 'text/csv',
				'post_title'     => basename( $name, '.csv' ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$path
		);

		if ( ! $attachment_id ) {
			return hp\rest_error( 400, esc_html__( 'The file could not be written.', 'hivepress-import' ) );
		}

		update_post_meta( $attachment_id, 'hp_exported', 1 );

		return hp\rest_response( 201, [] );
	}

	/**
	 * Redirects listing import page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_import_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		if ( ! empty( $_GET['reset'] ) ) {

			// Get attachment.
			$attachment = hivepress()->import->get_attachment();

			if ( $attachment ) {

				// Delete attachment.
				$attachment->delete();
			}
		}

		return true;
	}

	/**
	 * Redirects listing import upload page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_import_upload_page() {

		// Check attachment.
		if ( ! hivepress()->import->get_attachment() && hivepress()->router->get_current_route_name() === 'listing_import_upload_page' ) {
			return false;
		}

		return true;
	}

	/**
	 * Renders listing import upload page.
	 *
	 * @return string
	 */
	public function render_listing_import_upload_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_import_upload_page',
			]
		) )->render();
	}

	/**
	 * Redirects listing import process page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_import_process_page() {

		// Get attachment.
		$attachment = hivepress()->import->get_attachment();

		// Check attachment.
		if ( $attachment && ! strlen( get_post_meta( $attachment->get_id(), 'hp_import_count', true ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Renders listing import process page.
	 *
	 * @return string
	 */
	public function render_listing_import_process_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_import_process_page',
			]
		) )->render();
	}

	/**
	 * Renders listing import complete page.
	 *
	 * @return string
	 */
	public function render_listing_import_complete_page() {

		// Get attachment.
		$attachment = hivepress()->import->get_attachment();

		// Get details.
		$count  = 0;
		$errors = [];

		if ( $attachment ) {
			$count  = absint( get_post_meta( $attachment->get_id(), 'hp_import_count', true ) );
			$errors = array_filter( (array) get_post_meta( $attachment->get_id(), 'hp_import_errors', true ) );

			// Delete attachment.
			$attachment->delete();
		}

		return ( new Blocks\Template(
			[
				'template' => 'listing_import_complete_page',

				'context'  => [
					'listing_import_count'  => $count,
					'listing_import_errors' => $errors,
				],
			]
		) )->render();
	}

	/**
	 * Redirects listing export page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_export_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		// Check listings.
		if ( ! hivepress()->request->get_context( 'listing_count' ) ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		return false;
	}

	/**
	 * Renders listing export page.
	 *
	 * @return string
	 */
	public function render_listing_export_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_export_page',
			]
		) )->render();
	}

	/**
	 * Redirects listing export download page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_export_download_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check permissions.
		if ( ! hivepress()->import->is_allowed() ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		// Get attachment.
		$attachment = Models\Attachment::query()->filter(
			[
				'user' => get_current_user_id(),
			]
		)->set_args(
			[
				'meta_key' => 'hp_exported',
			]
		)->get_first();

		if ( ! $attachment ) {
			return hivepress()->router->get_url( 'listing_export_page' );
		}

		// Set request context.
		hivepress()->request->set_context( 'export_attachment', $attachment );
	}

	/**
	 * Renders listing export download page.
	 *
	 * @return string
	 */
	public function render_listing_export_download_page() {

		// Get attachment.
		$attachment = hivepress()->request->get_context( 'export_attachment' );

		// Set response headers.
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=export.csv' );

		// Get file content.
		$content = file_get_contents( $attachment->get_path() );

		// Delete attachment.
		$attachment->delete();

		return $content;
	}
}
