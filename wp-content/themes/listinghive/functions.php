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

