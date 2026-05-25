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
			'notes'       => $bookmark->link_notes,
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
				'id'          => $cat->term_id,
				'name'        => $cat->name,
				'slug'        => $cat->slug,
				'description' => $cat->description,
				'links'       => $category_links,
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
		// Get configurable Gravatar base URL
		$gravatar_base = rtrim(get_option('simple_theme_options', [])['gravatar_base_url'] ?? 'https://weavatar.com/avatar/', '/');
		$url = "{$gravatar_base}/{$hash}?s={$size}&d=mp";
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
	// URL-encode non-ASCII bytes so parse_url doesn't truncate Chinese content
	$safe_url = preg_replace_callback(
		'/[^\x20-\x7e\/\?\#&=\.\-\_\,\:\@\%]/',
		function ( $m ) { return rawurlencode( $m[0] ); },
		$url
	);

	$home = trailingslashit( home_url() );

	$parsed_home = wp_parse_url( $home );
	$parsed_url  = wp_parse_url( $safe_url );

	if ( ! $parsed_url ) {
		return '/';
	}

	if ( isset( $parsed_url['host'] ) ) {
		if ( ( $parsed_home['host'] ?? '' ) !== $parsed_url['host'] ) {
			return $safe_url;
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

	$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
	$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

	return $path . $query . $fragment;
}

/**
 * Find a post by path, with direct DB fallback for Chinese/URL-encoded slugs
 * that get_page_by_path() cannot handle (sanitize_title_for_query strips non-ASCII).
 *
 * @param string $path       Request URI path.
 * @param array  $post_types Post types to search.
 * @return WP_Post|null
 */
function simple_theme_find_post_by_path( $path, $post_types ) {
	global $wpdb;

	// Normalize: remove trailing slash so get_page_by_path and slug matching work consistently
	$path = '/' . trim( $path, '/' );

	// Try get_page_by_path first (works for ASCII slugs)
	$post = get_page_by_path( $path, OBJECT, $post_types );
	if ( $post ) {
		return $post;
	}

	// Extract last segment as slug candidate
	$trimmed   = trim( $path, '/' );
	$parts     = explode( '/', $trimmed );
	$last_slug = end( $parts );
	if ( ! $last_slug ) {
		return null;
	}

	// Also try the last segment alone via get_page_by_path
	$post = get_page_by_path( $last_slug, OBJECT, $post_types );
	if ( $post ) {
		return $post;
	}

	// Build IN clause for post types (safe: array_map with static values)
	$in_types = "'" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "'";

	// Helper: escape % for $wpdb->prepare (sprintf interprets %e8 etc. as format specifiers)
	$escape_pct = function ( $s ) { return str_replace( '%', '%%', $s ); };

	// Strategy 1: URL-decoded slug (e.g. "test-说说")
	// This is what gets passed by default (PHP POST/GET automatically URL-decodes)
	$decoded = urldecode( $last_slug ); // safe: idempotent if already decoded
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_name = %s
		AND post_type IN ({$in_types})
		AND post_status = 'publish'
		LIMIT 1",
		$escape_pct( $decoded )
	) );
	if ( $row ) {
		return get_post( $row->ID );
	}

	// Strategy 2: URL-encoded slug (lowercase, as stored by WordPress)
	// e.g. "test-说说" → urlencode → "test-%E8%AF%B4%E8%AF%B4" → strtolower → "test-%e8%af%b4%e8%af%b4"
	$re_encoded = strtolower( urlencode( $decoded ) );
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_name = %s
		AND post_type IN ({$in_types})
		AND post_status = 'publish'
		LIMIT 1",
		$escape_pct( $re_encoded )
	) );
	if ( $row ) {
		return get_post( $row->ID );
	}

	// Strategy 3: URL-encoded slug (uppercase hex)
	// Fallback for sites that might store uppercase
	$re_encoded_upper = urlencode( $decoded );
	if ( $re_encoded_upper !== $re_encoded ) {
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_name = %s
			AND post_type IN ({$in_types})
			AND post_status = 'publish'
			LIMIT 1",
			$escape_pct( $re_encoded_upper )
		) );
		if ( $row ) {
			return get_post( $row->ID );
		}
	}

	// Strategy 4: LIKE fallback for truncated/partial slugs
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_name LIKE %s
		AND post_type IN ({$in_types})
		AND post_status = 'publish'
		LIMIT 1",
		$escape_pct( $wpdb->esc_like( $re_encoded ) . '%' )
	) );
	if ( $row ) {
		return get_post( $row->ID );
	}

	// Strategy 5: remove known CPT prefix and try again
	// e.g. /shuoshuo/test-说说 → try "test-说说" only
	$stripped = ltrim( $path, '/' );
	$cpt_slugs = array( 'shuoshuo' );
	foreach ( $cpt_slugs as $cpt_slug ) {
		if ( 0 === strpos( $stripped, $cpt_slug . '/' ) ) {
			$inner_path = '/' . substr( $stripped, strlen( $cpt_slug ) + 1 );
			$post = get_page_by_path( $inner_path, OBJECT, $post_types );
			if ( $post ) {
				return $post;
			}
		}
	}

	return null;
}

