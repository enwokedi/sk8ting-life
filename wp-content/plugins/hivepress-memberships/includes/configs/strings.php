<?php
/**
 * Strings configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'featuring_period'          => esc_html__( 'Featuring Period', 'hivepress-memberships' ),
	'feature_listing'           => esc_html__( 'Feature Listing', 'hivepress-memberships' ),
	'listing_featured'          => esc_html__( 'Listing Featured', 'hivepress-memberships' ),
	'confirm_listing_featuring' => esc_html__( 'Are you sure you want to feature this listing?', 'hivepress-memberships' ),
	/* translators: %s: Listing title. */
	'listing_has_been_featured' => esc_html__( 'Thank you! Your listing "%s" is featured and will appear at the top of the page.', 'hivepress-memberships' ),
];
