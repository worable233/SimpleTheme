<?php
/**
 * Asset Enqueuing (Frontend + Admin)
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Manifest & Version Helpers ==========

define( 'SIMPLE_THEME_HANDLE', 'simple-theme-bundle' );

function simple_theme_get_manifest() {
	$manifest_file = get_theme_file_path( 'dist/.vite/manifest.json' );
	if ( ! file_exists( $manifest_file ) ) {
		return array();
	}
	$contents = file_get_contents( $manifest_file );
	if ( false === $contents ) {
		return array();
	}
	$decoded = json_decode( $contents, true );
	return is_array( $decoded ) ? $decoded : array();
}

function simple_theme_get_asset_version( $relative_path ) {
	$manifest = simple_theme_get_manifest();
	// manifest 存储的路径不含 dist/ 前缀，去掉后再比较
	$search_key = preg_replace( '#^dist/?#', '', $relative_path );
	foreach ( $manifest as $entry ) {
		if ( isset( $entry['file'] ) && $entry['file'] === $search_key ) {
			return ! empty( $entry['version'] ) ? $entry['version'] : filemtime( get_theme_file_path( $relative_path ) );
		}
	}
	return filemtime( get_theme_file_path( $relative_path ) );
}

function simple_theme_asset_uri( $path = '' ) {
	return wp_make_link_relative( get_theme_file_uri( $path ) );
}

// ========== Frontend Config (injected into page) ==========

function simple_theme_get_frontend_config() {
	$current_user = null;
	$user = wp_get_current_user();
	if ( $user->ID !== 0 ) {
		$current_user = array(
			'displayName' => $user->display_name,
			'email'       => $user->user_email,
			'url'         => $user->user_url,
		);
	}

	$theme_options = get_option( 'simple_theme_options', array() );

	return array(
		'siteUrl'  => trailingslashit( site_url( '/' ) ),
		'homeUrl'  => trailingslashit( home_url( '/' ) ),
		'restRoot' => esc_url_raw( trailingslashit( rest_url() ) ),
		'themeUrl' => get_theme_file_uri(),
		'illustrationsUrl' => esc_url_raw( get_theme_file_uri( 'dist/illustrations/' ) ),
		'routes'   => array(
			'resolveUrl' => esc_url_raw( rest_url( 'simple-theme/v1/resolve-url' ) ),
			'menusBase'  => esc_url_raw( rest_url( 'simple-theme/v1/navigation' ) ),
			'siteInfo'   => esc_url_raw( rest_url( 'simple-theme/v1/site-info' ) ),
			'collection' => esc_url_raw( rest_url( 'simple-theme/v1/collection' ) ),
			'about'      => esc_url_raw( rest_url( 'simple-theme/v1/about' ) ),
			'links'      => esc_url_raw( rest_url( 'simple-theme/v1/links' ) ),
			'settings'   => esc_url_raw( rest_url( 'simple-theme/v1/settings' ) ),
		),
		'currentUser' => $current_user,
		'restNonce'  => wp_create_nonce( 'wp_rest' ),
		'features'   => array(
			'prismHighlight' => (bool) ( $theme_options['enable_prism_highlight'] ?? true ),
			'showStats'   => (bool) ($theme_options['sidebar_show_stats']   ?? true),
			'showHeatmap' => (bool) ($theme_options['sidebar_show_heatmap'] ?? true),
						'showSocial'  => (bool) ($theme_options['sidebar_show_social']  ?? true),
			'meta' => array(
				'showCategory'     => (bool) ($theme_options['meta_show_category']      ?? true),
				'showPublishDate'  => (bool) ($theme_options['meta_show_publish_date']   ?? true),
				'showModifiedDate' => (bool) ($theme_options['meta_show_modified_date']  ?? false),
				'showCommentCount' => (bool) ($theme_options['meta_show_comment_count']  ?? true),
				'showViewCount'    => (bool) ($theme_options['meta_show_view_count']     ?? true),
				'showReadingTime'  => (bool) ($theme_options['meta_show_reading_time']     ?? true),
				'showWordCount'    => (bool) ($theme_options['meta_show_word_count']       ?? false),
			),
		),
	);
}

// ========== Prism (loaded as regular <script> tags, not ES modules) ==========

add_action( 'wp_enqueue_scripts', 'simple_theme_enqueue_prism', 8 );
function simple_theme_enqueue_prism() {
	$prism_path = get_theme_file_path( 'dist/prism/prism-core.min.js' );
	if ( ! file_exists( $prism_path ) ) {
		return; // prism files not copied yet (npm run build-only)
	}

	$prism_uri = simple_theme_asset_uri( 'dist/prism/' );
	$version   = '1.30.0';

	// Core must come first
	wp_enqueue_script( 'st-prism-core', $prism_uri . 'prism-core.min.js', array(), $version, true );

	// Languages — dependency graph ensures correct load order
	wp_enqueue_script( 'st-prism-clike', $prism_uri . 'prism-clike.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-markup', $prism_uri . 'prism-markup.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-javascript', $prism_uri . 'prism-javascript.min.js', array( 'st-prism-clike' ), $version, true );
	wp_enqueue_script( 'st-prism-typescript', $prism_uri . 'prism-typescript.min.js', array( 'st-prism-javascript' ), $version, true );
	wp_enqueue_script( 'st-prism-css', $prism_uri . 'prism-css.min.js', array( 'st-prism-markup' ), $version, true );
	wp_enqueue_script( 'st-prism-bash', $prism_uri . 'prism-bash.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-json', $prism_uri . 'prism-json.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-python', $prism_uri . 'prism-python.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-sql', $prism_uri . 'prism-sql.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-yaml', $prism_uri . 'prism-yaml.min.js', array( 'st-prism-core' ), $version, true );
	wp_enqueue_script( 'st-prism-markdown', $prism_uri . 'prism-markdown.min.js', array( 'st-prism-markup' ), $version, true );
	wp_enqueue_script( 'st-prism-markup-templating', $prism_uri . 'prism-markup-templating.min.js', array( 'st-prism-markup' ), $version, true );
	wp_enqueue_script( 'st-prism-php', $prism_uri . 'prism-php.min.js', array( 'st-prism-markup-templating' ), $version, true );
}

// ========== Oat (toast notifications) ==========

add_action( 'wp_enqueue_scripts', 'simple_theme_enqueue_oat', 9 );
function simple_theme_enqueue_oat() {
	$oat_file = 'dist/oat.min.js';
	$oat_path = get_theme_file_path( $oat_file );
	if ( ! file_exists( $oat_path ) ) {
		return;
	}
	wp_enqueue_script(
		'simple-theme-oat',
		simple_theme_asset_uri( $oat_file ),
		array(),
		filemtime( $oat_path ),
		true
	);
}

// ========== Frontend Assets ==========

/**
 * Recursively collect CSS from an entry and all its imports.
 *
 * Vite/Rolldown may extract CSS from code-split chunks (e.g. Fancybox)
 * into separate CSS files. The entry's direct `css` array only includes
 * CSS imported synchronously -- async/dynamic imports end up in child
 * chunk entries. This walks the `imports` chain to find them all.
 */
