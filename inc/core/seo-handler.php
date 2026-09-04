<?php
/**
 * SEO / Special Route Safety Net
 *
 * Ensures SEO-critical paths (sitemap.xml, robots.txt, feeds, etc.) are
 * handled by WordPress core or SEO plugins BEFORE the Vue SPA shell renders.
 *
 * WordPress Core handles automatically (exits before theme loads):
 *   ─ /robots.txt         via do_robots() inside WP::handle_404()
 *   ─ /feed/, /rss/, etc. via do_feed() on the 'wp' action
 *   ─ /wp-json/*          via rest_api_loaded() very early
 *
 * SEO Plugins handle automatically (exit during template_redirect):
 *   ─ Yoast SEO           ─ Rank Math
 *   ─ All in One SEO      ─ Jetpack (sitemaps)
 *   ─ Google XML Sitemaps ─ XML Sitemap & Google News
 *   ─ SEOPress            ─ The SEO Framework
 *     … and any other plugin using standard WordPress hooks.
 *
 * This file ONLY handles paths that somehow fell through all of the above,
 * returning proper content-type responses instead of the SPA HTML shell.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve frontend-only routes which WordPress does not otherwise know about.
 *
 * @return array{path:string,title:string,noindex:bool}|null
 */
function simple_theme_get_virtual_frontend_route() {
	$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );
	$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( ! is_string( $request_path ) || ! is_string( $home_path ) ) {
		return null;
	}

	$relative_path = '/' . trim( $request_path, '/' );
	$home_path     = '/' . trim( $home_path, '/' );
	if ( '/' !== $home_path ) {
		if ( $relative_path === $home_path ) {
			$relative_path = '/';
		} elseif ( 0 === strpos( $relative_path, trailingslashit( $home_path ) ) ) {
			$relative_path = substr( $relative_path, strlen( $home_path ) );
		}
	}

	$relative_path = '/' . trim( $relative_path, '/' );
	$routes        = array(
		'/go'        => array( 'title' => '外部链接跳转', 'noindex' => true ),
		'/archives'  => array( 'title' => '归档', 'noindex' => false ),
		'/about'     => array( 'title' => '关于', 'noindex' => false ),
		'/links'     => array( 'title' => '友人帐', 'noindex' => false ),
		'/shuoshuo'  => array( 'title' => '说说', 'noindex' => false ),
	);

	if ( isset( $routes[ $relative_path ] ) ) {
		return array(
			'path'    => $relative_path,
			'title'   => $routes[ $relative_path ]['title'],
			'noindex' => $routes[ $relative_path ]['noindex'],
		);
	}

	if ( preg_match( '#^/categories/([^/]+)$#', $relative_path, $matches ) ) {
		$slug = sanitize_title( rawurldecode( $matches[1] ) );
		$term = $slug ? get_term_by( 'slug', $slug, 'category' ) : false;
		if ( ! ( $term instanceof WP_Term ) ) {
			return null;
		}

		return array(
			'path'    => $relative_path,
			'title'   => $term->name,
			'noindex' => false,
		);
	}

	return null;
}

/**
 * Return a route only while it is acting as a WordPress 404 replacement.
 * This prevents reserved SPA paths from overriding real Pages or archives.
 *
 * @return array{path:string,title:string,noindex:bool}|null
 */
function simple_theme_get_active_virtual_frontend_route() {
	if ( isset( $GLOBALS['simple_theme_virtual_frontend_route'] ) && is_array( $GLOBALS['simple_theme_virtual_frontend_route'] ) ) {
		return $GLOBALS['simple_theme_virtual_frontend_route'];
	}

	return is_404() ? simple_theme_get_virtual_frontend_route() : null;
}

/**
 * Return the canonical permalink for a frontend-only route.
 *
 * @param array{path:string,title:string,noindex:bool} $route Route metadata.
 */
function simple_theme_get_virtual_frontend_route_url( array $route ): string {
	return home_url( user_trailingslashit( ltrim( $route['path'], '/' ) ) );
}

/**
 * Supply a meaningful browser title for a frontend-only route.
 */
