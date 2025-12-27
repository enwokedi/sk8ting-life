<?php
/**
 * Authentication component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Authentication component class.
 *
 * @class Auth
 */
final class Auth extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {
		if ( is_admin() ) {

			// Add settings.
			add_filter( 'hivepress/v1/settings', [ $this, 'add_settings' ] );
		} else {

			// Render form header.
			add_filter( 'hivepress/v1/forms/user_login', [ $this, 'render_form_header' ] );
			add_filter( 'hivepress/v1/forms/user_register', [ $this, 'render_form_header' ] );
		}

		parent::__construct( $args );
	}

	/**
	 * Gets provider.
	 *
	 * @param string $name Provider name.
	 * @return object
	 */
	public function get_provider( $name ) {

		// Get provider.
		$provider = hp\get_array_value( hivepress()->get_config( 'auth_providers' ), $name );

		if ( $provider ) {

			// Get class.
			$class = hp\get_array_value( $provider, 'class', ucfirst( $name ) );

			if ( ! strpos( $class, '\\' ) ) {
				$class = 'League\OAuth2\Client\Provider\\' . $class;
			}

			$class = '\\' . $class;

			// Get arguments.
			$args = [
				'clientId'     => get_option( hp\prefix( $name . '_client_id' ) ),
				'clientSecret' => get_option( hp\prefix( $name . '_client_secret' ) ),
				'redirectUri'  => hivepress()->router->get_url( 'user_auth_page' ),
			];

			if ( 'facebook' === $name ) {
				$args['graphApiVersion'] = 'v12.0';
			}

			return hp\create_class_instance( $class, [ $args ] );
		}
	}

	/**
	 * Adds settings.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function add_settings( $settings ) {

		// Get providers.
		$providers = hivepress()->get_config( 'auth_providers' );

		$settings['users']['sections']['registration']['fields']['user_auth_methods']['options'] = wp_list_pluck( $providers, 'label' );

		// Get methods.
		$methods = (array) get_option( 'hp_user_auth_methods' );

		// Get sections.
		$sections = [];

		foreach ( $providers as $provider_name => $provider ) {
			if ( in_array( $provider_name, $methods, true ) ) {

				// Get settings.
				$provider_settings = hp\merge_arrays(
					[
						'client_id'     => [
							'label'      => hivepress()->translator->get_string( 'client_id' ),
							'type'       => 'text',
							'max_length' => 256,
							'_order'     => 10,
						],

						'client_secret' => [
							'label'      => hivepress()->translator->get_string( 'client_secret' ),
							'type'       => 'text',
							'max_length' => 256,
							'_order'     => 20,
						],
					],
					hp\get_array_value( $provider, 'settings', [] )
				);

				// Add section.
				$sections[ $provider_name ] = [
					'title'  => $provider['label'],
					'_order' => 100,

					'fields' => array_combine(
						array_map(
							function( $field_name ) use ( $provider_name ) {
								return $provider_name . '_' . $field_name;
							},
							array_keys( $provider_settings )
						),
						$provider_settings
					),
				];
			}
		}

		return hp\merge_arrays(
			$settings,
			[
				'integrations' => [
					'sections' => $sections,
				],
			]
		);
	}

	/**
	 * Renders form header.
	 *
	 * @param array $args Form arguments.
	 * @return array
	 */
	public function render_form_header( $args ) {
		$output = '';

		// Get providers.
		$providers = array_intersect_key( hivepress()->get_config( 'auth_providers' ), array_flip( (array) get_option( 'hp_user_auth_methods' ) ) );

		if ( $providers ) {

			// Render links.
			$output = '<div class="hp-social-links">';

			foreach ( $providers as $provider_name => $provider_args ) {

				// Get provider.
				$provider = $this->get_provider( $provider_name );

				if ( $provider ) {
					$provider_slug = hp\sanitize_slug( $provider_name );

					// Get link label.
					/* translators: %s: platform name. */
					$link_label = __( 'Sign in with %s', 'hivepress-social-login' );

					if ( strpos( current_filter(), 'register' ) ) {

						/* translators: %s: platform name. */
						$link_label = __( 'Register with %s', 'hivepress-social-login' );

						if ( 'google' === $provider_name ) {

							/* translators: %s: platform name. */
							$link_label = __( 'Sign up with %s', 'hivepress-social-login' );
						}
					}

					// Get link arguments.
					$link_args = [
						'state' => wp_json_encode(
							[
								'provider' => $provider_name,
								'redirect' => urldecode( hivepress()->router->get_current_url() ),
							]
						),
					];

					if ( isset( $provider_args['scope'] ) ) {
						$link_args['scope'] = $provider_args['scope'];
					}

					if ( 'google' === $provider_name ) {
						$link_args['prompt'] = 'select_account';
					}

					// Render link.
					if ( 'google' === $provider_name ) {
						$output .= '<a href="' . esc_url( $provider->getAuthorizationUrl( $link_args ) ) . '" class="gsi-material-button">
							<div class="gsi-material-button-state"></div>
							<div class="gsi-material-button-content-wrapper">
								<div class="gsi-material-button-icon">
									<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
										<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
										<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
										<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
										<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
										<path fill="none" d="M0 0h48v48H0z"></path>
									</svg>
								</div>
								<span class="gsi-material-button-contents">' . esc_html( sprintf( $link_label, $provider_args['label'] ) ) . '</span>
							</div>
						</a>';
					} else {
						$output .= '<a href="' . esc_url( $provider->getAuthorizationUrl( $link_args ) ) . '" rel="nofollow" class="hp-social-links__item hp-social-links__item--' . esc_attr( $provider_slug ) . ' button button--large button--primary alt">';

						$output .= '<img src="' . esc_url( hivepress()->get_url( 'social_login' ) . '/assets/images/icons/' . $provider_slug . '.svg' ) . '" alt="' . esc_attr( $provider_args['label'] ) . '" />';
						$output .= '<span>' . esc_html( sprintf( $link_label, $provider_args['label'] ) ) . '</span>';

						$output .= '</a>';
					}
				}
			}

			$output .= '</div>';
		}

		// Set header.
		$args['header'] = hp\get_array_value( $args, 'header' ) . $output;

		return $args;
	}
}
