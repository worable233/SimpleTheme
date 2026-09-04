<?php
/**
 * Utility Helpers
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Option Read/Write ==========

function simple_theme_option( $key = '', $default = null ) {
	$cache = get_option( 'simple_theme_options', array() );
	if ( '' === $key ) {
		return $cache;
	}
	return isset( $cache[ $key ] ) ? $cache[ $key ] : $default;
}

function simple_theme_update_option( $key, $value ) {
	$options = get_option( 'simple_theme_options', array() );
	$options[ $key ] = $value;
	update_option( 'simple_theme_options', $options, false );
}

function simple_theme_update_options( $data ) {
	$options = get_option( 'simple_theme_options', array() );
	$options = array_merge( $options, $data );
	update_option( 'simple_theme_options', $options, false );
}

// ========== Sanitize Helpers ==========

function simple_theme_sanitize_choice( $value, array $allowed, $default ) {
	return in_array( $value, $allowed, true ) ? $value : $default;
}

function simple_theme_sanitize_bool( $value ) {
	return ! empty( $value );
}

/**
 * Return the direct, valid client address. Proxy forwarding headers are not
 * trusted here because their trust boundary is deployment-specific.
 */
function simple_theme_get_request_ip() {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Create a non-reversible, site-specific identifier for an IP address.
 */
function simple_theme_hash_ip( $ip = '' ) {
	$ip = $ip ? $ip : simple_theme_get_request_ip();
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return '';
	}

	return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
}

function simple_theme_get_option_number( $key, $default, $min, $max ) {
	$options = get_option( 'simple_theme_options', array() );
	$value   = isset( $options[ $key ] ) && is_numeric( $options[ $key ] ) ? (int) $options[ $key ] : null;
	if ( null === $value ) {
		$value = $default;
	}
	return max( $min, min( $max, $value ) );
}

// ========== Post Utilities ==========

function simple_theme_get_post_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	return array_map( function ( $term ) {
		return $term->name;
	}, $terms );
}

function simple_theme_calculate_post_stats( $post ) {
	$content = $post->post_content;
	$content = strip_shortcodes( $content );
	$content = wp_strip_all_tags( $content );
	$cjk_count = 0;
	if ( preg_match_all( '/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', $content, $matches ) ) {
		$cjk_count = count( $matches[0] );
	}
	$content_without_cjk = preg_replace( '/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', '', $content );
	$english_words = str_word_count( $content_without_cjk, 0, '0123456789' );
	$word_count = $cjk_count + $english_words;
	$reading_speed = simple_theme_get_option_number( 'reading_speed', 300, 100, 600 );
	$reading_time  = max( 1, ceil( $word_count / $reading_speed ) );

	return array(
		'wordCount'   => $word_count,
		'readingTime' => $reading_time,
	);
}

function simple_theme_get_current_commenter() {
	$user = wp_get_current_user();
	if ( $user->ID === 0 ) {
		return null;
	}
	return array(
		'displayName' => $user->display_name,
		'email'       => $user->user_email,
		'url'         => $user->user_url,
	);
}

// ========== IP Location (归属地) ==========

function simple_theme_get_ip_location( $ip ) {
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return null;
	}

	$options = get_option( 'simple_theme_options', array() );
	$api     = isset( $options['ip_location_api'] ) ? $options['ip_location_api'] : 'xinyew';
	$cache   = isset( $options['ip_location_cache'] ) ? (bool) $options['ip_location_cache'] : true;

	if ( $cache ) {
		$cache_key = 'st_ip_' . simple_theme_hash_ip( $ip );
		$location = get_transient( $cache_key );
		if ( false !== $location ) {
			return $location;
		}
		$location = simple_theme_query_ip_location( $ip, $api );
		if ( $location ) {
			set_transient( $cache_key, $location, DAY_IN_SECONDS );
		}
		return $location;
	}

	return simple_theme_query_ip_location( $ip, $api );
}

function simple_theme_query_ip_location( $ip, $api = 'xinyew' ) {
	switch ( $api ) {
		case 'ip.sb':
			return simple_theme_try_api_upk( $ip );
		case 'ip-api.com':
			return simple_theme_try_api_ipapi( $ip );
		case 'xinyew':
		default:
			return simple_theme_try_api_xinyew( $ip );
	}
}

function simple_theme_try_api_xinyew( $ip ) {
	if ( ! function_exists( 'wp_remote_get' ) ) {
		return null;
	}
	$response = wp_remote_get( 'https://api.xinyew.cn/api/baiduchaip?ip=' . rawurlencode( $ip ), array(
		'timeout' => 5,
	) );
	if ( is_wp_error( $response ) ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['status'] ) && '0' === $data['status'] && ! empty( $data['data'][0]['location'] ) ) {
		return $data['data'][0]['location'];
	}
	return null;
}

function simple_theme_try_api_upk( $ip ) {
	if ( ! function_exists( 'wp_remote_get' ) ) {
		return null;
	}
	$response = wp_remote_get( 'https://api.ip.sb/geoip/' . rawurlencode( $ip ), array(
		'timeout' => 5,
		'headers' => array( 'Accept' => 'application/json' ),
	) );
	if ( is_wp_error( $response ) ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['country'] ) && ! empty( $data['city'] ) ) {
		return $data['country'] . ' - ' . $data['city'];
	}
	return null;
}

function simple_theme_try_api_ipapi( $ip ) {
	if ( ! function_exists( 'wp_remote_get' ) ) {
		return null;
	}
	$response = wp_remote_get( 'http://ip-api.com/json/' . rawurlencode( $ip ) . '?lang=zh-CN', array(
		'timeout' => 5,
	) );
	if ( is_wp_error( $response ) ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! empty( $data['country'] ) && ! empty( $data['city'] ) && 'success' === ( $data['status'] ?? '' ) ) {
		return $data['country'] . ' - ' . $data['city'];
	}
	return null;
}
