<?php
/**
 * Simple Theme 的主题引导与 WordPress 集成。
 *
 * @package SimpleTheme
 */

// ============================================================
// 1. Required files (in order)
// ============================================================

// Sakurairo block compatibility (must be loaded first, before Gutenberg)
if ( file_exists( __DIR__ . '/inc/blocks/sakurairo.php' ) ) {
	require_once __DIR__ . '/inc/blocks/sakurairo.php';
}

// Core modules
require_once __DIR__ . '/inc/core/setup.php';
require_once __DIR__ . '/inc/core/helpers.php';
require_once __DIR__ . '/inc/core/assets.php';
require_once __DIR__ . '/inc/core/widgets.php';

// Global admin theme — Vue shell + scoped CSS (no @layer; see inc/core/admin-theme.php)
require_once __DIR__ . '/inc/core/admin-theme.php';

// Enable WordPress Link Manager (disabled by default since WP 3.5)
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

// Auth handler — login/register/password-reset REST API + wp-login.php interception
require_once __DIR__ . '/inc/core/auth-handler.php';

// SEO / special route safety net (must load after core modules)
require_once __DIR__ . '/inc/core/seo-handler.php';

// Admin modules
require_once __DIR__ . '/inc/admin/options.php';
require_once __DIR__ . '/inc/admin/menu.php';

// SMTP Mail handler — configures PHPMailer from theme settings (must load after options.php)
require_once __DIR__ . '/inc/core/smtp-handler.php';

// Local Avatars — user-uploaded custom avatars (loads conditionally based on setting)
require_once __DIR__ . '/inc/core/local-avatars.php';

// REST API modules
require_once __DIR__ . '/inc/rest/site-info.php';
require_once __DIR__ . '/inc/rest/posts.php';
require_once __DIR__ . '/inc/rest/comments.php';
require_once __DIR__ . '/inc/rest/navigation.php';
require_once __DIR__ . '/inc/rest/misc.php';
require_once __DIR__ . '/inc/rest/about.php';
require_once __DIR__ . '/inc/rest/register.php';

// Email templates — HTML email formatting with multiple styles
require_once __DIR__ . '/inc/core/email-templates.php';

// ============================================================
// 2. CORS — early hook (before headers sent)
// ============================================================
// (handled in inc/core/setup.php via init hook)



// ============================================================
// 3. Service Worker - serve with root scope
// ============================================================
add_action( 'init', function () {
    $request_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    if ( ! is_string( $request_path ) ) {
        return;
    }

    // Serve the worker relative to the WordPress home path so subdirectory
    // installs keep control inside their own site instead of claiming `/`.
    $home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $home_path = '/' . trim( is_string( $home_path ) ? $home_path : '', '/' );
    $relative_path = $request_path;
    if ( '/' !== $home_path ) {
        if ( $request_path === $home_path ) {
            $relative_path = '/';
        } elseif ( 0 === strpos( $request_path, trailingslashit( $home_path ) ) ) {
            $relative_path = substr( $request_path, strlen( $home_path ) );
        }
    }
    $worker_scope = trailingslashit( $home_path );

    if ( $relative_path === '/sw.js' ) {
        $sw_path = __DIR__ . '/dist/sw.js';
        if ( file_exists( $sw_path ) ) {
            header( 'Service-Worker-Allowed: ' . $worker_scope );
            header( 'Content-Type: application/javascript' );
            header( 'Cache-Control: no-cache, max-age=0' );
            readfile( $sw_path );
        } else {
            header( 'HTTP/1.1 404 Not Found' );
            echo '/* Service Worker not found */';
        }
        exit;
    }

    // Serve workbox dependency at root scope (imported by sw.js via importScripts)
    if ( preg_match( '#^/workbox-\w+\.js$#', $relative_path ) ) {
        $filename = basename( $relative_path );
        $wb_path  = __DIR__ . '/dist/' . $filename;
        if ( file_exists( $wb_path ) ) {
            header( 'Content-Type: application/javascript' );
            header( 'Cache-Control: max-age=86400' );
            readfile( $wb_path );
        } else {
            header( 'HTTP/1.1 404 Not Found' );
            echo '/* Workbox script not found */';
        }
        exit;
    }

    // Serve manifest.webmanifest at root scope (precached by sw.js)
    if ( $relative_path === '/manifest.webmanifest' ) {
        $mf_path = __DIR__ . '/dist/manifest.webmanifest';
        if ( file_exists( $mf_path ) ) {
            header( 'Content-Type: application/manifest+json' );
            header( 'Cache-Control: max-age=86400' );
            readfile( $mf_path );
        } else {
            header( 'HTTP/1.1 404 Not Found' );
            echo '{}';
        }
        exit;
    }
} );