function simple_theme_resolve_path( WP_REST_Request $request ) {
	$path = $request->get_param( 'path' );
	if ( ! $path ) {
		return new WP_REST_Response( array( 'message' => 'Path required' ), 400 );
	}

	error_log( '[simple-theme] simple_theme_resolve_path called: path=' . $path );

	// Try to match WordPress native routes
	$home_url  = home_url( '/' );

	// URL-encode non-ASCII characters so wp_parse_url can handle the path
	// (PHP's parse_url cannot handle raw UTF-8; it truncates or mangles Chinese content)
	$safe_path = preg_replace_callback(
		'/[^\x20-\x7e\/\?\#&=\.\-\_\,\:\@\%]/',
		function ( $m ) { return rawurlencode( $m[0] ); },
		$path
	);

	$full_url = $safe_path;

	if ( ! preg_match( '#^https?://#', $safe_path ) ) {
		$full_url = $home_url . ltrim( $safe_path, '/' );
	}

	$internal_path = simple_theme_get_internal_path( $full_url );

	// Check if it's a post/CPT by slug (with Chinese slug fallback)
	$post = simple_theme_find_post_by_path( $internal_path, array( 'post', 'page', 'shuoshuo' ) );
	if ( $post ) {
		$rest_base_map = array(
			'post'     => 'posts',
			'page'     => 'pages',
			'shuoshuo' => 'shuoshuo',
		);
		$rest_base = $rest_base_map[ $post->post_type ] ?? $post->post_type . 's';
		return new WP_REST_Response( array(
			'type'      => $post->post_type,
			'id'        => $post->ID,
			'name'      => $post->post_name,
			'permalink' => get_permalink( $post ),
			'restUrl'   => rest_url( 'wp/v2/' . $rest_base . '/' . $post->ID . '?_embed=1' ),
			'path'      => $internal_path,
		), 200 );
	}

	// Check if it's a category
	$term = simple_theme_path_to_term( $internal_path );
	if ( $term ) {
		return new WP_REST_Response( array(
			'type'      => 'term',
			'id'        => $term->term_id,
			'name'      => $term->name,
			'taxonomy'  => $term->taxonomy,
			'permalink' => get_term_link( $term ),
			'path'      => $internal_path,
		), 200 );
	}

	// Check if it's the home page
	if ( '/' === $internal_path || '' === $internal_path ) {
		return new WP_REST_Response( array(
			'type' => 'home',
			'id'   => 0,
			'name' => '',
			'permalink' => $home_url,
			'path' => '/',
		), 200 );
	}

	return new WP_REST_Response( array(
		'type'      => '404',
		'id'        => 0,
		'name'      => trim( $internal_path, '/' ),
		'permalink' => $full_url,
		'path'      => $internal_path,
		'message'   => '页面未找到',
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
