<?php
/**
 * Zoom controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Manages Zoom API.
 */
final class Zoom extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'zoom_oauth_base'                 => [
						'base' => 'oauth_base',
						'path' => '/zoom',
					],

					'zoom_oauth_grant_access_action'  => [
						'base'     => 'zoom_oauth_base',
						'path'     => '/grant-access',
						'redirect' => [ $this, 'grant_access' ],
					],

					'zoom_oauth_revoke_access_action' => [
						'base'     => 'zoom_oauth_base',
						'path'     => '/revoke-access',
						'redirect' => [ $this, 'revoke_access' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Grants Zoom OAuth access.
	 *
	 * @return mixed
	 */
	public function grant_access() {

		// Check settings.
		if ( ! hivepress()->zoom->is_enabled() ) {
			return true;
		}

		// Get state.
		$state = json_decode( wp_unslash( hp\get_array_value( $_GET, 'state' ) ), true );

		if ( ! $state ) {
			return true;
		}

		// Verify nonce.
		$nonce = hp\get_array_value( $state, 'nonce' );

		if ( ! wp_verify_nonce( $nonce, 'zoom_oauth_grant_access' ) ) {
			return true;
		}

		// Get vendor ID.
		$vendor_id = absint( hp\get_array_value( $state, 'vendor_id' ) );

		if ( ! $vendor_id ) {
			return true;
		}

		// Get authorization code.
		$code = hp\get_array_value( $_GET, 'code' );

		if ( ! $code ) {
			return true;
		}

		// Get API client.
		$client = hivepress()->zoom->get_client();

		try {

			// Get access token.
			$token = $client->getAccessToken(
				'authorization_code',
				[
					'code' => $code,
				]
			);
		} catch ( \Exception $exception ) {
			wp_die( esc_html( $exception->getMessage() ) );
		}

		// Update access token.
		if ( ! hivepress()->zoom->update_token( $vendor_id, $token ) ) {
			return true;
		}

		return hivepress()->router->get_url( 'user_edit_settings_page' );
	}

	/**
	 * Revokes Zoom OAuth access.
	 *
	 * @return mixed
	 */
	public function revoke_access() {

		// Check settings.
		if ( ! hivepress()->zoom->is_enabled() ) {
			return true;
		}

		// Verify nonce.
		$nonce = hp\get_array_value( $_GET, 'nonce' );

		if ( ! wp_verify_nonce( $nonce, 'zoom_oauth_revoke_access' ) ) {
			return true;
		}

		// Get vendor ID.
		$vendor_id = absint( hp\get_array_value( $_GET, 'vendor_id' ) );

		if ( ! $vendor_id ) {
			return true;
		}

		// Delete access token.
		if ( ! hivepress()->zoom->update_token( $vendor_id, null ) ) {
			return true;
		}

		return hivepress()->router->get_url( 'user_edit_settings_page' );
	}
}
