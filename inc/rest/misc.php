<?php
/**
 * REST: Miscellaneous Endpoints (Links, Avatar, Path Resolution, Illustration)
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Links ==========

function simple_theme_get_links() {
	$bookmarks = get_bookmarks( array(
		'orderby'        => 'rating',
		'order'          => 'DESC',
		'category_name'  => '',
		'hide_invisible' => 1,
		'show_updated'   => 0,
		'include'        => '',
		'exclude'        => '',
	) );

	$categories = get_terms( array(
		'taxonomy'   => 'link_category',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	$links = array();
	foreach ( $bookmarks as $bookmark ) {
		$cat_ids = wp_get_object_terms( $bookmark->link_id, 'link_category', array( 'fields' => 'ids' ) );
		$links[] = array(
			'id'          => $bookmark->link_id,
			'name'        => $bookmark->link_name,
			'url'         => $bookmark->link_url,
			'description' => $bookmark->link_description,
			'image'       => $bookmark->link_image,
			'rating'      => $bookmark->link_rating,
			'categories'  => $cat_ids ? array_map( 'intval', $cat_ids ) : array(),
			'target'      => $bookmark->link_target,
		);
	}

	$result = array();
	foreach ( $categories as $cat ) {
		$category_links = array();
		foreach ( $links as $link ) {
			if ( in_array( $cat->term_id, $link['categories'] ) ) {
				$category_links[] = $link;
			}
		}
		if ( ! empty( $category_links ) ) {
			$result[] = array(
				'id'    => $cat->term_id,
				'name'  => $cat->name,
				'slug'  => $cat->slug,
				'links' => $category_links,
			);
		}
	}

	// Collect uncategorized links (no category assigned) under '未分类'
	$uncategorized = array();
	foreach ( $links as $link ) {
		if ( empty( $link['categories'] ) ) {
			$uncategorized[] = $link;
		}
	}
	if ( ! empty( $uncategorized ) ) {
		$result[] = array(
			'id'    => 0,
			'name'  => '未分类',
			'slug'  => 'uncategorized',
			'links' => $uncategorized,
		);
	}

	return new WP_REST_Response( $result, 200 );
}

// ========== Avatar Proxy ==========

function simple_theme_avatar_proxy( WP_REST_Request $request ) {
	$qq   = $request->get_param( 'qq' );
	$hash = $request->get_param( 'hash' );
	$size = min( 200, max( 40, (int) $request->get_param( 's' ) ?: 64 ) );

	if ( $qq ) {
		$url = "https://q1.qlogo.cn/g?b=qq&nk={$qq}&s={$size}";
	} elseif ( $hash ) {
		$url = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
	} else {
		wp_die( 'Missing parameter', '', 400 );
	}

	$response = wp_remote_get( $url, array(
		'timeout'    => 1,
		'user-agent' => 'SimpleTheme Avatar Proxy',
	) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		wp_die( 'Failed to fetch avatar', '', 502 );
	}

	$content_type = wp_remote_retrieve_header( $response, 'content-type' ) ?: 'image/png';
	$body         = wp_remote_retrieve_body( $response );

	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $_SERVER['HTTP_ORIGIN'] ) );
		header( 'Access-Control-Allow-Credentials: true' );
	}
	header( 'Content-Type: ' . $content_type );
	header( 'Cache-Control: public, max-age=86400' );
	echo $body;
	exit;
}

// ========== Illustration ==========

function simple_theme_serve_illustration( WP_REST_Request $request ) {
	$name = $request->get_param( 'name' );
	if ( ! $name ) {
		wp_die( 'Illustration name required', '', 400 );
	}

	$file = get_theme_file_path( 'dist/illustrations/' . sanitize_file_name( $name ) );
	if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
		wp_die( 'Illustration not found', '', 404 );
	}

	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $_SERVER['HTTP_ORIGIN'] ) );
		header( 'Access-Control-Allow-Credentials: true' );
	}

	header( 'Content-Type: image/svg+xml' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Cache-Control: public, max-age=31536000, immutable' );
	echo file_get_contents( $file );
	exit;
}

// ========== Path Resolution ==========

function simple_theme_get_internal_path( $url ) {
	$home = trailingslashit( home_url() );

	$parsed_home = wp_parse_url( $home );
	$parsed_url  = wp_parse_url( $url );

	if ( ! $parsed_url ) {
		return '/';
	}

	if ( isset( $parsed_url['host'] ) ) {
		if ( ( $parsed_home['host'] ?? '' ) !== $parsed_url['host'] ) {
			return $url;
		}
		$path = $parsed_url['path'] ?? '/';
		// Strip home path (for subdirectory installs)
		$home_path = $parsed_home['path'] ?? '/';
		if ( '/' !== $home_path && 0 === strpos( $path, $home_path ) ) {
			$path = substr( $path, strlen( $home_path ) - 1 );
		}
	} else {
		$path = $parsed_url['path'] ?? '/';
	}

	$query  = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
	$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

	return $path . $query . $fragment;
}

function simple_theme_resolve_path( WP_REST_Request $request ) {
	$path = $request->get_param( 'path' );
	if ( ! $path ) {
		return new WP_REST_Response( array( 'error' => 'Path required' ), 400 );
	}

	// Try to match WordPress native routes
	$home_url  = home_url( '/' );
	$full_url  = $path;

	if ( ! preg_match( '#^https?://#', $path ) ) {
		$full_url = $home_url . ltrim( $path, '/' );
	}

	$internal_path = simple_theme_get_internal_path( $full_url );

	// Check if it's a post/CPT by slug
	$post = get_page_by_path( $internal_path, OBJECT, array( 'post', 'page', 'shuoshuo' ) );
	if ( $post ) {
		return new WP_REST_Response( array(
			'type' => 'post',
			'id'   => $post->ID,
			'slug' => $post->post_name,
			'url'  => get_permalink( $post ),
			'path' => $internal_path,
		), 200 );
	}

	// Check if it's a category
	$term = simple_theme_path_to_term( $internal_path );
	if ( $term ) {
		return new WP_REST_Response( array(
			'type' => 'term',
			'id'   => $term->term_id,
			'slug' => $term->slug,
			'url'  => get_term_link( $term ),
			'path' => $internal_path,
		), 200 );
	}

	// Check if it's the home page
	if ( '/' === $internal_path || '' === $internal_path ) {
		return new WP_REST_Response( array(
			'type' => 'home',
			'id'   => 0,
			'slug' => '',
			'url'  => $home_url,
			'path' => '/',
		), 200 );
	}

	return new WP_REST_Response( array(
		'type' => 'unknown',
		'id'   => 0,
		'slug' => trim( $internal_path, '/' ),
		'url'  => $full_url,
		'path' => $internal_path,
	), 200 );
}

function simple_theme_normalize_requested_path( $path ) {
	$decoded = urldecode( $path );
	$decoded = preg_replace( '/\?.*$/', '', $decoded );
	$decoded = rtrim( $decoded, '/' );
	if ( '' === $decoded ) {
		$decoded = '/';
	}
	return $decoded;
}

function simple_theme_path_to_term( $path ) {
	$normalized = simple_theme_normalize_requested_path( $path );

	$taxonomies = array( 'category', 'post_tag' );
	$home_path  = trim( parse_url( home_url( '/' ), PHP_URL_PATH ) ?? '', '/' );

	$slug = ltrim( $normalized, '/' );
	if ( $home_path ) {
		$slug = preg_replace( '#^' . preg_quote( $home_path, '#' ) . '/?#', '', $slug );
	}

	// Remove category base if present
	$category_base = get_option( 'category_base' ) ?: 'category';
	$slug = preg_replace( '#^' . preg_quote( $category_base, '#' ) . '/#', '', $slug );

	foreach ( $taxonomies as $taxonomy ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			return $term;
		}

		// Try with full path as slug
		$parts = explode( '/', $slug );
		$last_slug = end( $parts );
		if ( $last_slug && $last_slug !== $slug ) {
			$term = get_term_by( 'slug', $last_slug, $taxonomy );
			if ( $term ) {
				return $term;
			}
		}
	}

	return null;
}
