<?php
/**
 * Zoom component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Grant;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Implements integration with Zoom API.
 */
final class Zoom extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Check settings.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Add vendor fields.
		add_filter( 'hivepress/v1/models/vendor', [ $this, 'add_vendor_fields' ] );

		// Alter vendor form.
		add_filter( 'hivepress/v1/forms/vendor_update', [ $this, 'alter_vendor_update_form' ], 300, 2 );

		// Add booking attributes.
		add_filter( 'hivepress/v1/models/booking/attributes', [ $this, 'add_booking_attributes' ], 100 );

		// Update booking status.
		add_action( 'hivepress/v1/models/booking/update_status', [ $this, 'update_booking_status' ], 10, 4 );

		parent::__construct( $args );
	}

	/**
	 * Checks API settings.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return get_option( 'hp_booking_enable_time' ) && get_option( 'hp_zoom_client_id' ) && get_option( 'hp_zoom_client_secret' );
	}

	/**
	 * Gets API client.
	 *
	 * @return object
	 */
	public function get_client() {
		return new Provider\Zoom(
			[
				'clientId'     => get_option( 'hp_zoom_client_id' ),
				'clientSecret' => get_option( 'hp_zoom_client_secret' ),
				'redirectUri'  => hivepress()->router->get_url( 'zoom_oauth_grant_access_action' ),
			]
		);
	}

	/**
	 * Gets access token.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return mixed
	 */
	public function get_token( $vendor_id ) {

		// Get vendor.
		$vendor = $vendor_id;

		if ( ! is_object( $vendor_id ) ) {
			$vendor = Models\Vendor::query()->get_by_id( $vendor_id );
		}

		if ( ! $vendor || ! in_array( $vendor->get_status(), [ 'draft', 'publish' ] ) ) {
			return null;
		}

		// Get access token.
		$access_token = $vendor->get_zoom_access_token();

		if ( $access_token && $vendor->get_zoom_expired_time() <= time() ) {

			// Get API client.
			$client = $this->get_client();

			try {

				// Get access token.
				$token = $client->getAccessToken(
					( new Grant\RefreshToken() ),
					[
						'refresh_token' => $vendor->get_zoom_refresh_token(),
					]
				);
			} catch ( \Exception $exception ) {

				// Delete access token.
				$this->update_token( $vendor, null );

				return;
			}

			// Update access token.
			$this->update_token( $vendor, $token );

			// Set access token.
			$access_token = $token->getToken();
		}

		return $access_token;
	}

	/**
	 * Updates access token.
	 *
	 * @param int    $vendor_id Vendor ID.
	 * @param object $token Token object.
	 * @return bool
	 */
	public function update_token( $vendor_id, $token ) {

		// Get vendor.
		$vendor = $vendor_id;

		if ( ! is_object( $vendor_id ) ) {
			$vendor = Models\Vendor::query()->get_by_id( $vendor_id );
		}

		if ( ! $vendor || ! in_array( $vendor->get_status(), [ 'draft', 'publish' ] ) ) {
			return false;
		}

		// Set values.
		$values = [
			'zoom_access_token' => $token ? $token->getToken() : null,
			'zoom_expired_time' => $token ? $token->getExpires() : null,
		];

		if ( ! $token ) {
			$values['zoom_refresh_token'] = null;
		} elseif ( $token->getRefreshToken() ) {
			$values['zoom_refresh_token'] = $token->getRefreshToken();
		}

		// Update vendor.
		if ( ! $vendor->fill( $values )->save( array_keys( $values ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Adds vendor fields.
	 *
	 * @param array $model Model arguments.
	 * @return array
	 */
	public function add_vendor_fields( $model ) {
		$model['fields']['zoom_access_token'] = [
			'type'       => 'text',
			'max_length' => 1024,
			'_external'  => true,
		];

		$model['fields']['zoom_refresh_token'] = [
			'type'       => 'text',
			'max_length' => 1024,
			'_external'  => true,
		];

		$model['fields']['zoom_expired_time'] = [
			'type'      => 'number',
			'min_value' => 0,
			'_external' => true,
		];

		return $model;
	}

	/**
	 * Alters vendor update form.
	 *
	 * @param array  $form_args Form arguments.
	 * @param object $form Form object.
	 * @return array
	 */
	public function alter_vendor_update_form( $form_args, $form ) {

		// Get vendor.
		$vendor = $form->get_model();

		if ( ! $vendor || ! in_array( $vendor->get_status(), [ 'draft', 'publish' ] ) ) {
			return $form_args;
		}

		// Set field arguments.
		$field_args = [
			'label'       => 'Zoom',
			/* translators: %s: Platform name. */
			'caption'     => sprintf( esc_html__( 'Connect %s', 'hivepress-bookings' ), 'Zoom' ),
			/* translators: %s: Platform name. */
			'description' => sprintf( esc_html__( 'Connect %s to schedule meetings automatically.', 'hivepress-bookings' ), 'Zoom' ),
			'type'        => 'button',
			'_order'      => 295,

			'attributes'  => [
				'data-component' => 'link',
			],
		];

		if ( $this->get_token( $vendor ) ) {

			// Set caption.
			/* translators: %s: Platform name. */
			$field_args['caption'] = sprintf( esc_html__( 'Disconnect %s', 'hivepress-bookings' ), 'Zoom' );

			// Set URL.
			$field_args['attributes']['data-url'] = esc_url(
				hivepress()->router->get_url(
					'zoom_oauth_revoke_access_action',
					[
						'vendor_id' => $vendor->get_id(),
						'nonce'     => wp_create_nonce( 'zoom_oauth_revoke_access' ),
					]
				)
			);
		} else {

			// Get API client.
			$client = $this->get_client();

			// Set URL.
			$field_args['attributes']['data-url'] = esc_url(
				$client->getAuthorizationUrl(
					[
						'scope' => [ 'meeting:write:meeting', 'meeting:write:meeting:admin', 'meeting:delete:meeting', 'meeting:delete:meeting:admin' ],

						'state' => wp_json_encode(
							[
								'vendor_id' => $vendor->get_id(),
								'nonce'     => wp_create_nonce( 'zoom_oauth_grant_access' ),
							]
						),
					]
				)
			);
		}

		// Add field.
		$form_args['fields']['zoom_oauth'] = $field_args;

		return $form_args;
	}

	/**
	 * Adds booking attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return array
	 */
	public function add_booking_attributes( $attributes ) {
		$attributes['zoom_id'] = [
			'protected'  => true,

			'edit_field' => [
				'type'       => 'text',
				'max_length' => 256,
			],
		];

		$attributes['zoom_join_url'] = [
			'protected'      => true,
			'display_format' => '<a href="%value%" target="_blank" class="hp-link"><i class="hp-icon fas fa-external-link-alt"></i><span>' . esc_html__( 'Join Meeting', 'hivepress-bookings' ) . '</span></a>',

			'display_areas'  => [
				'view_page_primary',
			],

			'edit_field'     => [
				'type' => 'url',
			],
		];

		return $attributes;
	}

	/**
	 * Updates booking status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $booking Booking object.
	 */
	public function update_booking_status( $booking_id, $new_status, $old_status, $booking ) {

		// Check status.
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing || ! hivepress()->booking->is_time_enabled( $listing ) ) {
			return;
		}

		// Get vendor.
		$vendor = $listing->get_vendor();

		if ( ! $vendor ) {
			return;
		}

		// Get access token.
		$token = $this->get_token( $vendor );

		if ( ! $token ) {
			return;
		}

		// Set request headers.
		$headers = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		];

		if ( 'publish' === $new_status ) {

			// Get user.
			$user = $booking->get_user();

			// Schedule meeting.
			$response = json_decode(
				wp_remote_retrieve_body(
					wp_remote_post(
						'https://api.zoom.us/v2/users/me/meetings',
						[
							'headers' => $headers,

							'body'    => wp_json_encode(
								[
									'topic'      => $user->get_display_name() . ( get_option( 'hp_booking_enable_quantity' ) ? ' (' . $booking->get_quantity() . ')' : null ),
									'agenda'     => hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ),
									'timezone'   => get_option( 'hp_booking_enable_timezone' ) && $listing->get_booking_timezone() ? $listing->get_booking_timezone() : 'UTC',
									'start_time' => str_replace( ' ', 'T', date( 'Y-m-d H:i:s', $booking->get_start_time() ) ),
									'duration'   => round( absint( $booking->get_end_time() - $booking->get_start_time() ) / 60 ),
								]
							),
						]
					)
				),
				true
			);

			if ( ! is_array( $response ) || ! isset( $response['id'] ) ) {
				return;
			}

			// Update booking.
			$booking->fill(
				[
					'zoom_id'       => $response['id'],
					'zoom_join_url' => $response['join_url'],
				]
			)->save( [ 'zoom_id', 'zoom_join_url' ] );
		} elseif ( $booking->get_zoom_id() ) {

			// Delete meeting.
			$response = wp_remote_request(
				'https://api.zoom.us/v2/meetings/' . $booking->get_zoom_id(),
				[
					'headers' => $headers,
					'method'  => 'DELETE',
				]
			);
		}
	}
}
