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
 * Generate a context-aware <meta name="description"> for the current page.
 *
 * - Homepage:     使用站点副标题（tagline）
 * - 文章/页面:    使用摘要（excerpt），无摘要则取内容前 160 字
 * - 分类/标签:    使用分类描述
 * - 其他:         使用站点副标题
 */
function simple_theme_get_meta_description(): string {
	if ( is_front_page() || is_home() ) {
		$desc = get_bloginfo( 'description', 'display' );
		if ( ! empty( $desc ) ) {
			return $desc;
		}
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
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
 * Safety net: catch unhandled technical file requests before the SPA shell.
 *
 * Priority 5: runs after SEO plugins (typically 0-1) and WordPress core,
 * but before the theme template is loaded. Only paths not handled by
 * anything above reach this point.
 */
add_action( 'template_redirect', 'simple_theme_seo_safety_net', 5 );
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
