<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

echo esc_html( $membership_plan->get_name() );