add_filter( 'document_title_parts', 'simple_theme_virtual_frontend_document_title', 20 );
function simple_theme_virtual_frontend_document_title( $parts ) {
	$route = simple_theme_get_active_virtual_frontend_route();
	if ( $route ) {
		$parts['title'] = $route['title'];
	}

	return $parts;
}

/**
 * Generate a context-aware <meta name="description"> for the current page.
 *
 * - Homepage:     使用站点副标题（tagline）
 * - 文章/页面:    使用摘要（excerpt），无摘要则取内容前 160 字
 * - 分类/标签:    使用分类描述
 * - 其他:         使用站点副标题
 */
function simple_theme_get_meta_description(): string {
	$virtual_route = simple_theme_get_active_virtual_frontend_route();
	if ( $virtual_route ) {
		$site_description = get_bloginfo( 'description', 'display' );
		return $site_description ? $virtual_route['title'] . ' - ' . $site_description : $virtual_route['title'];
	}

	if ( is_front_page() || is_home() ) {
		$desc = get_bloginfo( 'description', 'display' );
		if ( ! empty( $desc ) ) {
			return $desc;
		}
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			// 密码保护文章不得泄漏摘要/正文
			if ( post_password_required( $post ) ) {
				return get_bloginfo( 'description', 'display' );
			}
			// 有手动摘要则用摘要
			if ( ! empty( $post->post_excerpt ) ) {
				return wp_trim_words( $post->post_excerpt, 40 );
			}
			// 否则取正文前 160 字符
			$content = wp_strip_all_tags( $post->post_content, true );
			$content = preg_replace( '/\s+/', ' ', $content );
			return mb_substr( $content, 0, 160 );
		}
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && ! empty( $term->description ) ) {
			return wp_trim_words( $term->description, 40 );
		}
	}

	// 兜底：站点副标题
	return get_bloginfo( 'description', 'display' );
}

/**
 * Whether the theme should output SEO meta (description / OG / JSON-LD).
 *
 * Steps aside automatically when a major SEO plugin is active, and can be
 * disabled entirely via the 'simple_theme_output_seo_meta' filter.
 */
function simple_theme_should_output_seo_meta(): bool {
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' ) ) {
		return false;
	}
	return (bool) apply_filters( 'simple_theme_output_seo_meta', true );
}

/**
 * Resolve the share image for the current page.
 *
 * Featured image (large) → site icon (512px) → none.
 *
 * @return array{url:string,width:int,height:int}|null
 */
function simple_theme_get_share_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
			);
		}
	}

	$icon = get_site_icon_url( 512 );
	if ( $icon ) {
		return array(
			'url'    => $icon,
			'width'  => 512,
			'height' => 512,
		);
	}

	return null;
}

/**
 * Output canonical and robots directives for virtual frontend routes.
 */
add_action( 'wp_head', 'simple_theme_output_virtual_frontend_route_meta', 3 );
function simple_theme_output_virtual_frontend_route_meta(): void {
	if ( ! simple_theme_should_output_seo_meta() ) {
		return;
	}

	$route = simple_theme_get_active_virtual_frontend_route();
	if ( ! $route ) {
		return;
	}

	echo '<link rel="canonical" href="' . esc_url( simple_theme_get_virtual_frontend_route_url( $route ) ) . '">' . "\n";
	if ( $route['noindex'] ) {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
}

/**
 * Output <meta name="description"> in wp_head.
 */
add_action( 'wp_head', 'simple_theme_output_meta_description', 4 );
function simple_theme_output_meta_description(): void {
	if ( ! simple_theme_should_output_seo_meta() ) {
		return;
	}

	$desc = simple_theme_get_meta_description();
	if ( '' === $desc ) {
		return;
	}
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}

/**
 * Output Open Graph + Twitter Card meta tags.
 */
add_action( 'wp_head', 'simple_theme_output_og_meta', 5 );
function simple_theme_output_og_meta(): void {
	if ( ! simple_theme_should_output_seo_meta() ) {
		return;
	}

	// 404 / 搜索页没有可分享对象
	if ( is_404() || is_search() ) {
		return;
	}

	$virtual_route = simple_theme_get_active_virtual_frontend_route();

	// 解析当前页 URL（与 core rel_canonical 逻辑对齐）
	$url = '';
	if ( $virtual_route ) {
		$url = simple_theme_get_virtual_frontend_route_url( $virtual_route );
	} elseif ( is_singular() ) {
		$url = get_permalink();
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		$url  = is_wp_error( $link ) ? '' : $link;
	} elseif ( is_front_page() || is_home() ) {
		$url = home_url( '/' );
	}

	$title = wp_get_document_title();
	$desc  = simple_theme_get_meta_description();
	$image = simple_theme_get_share_image();

	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( '' !== $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $url ) {
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image['url'] ) . '">' . "\n";
		if ( $image['width'] && $image['height'] ) {
			echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
			echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";
		}
	}

	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}

	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}

