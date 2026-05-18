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

function simple_theme_get_option_number( $key, $default, $min, $max ) {
	$options = get_option( 'simple_theme_options', array() );
	$value   = isset( $options[ $key ] ) ? (int) $options[ $key ] : $default;
	return max( $min, min( $max, $value ) );
}

// ========== Post Utilities ==========

function simple_theme_get_post_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	return array_map( function ( $term ) {
		return array(
			'id'   => $term->term_id,
			'name' => $term->name,
			'slug' => $term->slug,
		);
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
	$reading_time = max( 1, ceil( $word_count / simple_theme_option('reading_speed', 300) ) );

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

// ========== IP Location ==========

function simple_theme_get_ip_location( $ip ) {
	$location = get_transient( 'st_ip_' . $ip );
	if ( false !== $location ) {
		return $location;
	}
	$location = simple_theme_query_ip_location( $ip );
	if ( $location ) {
		set_transient( 'st_ip_' . $ip, $location, DAY_IN_SECONDS );
	}
	return $location;
}

function simple_theme_query_ip_location( $ip ) {
	$result = simple_theme_try_api_upk( $ip );
	if ( $result ) {
		return $result;
	}
	$result = simple_theme_try_api_ipapi( $ip );
	if ( $result ) {
		return $result;
	}
	return null;
}

function simple_theme_try_api_upk( $ip ) {
	if ( ! function_exists( 'wp_remote_get' ) ) {
		return null;
	}
	$response = wp_remote_get( "https://api.ip.sb/geoip/{$ip}", array(
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
	$response = wp_remote_get( "http://ip-api.com/json/{$ip}?lang=zh-CN", array(
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
