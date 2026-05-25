<?php
/**
 * Simple Theme 的主题引导与 WordPress 集成�? *
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

// Global admin 小红书 theme (CSS @layer isolation)
require_once __DIR__ . '/inc/core/admin-theme.php';

// Auth handler — login/register/password-reset REST API + wp-login.php interception
require_once __DIR__ . '/inc/core/auth-handler.php';

// SEO / special route safety net (must load after core modules)
require_once __DIR__ . '/inc/core/seo-handler.php';

// Crawler bot detection & native WordPress fallback (for search engine indexing)
require_once __DIR__ . '/inc/core/crawler-handler.php';

// Admin modules
require_once __DIR__ . '/inc/admin/options.php';
require_once __DIR__ . '/inc/admin/menu.php';

// SMTP Mail handler — configures PHPMailer from theme settings (must load after options.php)
require_once __DIR__ . '/inc/core/smtp-handler.php';

// REST API modules
require_once __DIR__ . '/inc/rest/site-info.php';
require_once __DIR__ . '/inc/rest/posts.php';
require_once __DIR__ . '/inc/rest/comments.php';
require_once __DIR__ . '/inc/rest/navigation.php';
require_once __DIR__ . '/inc/rest/misc.php';
require_once __DIR__ . '/inc/rest/about.php';
require_once __DIR__ . '/inc/rest/register.php';

// Cache version — server-controlled version number for frontend cache invalidation
require_once __DIR__ . '/inc/core/cache-version.php';

// ============================================================
// 2. Old comment-extras.php �?ONLY for external plugin/child-theme
// ============================================================
// All functions previously in includes/comment-extras.php have been
// merged into inc/rest/comments.php. The old file is intentionally
// NOT loaded here �?loading it alongside inc/rest/comments.php
// causes fatal "cannot redeclare function" errors.
// Plugins/child-themes that directly require this file can still do so:
//   require_once get_theme_file_path( 'includes/comment-extras.php' );

// ============================================================
// 3. CORS �?early hook (before headers sent)
// ============================================================
// (handled in inc/core/setup.php via init hook)



// ============================================================
// 5. Service Worker - serve with root scope
// ============================================================
add_action( 'init', function () {
    $request_path = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    if ( $request_path === '/sw.js' ) {
        $sw_path = __DIR__ . '/dist/sw.js';
        if ( file_exists( $sw_path ) ) {
            header( 'Service-Worker-Allowed: /' );
            header( 'Content-Type: application/javascript' );
            header( 'Cache-Control: no-cache, max-age=0' );
            readfile( $sw_path );
        } else {
            header( 'HTTP/1.1 404 Not Found' );
            echo '/* Service Worker not found */';
        }
        exit;
    }
} );
