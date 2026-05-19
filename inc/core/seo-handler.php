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
