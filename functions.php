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

// Admin modules
require_once __DIR__ . '/inc/admin/options.php';
require_once __DIR__ . '/inc/admin/menu.php';

// REST API modules
require_once __DIR__ . '/inc/rest/site-info.php';
require_once __DIR__ . '/inc/rest/posts.php';
require_once __DIR__ . '/inc/rest/comments.php';
require_once __DIR__ . '/inc/rest/navigation.php';
require_once __DIR__ . '/inc/rest/misc.php';
require_once __DIR__ . '/inc/rest/register.php';

// ============================================================
// 2. Old comment-extras.php backward compatibility
// ============================================================
// All functions previously in includes/comment-extras.php have been
// merged into inc/rest/comments.php. This require is kept for
// plugin/child-theme code that may do direct require_once.
if ( file_exists( __DIR__ . '/includes/comment-extras.php' ) ) {
	require_once __DIR__ . '/includes/comment-extras.php';
}

// ============================================================
// 3. CORS — early hook (before headers sent)
// ============================================================
// (handled in inc/core/setup.php via init hook)
