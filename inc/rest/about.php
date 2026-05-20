<?php
/**
 * REST: About Info Endpoint
 *
 * Returns the about page data stored in theme options.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simple_theme_get_about() {
	$theme_options = get_option( 'simple_theme_options', array() );
	$about_info    = array();

	if ( ! empty( $theme_options['about_info'] ) ) {
		$decoded = json_decode( $theme_options['about_info'], true );
		if ( is_array( $decoded ) ) {
			$about_info = $decoded;
		}
	}

	return new WP_REST_Response( $about_info, 200 );
}
