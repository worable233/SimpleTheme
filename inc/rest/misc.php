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

// ========== Route core Gravatar URLs through the configurable mirror ==========
//
// WordPress core (admin bar, get_avatar()/get_avatar_url() for registered
// commenters, etc.) emits https://secure.gravatar.com/avatar/… URLs. On
// networks where gravatar.com is unreachable, the browser hangs on each of
// these image requests until its TCP timeout (~1 min), which is exactly the
// slow/failed avatar requests seen in the network panel. Rewrite the host of
// any gravatar.com avatar URL to the configurable mirror (default
// weavatar.com, same as the avatar proxy) so every avatar resolves quickly.
// Runs on the final get_avatar_data so local/QQ/upload URLs (which never point
// at gravatar.com) are left untouched.
add_filter( 'get_avatar_data', 'simple_theme_mirror_gravatar_host', 20 );
function simple_theme_mirror_gravatar_host( $args ) {
	if ( empty( $args['url'] ) ) {
		return $args;
	}
	$options = get_option( 'simple_theme_options', array() );
	$base    = ( is_array( $options ) && ! empty( $options['gravatar_base_url'] ) )
		? $options['gravatar_base_url']
		: 'https://weavatar.com/avatar/';
	$base = rtrim( $base, '/' );
	if ( '' === $base ) {
		return $args;
	}
	$args['url'] = preg_replace(
		'#^https?://(?:[a-z0-9-]+\.)*gravatar\.com/avatar#i',
		$base,
		$args['url']
	);
	return $args;
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
 * Find a post by path.
 *
 * 优先走 WordPress 原生解析：
 *   ① url_to_postid() — 基于 rewrite 规则，正确处理日期型固定链接、子目录站点、CPT
 *   ② get_page_by_path() — 层级页面路径
 *   ③ WP_Query post_name__in — 中文/URL 编码 slug 兑底
 *      （get_page_by_path 的 sanitize 会丢弃非 ASCII，数据库存的是编码后的 post_name）
 *
 * @param string $path       Request URI path.
 * @param array  $post_types Post types to search.
 * @return WP_Post|null
 */
function simple_theme_find_post_by_path( $path, $post_types ) {
	// Normalize: remove trailing slash so path matching works consistently
	$path = '/' . trim( $path, '/' );

	// ① WordPress 原生 rewrite 解析
	$post_id = url_to_postid( home_url( $path ) );
	if ( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'publish' === $post->post_status && in_array( $post->post_type, $post_types, true ) ) {
			return $post;
		}
	}

	// ② 层级路径（ASCII slug 页面/文章）
	$post = get_page_by_path( $path, OBJECT, $post_types );
	if ( $post && 'publish' === $post->post_status ) {
		return $post;
	}

	// ③ 末段 slug 兑底：一次 WP_Query 同时尝试解码/小写编码/大写编码/原样四种候选
	$parts     = explode( '/', trim( $path, '/' ) );
	$last_slug = end( $parts );
	if ( ! $last_slug ) {
		return null;
	}
	$decoded    = urldecode( $last_slug ); // safe: idempotent if already decoded
	$candidates = array_unique( array(
		$decoded,
		strtolower( urlencode( $decoded ) ), // WordPress 存储中文 slug 的常规形式
		urlencode( $decoded ),
		$last_slug,
	) );

	$query = new WP_Query( array(
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'post_name__in'  => $candidates,
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		// 必须忽略置顶：否则 WP_Query 会把置顶文章强制前置到任意查询结果，
		// 导致不存在的 slug（如 /tag/xxx/）被误解析成置顶文章
		'ignore_sticky_posts' => true,
	) );

	return $query->posts ? $query->posts[0] : null;
}

function simple_theme_resolve_path( WP_REST_Request $request ) {
	$path = $request->get_param( 'path' );
	if ( ! $path ) {
		return new WP_REST_Response( array( 'message' => 'Path required' ), 400 );
	}

	// 先剥离 query string（如 /?s=test）：查询参数不参与路径解析，
	// 否则 "/?s=xxx" 会被后续兼容逻辑误判为分类/文章
	$path = preg_replace( '/[?#].*$/', '', $path );
	if ( '' === $path || '/' === $path ) {
		return new WP_REST_Response( array(
			'type' => 'home',
			'id'   => 0,
			'name' => '',
			'permalink' => home_url( '/' ),
			'path' => '/',
		), 200 );
	}

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

	// Check if it's a date archive (/YYYY/ or /YYYY/MM/) — archives 块/日历链接
	if ( preg_match( '#^/(\d{4})(?:/(\d{1,2}))?/?$#', $internal_path, $m ) ) {
		$year  = (int) $m[1];
		$month = isset( $m[2] ) ? (int) $m[2] : 0;
		if ( $year >= 1970 && $year <= 2200 && $month <= 12 ) {
			return new WP_REST_Response( array(
				'type'  => 'date',
				'id'    => 0,
				'name'  => $month ? sprintf( '%d 年 %d 月', $year, $month ) : sprintf( '%d 年', $year ),
				'year'  => $year,
				'month' => $month,
				'permalink' => $month ? get_month_link( $year, $month ) : get_year_link( $year ),
				'path'  => $internal_path,
			), 200 );
		}
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

	$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$slug = ltrim( $normalized, '/' );
	if ( $home_path ) {
		$slug = preg_replace( '#^' . preg_quote( $home_path, '#' ) . '/?#', '', $slug );
	}

	// ① WordPress 原生分类路径解析（自动处理 category_base 与层级）
	$category = get_category_by_path( $slug, false );
	if ( $category instanceof WP_Term ) {
		return $category;
	}

	// ② 末段 slug 兑底（含中文 slug 的解码/编码候选），覆盖标签与非常规分类路径
	$parts = explode( '/', $slug );
	$last  = end( $parts ) ?: $slug;
	$decoded    = urldecode( $last );
	$candidates = array_unique( array( $last, $decoded, strtolower( urlencode( $decoded ) ) ) );

	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		foreach ( $candidates as $candidate ) {
			$term = get_term_by( 'slug', $candidate, $taxonomy );
			if ( $term ) {
				return $term;
			}
		}
	}

	return null;
}
