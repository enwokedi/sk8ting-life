<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include the theme framework.
require_once __DIR__ . '/vendor/hivepress/hivetheme/hivetheme.php';

// Custom: Hide or modify booking date field for events category
add_filter( 'hivepress/v1/forms/booking_make', function( $form_args, $form ) {
	// Get the booking model
	$booking = $form->get_model();

	if ( $booking ) {
		$listing = $booking->get_listing();

		if ( $listing && has_term( 'events', 'hp_listing_category', $listing->get_id() ) ) {
			// Get the event date from the listing
			$event_date = $listing->get_event_date();

			// Hide the dates field by changing it to hidden type with a default value
			if ( isset( $form_args['fields']['_dates'] ) ) {
				// Set the field to hidden
				$form_args['fields']['_dates']['display_type'] = 'hidden';
				$form_args['fields']['_dates']['required'] = false;

				// Set default value from event_date field
				if ( $event_date ) {
					// If event_date is a single date, use it for both start and end
					$event_date_formatted = date( 'Y-m-d', strtotime( $event_date ) );
					$form_args['fields']['_dates']['default'] = [
						$event_date_formatted,
						$event_date_formatted
					];
				} else {
					// Fallback to current date if no event date is set
					$form_args['fields']['_dates']['default'] = [
						date( 'Y-m-d' ),
						date( 'Y-m-d' )
					];
				}
			}

			// Also hide time field if it exists
			if ( isset( $form_args['fields']['_time'] ) ) {
				$form_args['fields']['_time']['display_type'] = 'hidden';
				$form_args['fields']['_time']['required'] = false;
			}

			// Add custom class to identify events category booking form
			if ( ! isset( $form_args['attributes'] ) ) {
				$form_args['attributes'] = [];
			}
			if ( ! isset( $form_args['attributes']['class'] ) ) {
				$form_args['attributes']['class'] = [];
			}

			$form_args['attributes']['class'][] = 'hp-booking-form--events';
		}
	}

	return $form_args;
}, 1000, 2 );

// Add custom CSS to hide the dates field for events or modify its appearance
add_action( 'wp_head', function() {
	// Check if we're on a listing page in the events category
	if ( is_singular( 'hp_listing' ) && has_term( 'events', 'hp_listing_category' ) ) {
		?>
		<style type="text/css">
			/* Hide the dates field for events category */
			.hp-booking-form--events .hp-form__field--date-range,
			.hp-booking-form--events .hp-form__field--date {
				display: none !important;
			}
		</style>
		<?php
	}
}, 100 );

// Custom: Add "Buy Now" button for WooCommerce synced products in Merch8ndise category
add_action( 'hivepress/v1/templates/listing_view_page/listing_description', function( $template ) {
	if ( is_singular( 'hp_listing' ) && has_term( 'merch8ndise', 'hp_listing_category' ) ) {
		$listing = $template->get_context( 'listing' );

		if ( $listing ) {
			$product_url = get_post_meta( $listing->get_id(), 'hp_product_url', true );
			$price_html = get_post_meta( $listing->get_id(), 'hp_price_formatted', true );

			if ( $product_url ) {
				?>
				<div class="hp-listing__product-info" style="margin: 20px 0; padding: 20px; background: #f8f8f8; border-radius: 8px;">
					<?php if ( $price_html ) : ?>
						<div class="hp-listing__product-price" style="font-size: 24px; font-weight: bold; margin-bottom: 15px; color: #333;">
							<?php echo wp_kses_post( $price_html ); ?>
						</div>
					<?php endif; ?>
					<a href="<?php echo esc_url( $product_url ); ?>" class="button hp-listing__product-button" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; transition: background 0.3s;">
						<?php esc_html_e( 'Buy Now', 'listinghive' ); ?>
					</a>
				</div>
				<style>
					.hp-listing__product-button:hover {
						background: #005a87;
						color: #fff;
					}
				</style>
				<?php
			}
		}
	}
}, 20 );

// Custom: Display price in listing cards for Merch8ndise category
add_action( 'hivepress/v1/templates/listing_view_block/listing_footer', function( $template ) {
	if ( is_object( $template ) && method_exists( $template, 'get_context' ) ) {
		$listing = $template->get_context( 'listing' );

		if ( $listing && has_term( 'merch8ndise', 'hp_listing_category', $listing->get_id() ) ) {
			$price_html = get_post_meta( $listing->get_id(), 'hp_price_formatted', true );

			if ( $price_html ) {
				?>
				<div class="hp-listing__product-price" style="font-size: 18px; font-weight: bold; color: #0073aa; margin-top: 10px;">
					<?php echo wp_kses_post( $price_html ); ?>
				</div>
				<?php
			}
		}
	}
}, 5 );

