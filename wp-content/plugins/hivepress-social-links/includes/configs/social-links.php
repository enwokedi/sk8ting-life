<?php
/**
 * Social links configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'website'     => [
		'label' => esc_html__( 'Website', 'hivepress-social-links' ),
	],

	'email'       => [
		'label'  => esc_html__( 'Email', 'hivepress-social-links' ),
		'type'   => 'email',
		'prefix' => 'mailto:',
	],

	'amazon'      => [
		'label' => esc_html__( 'Amazon', 'hivepress-social-links' ),
	],

	'app_store'   => [
		'label' => esc_html__( 'App Store', 'hivepress-social-links' ),
	],

	'bandcamp'    => [
		'label' => esc_html__( 'Bandcamp', 'hivepress-social-links' ),
	],

	'behance'     => [
		'label' => esc_html__( 'Behance', 'hivepress-social-links' ),
	],

	'dribbble'    => [
		'label' => esc_html__( 'Dribbble', 'hivepress-social-links' ),
	],

	'etsy'        => [
		'label' => esc_html__( 'Etsy', 'hivepress-social-links' ),
	],

	'facebook'    => [
		'label' => esc_html__( 'Facebook', 'hivepress-social-links' ),
	],

	'github'      => [
		'label' => esc_html__( 'GitHub', 'hivepress-social-links' ),
	],

	'google_play' => [
		'label' => esc_html__( 'Google Play', 'hivepress-social-links' ),
	],

	'instagram'   => [
		'label' => esc_html__( 'Instagram', 'hivepress-social-links' ),
	],

	'linkedin'    => [
		'label' => esc_html__( 'LinkedIn', 'hivepress-social-links' ),
	],

	'medium'      => [
		'label' => esc_html__( 'Medium', 'hivepress-social-links' ),
	],

	'mixcloud'    => [
		'label' => esc_html__( 'Mixcloud', 'hivepress-social-links' ),
	],

	'patreon'     => [
		'label' => esc_html__( 'Patreon', 'hivepress-social-links' ),
	],

	'pinterest'   => [
		'label' => esc_html__( 'Pinterest', 'hivepress-social-links' ),
	],

	'reddit'      => [
		'label' => esc_html__( 'Reddit', 'hivepress-social-links' ),
	],

	'soundcloud'  => [
		'label' => esc_html__( 'SoundCloud', 'hivepress-social-links' ),
	],

	'spotify'     => [
		'label' => esc_html__( 'Spotify', 'hivepress-social-links' ),
	],

	'steam'       => [
		'label' => esc_html__( 'Steam', 'hivepress-social-links' ),
	],

	'telegram'    => [
		'label'  => esc_html__( 'Telegram', 'hivepress-social-links' ),
		'type'   => 'phone',
		'prefix' => 'https://t.me/',
	],

	'tiktok'      => [
		'label' => esc_html__( 'TikTok', 'hivepress-social-links' ),
	],

	'tripadvisor' => [
		'label' => esc_html__( 'Tripadvisor', 'hivepress-social-links' ),
	],

	'tumblr'      => [
		'label' => esc_html__( 'Tumblr', 'hivepress-social-links' ),
	],

	'twitch'      => [
		'label' => esc_html__( 'Twitch', 'hivepress-social-links' ),
	],

	'twitter'     => [
		'label' => esc_html__( 'Twitter', 'hivepress-social-links' ),
	],

	'viber'       => [
		'label'  => esc_html__( 'Viber', 'hivepress-social-links' ),
		'type'   => 'phone',
		'prefix' => 'chat?number=',
	],

	'vimeo'       => [
		'label' => esc_html__( 'Vimeo', 'hivepress-social-links' ),
	],

	'vk'          => [
		'label' => esc_html__( 'VK', 'hivepress-social-links' ),
	],

	'whatsapp'    => [
		'label'  => esc_html__( 'WhatsApp', 'hivepress-social-links' ),
		'type'   => 'phone',
		'prefix' => 'https://wa.me/',
	],

	'yelp'        => [
		'label' => esc_html__( 'Yelp', 'hivepress-social-links' ),
	],

	'youtube'     => [
		'label' => esc_html__( 'YouTube', 'hivepress-social-links' ),
	],
];