/**
 * Output JSON-LD structured data (WebSite on home, BlogPosting on posts).
 */
add_action( 'wp_head', 'simple_theme_output_json_ld', 6 );
function simple_theme_output_json_ld(): void {
	if ( ! simple_theme_should_output_seo_meta() ) {
		return;
	}

	$data = null;

	if ( is_front_page() || is_home() ) {
		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'description'     => get_bloginfo( 'description', 'display' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
	} elseif ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$image     = simple_theme_get_share_image();
		$site_icon = get_site_icon_url( 512 );
		$publisher = array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
		);
		if ( $site_icon ) {
			$publisher['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $site_icon,
			);
		}

		$data = array_filter(
			array(
				'@context'         => 'https://schema.org',
				'@type'            => 'BlogPosting',
				'headline'         => get_the_title( $post ),
				'description'      => simple_theme_get_meta_description(),
				'datePublished'    => get_the_date( 'c', $post ),
				'dateModified'     => get_the_modified_date( 'c', $post ),
				'mainEntityOfPage' => get_permalink( $post ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				),
				'publisher'        => $publisher,
				'image'            => $image ? $image['url'] : null,
			)
		);
	}

	if ( ! $data ) {
		return;
	}

	echo '<script type="application/ld+json">' .
		wp_json_encode(
			$data,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) .
		'</script>' . "\n";
}

/**
 * Safety net: catch unhandled technical file requests before the SPA shell.
 *
 * Priority 5: runs after SEO plugins (typically 0-1) and WordPress core,
 * but before the theme template is loaded. Only paths not handled by
 * anything above reach this point.
 */
add_action( 'template_redirect', 'simple_theme_seo_safety_net', 5 );

/**
 * Keep routes owned by the frontend SPA from inheriting WordPress' 404
 * status when no matching Page exists in the database.
 */
add_action( 'template_redirect', 'simple_theme_mark_virtual_frontend_routes', 4 );
function simple_theme_mark_virtual_frontend_routes() {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
		return;
	}

	// Existing Pages, archives, and unknown paths retain WordPress' own state.
	if ( ! is_404() ) {
		return;
	}

	$route = simple_theme_get_virtual_frontend_route();
	if ( ! $route ) {
		return;
	}

	$GLOBALS['simple_theme_virtual_frontend_route'] = $route;
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}
	status_header( 200 );
}

function simple_theme_seo_safety_net() {
	// Never interfere with admin, REST API, or AJAX
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
		return;
	}

	$path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );

	// ── XML files (sitemaps, WLW manifest, etc.) ──
	// If an SEO plugin was going to handle this, it already exited.
	// If not, return a proper XML 404 to prevent the SPA shell from showing.
	if ( preg_match( '/\.xml$/i', $path ) ) {
		status_header( 404 );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );
		echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?>' . "\n";
		echo '<error><message>404 Not Found</message></error>' . "\n";
		exit;
	}

	// ── Plain text files ──
	// (robots.txt is already handled by WordPress core above,
	//  so this catches any other .txt paths that fell through.)
	if ( preg_match( '/\.txt$/i', $path ) ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		echo "404 Not Found\n";
		exit;
	}
}
