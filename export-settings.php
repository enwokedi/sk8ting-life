<?php
/**
 * WordPress Settings Exporter
 *
 * Upload this file to the remote WordPress installation root directory
 * and access it via browser: https://your-site.com/export-settings.php
 *
 * This will generate a JSON file with all important WordPress settings
 */

// Prevent direct access without authentication
define('EXPORT_PASSWORD', '22nPY6k2eN'); // Change this to a secure password

// Load WordPress
require_once('wp-load.php');

// Check if user is admin or has correct password
if (!isset($_GET['password']) || $_GET['password'] !== EXPORT_PASSWORD) {
    die('Access denied. Use: export-settings.php?password=YOUR_PASSWORD');
}

// Check if user is logged in as admin
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator');
}

// Settings to export
$export_data = array(
    'meta' => array(
        'export_date' => current_time('mysql'),
        'site_url' => get_site_url(),
        'wp_version' => get_bloginfo('version'),
    ),

    // General Settings
    'general' => array(
        'blogname' => get_option('blogname'),
        'blogdescription' => get_option('blogdescription'),
        'siteurl' => get_option('siteurl'),
        'home' => get_option('home'),
        'admin_email' => get_option('admin_email'),
        'users_can_register' => get_option('users_can_register'),
        'default_role' => get_option('default_role'),
        'timezone_string' => get_option('timezone_string'),
        'date_format' => get_option('date_format'),
        'time_format' => get_option('time_format'),
        'start_of_week' => get_option('start_of_week'),
        'WPLANG' => get_option('WPLANG'),
    ),

    // Reading Settings
    'reading' => array(
        'show_on_front' => get_option('show_on_front'),
        'page_on_front' => get_option('page_on_front'),
        'page_for_posts' => get_option('page_for_posts'),
        'posts_per_page' => get_option('posts_per_page'),
        'posts_per_rss' => get_option('posts_per_rss'),
        'rss_use_excerpt' => get_option('rss_use_excerpt'),
        'blog_public' => get_option('blog_public'),
    ),

    // Discussion Settings
    'discussion' => array(
        'default_pingback_flag' => get_option('default_pingback_flag'),
        'default_ping_status' => get_option('default_ping_status'),
        'default_comment_status' => get_option('default_comment_status'),
        'require_name_email' => get_option('require_name_email'),
        'comment_registration' => get_option('comment_registration'),
        'close_comments_for_old_posts' => get_option('close_comments_for_old_posts'),
        'close_comments_days_old' => get_option('close_comments_days_old'),
        'thread_comments' => get_option('thread_comments'),
        'thread_comments_depth' => get_option('thread_comments_depth'),
        'page_comments' => get_option('page_comments'),
        'comments_per_page' => get_option('comments_per_page'),
        'default_comments_page' => get_option('default_comments_page'),
        'comment_order' => get_option('comment_order'),
        'comments_notify' => get_option('comments_notify'),
        'moderation_notify' => get_option('moderation_notify'),
        'comment_moderation' => get_option('comment_moderation'),
        'comment_whitelist' => get_option('comment_whitelist'),
        'comment_max_links' => get_option('comment_max_links'),
        'moderation_keys' => get_option('moderation_keys'),
        'blacklist_keys' => get_option('blacklist_keys'),
        'show_avatars' => get_option('show_avatars'),
        'avatar_rating' => get_option('avatar_rating'),
        'avatar_default' => get_option('avatar_default'),
    ),

    // Media Settings
    'media' => array(
        'thumbnail_size_w' => get_option('thumbnail_size_w'),
        'thumbnail_size_h' => get_option('thumbnail_size_h'),
        'thumbnail_crop' => get_option('thumbnail_crop'),
        'medium_size_w' => get_option('medium_size_w'),
        'medium_size_h' => get_option('medium_size_h'),
        'medium_large_size_w' => get_option('medium_large_size_w'),
        'medium_large_size_h' => get_option('medium_large_size_h'),
        'large_size_w' => get_option('large_size_w'),
        'large_size_h' => get_option('large_size_h'),
        'uploads_use_yearmonth_folders' => get_option('uploads_use_yearmonth_folders'),
    ),

    // Permalink Settings
    'permalinks' => array(
        'permalink_structure' => get_option('permalink_structure'),
        'category_base' => get_option('category_base'),
        'tag_base' => get_option('tag_base'),
    ),

    // Active Theme
    'theme' => array(
        'template' => get_option('template'),
        'stylesheet' => get_option('stylesheet'),
        'current_theme' => wp_get_theme()->get('Name'),
        'theme_mods' => get_option('theme_mods_' . get_option('stylesheet')),
    ),

    // Active Plugins
    'plugins' => array(
        'active_plugins' => get_option('active_plugins'),
    ),

    // Menus
    'menus' => array(),

    // Widgets
    'widgets' => array(),

    // HivePress Settings (if active)
    'hivepress' => array(),

    // WooCommerce Settings (if active)
    'woocommerce' => array(),
);

