<?php
/**
 * Plugin Name: WooCommerce to HivePress T-Shirt Sync
 * Description: Automatically syncs WooCommerce T-Shirt category products to HivePress Merch8ndise/T-Shirt category listings
 * Version: 1.0.0
 * Author: Sk8ting Life
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class WooToHivePress_TShirt_Sync {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if required plugins are active
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Hook into product save/update
        add_action('woocommerce_update_product', array($this, 'sync_product'), 10, 1);
        add_action('woocommerce_new_product', array($this, 'sync_product'), 10, 1);

        // Hook into product deletion
        add_action('before_delete_post', array($this, 'handle_product_deletion'), 10, 1);

        // Hook into category changes
        add_action('set_object_terms', array($this, 'handle_category_change'), 10, 6);
    }

    /**
     * Check if required plugins are active
     */
    private function check_dependencies() {
        return class_exists('WooCommerce') && class_exists('HivePress\Core');
    }

    /**
     * Show dependency notice
     */
    public function dependency_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WooCommerce to HivePress T-Shirt Sync:</strong> This plugin requires both WooCommerce and HivePress to be installed and activated.';
        echo '</p></div>';
    }

    /**
     * Main sync function - syncs WooCommerce product to HivePress listing
     */
    public function sync_product($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return;
        }

        // Check if product is in T-Shirt category
        if (!$this->is_tshirt_product($product)) {
            // If product was previously synced but category changed, delete listing
            $listing_id = get_post_meta($product_id, '_hp_listing_id', true);
            if ($listing_id) {
                wp_delete_post($listing_id, true);
                delete_post_meta($product_id, '_hp_listing_id');
            }
            return;
        }

        // Check if listing already exists
        $listing_id = get_post_meta($product_id, '_hp_listing_id', true);

        if ($listing_id && get_post_status($listing_id)) {
            // Update existing listing
            $this->update_listing($listing_id, $product);
        } else {
            // Create new listing
            $listing_id = $this->create_listing($product);
            if ($listing_id) {
                update_post_meta($product_id, '_hp_listing_id', $listing_id);
                update_post_meta($listing_id, '_woo_product_id', $product_id);
            }
        }
    }

    /**
     * Check if product is in T-Shirt category
     */
    private function is_tshirt_product($product) {
        $product_id = $product->get_id();
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));

        return in_array('t-shirt', $categories) || in_array('t-shirts', $categories) || in_array('tshirt', $categories);
    }

    /**
     * Create new HivePress listing from WooCommerce product
     */
    private function create_listing($product) {
        $product_id = $product->get_id();

        // Get HivePress category ID for Merch8ndise/T-Shirt
        $category_id = $this->get_hivepress_category_id();

        if (!$category_id) {
            error_log('WooCommerce to HivePress Sync: Could not find Merch8ndise or T-Shirt category in HivePress');
            return false;
        }

        // Get full description, fallback to short description
        $description = $product->get_description();
        if (empty($description)) {
            $description = $product->get_short_description();
        }

        // Prepare listing data
        $listing_data = array(
            'post_title'   => $product->get_name(),
            'post_content' => $description,
            'post_excerpt' => $product->get_short_description(),
            'post_status'  => $product->get_status() === 'publish' ? 'publish' : 'draft',
            'post_type'    => 'hp_listing',
            'post_author'  => get_current_user_id() ?: 1,
        );

        // Insert listing
        $listing_id = wp_insert_post($listing_data);

        if (is_wp_error($listing_id)) {
            error_log('WooCommerce to HivePress Sync: Error creating listing - ' . $listing_id->get_error_message());
            return false;
        }

        // Set category
        wp_set_object_terms($listing_id, array($category_id), 'hp_listing_category');

        // Sync images
        $this->sync_images($listing_id, $product);

        // Store product URL and price as meta
        update_post_meta($listing_id, '_woo_product_url', get_permalink($product_id));
        update_post_meta($listing_id, '_woo_product_price', $product->get_price());
        update_post_meta($listing_id, '_woo_product_id', $product_id);

        return $listing_id;
    }

    /**
     * Update existing HivePress listing from WooCommerce product
     */
    private function update_listing($listing_id, $product) {
        $product_id = $product->get_id();

        // Get full description, fallback to short description
        $description = $product->get_description();
        if (empty($description)) {
            $description = $product->get_short_description();
        }

        // Update listing data
        $listing_data = array(
            'ID'           => $listing_id,
            'post_title'   => $product->get_name(),
            'post_content' => $description,
            'post_excerpt' => $product->get_short_description(),
            'post_status'  => $product->get_status() === 'publish' ? 'publish' : 'draft',
        );

        wp_update_post($listing_data);

        // Get HivePress category ID
        $category_id = $this->get_hivepress_category_id();
        if ($category_id) {
            wp_set_object_terms($listing_id, array($category_id), 'hp_listing_category');
        }

        // Sync images
        $this->sync_images($listing_id, $product);

        // Update meta
        update_post_meta($listing_id, '_woo_product_url', get_permalink($product_id));
        update_post_meta($listing_id, '_woo_product_price', $product->get_price());
    }

    /**
     * Sync product images to listing
     */
    private function sync_images($listing_id, $product) {
        // Set featured image
        $thumbnail_id = $product->get_image_id();
        if ($thumbnail_id) {
            set_post_thumbnail($listing_id, $thumbnail_id);

            // Set HivePress attachment metadata for featured image
            update_post_meta($thumbnail_id, 'hp_parent_model', 'listing');
            update_post_meta($thumbnail_id, 'hp_parent_field', 'image');
            wp_update_post(array(
                'ID' => $thumbnail_id,
                'post_parent' => $listing_id
            ));
        }

        // Set gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_ids)) {
            // Store image IDs array for HivePress
            update_post_meta($listing_id, 'hp_images', $gallery_ids);

            // Set HivePress attachment metadata for each gallery image
            $sort_order = 0;
            foreach ($gallery_ids as $image_id) {
                update_post_meta($image_id, 'hp_parent_model', 'listing');
                update_post_meta($image_id, 'hp_parent_field', 'images');
                wp_update_post(array(
                    'ID' => $image_id,
                    'post_parent' => $listing_id,
                    'menu_order' => $sort_order++
                ));
            }
        }
    }

    /**
     * Get HivePress category ID for Merch8ndise/T-Shirt
     */
    private function get_hivepress_category_id() {
        // First try to find T-Shirt category under Merch8ndise
        $tshirt_category = get_term_by('slug', 't-shirt', 'hp_listing_category');

        if (!$tshirt_category) {
            // Try alternative slugs
            $tshirt_category = get_term_by('slug', 'tshirt', 'hp_listing_category');
        }

        if (!$tshirt_category) {
            $tshirt_category = get_term_by('slug', 't-shirts', 'hp_listing_category');
        }

        if ($tshirt_category) {
            return $tshirt_category->term_id;
        }

        // If T-Shirt doesn't exist, try Merch8ndise parent category
        $merch_category = get_term_by('slug', 'merch8ndise', 'hp_listing_category');

        if ($merch_category) {
            return $merch_category->term_id;
        }

        return false;
    }

    /**
     * Handle product deletion
     */
    public function handle_product_deletion($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        // Get linked listing
        $listing_id = get_post_meta($post_id, '_hp_listing_id', true);

        if ($listing_id) {
            // Delete the listing
            wp_delete_post($listing_id, true);
        }
    }

    /**
     * Handle category changes
     */
    public function handle_category_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
        if ($taxonomy !== 'product_cat') {
            return;
        }

        if (get_post_type($object_id) !== 'product') {
            return;
        }

        // Re-sync the product
        $this->sync_product($object_id);
    }
}

// Initialize plugin
WooToHivePress_TShirt_Sync::get_instance();
