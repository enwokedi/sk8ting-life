<?php
/**
 * Authentication controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Authentication controller class.
 *
 * @class Auth
 */
final class Auth extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'user_auth_page' => [
						'base'     => 'user_account_page',
						'path'     => '/authenticate',
						'redirect' => [ $this, 'redirect_user_auth_page' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Redirects user authenticate page.
	 *
	 * @return mixed
	 */
	public function redirect_user_auth_page() {

		// Check authentication.
		if ( is_user_logged_in() ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		// Get request state.
		$state = json_decode( wp_unslash( html_entity_decode( hp\get_array_value( $_GET, 'state' ) ) ), true );

		if ( ! $state ) {
			return true;
		}

		// Get provider name.
		$provider_name = sanitize_text_field( hp\get_array_value( $state, 'provider' ) );

		if ( ! $provider_name || ! in_array( $provider_name, (array) get_option( 'hp_user_auth_methods' ), true ) ) {
			return true;
		}

		// Get provider.
		$provider = hivepress()->auth->get_provider( $provider_name );

		if ( ! $provider ) {
			return true;
		}

		// Get authorization code.
		$code = sanitize_text_field( hp\get_array_value( $_GET, 'code' ) );

		if ( ! $code ) {
			return true;
		}

		try {

			// Get access token.
			$token = $provider->getAccessToken(
				'authorization_code',
				[
					'code' => $code,
				]
			);

			// Get user profile.
			$profile = $provider->getResourceOwner( $token );
		} catch ( \Exception $exception ) {
			wp_die( esc_html( $exception->getMessage() ) );
		}

		// Get provider ID.
		$provider_id = $profile->getId() ? $profile->getId() : $profile->getAttribute( 'sub' );

		if ( ! $provider_id || ! $profile->getEmail() ) {
			return true;
		}

		// Get user by provider ID.
		$user_object = hp\get_first_array_value(
			get_users(
				[
					'meta_key'   => hp\prefix( $provider_name . '_id' ),
					'meta_value' => $provider_id,
					'number'     => 1,
				]
			)
		);

		if ( ! $user_object ) {

			// Get user by email.
			$user_object = get_user_by( 'email', $profile->getEmail() );

			if ( $user_object ) {

				// Set provider ID.
				update_user_meta( $user_object->ID, hp\prefix( $provider_name . '_id' ), $provider_id );
			}
		}

		if ( ! $user_object ) {

			// Get username.
			$username = hp\get_first_array_value( explode( '@', $profile->getEmail() ) );

			$username = sanitize_user( $username, true );

			if ( ! $username ) {
				$username = 'user';
			}

			while ( username_exists( $username ) ) {
				$username .= wp_rand( 1, 9 );
			}

			// Get password.
			$password = wp_generate_password();

			// Create user.
			$user = new Models\User();

			// Get fields.
			$fields = array_merge(
				hp\get_array_value( hivepress()->get_config( 'auth_providers' )[ $provider_name ], 'fields', [] ),
				[
					'email' => 'getEmail',
				]
			);

			$field_values  = [];
			$attachment_id = null;

			foreach ( $fields as $field_name => $field_method ) {

				// Check field method.
				if ( ! method_exists( $profile, $field_method ) ) {
					continue;
				}

				if ( 'image' === $field_name ) {

					// Get image field.
					$image_field = hp\get_array_value( $user->_get_fields(), $field_name );

					if ( ! $image_field ) {
						continue;
					}

					// Include dependencies.
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';

					// Get attachment URL.
					$attachment_url = hp\get_first_array_value( (array) call_user_func( [ $profile, $field_method ] ) );

					if ( ! $attachment_url ) {
						continue;
					}

					$attachment_url = esc_url_raw( $attachment_url );

					// Download file.
					$attachment_path = download_url( $attachment_url );

					if ( is_wp_error( $attachment_path ) ) {
						continue;
					}

					// Get file name.
					$attachment_name = basename( hp\get_array_value( wp_parse_url( $attachment_url ), 'path' ) );

					if ( ! $attachment_name ) {
						@unlink( $attachment_path );

						continue;
					}

					// Check file format.
					if ( ! hivepress()->attachment->is_valid_file( $attachment_path, $attachment_name, $image_field->get_formats() ) ) {
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
						continue;
					}

					// Set parent details.
					update_post_meta( $attachment_id, 'hp_parent_model', 'user' );
					update_post_meta( $attachment_id, 'hp_parent_field', $image_field->get_name() );

					// Set field value.
					$field_values[ $field_name ] = $attachment_id;
				} else {

					// Set field value.
					$field_values[ $field_name ] = call_user_func( [ $profile, $field_method ] );
				}
			}

			// Register user.
			$user->fill(
				array_merge(
					$field_values,
					[
						'username' => $username,
						'password' => $password,
					]
				)
			);

			if ( ! $user->save() ) {
				return true;
			}

			// Update attachment.
			if ( $attachment_id ) {
				wp_update_post(
					[
						'ID'            => $attachment_id,
						'comment_count' => $user->get_id(),
					]
				);
			}

			// Update user name.
			$user->save( [ 'first_name', 'last_name' ] );

			// Set user object.
			$user_object = get_userdata( $user->get_id() );

			// Set provider ID.
			update_user_meta( $user->get_id(), hp\prefix( $provider_name . '_id' ), $provider_id );

			do_action(
				'hivepress/v1/models/user/register',
				$user->get_id(),
				array_merge(
					$field_values,
					[
						'id'       => $provider_id,
						'password' => $password,
					]
				)
			);
		} else {

			// Get user.
			$user = Models\User::query()->get_by_id( $user_object );
		}

		// Authenticate user.
		if ( ! is_user_logged_in() ) {
			do_action( 'hivepress/v1/models/user/login' );

			wp_set_auth_cookie( $user->get_id(), true );

			do_action( 'wp_login', $user->get_username(), $user_object );
		}

		// Redirect user.
		$redirect = hp\get_array_value( $state, 'redirect' );

		if ( is_string( $redirect ) ) {
			$redirect = wp_validate_redirect( add_query_arg( [ '_cache' => time() ], $redirect ) );

			if ( $redirect ) {

				// Get query parameters.
				parse_str( hp\get_array_value( parse_url( $redirect ), 'query' ), $params );

				// Get redirect URL.
				$url = hp\get_array_value( $params, 'redirect', $redirect );

				if ( ! $url ) {
					return $redirect;
				}

				// Add query parameters.
				unset( $params['redirect'] );

				$url = add_query_arg( $params, $url );

				return $url;
			}
		}

		return true;
	}
}