// Export Menus
$menus = wp_get_nav_menus();
foreach ($menus as $menu) {
    $export_data['menus'][$menu->term_id] = array(
        'name' => $menu->name,
        'slug' => $menu->slug,
        'items' => wp_get_nav_menu_items($menu->term_id),
    );
}

// Export Menu Locations
$export_data['menu_locations'] = get_nav_menu_locations();

// Export Widgets
$sidebars_widgets = get_option('sidebars_widgets');
$export_data['widgets']['sidebars_widgets'] = $sidebars_widgets;

// Get widget settings for each widget type
global $wp_registered_widgets;
foreach ($wp_registered_widgets as $widget_id => $widget) {
    if (isset($widget['callback'][0]) && is_object($widget['callback'][0])) {
        $widget_object = $widget['callback'][0];
        $widget_option = $widget_object->option_name;
        $export_data['widgets'][$widget_option] = get_option($widget_option);
    }
}

// Export HivePress Settings if plugin is active
if (class_exists('HivePress\Core')) {
    $hp_options = array(
        'hp_settings',
        'hp_listing_attributes',
        'hp_vendor_attributes',
        'hp_review_attributes',
        'hp_listing_categories',
        'hp_vendor_categories',
    );

    foreach ($hp_options as $option) {
        $value = get_option($option);
        if ($value !== false) {
            $export_data['hivepress'][$option] = $value;
        }
    }

    // Get all HivePress options
    global $wpdb;
    $hp_all_options = $wpdb->get_results(
        "SELECT option_name, option_value
        FROM {$wpdb->options}
        WHERE option_name LIKE 'hp_%'
        OR option_name LIKE 'hivepress_%'"
    );

    foreach ($hp_all_options as $option) {
        $export_data['hivepress'][$option->option_name] = maybe_unserialize($option->option_value);
    }
}

// Export WooCommerce Settings if plugin is active
if (class_exists('WooCommerce')) {
    $wc_options = array(
        'woocommerce_store_address',
        'woocommerce_store_address_2',
        'woocommerce_store_city',
        'woocommerce_default_country',
        'woocommerce_store_postcode',
        'woocommerce_currency',
        'woocommerce_currency_pos',
        'woocommerce_price_thousand_sep',
        'woocommerce_price_decimal_sep',
        'woocommerce_price_num_decimals',
        'woocommerce_shop_page_id',
        'woocommerce_cart_page_id',
        'woocommerce_checkout_page_id',
        'woocommerce_myaccount_page_id',
        'woocommerce_terms_page_id',
        'woocommerce_force_ssl_checkout',
        'woocommerce_unforce_ssl_checkout',
        'woocommerce_enable_guest_checkout',
        'woocommerce_enable_checkout_login_reminder',
        'woocommerce_enable_signup_and_login_from_checkout',
        'woocommerce_enable_myaccount_registration',
        'woocommerce_registration_generate_username',
        'woocommerce_registration_generate_password',
        'woocommerce_calc_taxes',
        'woocommerce_tax_based_on',
        'woocommerce_shipping_tax_class',
        'woocommerce_tax_round_at_subtotal',
        'woocommerce_tax_classes',
        'woocommerce_tax_display_shop',
        'woocommerce_tax_display_cart',
        'woocommerce_price_display_suffix',
        'woocommerce_tax_total_display',
        'woocommerce_enable_shipping_calc',
        'woocommerce_shipping_cost_requires_address',
        'woocommerce_ship_to_countries',
        'woocommerce_ship_to_destination',
        'woocommerce_calc_shipping',
        'woocommerce_enable_review_rating',
        'woocommerce_review_rating_required',
        'woocommerce_review_rating_verification_label',
        'woocommerce_review_rating_verification_required',
    );

    foreach ($wc_options as $option) {
        $value = get_option($option);
        if ($value !== false) {
            $export_data['woocommerce'][$option] = $value;
        }
    }

    // Get all WooCommerce options
    global $wpdb;
    $wc_all_options = $wpdb->get_results(
        "SELECT option_name, option_value
        FROM {$wpdb->options}
        WHERE option_name LIKE 'woocommerce_%'"
    );

    foreach ($wc_all_options as $option) {
        if (!isset($export_data['woocommerce'][$option->option_name])) {
            $export_data['woocommerce'][$option->option_name] = maybe_unserialize($option->option_value);
        }
    }
}

// Custom Post Types
$export_data['post_types'] = get_post_types(array('_builtin' => false), 'objects');

// Custom Taxonomies
$export_data['taxonomies'] = get_taxonomies(array('_builtin' => false), 'objects');

// Generate JSON
$json = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Set headers for download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="wordpress-settings-export-' . date('Y-m-d-His') . '.json"');
header('Content-Length: ' . strlen($json));

// Output JSON
echo $json;
exit;
