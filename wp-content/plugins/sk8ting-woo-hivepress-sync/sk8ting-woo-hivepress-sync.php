<?php
/**
 * Plugin Name: SK8ting WooCommerce to HivePress Sync
 * Plugin URI: https://sk8ting.life
 * Description: Automatically syncs WooCommerce T-Shirt category products to HivePress Merch8ndise category listings
 * Version: 1.0.0
 * Author: SK8ting Life
 * Author URI: https://sk8ting.life
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: sk8ting-woo-hp-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SK8ting_WooCommerce_HivePress_Sync {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * WooCommerce T-Shirt category slug/name
     */
    private $woo_category = 't-shirt';

    /**
     * HivePress Merch8ndise category slug/name
     */
    private $hp_category = 'merch8ndise';

    /**
     * Meta key to store the connection between WooCommerce product and HivePress listing
     */
    private $meta_key_product_to_listing = '_hp_listing_id';
    private $meta_key_listing_to_product = '_woo_product_id';

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if required plugins are active
        if ( ! $this->check_dependencies() ) {
            add_action( 'admin_notices', [ $this, 'dependency_notice' ] );
            return;
        }

        // Hook into product save/update
        add_action( 'woocommerce_update_product', [ $this, 'sync_product_to_listing' ], 10, 1 );
        add_action( 'woocommerce_new_product', [ $this, 'sync_product_to_listing' ], 10, 1 );

        // Hook into product deletion
        add_action( 'before_delete_post', [ $this, 'handle_product_deletion' ], 10, 1 );

        // Hook into product category changes
        add_action( 'set_object_terms', [ $this, 'handle_category_change' ], 10, 6 );

        // Admin menu for bulk sync
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

        // AJAX handler for bulk sync
        add_action( 'wp_ajax_sk8ting_bulk_sync', [ $this, 'ajax_bulk_sync' ] );
    }

    /**
     * Check if required plugins are active
     */
    private function check_dependencies() {
        return class_exists( 'WooCommerce' ) && function_exists( 'hivepress' );
    }

    /**
     * Show admin notice if dependencies are missing
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'SK8ting WooCommerce to HivePress Sync requires both WooCommerce and HivePress to be installed and activated.', 'sk8ting-woo-hp-sync' ); ?></p>
        </div>
        <?php
    }

    /**
     * Sync WooCommerce product to HivePress listing
     *
     * @param int $product_id WooCommerce product ID
     */
    public function sync_product_to_listing( $product_id ) {
        // Get WooCommerce product
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return;
        }

        // Check if product is in T-Shirt category
        if ( ! $this->is_product_in_category( $product_id, $this->woo_category ) ) {
            // If product was previously synced but no longer in category, remove listing
            $this->remove_listing_if_exists( $product_id );
            return;
        }

        // Check if listing already exists for this product
        $listing_id = get_post_meta( $product_id, $this->meta_key_product_to_listing, true );

        if ( $listing_id && get_post_status( $listing_id ) ) {
            // Update existing listing
            $this->update_listing( $listing_id, $product );
        } else {
            // Create new listing
            $listing_id = $this->create_listing( $product );

            if ( $listing_id ) {
                // Store the connection
                update_post_meta( $product_id, $this->meta_key_product_to_listing, $listing_id );
                update_post_meta( $listing_id, $this->meta_key_listing_to_product, $product_id );
            }
        }
    }

    /**
     * Check if product is in specific category
     *
     * @param int $product_id Product ID
     * @param string $category_slug Category slug or name
     * @return bool
     */
    private function is_product_in_category( $product_id, $category_slug ) {
        $terms = get_the_terms( $product_id, 'product_cat' );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return false;
        }

        foreach ( $terms as $term ) {
            if ( $term->slug === $category_slug || $term->name === $category_slug ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create new HivePress listing from WooCommerce product
     *
     * @param WC_Product $product WooCommerce product object
     * @return int|false Listing ID on success, false on failure
     */
    private function create_listing( $product ) {
        // Get HivePress category
        $hp_category_term = $this->get_or_create_hp_category();

        if ( ! $hp_category_term ) {
            return false;
        }

        // Prepare listing data
        $listing_data = [
            'post_title'   => $product->get_name(),
            'post_content' => $product->get_description() ?: $product->get_short_description(),
            'post_status'  => $product->get_status() === 'publish' ? 'publish' : 'draft',
            'post_type'    => 'hp_listing',
            'post_author'  => get_current_user_id() ?: 1,
        ];

        // Insert listing
        $listing_id = wp_insert_post( $listing_data );

        if ( is_wp_error( $listing_id ) ) {
            return false;
        }

        // Set listing category
        wp_set_object_terms( $listing_id, [ $hp_category_term->term_id ], 'hp_listing_category' );

        // Sync images
        $this->sync_images( $listing_id, $product );

        // Add pricing and product link meta
        $this->sync_pricing_data( $listing_id, $product );

        return $listing_id;
    }

    /**
     * Update existing HivePress listing
     *
     * @param int $listing_id HivePress listing ID
     * @param WC_Product $product WooCommerce product object
     */
    private function update_listing( $listing_id, $product ) {
        // Update listing data
        $listing_data = [
            'ID'           => $listing_id,
            'post_title'   => $product->get_name(),
            'post_content' => $product->get_description() ?: $product->get_short_description(),
            'post_status'  => $product->get_status() === 'publish' ? 'publish' : 'draft',
        ];

        wp_update_post( $listing_data );

        // Update images
        $this->sync_images( $listing_id, $product );

        // Update pricing data
        $this->sync_pricing_data( $listing_id, $product );
    }

    /**
     * Sync product images to listing
     *
     * @param int $listing_id HivePress listing ID
     * @param WC_Product $product WooCommerce product object
     */
    private function sync_images( $listing_id, $product ) {
        // Set featured image
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            set_post_thumbnail( $listing_id, $image_id );
        }

        // Set gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $listing_id, 'hp_images', $gallery_ids );
        }
    }

    /**
     * Sync pricing data and product link
     *
     * @param int $listing_id HivePress listing ID
     * @param WC_Product $product WooCommerce product object
     */
    private function sync_pricing_data( $listing_id, $product ) {
        // Store price
        $price = $product->get_price();
        if ( $price ) {
            update_post_meta( $listing_id, 'hp_price', $price );
        }

        // Store product URL for "Buy Now" button
        update_post_meta( $listing_id, 'hp_product_url', $product->get_permalink() );

        // Store formatted price for display
        update_post_meta( $listing_id, 'hp_price_formatted', $product->get_price_html() );
    }

    /**
     * Get or create HivePress Merch8ndise category
     *
     * @return WP_Term|false Term object on success, false on failure
     */
    private function get_or_create_hp_category() {
        // Check if category exists
        $term = get_term_by( 'slug', $this->hp_category, 'hp_listing_category' );

        if ( $term ) {
            return $term;
        }

        // Create category if it doesn't exist
        $result = wp_insert_term(
            'Merch8ndise', // Term name
            'hp_listing_category', // Taxonomy
            [
                'slug' => $this->hp_category,
                'description' => 'Merchandise and branded products from the shop',
            ]
        );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        return get_term( $result['term_id'], 'hp_listing_category' );
    }

    /**
     * Remove listing if product is no longer in T-Shirt category
     *
     * @param int $product_id WooCommerce product ID
     */
    private function remove_listing_if_exists( $product_id ) {
        $listing_id = get_post_meta( $product_id, $this->meta_key_product_to_listing, true );

        if ( $listing_id && get_post_status( $listing_id ) ) {
            // Move to trash instead of permanent deletion
            wp_trash_post( $listing_id );

            // Remove connection meta
            delete_post_meta( $product_id, $this->meta_key_product_to_listing );
        }
    }

    /**
     * Handle product deletion
     *
     * @param int $post_id Post ID being deleted
     */
    public function handle_product_deletion( $post_id ) {
        if ( get_post_type( $post_id ) !== 'product' ) {
            return;
        }

        $this->remove_listing_if_exists( $post_id );
    }

    /**
     * Handle category changes
     *
     * @param int $object_id Object ID
     * @param array $terms Terms
     * @param array $tt_ids Term taxonomy IDs
     * @param string $taxonomy Taxonomy slug
     * @param bool $append Whether to append or replace
     * @param array $old_tt_ids Old term taxonomy IDs
     */
    public function handle_category_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        // Only process product category changes
        if ( $taxonomy !== 'product_cat' || get_post_type( $object_id ) !== 'product' ) {
            return;
        }

        // Re-sync the product (will create, update, or remove listing as needed)
        $this->sync_product_to_listing( $object_id );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Sync to HivePress', 'sk8ting-woo-hp-sync' ),
            __( 'Sync to HivePress', 'sk8ting-woo-hp-sync' ),
            'manage_woocommerce',
            'sk8ting-woo-hp-sync',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WooCommerce to HivePress Sync', 'sk8ting-woo-hp-sync' ); ?></h1>

            <div class="card">
                <h2><?php esc_html_e( 'Bulk Sync T-Shirt Products', 'sk8ting-woo-hp-sync' ); ?></h2>
                <p><?php esc_html_e( 'Click the button below to sync all existing T-Shirt category products to HivePress Merch8ndise category listings.', 'sk8ting-woo-hp-sync' ); ?></p>

                <button type="button" id="sk8ting-bulk-sync-btn" class="button button-primary">
                    <?php esc_html_e( 'Start Bulk Sync', 'sk8ting-woo-hp-sync' ); ?>
                </button>

                <div id="sk8ting-sync-progress" style="display:none; margin-top: 20px;">
                    <p><strong><?php esc_html_e( 'Sync in progress...', 'sk8ting-woo-hp-sync' ); ?></strong></p>
                    <div id="sk8ting-sync-status"></div>
                </div>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Settings', 'sk8ting-woo-hp-sync' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'WooCommerce Category', 'sk8ting-woo-hp-sync' ); ?></th>
                        <td><code><?php echo esc_html( $this->woo_category ); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'HivePress Category', 'sk8ting-woo-hp-sync' ); ?></th>
                        <td><code><?php echo esc_html( $this->hp_category ); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sync Status', 'sk8ting-woo-hp-sync' ); ?></th>
                        <td><?php esc_html_e( 'Automatic sync is active', 'sk8ting-woo-hp-sync' ); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sk8ting-bulk-sync-btn').on('click', function() {
                var $btn = $(this);
                var $progress = $('#sk8ting-sync-progress');
                var $status = $('#sk8ting-sync-status');

                $btn.prop('disabled', true);
                $progress.show();
                $status.html('<p>Starting sync...</p>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sk8ting_bulk_sync',
                        nonce: '<?php echo wp_create_nonce( 'sk8ting_bulk_sync' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<p style="color: green;">' + response.data.message + '</p>');
                        } else {
                            $status.html('<p style="color: red;">Error: ' + response.data.message + '</p>');
                        }
                        $btn.prop('disabled', false);
                    },
                    error: function() {
                        $status.html('<p style="color: red;">Error: Failed to complete sync.</p>');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for bulk sync
     */
    public function ajax_bulk_sync() {
        // Verify nonce
        if ( ! check_ajax_referer( 'sk8ting_bulk_sync', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Invalid security token' ] );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
        }

        // Get all products in T-Shirt category
        $category = get_term_by( 'slug', $this->woo_category, 'product_cat' );

        if ( ! $category ) {
            wp_send_json_error( [ 'message' => 'T-Shirt category not found. Please create it first.' ] );
        }

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category->term_id,
                ],
            ],
        ];

        $products = get_posts( $args );
        $synced = 0;

        foreach ( $products as $product_post ) {
            $this->sync_product_to_listing( $product_post->ID );
            $synced++;
        }

        wp_send_json_success( [
            'message' => sprintf(
                'Successfully synced %d product(s) to HivePress listings.',
                $synced
            ),
        ] );
    }
}

// Initialize plugin
SK8ting_WooCommerce_HivePress_Sync::get_instance();
