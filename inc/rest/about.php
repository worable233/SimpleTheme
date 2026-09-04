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

/**
 * About data predates the current page implementation but remains public for
 * integrations. Keep its URL fields explicitly typed rather than passing an
 * arbitrary option object through the REST API.
 */
function simple_theme_sanitize_about_http_url( $value ) {
	$url = trim( wp_strip_all_tags( (string) $value ) );
	if ( '' === $url || 0 === strpos( $url, '//' ) ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if (
		! $parts ||
		empty( $parts['scheme'] ) ||
		empty( $parts['host'] ) ||
		! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true )
	) {
		return '';
	}

	return esc_url_raw( $url, array( 'http', 'https' ) );
}

function simple_theme_sanitize_about_link( $value ) {
	$url = trim( wp_strip_all_tags( (string) $value ) );
	if ( '' === $url || 0 === strpos( $url, '//' ) ) {
		return '';
	}

	if ( 0 === strpos( $url, '/' ) || 0 === strpos( $url, '?' ) || 0 === strpos( $url, '#' ) ) {
		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	return simple_theme_sanitize_about_http_url( $url );
}

function simple_theme_sanitize_about_text( $value, $limit = 500 ) {
	$text = sanitize_text_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
}

function simple_theme_sanitize_about_textarea( $value, $limit = 1000 ) {
	$text = sanitize_textarea_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
}

function simple_theme_sanitize_about_text_list( $value, $limit = 50 ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$items = array();
	foreach ( array_slice( $value, 0, $limit ) as $item ) {
		$text = simple_theme_sanitize_about_text( $item );
		if ( '' !== $text ) {
			$items[] = $text;
		}
	}

	return $items;
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

	if ( empty( $about_info ) ) {
		return new WP_REST_Response( array(), 200 );
	}

	$timeline = array();
	if ( ! empty( $about_info['timeline'] ) && is_array( $about_info['timeline'] ) ) {
		foreach ( array_slice( $about_info['timeline'], 0, 50 ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$timeline[] = array(
				'period'   => simple_theme_sanitize_about_text( $entry['period'] ?? '' ),
				'title'    => simple_theme_sanitize_about_text( $entry['title'] ?? '' ),
				'subtitle' => simple_theme_sanitize_about_text( $entry['subtitle'] ?? '' ),
				'image'    => simple_theme_sanitize_about_http_url( $entry['image'] ?? '' ),
			);
		}
	}

	$games = array();
	if ( ! empty( $about_info['games'] ) && is_array( $about_info['games'] ) ) {
		foreach ( array_slice( $about_info['games'], 0, 50 ) as $game ) {
			if ( ! is_array( $game ) ) {
				continue;
			}
			$games[] = array(
				'name' => simple_theme_sanitize_about_text( $game['name'] ?? '' ),
				'icon' => simple_theme_sanitize_about_http_url( $game['icon'] ?? '' ),
				'uid'  => simple_theme_sanitize_about_text( $game['uid'] ?? '' ),
			);
		}
	}

	$sponsors = array();
	if ( ! empty( $about_info['sponsorList'] ) && is_array( $about_info['sponsorList'] ) ) {
		foreach ( array_slice( $about_info['sponsorList'], 0, 100 ) as $sponsor ) {
			if ( ! is_array( $sponsor ) ) {
				continue;
			}
			$sponsors[] = array(
				'name'   => simple_theme_sanitize_about_text( $sponsor['name'] ?? '' ),
				'amount' => simple_theme_sanitize_about_text( $sponsor['amount'] ?? '' ),
			);
		}
	}

	return new WP_REST_Response(
		array(
			'avatar'             => simple_theme_sanitize_about_http_url( $about_info['avatar'] ?? '' ),
			'subtitleLines'       => simple_theme_sanitize_about_text_list( $about_info['subtitleLines'] ?? array() ),
			'identityTags'        => simple_theme_sanitize_about_text_list( $about_info['identityTags'] ?? array() ),
			'greeting'            => simple_theme_sanitize_about_text( $about_info['greeting'] ?? '' ),
			'sloganBlock'         => simple_theme_sanitize_about_textarea( $about_info['sloganBlock'] ?? '' ),
			'skills'              => simple_theme_sanitize_about_text_list( $about_info['skills'] ?? array() ),
			'timeline'            => $timeline,
			'mbtiType'            => simple_theme_sanitize_about_text( $about_info['mbtiType'] ?? '' ),
			'mbtiLabel'           => simple_theme_sanitize_about_text( $about_info['mbtiLabel'] ?? '' ),
			'mbtiImage'           => simple_theme_sanitize_about_http_url( $about_info['mbtiImage'] ?? '' ),
			'mbtiUrl'             => simple_theme_sanitize_about_link( $about_info['mbtiUrl'] ?? '' ),
			'games'               => $games,
			'animeTitle'          => simple_theme_sanitize_about_text( $about_info['animeTitle'] ?? '' ),
			'animeTagline'        => simple_theme_sanitize_about_text( $about_info['animeTagline'] ?? '' ),
			'musicArtists'        => simple_theme_sanitize_about_text( $about_info['musicArtists'] ?? '' ),
			'musicUrl'            => simple_theme_sanitize_about_link( $about_info['musicUrl'] ?? '' ),
			'location'            => simple_theme_sanitize_about_text( $about_info['location'] ?? '' ),
			'birthYear'           => max( 0, min( (int) gmdate( 'Y' ) + 1, (int) ( $about_info['birthYear'] ?? 0 ) ) ),
			'education'           => simple_theme_sanitize_about_text( $about_info['education'] ?? '' ),
			'occupation'          => simple_theme_sanitize_about_text( $about_info['occupation'] ?? '' ),
			'sponsorTotal'        => simple_theme_sanitize_about_text( $about_info['sponsorTotal'] ?? '' ),
			'sponsorList'         => $sponsors,
			'sponsorUrl'          => simple_theme_sanitize_about_link( $about_info['sponsorUrl'] ?? '' ),
			'donationWechatQr'    => simple_theme_sanitize_about_http_url( $about_info['donationWechatQr'] ?? '' ),
			'donationAlipayQr'    => simple_theme_sanitize_about_http_url( $about_info['donationAlipayQr'] ?? '' ),
			'donationTotal'       => simple_theme_sanitize_about_textarea( $about_info['donationTotal'] ?? '' ),
		),
		200
	);
}