function simple_theme_collect_entry_css( $manifest, $entry_key, &$seen = array() ) {
	$css_files = array();
	if ( empty( $manifest[ $entry_key ] ) ) {
		return $css_files;
	}
	$entry = $manifest[ $entry_key ];

	if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
		foreach ( $entry['css'] as $css_file ) {
			if ( ! in_array( $css_file, $seen, true ) ) {
				$seen[] = $css_file;
				$css_files[] = $css_file;
			}
		}
	}

	if ( ! empty( $entry['imports'] ) && is_array( $entry['imports'] ) ) {
		foreach ( $entry['imports'] as $import_key ) {
			$child_css = simple_theme_collect_entry_css( $manifest, $import_key, $seen );
			$css_files = array_merge( $css_files, $child_css );
		}
	}

	return $css_files;
}

add_action( 'wp_enqueue_scripts', 'simple_theme_enqueue_assets' );
function simple_theme_enqueue_assets() {
	$manifest = simple_theme_get_manifest();

	wp_enqueue_style(
		'simple-theme-style',
		get_stylesheet_uri(),
		array(),
		simple_theme_get_asset_version( 'style.css' )
	);

	if ( empty( $manifest['src/main.ts'] ) || empty( $manifest['src/main.ts']['file'] ) ) {
		return;
	}

	$entry      = $manifest['src/main.ts'];
	$script_uri = simple_theme_asset_uri( 'dist/' . ltrim( $entry['file'], '/' ) );
	$script_ver = simple_theme_get_asset_version( 'dist/' . ltrim( $entry['file'], '/' ) );

	$all_css = simple_theme_collect_entry_css( $manifest, 'src/main.ts' );
	foreach ( $all_css as $index => $css_file ) {
		$relative_css_path = 'dist/' . ltrim( $css_file, '/' );
		wp_enqueue_style(
			"simple-theme-bundle-{$index}",
			simple_theme_asset_uri( $relative_css_path ),
			array( 'simple-theme-style' ),
			simple_theme_get_asset_version( $relative_css_path )
		);
	}

	wp_enqueue_script(
		SIMPLE_THEME_HANDLE,
		$script_uri,
		array(),
		$script_ver,
		true
	);

	add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
		if ( SIMPLE_THEME_HANDLE === $handle ) {
			// Strip origin to avoid CORS on module scripts when WP site URL differs from page URL
			$src = preg_replace( '#^https?://[^/]+#', '', $src );
			return '<script type="module" src="' . esc_url( $src ) . '"></script>';
		}
		return $tag;
	}, 10, 3 );
}

