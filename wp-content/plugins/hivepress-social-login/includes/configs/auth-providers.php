<?php
/**
 * Authentication providers configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'google'   => [
		'label'  => 'Google',

		'fields' => [
			'first_name' => 'getFirstName',
			'last_name'  => 'getLastName',
			'image'      => 'getAvatar',
		],
	],

	'facebook' => [
		'label'    => 'Facebook',

		'settings' => [
			'client_id'     => [
				'label' => hivepress()->translator->get_string( 'app_id' ),
			],

			'client_secret' => [
				'label' => esc_html__( 'App Secret', 'hivepress-social-login' ),
			],
		],

		'fields'   => [
			'first_name'  => 'getFirstName',
			'last_name'   => 'getLastName',
			'description' => 'getBio',
			'image'       => 'getPictureUrl',
		],
	],

	'linkedin' => [
		'label'  => 'LinkedIn',
		'class'  => 'LinkedIn',

		'fields' => [
			'first_name' => 'getFirstName',
			'last_name'  => 'getLastName',
			'image'      => 'getImageUrl',
		],
	],

	'amazon'   => [
		'label'  => 'Amazon',
		'class'  => 'Luchianenco\OAuth2\Client\Provider\Amazon',

		'fields' => [
			'first_name' => 'getName',
		],
	],

	'spotify'  => [
		'label'  => 'Spotify',
		'class'  => 'Kerox\OAuth2\Client\Provider\Spotify',
		'scope'  => [ 'user-read-email' ],

		'fields' => [
			'first_name' => 'getDisplayName',
			'image'      => 'getImages',
		],
	],

	'twitch'   => [
		'label'  => 'Twitch',
		'class'  => 'Vertisan\OAuth2\Client\Provider\TwitchHelix',
		'scope'  => [ 'user:read:email' ],

		'fields' => [
			'first_name'  => 'getDisplayName',
			'description' => 'getDescription',
			'image'       => 'getProfileImageUrl',
		],
	],

	'github'   => [
		'label' => 'GitHub',
		'scope' => [ 'user:email' ],
	],

	'discord'  => [
		'label' => 'Discord',
		'class' => 'Wohali\OAuth2\Client\Provider\Discord',
		'scope' => [ 'identify', 'email' ],
	],
];
