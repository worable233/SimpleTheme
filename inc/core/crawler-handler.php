<?php
/**
 * Crawler (Bot) Detection & WordPress Native Fallback
 *
 * Detects search engine crawlers and other bots, and serves them a
 * WordPress-native static HTML page instead of the Vue SPA shell.
 * This ensures all major search engines can index the actual content.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Lightweight mode for crawlers ─────────────────────────────────────────────
// Strip everything that crawlers don't need: Vue SPA bundle, Prism, emoji, etc.
// Hooked very late (999) so all enqueues have already run — we can dequeue them.
add_action( 'wp_enqueue_scripts', 'simple_theme_crawler_strip_assets', 999 );
function simple_theme_crawler_strip_assets(): void {
	if ( ! simple_theme_is_crawler() ) {
		return;
	}

	// SPA frontend — not needed by crawlers.
	$handles = array(
		// Theme core
		'simple-theme-style',
		'simple-theme-bundle-0',
		'simple-theme',
		// Prism syntax highlighter
		'st-prism-core',
		'st-prism-clike',
		'st-prism-markup',
		'st-prism-javascript',
		'st-prism-typescript',
		'st-prism-css',
		'st-prism-bash',
		'st-prism-json',
		'st-prism-python',
		'st-prism-sql',
		'st-prism-yaml',
		'st-prism-markdown',
		'st-prism-markup-templating',
		'st-prism-php',
		// Sakurairo block styles (frontend only)
		'simple-theme-sakurairo-blocks',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_dequeue_script( $handle );
	}

	// WordPress built-in bloat — not useful for search engines.
	wp_dequeue_script( 'wp-embed' );
	wp_dequeue_script( 'comment-reply' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}

/**
 * Known search engine / bot User-Agent patterns.
 *
 * Core search engines + common audit/validation tools.
 * Users can extend via the 'simple_theme_crawler_patterns' filter.
 *
 * @return string[] Array of regex patterns (lowercase).
 */
function simple_theme_get_crawler_patterns(): array {
	$patterns = array(
		// Major search engines
		'googlebot',            // Google
		'bingbot',              // Bing
		'baiduspider',          // Baidu
		'yandexbot',            // Yandex
		'duckduckbot',          // DuckDuckGo
		'slurp',                // Yahoo (Slurp)
		'facebot',              // Facebook
		'twitterbot',           // Twitter/X
		'applebot',             // Apple
		'discordbot',           // Discord
		'slackbot',             // Slack
		'telegrambot',          // Telegram
		'whatsapp',             // WhatsApp
		'semperbot',            // Semrush
		'ahrefsbot',            // Ahrefs
		'majestic-12',          // Majestic
		'mj12bot',              // Majestic (alt)
		'rogerbot',             // Moz
		'prerender',            // Prerender.io
		'seznambot',            // Seznam
		'sogou',                // Sogou
		'360spider',            // 360
		'bytespider',           // ByteDance
		'coccocbot',            // Cốc Cốc
		'petalbot',             // Huawei
		'SemanticScholarBot',   // Semantic Scholar
		// Common validation / audit
		'w3c_validator',
		'w3c-checklink',
		'validator',
		'curl',
		'python-requests',
		'python-urllib',
		'go-http-client',
	);

	/**
	 * Filter the crawler User-Agent pattern list.
	 *
	 * @param string[] $patterns Array of lowercase substrings to match against UA.
	 */
	return apply_filters( 'simple_theme_crawler_patterns', $patterns );
}

/**
 * Check whether the current request comes from a known crawler bot.
 *
 * Results are cached in a static variable for the lifetime of the request.
 *
 * @return bool True if the User-Agent matches a known crawler.
 */
function simple_theme_is_crawler(): bool {
	static $is_bot = null;

	if ( null !== $is_bot ) {
		return $is_bot;
	}

	// Never treat logged-in admin as a bot.
	if ( is_user_logged_in() ) {
		$is_bot = false;
		return false;
	}

	$user_agent = '';
	if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$user_agent = strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	// No UA at all — treat as potential bot (libraries, scripts).
	if ( '' === $user_agent ) {
		$is_bot = true;
		return true;
	}

	$patterns = simple_theme_get_crawler_patterns();
	foreach ( $patterns as $pattern ) {
		if ( false !== strpos( $user_agent, strtolower( $pattern ) ) ) {
			$is_bot = true;
			return true;
		}
	}

	$is_bot = false;
	return false;
}

/**
 * Intercept the WordPress template for crawler bots.
 *
 * When a known crawler visits, serve a native WordPress template
 * with full static HTML content instead of the SPA shell.
 *
 * Hooked on 'template_include' at priority 99 (after all other filters).
 *
 * @param string $template The path to the template file.
 * @return string The (possibly replaced) template path.
 */
add_filter( 'template_include', 'simple_theme_crawler_template', 99 );
function simple_theme_crawler_template( string $template ): string {
	// Only intercept for known crawlers.
	if ( ! simple_theme_is_crawler() ) {
		return $template;
	}

	// Never intercept REST API, AJAX, admin, or POST requests.
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
		return $template;
	}

	if ( ! empty( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return $template;
	}

	// Check if there's real content to show.
	// If it's a 404 with no matching content, let the SPA 404 handle it.
	if ( is_404() ) {
		global $wp_query;
		if ( empty( $wp_query->query_vars ) || ! $wp_query->have_posts() ) {
			return $template;
		}
	}

	// Serve our fallback template for known content types.
	$fallback = get_theme_file_path( 'templates/crawler-fallback.php' );
	if ( file_exists( $fallback ) ) {
		return $fallback;
	}

	return $template;
}