/**
 * Output the frontend config as an inline script.
 *
 * We use wp_head (priority 0) instead of wp_add_inline_script because the
 * wp_opt plugin or the script_loader_tag filter may suppress the inline
 * output. Manually printing the <script> tag ensures the config is always
 * available to the Vue app before the module script executes.
 */
add_action( 'wp_head', 'simple_theme_output_frontend_config', 0 );
function simple_theme_output_frontend_config() {
	$config = wp_json_encode( simple_theme_get_frontend_config() );
	if ( ! $config ) {
		return;
	}
	echo '<script>window.SimpleThemeConfig = ' . $config . ';</script>' . "\n";
}


/**
 * Fallback: output bundle CSS <link> tags directly in wp_head.
 *
 * The WPOPT (wp-opt) plugin suppresses output of some enqueued styles,
 * specifically the first indexed CSS from the Vite manifest (index 0).
 * This ensures the CSS always renders regardless of plugin interference.
 */
add_action( 'wp_head', 'simple_theme_output_bundle_css', 1 );
function simple_theme_output_bundle_css() {
	$manifest = simple_theme_get_manifest();
	if ( empty( $manifest['src/main.ts'] ) ) {
		return;
	}

	$all_css = simple_theme_collect_entry_css( $manifest, 'src/main.ts' );
	foreach ( $all_css as $index => $css_file ) {
		$uri = simple_theme_asset_uri( 'dist/' . ltrim( $css_file, '/' ) );
		echo '<link rel="stylesheet" id="st-bundle-css-' . $index . '" href="' . esc_url( $uri ) . '">' . "
";
	}
}

// ========== Console Warning Suppression ==========

/**
 * Inject inline script to suppress console warnings matching known plugin patterns (e.g. WPOPT).
 * Controlled via the "suppress_console_warnings" admin setting.
 */
add_action( 'wp_head', 'simple_theme_output_console_suppression', 2 );
function simple_theme_output_console_suppression() {
	$options = get_option( 'simple_theme_options', array() );
	if ( empty( $options['suppress_console_warnings'] ) ) {
		return;
	}

	$patterns = array( 'wp-opt', 'WPOPT' );
	$patterns_json = wp_json_encode( $patterns );
	if ( ! $patterns_json ) {
		return;
	}
	echo '<script>window.__SUPPRESS_CONSOLE_PATTERNS=' . $patterns_json . ';</script>';
	echo "<script>(function(){var p=window.__SUPPRESS_CONSOLE_PATTERNS||[];if(!p.length)return;var wn=console.warn;console.warn=function(){for(var a=arguments,l=a.length,i=0;i<l;i++)for(var j=0;j<p.length;j++)if(String(a[i]).indexOf(p[j])>-1)return;return wn.apply(console,a)}})();</script>\n";
}

// ========== Admin Assets (Vue admin app) ==========

add_action( 'admin_enqueue_scripts', 'simple_theme_enqueue_admin_assets' );
function simple_theme_enqueue_admin_assets( $hook ) {
	if ( 'toplevel_page_simple-theme' !== $hook ) {
		return;
	}

	$manifest = simple_theme_get_manifest();
	if ( empty( $manifest['src/admin/main.ts'] ) ) {
		return;
	}

	// Load WordPress Media Library JS (wp.media) for image selection buttons.
	wp_enqueue_media();

	$entry = $manifest['src/admin/main.ts'];

	if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
		foreach ( $entry['css'] as $index => $css_file ) {
			wp_enqueue_style(
				'simple-theme-admin-bundle-' . $index,
				simple_theme_asset_uri( 'dist/' . ltrim( $css_file, '/' ) ),
				array(),
				simple_theme_get_asset_version( 'dist/' . ltrim( $css_file, '/' ) )
			);
		}
	}

	if ( ! empty( $entry['file'] ) ) {
		$admin_handle = 'simple-theme-admin-bundle';
		wp_enqueue_script(
			$admin_handle,
			simple_theme_asset_uri( 'dist/' . ltrim( $entry['file'], '/' ) ),
			array(),
			simple_theme_get_asset_version( 'dist/' . ltrim( $entry['file'], '/' ) ),
			true
		);

		wp_add_inline_script(
			$admin_handle,
			'window.SimpleThemeConfig = ' . wp_json_encode( simple_theme_get_frontend_config() ) . ';',
			'before'
		);

		add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) use ( $admin_handle ) {
			if ( $admin_handle === $handle ) {
				return '<script type="module" src="' . esc_url( $src ) . '"></script>';
			}
			return $tag;
		}, 10, 3 );
	}
}
