<?php
/**
 * Post types configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'membership_plan' => [
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'edit.php?post_type=hp_membership',
		'supports'     => [ 'title', 'editor', 'page-attributes' ],

		'labels'       => [
			'name'               => esc_html__( 'Plans', 'hivepress-memberships' ),
			'singular_name'      => esc_html__( 'Plan', 'hivepress-memberships' ),
			'add_new_item'       => esc_html__( 'Add Plan', 'hivepress-memberships' ),
			'add_new'            => esc_html_x( 'Add New', 'plan', 'hivepress-memberships' ),
			'edit_item'          => esc_html__( 'Edit Plan', 'hivepress-memberships' ),
			'new_item'           => esc_html__( 'Add Plan', 'hivepress-memberships' ),
			'all_items'          => esc_html__( 'Plans', 'hivepress-memberships' ),
			'search_items'       => esc_html__( 'Search Plans', 'hivepress-memberships' ),
			'not_found'          => esc_html__( 'No plans found.', 'hivepress-memberships' ),
			'not_found_in_trash' => esc_html__( 'No plans found.', 'hivepress-memberships' ),
		],
	],

	'membership'      => [
		'public'    => false,
		'show_ui'   => true,
		'supports'  => [ 'title' ],
		'menu_icon' => 'dashicons-id-alt',

		'labels'    => [
			'name'               => esc_html__( 'Memberships', 'hivepress-memberships' ),
			'singular_name'      => esc_html__( 'Membership', 'hivepress-memberships' ),
			'add_new_item'       => esc_html__( 'Add Membership', 'hivepress-memberships' ),
			'add_new'            => esc_html_x( 'Add New', 'membership', 'hivepress-memberships' ),
			'edit_item'          => esc_html__( 'Edit Membership', 'hivepress-memberships' ),
			'new_item'           => esc_html__( 'Add Membership', 'hivepress-memberships' ),
			'all_items'          => esc_html__( 'Memberships', 'hivepress-memberships' ),
			'search_items'       => esc_html__( 'Search Memberships', 'hivepress-memberships' ),
			'not_found'          => esc_html__( 'No memberships found.', 'hivepress-memberships' ),
			'not_found_in_trash' => esc_html__( 'No memberships found.', 'hivepress-memberships' ),
		],
	],
];
