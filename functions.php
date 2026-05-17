<?php
/**
 * Simple Theme 鐨勪富棰樺紩瀵间笌 WordPress 闆嗘垚銆? *
 * @package SimpleTheme
 */

/**
 * Sakurairo block compatibility layer.
 * Registers PHP render_callbacks and editor assets for Sakurairo custom blocks
 * (notice-block, showcard-block, conversations-block, vbilibili) so that
 * existing content authored in Sakurairo continues to render and be editable.
 */
if ( file_exists( __DIR__ . '/inc/blocks/sakurairo.php' ) ) {
	require_once __DIR__ . '/inc/blocks/sakurairo.php';
}

/**
 * Handle CORS preflight OPTIONS requests.
 * Uses raw HTTP_ORIGIN (bypasses WordPress get_http_origin() host check)
 * so that dev setups with different hostnames (e.g. localhost vs 127.0.0.1) work.
 */
add_action( 'init', 'simple_theme_cors_handler' );
function simple_theme_cors_handler() {
	if ( empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		return;
	}

	$origin = esc_url_raw( $_SERVER['HTTP_ORIGIN'] );

	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Type, X-Requested-With' );

	if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		status_header( 204 );
		exit;
	}
}

/**
 * Allow localhost as a valid HTTP origin for REST API requests.
 * WordPress's get_http_origin() rejects origins that don't match site_url,
 * which breaks CORS when WP is at 127.0.0.1 but accessed via localhost.
 */
add_filter( 'allowed_http_origin', 'simple_theme_allow_localhost_origin' );
function simple_theme_allow_localhost_origin( $origin ) {
	if ( empty( $origin ) && ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		return esc_url_raw( $_SERVER['HTTP_ORIGIN'] );
	}
	return $origin;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SIMPLE_THEME_HANDLE' ) ) {
	define( 'SIMPLE_THEME_HANDLE', 'simple-theme-app' );
}

// 加载后台设置页面。
if ( is_admin() ) {
	require_once get_theme_file_path( 'admin/theme-options.php' );
}

/**
 * 娉ㄥ唽涓婚鏀寔涓庤彍鍗曚綅缃€? */
function simple_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'post-formats',
		array(
			'aside',
		)
	);
	add_theme_support(
		'html5',
		array(
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'script',
			'style',
			'search-form',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Navigation', 'simple-theme' ),
			'footer'  => __( 'Footer Navigation', 'simple-theme' ),
		)
	);

	add_filter( 'pre_option_link_manager_enabled', '__return_true' );
}
add_action( 'after_setup_theme', 'simple_theme_setup' );

function simple_theme_register_shuoshuo_post_type() {
	$labels = array(
		'name'               => __( '说说', 'simple-theme' ),
		'singular_name'      => __( '说说', 'simple-theme' ),
		'menu_name'          => __( '说说', 'simple-theme' ),
		'name_admin_bar'     => __( '说说', 'simple-theme' ),
		'add_new'            => __( '新建', 'simple-theme' ),
		'add_new_item'       => __( '新建说说', 'simple-theme' ),
		'new_item'           => __( '新说说', 'simple-theme' ),
		'edit_item'          => __( '编辑说说', 'simple-theme' ),
		'view_item'          => __( '查看说说', 'simple-theme' ),
		'all_items'          => __( '全部说说', 'simple-theme' ),
		'search_items'       => __( '搜索说说', 'simple-theme' ),
		'not_found'          => __( '还没有说说内容。', 'simple-theme' ),
		'not_found_in_trash' => __( '回收站里没有说说内容。', 'simple-theme' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'shuoshuo' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_icon'          => 'dashicons-format-status',
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		'taxonomies'         => array( 'category', 'post_tag', 'post_format' ),
	);

	register_post_type( 'shuoshuo', $args );
}
add_action( 'init', 'simple_theme_register_shuoshuo_post_type' );

/**
 * 注册导航菜单项的自定义 meta 字段（图标）。
 * 注册后可通过 REST API 的 nav_menu_item 端点访问。
 */
function simple_theme_register_menu_item_meta() {
	register_post_meta(
		'nav_menu_item',
		'_menu_item_icon',
		array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'default'      => '',
			'auth_callback' => function () {
				return current_user_can( 'edit_theme_options' );
			},
		)
	);
}
add_action( 'init', 'simple_theme_register_menu_item_meta' );

function simple_theme_get_manifest() {
	static $manifest = null;

	if ( null !== $manifest ) {
		return $manifest;
	}

	$manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );

	if ( ! is_readable( $manifest_path ) ) {
		$manifest = array();
		return $manifest;
	}

	$manifest_contents = file_get_contents( $manifest_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 杩欓噷璇诲彇鐨勬槸涓婚鐩綍鍐呯殑鏈湴 manifest 鏂囦欢銆?
	if ( false === $manifest_contents ) {
		$manifest = array();
		return $manifest;
	}

	$decoded_manifest = json_decode( $manifest_contents, true );
	$manifest         = is_array( $decoded_manifest ) ? $decoded_manifest : array();

	return $manifest;
}

/**
 * 鐢熸垚鍓嶇璧勬簮鐗堟湰鍙凤紝閬垮厤娴忚鍣ㄧ紦瀛樻棫鏂囦欢銆? *
 * @param string $relative_path 鐩稿涓婚鐩綍鐨勬枃浠惰矾寰?
 * @return string
 */
function simple_theme_get_asset_version( $relative_path ) {
	$absolute_path = get_theme_file_path( $relative_path );

	if ( is_readable( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return wp_get_theme()->get( 'Version' );
}

/**
 * 鍚戝墠绔敞鍏ヤ富棰橀厤缃紝閬垮厤鍦?Vue 閲屽啓姝荤珯鐐瑰湴鍧€銆? *
 * @return array<string, mixed>
 */
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

	return array(
		'siteUrl'  => trailingslashit( site_url( '/' ) ),
		'homeUrl'  => trailingslashit( home_url( '/' ) ),
		'restRoot' => esc_url_raw( trailingslashit( rest_url() ) ),
		'themeUrl' => get_theme_file_uri(),
		'illustrationsUrl' => esc_url_raw( rest_url( 'simple-theme/v1/illustration/' ) ),
		'routes'   => array(
			'resolveUrl' => esc_url_raw( rest_url( 'simple-theme/v1/resolve-url' ) ),
			'menusBase'  => esc_url_raw( rest_url( 'simple-theme/v1/navigation' ) ),
			'siteInfo'   => esc_url_raw( rest_url( 'simple-theme/v1/site-info' ) ),
			'collection' => esc_url_raw( rest_url( 'simple-theme/v1/collection' ) ),
			'about'      => esc_url_raw( rest_url( 'simple-theme/v1/about' ) ),
			'links'      => esc_url_raw( rest_url( 'simple-theme/v1/links' ) ),
		),
		'currentUser' => $current_user,
	);
}

/**
 * 娉ㄥ唽涓婚鏍峰紡涓庢墦鍖呭悗鐨勫墠绔祫婧愩€? */
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
	$script_uri = get_theme_file_uri( 'dist/' . ltrim( $entry['file'], '/' ) );
	$script_ver = simple_theme_get_asset_version( 'dist/' . ltrim( $entry['file'], '/' ) );

	if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
		foreach ( $entry['css'] as $index => $css_file ) {
			$relative_css_path = 'dist/' . ltrim( $css_file, '/' );

			wp_enqueue_style(
				"simple-theme-bundle-{$index}",
				get_theme_file_uri( $relative_css_path ),
				array( 'simple-theme-style' ),
				simple_theme_get_asset_version( $relative_css_path )
			);
		}
	}

	wp_enqueue_script(
		SIMPLE_THEME_HANDLE,
		$script_uri,
		array(),
		$script_ver,
		true
	);

	wp_add_inline_script(
		SIMPLE_THEME_HANDLE,
		'window.SimpleThemeConfig = ' . wp_json_encode( simple_theme_get_frontend_config() ) . ';',
		'before'
	);

}
add_action( 'wp_enqueue_scripts', 'simple_theme_enqueue_assets' );

/**
 * 娉ㄥ唽鍓嶇闇€瑕佺殑鑷畾涔?REST 璺敱銆? */
function simple_theme_register_rest_routes() {
	register_rest_route(
		'simple-theme/v1',
		'/site-info',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_get_site_info',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/navigation/(?P<location>[a-zA-Z0-9_-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_get_navigation',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/resolve-url',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'simple_theme_resolve_path',
			'permission_callback' => '__return_true',
			'args'                => array(
				'path' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/comments/(?P<post_id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_get_comments',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/comments',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'simple_theme_create_comment',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/comment-like',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'simple_theme_like_comment',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/avatar-proxy',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_avatar_proxy',
			'permission_callback' => '__return_true',
			'args'                => array(
				'qq' => array(
					'required'          => true,
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/collection',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_get_collection',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/home-posts',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'simple_theme_get_home_posts',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'simple-theme/v1',
		'/track-view',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'simple_theme_track_post_view',
			'permission_callback' => '__return_true',
		)
	);

		register_rest_route(
			'simple-theme/v1',
			'/links',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_theme_get_links',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'simple-theme/v1',
			'/illustration/(?P<name>[^/]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'simple_theme_serve_illustration',
				'permission_callback' => '__return_true',
				'args'                => array(
					'name' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_file_name',
					),
				),
			)
		);
	}
add_action( 'rest_api_init', 'simple_theme_register_rest_routes' );

/**
 * Serve illustration SVGs via REST API (with CORS headers).
 *
 * @param WP_REST_Request $request REST request object.
 * @return void
 */
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

/**
 * 获取友链数据（按分类分组）
 */
function simple_theme_get_links() {
	$categories = get_terms(
		array(
			'taxonomy'   => 'link_category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $categories ) ) {
		return new WP_REST_Response( array(), 200 );
	}

	$result = array();

	foreach ( $categories as $category ) {
		$bookmarks = get_bookmarks(
			array(
				'category' => $category->term_id,
				'orderby'  => 'rating',
				'order'    => 'DESC',
			)
		);

		$links = array();
		foreach ( $bookmarks as $bookmark ) {
			$links[] = array(
				'id'          => $bookmark->link_id,
				'name'        => $bookmark->link_name,
				'url'         => $bookmark->link_url,
				'description' => $bookmark->link_description,
				'image'       => $bookmark->link_image,
				'target'      => $bookmark->link_target,
				'rating'      => intval( $bookmark->link_rating ),
				'notes'       => $bookmark->link_notes,
			);
		}

		$result[] = array(
			'id'          => $category->term_id,
			'name'        => $category->name,
			'slug'        => $category->slug,
			'description' => $category->description,
			'links'       => $links,
		);
	}

	return new WP_REST_Response( $result, 200 );
}

function simple_theme_sanitize_choice( $value, array $allowed, $default ) {
	$value = (string) $value;
	return in_array( $value, $allowed, true ) ? $value : $default;
}

function simple_theme_sanitize_bool( $value ) {
	return ! empty( $value );
}

function simple_theme_get_option_number( $key, $default, $min, $max ) {
	$options = get_option( 'simple_theme_options', array() );
	$value   = isset( $options[ $key ] ) ? (int) $options[ $key ] : (int) $default;
	return max( $min, min( $max, $value ) );
}

function simple_theme_get_post_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! is_array( $terms ) ) {
		return array();
	}

		return array_values(
		array_map(
			static function ( $term ) {
				return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			},
			$terms
		)
	);
}

/**
 * Sakurairo-compatible word counting: English words + CJK characters.
 */
function simple_theme_word_stat( string $text ) {
	$sum = 0;
	// English words / numbers: consecutive digits, uppercase, lowercase, titlecase
	$res = preg_match_all( '/[\d\p{Lu}\p{Ll}\p{Lt}]+/u', $text );
	if ( $res !== false ) {
		$sum += $res;
	}
	// CJK characters: Han, Katakana, Hiragana, Hangul (each char counts as 1)
	$res = preg_match_all( '/[\p{Han}\p{Katakana}\p{Hiragana}\p{Hangul}]/u', $text );
	if ( $res !== false ) {
		$sum += $res;
	}
	return $sum;
}

/**
 * Count words on save and store in Sakurairo-compatible meta key.
 */
function simple_theme_count_post_words( $post_ID ) {
	$post = get_post( $post_ID );
	if ( ! in_array( $post->post_type, array( 'post', 'shuoshuo' ), true ) ) {
		return;
	}
	$content = strip_tags( (string) $post->post_content );
	$count   = simple_theme_word_stat( $content );
	update_post_meta( $post_ID, 'post_words_count', $count );
	update_post_meta( $post_ID, 'simple_theme_word_count', $count );
	return $count;
}
add_action( 'save_post', 'simple_theme_count_post_words' );

function simple_theme_calculate_post_stats( $post ) {
	$post_id = (int) $post->ID;

	// Try Sakurairo meta key first, then our own, then calculate inline
	$word_count = (int) get_post_meta( $post_id, 'post_words_count', true );
	if ( ! $word_count ) {
		$word_count = (int) get_post_meta( $post_id, 'simple_theme_word_count', true );
	}
	if ( ! $word_count ) {
		$content_text = wp_strip_all_tags( (string) $post->post_content );
		$word_count   = simple_theme_word_stat( $content_text );
	}
	$word_count   = (int) max( 0, $word_count );
	$reading_time = (int) max( 1, ceil( $word_count / 220 ) );

	return array(
		'wordCount'   => $word_count,
		'readingTime' => $reading_time,
	);
}

function simple_theme_format_post_item( WP_Post $post ) {
	$post_id     = (int) $post->ID;
	$stats       = simple_theme_calculate_post_stats( $post );
	$view_count  = (int) get_post_meta( $post_id, 'views', true );
	$excerpt     = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 42, '…' );
	$excerpt_html = wpautop( wp_kses_post( $excerpt ) );

	return array(
		'id'             => $post_id,
		'date'           => get_post_time( DATE_RFC3339, true, $post_id ),
		'modified'       => get_post_modified_time( DATE_RFC3339, true, $post_id ),
		'link'           => get_permalink( $post_id ),
		'type'           => get_post_type( $post_id ),
		'comment_status' => $post->comment_status,
		'title'          => array( 'rendered' => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) ),
		'excerpt'        => array( 'rendered' => $excerpt_html ),
		'categories'     => simple_theme_get_post_term_names( $post_id, 'category' ),
		'tags'           => simple_theme_get_post_term_names( $post_id, 'post_tag' ),
		'featuredImage'  => get_the_post_thumbnail_url( $post_id, 'large' ) ?: '',
		'commentCount'   => (int) get_comments_number( $post_id ),
		'viewCount'      => max( 0, $view_count ),
		'wordCount'      => $stats['wordCount'],
		'readingTime'    => $stats['readingTime'],
	);
}

function simple_theme_get_current_commenter() {
	$user = wp_get_current_user();
	if ( $user->ID === 0 ) {
		return null;
	}

	return array(
		'displayName' => $user->display_name,
		'email'       => $user->user_email,
		'url'         => $user->user_url,
	);
}

function simple_theme_get_site_info() {
	$theme_options = get_option( 'simple_theme_options', array() );
	$comment_show_email   = (bool) ( $theme_options['comment_show_email'] ?? true );
	$comment_show_url     = (bool) ( $theme_options['comment_show_url'] ?? true );
	$comment_show_cookies = (bool) ( $theme_options['comment_show_cookies'] ?? (bool) get_option( 'show_comments_cookies_opt_in' ) );
	$login_url     = wp_login_url();
	$social_links  = array();
	if ( ! empty( $theme_options['social_links'] ) ) {
		$decoded = json_decode( $theme_options['social_links'], true );
		if ( is_array( $decoded ) ) {
			$social_links = $decoded;
		}
	}
	$icp_text     = ! empty( $theme_options['icp_text'] ) ? $theme_options['icp_text'] : '';
	$icp_gov_text = ! empty( $theme_options['icp_gov_text'] ) ? $theme_options['icp_gov_text'] : '';
	$stats        = simple_theme_compute_site_stats();

	return new WP_REST_Response(
		array(
			'name'          => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'description'   => html_entity_decode( get_bloginfo( 'description' ), ENT_QUOTES, 'UTF-8' ),
			'url'           => home_url( '/' ),
												'hero'          => array(
				'image'      => (string) ( $theme_options[ 'hero_image' ] ?? '' ),
				'showAvatar' => (bool) ( $theme_options[ 'hero_show_avatar' ] ?? false ),
				'avatar'     => (string) ( $theme_options[ 'hero_avatar' ] ?? '' ),
			),
			'theme'         => array(
								'primaryColor'    => sanitize_hex_color( (string) ( $theme_options[ 'primary_color' ] ?? '#333333' ) ) ?: '#333333',
				'bodyFont'        => (string) $theme_options[ 'body_font' ] ?? '"Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif',
				'headingFont'     => (string) $theme_options[ 'heading_font' ] ?? '"Noto Serif SC", "Source Han Serif SC", serif',
				'radius'          => simple_theme_sanitize_choice( $theme_options[ 'radius' ] ?? 'medium', array( 'small', 'medium', 'large' ), 'medium' ),
				'shadow'          => simple_theme_sanitize_choice( $theme_options[ 'shadow' ] ?? 'small', array( 'none', 'small', 'medium', 'large' ), 'small' ),
				'backgroundLight' => sanitize_hex_color( (string) ( $theme_options[ 'background_light' ] ?? '#f5f6f7' ) ) ?: '#f5f6f7',
				'backgroundDark'  => sanitize_hex_color( (string) ( $theme_options[ 'background_dark' ] ?? '#1a1a1a' ) ) ?: '#1a1a1a',
				'cardLight'       => sanitize_hex_color( (string) ( $theme_options[ 'card_light' ] ?? '#ffffff' ) ) ?: '#ffffff',
				'cardDark'        => sanitize_hex_color( (string) ( $theme_options[ 'card_dark' ] ?? '#222222' ) ) ?: '#222222',
				'foregroundLight' => sanitize_hex_color( (string) ( $theme_options[ 'foreground_light' ] ?? '#333333' ) ) ?: '#333333',
				'foregroundDark'  => sanitize_hex_color( (string) ( $theme_options[ 'foreground_dark' ] ?? '#e0e0e0' ) ) ?: '#e0e0e0',
				'accentLight'     => sanitize_hex_color( (string) ( $theme_options[ 'accent_light' ] ?? '#f5f5f5' ) ) ?: '#f5f5f5',
				'accentDark'      => sanitize_hex_color( (string) ( $theme_options[ 'accent_dark' ] ?? '#2a2a2a' ) ) ?: '#2a2a2a',
				'borderLight'     => sanitize_hex_color( (string) ( $theme_options[ 'border_light' ] ?? '#e2e2e2' ) ) ?: '#e2e2e2',
				'borderDark'      => sanitize_hex_color( (string) ( $theme_options[ 'border_dark' ] ?? '#333333' ) ) ?: '#333333',
				'containerMaxWidth'=> simple_theme_get_option_number( 'simple_theme_container_max_width', 1400, 960, 1680 ),
				'articleMaxWidth' => simple_theme_get_option_number( 'simple_theme_article_max_width', 900, 680, 1200 ),
				'copyrightStyle' => (string) ( $theme_options[ 'copyright_style' ] ?? 'detailed' ),
					'cardMeta'        => array(
					'showCategory'      => (bool) $theme_options[ 'meta_show_category' ] ?? true,
					'showPublishDate'   => (bool) $theme_options[ 'meta_show_publish_date' ] ?? true,
					'showModifiedDate'  => (bool) $theme_options[ 'meta_show_modified_date' ] ?? false,
					'showCommentCount'  => (bool) $theme_options[ 'meta_show_comment_count' ] ?? true,
					'showViewCount'     => (bool) $theme_options[ 'meta_show_view_count' ] ?? true,
					'showReadingTime'   => (bool) $theme_options[ 'meta_show_reading_time' ] ?? true,
					'showWordCount'     => (bool) $theme_options[ 'meta_show_word_count' ] ?? false,
				),
			),
			'comments'      => array(
				'requireNameEmail' => (bool) get_option( 'require_name_email' ),
				'registrationOnly' => (bool) get_option( 'comment_registration' ),
				'showEmailField'   => $comment_show_email,
				'showUrlField'     => $comment_show_url,
				'showCookiesOptIn' => $comment_show_cookies,
			),
			'collections'   => array(
				'postsTitle'         => (string) $theme_options[ 'posts_title' ] ?? '最新文章',
				'postsSubtitle'      => (string) $theme_options[ 'posts_subtitle' ] ?? '整理过的长文、笔记与项目更新。',
				'shuoshuoTitle'      => (string) $theme_options[ 'shuoshuo_title' ] ?? '最近说说',
				'shuoshuoSubtitle'   => (string) $theme_options[ 'shuoshuo_subtitle' ] ?? '更轻量的动态、灵感和碎片记录。',
				'showShuoshuoSection'=> (bool) $theme_options[ 'show_shuoshuo_section' ] ?? true,
				'homePostCount'      => simple_theme_get_option_number( 'simple_theme_home_post_count', 6, 3, 20 ),
				'homeShuoshuoCount'  => simple_theme_get_option_number( 'simple_theme_home_shuoshuo_count', 3, 0, 12 ),
				'shuoshuoPageSize'   => simple_theme_get_option_number( 'simple_theme_shuoshuo_page_size', 12, 6, 24 ),
			),
			'stats'          => $stats,
			'socialLinks'    => $social_links,
			'loginUrl'       => $login_url,
			'icp'            => $icp_text,
			'icpGov'         => $icp_gov_text,
			'endNote'        => ! empty( $theme_options['end_note'] ) ? $theme_options['end_note'] : '',
			'currentUser'    => simple_theme_get_current_commenter(),
		),
		200
	);
}

function simple_theme_compute_site_stats() {
	global $wpdb;

	$stats = array(
		'postCount'        => 0,
		'categoryCount'    => 0,
		'tagCount'         => 0,
		'shuoshuoCount'    => 0,
		'totalWordCount'   => 0,
		'commentCount'     => 0,
		'registeredDate'   => '',
		'lastActivityDate' => '',
	);

	$stats['postCount'] = (int) wp_count_posts( 'post' )->publish;

	$shuoshuo_count = wp_count_posts( 'shuoshuo' );
	if ( $shuoshuo_count && isset( $shuoshuo_count->publish ) ) {
		$stats['shuoshuoCount'] = (int) $shuoshuo_count->publish;
	}

	$stats['categoryCount'] = (int) wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$stats['tagCount']      = (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );

	$stats['commentCount'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'" );

	$posts_data = $wpdb->get_row(
		"SELECT SUM(LENGTH(post_content) - LENGTH(REPLACE(post_content, ' ', '')) + 1) AS total_words,
		        MIN(post_date) AS first_date,
		        MAX(post_modified) AS last_modified
		 FROM {$wpdb->posts}
		 WHERE post_type = 'post'
		   AND post_status = 'publish'"
	);

	if ( $posts_data ) {
		$stats['totalWordCount']   = (int) $posts_data->total_words;
		$stats['registeredDate']   = $posts_data->first_date ? (string) $posts_data->first_date : '';
		$stats['lastActivityDate'] = $posts_data->last_modified ? (string) $posts_data->last_modified : '';
	}

	return $stats;
}

function simple_theme_get_collection( WP_REST_Request $request ) {
	$type     = sanitize_key( (string) $request->get_param( 'type' ) );
	$limit    = max( 1, min( 24, (int) $request->get_param( 'limit' ) ?: 6 ) );
	$page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
	$taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
	$term_id  = (int) $request->get_param( 'termId' );
	$allowed  = array( 'post', 'page', 'shuoshuo' );

	if ( ! in_array( $type, $allowed, true ) ) {
		return new WP_Error( 'invalid_type', '不支持的内容类型。', array( 'status' => 400 ) );
	}

	$args = array(
		'post_type'           => $type,
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'paged'               => $page,
		'ignore_sticky_posts' => true,
	);

	if ( $term_id > 0 && in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);
	}

	$query = new WP_Query( $args );
	$items = array_map( 'simple_theme_format_post_item', $query->posts );

	wp_reset_postdata();

	return new WP_REST_Response(
		array(
			'items'      => $items,
			'total'      => (int) $query->found_posts,
			'totalPages' => (int) $query->max_num_pages,
			'page'       => $page,
			'perPage'    => $limit,
		),
		200
	);
}

function simple_theme_get_home_posts( WP_REST_Request $request ) {
	$limit = max( 1, min( 20, (int) $request->get_param( 'limit' ) ?: 6 ) );
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
		)
	);
	$items = array_map( 'simple_theme_format_post_item', $query->posts );
	wp_reset_postdata();
	return new WP_REST_Response( $items, 200 );
}

/**
 * Sakurairo-compatible post view tracking for direct WordPress page loads.
 * Uses Sakurairo's 'views' meta key for seamless migration compatibility.
 */
function simple_theme_set_post_views() {
 if ( ! is_singular() ) {
  return;
 }
 global $post;
 $post_id = intval( $post->ID );
 if ( ! $post_id ) {
  return;
 }
 $views = (int) get_post_meta( $post_id, 'views', true );
 $views++;
 update_post_meta( $post_id, 'views', $views );
}
add_action( 'get_header', 'simple_theme_set_post_views' );

function simple_theme_track_post_view( WP_REST_Request $request ) {
 $post_id = (int) $request->get_param( 'postId' );
 if ( $post_id <= 0 ) {
  return new WP_Error( 'invalid_post', '无效文章。', array( 'status' => 400 ) );
 }
 $view_count = (int) get_post_meta( $post_id, 'views', true );
 $view_count++;
 update_post_meta( $post_id, 'views', $view_count );
 return new WP_REST_Response( array( 'viewCount' => $view_count ), 200 );
}

function simple_theme_detect_browser( $agent ) {
	$agent = (string) $agent;
	if ( false !== stripos( $agent, 'edg/' ) ) {
		return 'Edge';
	}
	if ( false !== stripos( $agent, 'chrome/' ) ) {
		return 'Chrome';
	}
	if ( false !== stripos( $agent, 'safari/' ) && false === stripos( $agent, 'chrome/' ) ) {
		return 'Safari';
	}
	if ( false !== stripos( $agent, 'firefox/' ) ) {
		return 'Firefox';
	}
	return '未知浏览器';
}

function simple_theme_detect_os( $agent ) {
	$agent = (string) $agent;
	if ( false !== stripos( $agent, 'windows' ) ) {
		return 'Windows';
	}
	if ( false !== stripos( $agent, 'android' ) ) {
		return 'Android';
	}
	if ( false !== stripos( $agent, 'iphone' ) || false !== stripos( $agent, 'ipad' ) || false !== stripos( $agent, 'ios' ) ) {
		return 'iOS';
	}
	if ( false !== stripos( $agent, 'mac os' ) || false !== stripos( $agent, 'macintosh' ) ) {
		return 'macOS';
	}
	if ( false !== stripos( $agent, 'linux' ) ) {
		return 'Linux';
	}
	return '未知系统';
}

/**
 * 通过免费接口查询 IP 归属地（多 API 回退链，提升国内服务器可用性）
 */
function simple_theme_get_ip_location( $ip ) {
	$ip = (string) $ip;
	if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return '未知地区';
	}

	// 跳过内网 / 保留地址
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
		return '内网地址';
	}

	// 缓存，避免重复请求
	$cache_key = 'simple_theme_loc_' . md5( $ip );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$location = simple_theme_query_ip_location( $ip );

	// 缓存 7 天（失败也缓存短时间，避免每次请求都重试）
	$ttl = ( '未知地区' === $location ) ? HOUR_IN_SECONDS : DAY_IN_SECONDS * 7;
	set_transient( $cache_key, $location, $ttl );

	return $location;
}

/**
 * 按优先级依次查询多个 IP 地理接口
 */
function simple_theme_query_ip_location( $ip ) {
	// ---- API 1: api2.upk.com.cn ----
	$location = simple_theme_try_api_upk( $ip );
	if ( '未知地区' !== $location ) {
		return $location;
	}

	// ---- API 2: ip-api.com (中文，国外主机友好) ----
	$location = simple_theme_try_api_ipapi( $ip );
	if ( '未知地区' !== $location ) {
		return $location;
	}

	return '未知地区';
}

function simple_theme_try_api_upk( $ip ) {
	$response = wp_remote_get(
		'https://api2.upk.com.cn/ip/v5?ip=' . rawurlencode( $ip ),
		array( 'timeout' => 5 )
	);

	if ( is_wp_error( $response ) ) {
		return '未知地区';
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body ) || 200 !== ( $body['code'] ?? 0 ) ) {
		return '未知地区';
	}

	$data = $body['data'] ?? array();

	// area 字段最完整（例如 "美国加利福尼亚州洛杉矶 CloudFlare节点"），其次 region 或 country
	if ( ! empty( $data['area'] ) ) {
		return trim( $data['area'] );
	}
	if ( ! empty( $data['region'] ) ) {
		return trim( $data['region'] );
	}
	if ( ! empty( $data['country'] ) && '' !== trim( $data['country'] ) ) {
		return trim( $data['country'] );
	}

	return '未知地区';
}

function simple_theme_try_api_ipapi( $ip ) {
	$response = wp_remote_get(
		'http://ip-api.com/json/' . rawurlencode( $ip ) . '?lang=zh-CN',
		array( 'timeout' => 5 )
	);

	if ( is_wp_error( $response ) ) {
		return '未知地区';
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body ) || 'success' !== ( $body['status'] ?? '' ) ) {
		return '未知地区';
	}

	$parts = array();
	if ( ! empty( $body['country'] ) ) {
		$parts[] = trim( $body['country'] );
	}
	if ( ! empty( $body['regionName'] ) ) {
		$parts[] = trim( $body['regionName'] );
	}
	if ( ! empty( $body['city'] ) ) {
		$parts[] = trim( $body['city'] );
	}
	if ( ! empty( $body['isp'] ) ) {
		$parts[] = trim( $body['isp'] );
	}

	return ! empty( $parts ) ? implode( ' ', $parts ) : '未知地区';
}

function simple_theme_get_comment_avatar( string $email ): string {
	if ( '' === $email ) {
		return '';
	}

	$email = strtolower( trim( $email ) );

	// QQ email: use server proxy for privacy
	if ( preg_match( '/^[a-z0-9][a-z0-9._-]*@qq\\.com$/i', $email ) ) {
		$qq = str_replace( '@qq.com', '', $email );
		if ( is_numeric( $qq ) ) {
			return rest_url( 'simple-theme/v1/avatar-proxy?qq=' . urlencode( $qq ) );
		}
	}

	// Non-QQ email: use Gravatar (client-side)
	$hash = md5( $email );
	return 'https://www.gravatar.com/avatar/' . $hash . '?d=404&s=100';
}

function simple_theme_avatar_proxy( WP_REST_Request $request ) {
	$qq = intval( $request->get_param( 'qq' ) );
	if ( $qq <= 10000 ) {
		return new WP_Error( 'invalid_qq', 'Invalid QQ number', array( 'status' => 400 ) );
	}

	$avatar_url = 'https://q1.qlogo.cn/g?b=qq&nk=' . $qq . '&s=100';

	$response = new WP_REST_Response( null, 302 );
	$response->header( 'Location', $avatar_url );
	return $response;
}

function simple_theme_format_comment_item( WP_Comment $comment ) {
	$likes    = (int) get_comment_meta( $comment->comment_ID, 'simple_theme_like_count', true );
	$location = (string) get_comment_meta( $comment->comment_ID, 'simple_theme_location', true );
	$browser  = (string) get_comment_meta( $comment->comment_ID, 'simple_theme_browser', true );
	$os       = (string) get_comment_meta( $comment->comment_ID, 'simple_theme_os', true );
	$ip_mask  = (string) get_comment_meta( $comment->comment_ID, 'simple_theme_ip_mask', true );

	return array(
		'id'         => (int) $comment->comment_ID,
		'parent'     => (int) $comment->comment_parent,
		'date'       => mysql_to_rfc3339( $comment->comment_date_gmt ),
		'authorName' => html_entity_decode( (string) $comment->comment_author, ENT_QUOTES, 'UTF-8' ),
		'status'     => (string) $comment->comment_approved,
		'content'    => array(
			'rendered' => wpautop( wp_kses_post( $comment->comment_content ) ),
		),
		'likes'      => max( 0, $likes ),
		'avatar'     => simple_theme_get_comment_avatar( (string) $comment->comment_author_email ),
		'metaInfo'   => array(
			'location' => '' !== $location ? $location : '未知地区',
			'browser'  => '' !== $browser ? $browser : simple_theme_detect_browser( (string) $comment->comment_agent ),
			'os'       => '' !== $os ? $os : simple_theme_detect_os( (string) $comment->comment_agent ),
			'ipMask'   => '' !== $ip_mask ? $ip_mask : '隐私保护',
		),
	);
}

function simple_theme_build_comment_tree( array $items, $parent_id = 0 ) {
	$branch = array();
	foreach ( $items as $item ) {
		if ( (int) $item['parent'] !== (int) $parent_id ) {
			continue;
		}
		$item['children'] = simple_theme_build_comment_tree( $items, (int) $item['id'] );
		$branch[]         = $item;
	}
	return $branch;
}

function simple_theme_get_comments( WP_REST_Request $request ) {
	$post_id   = (int) $request->get_param( 'post_id' );
	$client_id = (string) $request->get_param( 'client_id' );
	if ( $post_id <= 0 ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	$comments = get_comments(
		array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'order'   => 'ASC',
			'orderby' => 'comment_date_gmt',
			'number'  => 200,
		)
	);

	if ( ! is_array( $comments ) ) {
		$comments = array();
	}

	if ( ! empty( $client_id ) ) {
		$unapproved = get_comments(
			array(
				'post_id'    => $post_id,
				'status'     => 'hold',
				'order'      => 'ASC',
				'orderby'    => 'comment_date_gmt',
				'number'     => 200,
				'meta_key'   => 'simple_theme_client_id',
				'meta_value' => $client_id,
			)
		);
		if ( is_array( $unapproved ) ) {
			$comments = array_merge( $comments, $unapproved );
		}
	}

	$formatted = array_map( 'simple_theme_format_comment_item', $comments );
	$tree      = simple_theme_build_comment_tree( $formatted, 0 );

	return new WP_REST_Response(
		array(
			'items' => $tree,
		),
		200
	);
}

function simple_theme_create_comment( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post' );
	$post    = get_post( $post_id );
	if ( ! $post || 'open' !== $post->comment_status ) {
		return new WP_Error( 'comment_closed', '当前文章未开启评论。', array( 'status' => 403 ) );
	}

	$comment_data = array(
		'comment_post_ID'      => $post_id,
		'comment_parent'       => (int) $request->get_param( 'parent' ),
		'comment_author'       => sanitize_text_field( (string) $request->get_param( 'author_name' ) ),
		'comment_author_email' => sanitize_email( (string) $request->get_param( 'author_email' ) ),
		'comment_author_url'   => esc_url_raw( (string) $request->get_param( 'author_url' ) ),
		'comment_content'      => wp_kses_post( (string) $request->get_param( 'content' ) ),
		'comment_type'         => '',
		'comment_approved'     => 0,
	);

	$client_id = sanitize_text_field( (string) ( $request->get_param( 'client_id' ) ?? $request->get_param( 'clientId' ) ?? '' ) );

	if ( empty( $comment_data['comment_author'] ) || empty( $comment_data['comment_content'] ) ) {
		return new WP_Error( 'invalid_comment', '请填写昵称和评论内容。', array( 'status' => 400 ) );
	}

	$comment_id = wp_new_comment( wp_slash( $comment_data ), true );
	if ( is_wp_error( $comment_id ) ) {
		return $comment_id;
	}

	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$ip_m  = preg_replace( '/\d+$/', '***', $ip );

	$location = simple_theme_get_ip_location( $ip );

	update_comment_meta( $comment_id, 'simple_theme_location', $location );
	update_comment_meta( $comment_id, 'simple_theme_browser', simple_theme_detect_browser( $agent ) );
	update_comment_meta( $comment_id, 'simple_theme_os', simple_theme_detect_os( $agent ) );
	update_comment_meta( $comment_id, 'simple_theme_ip_mask', $ip_m ? $ip_m : '隐私保护' );
	update_comment_meta( $comment_id, 'simple_theme_like_count', 0 );

	if ( ! empty( $client_id ) ) {
		update_comment_meta( $comment_id, 'simple_theme_client_id', $client_id );
	}

	$comment = get_comment( $comment_id );

	return new WP_REST_Response(
		array(
			'item' => $comment instanceof WP_Comment ? simple_theme_format_comment_item( $comment ) : null,
		),
		201
	);
}

function simple_theme_like_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$comment    = get_comment( $comment_id );
	if ( ! $comment instanceof WP_Comment ) {
		return new WP_Error( 'invalid_comment', '评论不存在。', array( 'status' => 404 ) );
	}

	$user_id = get_current_user_id();
	$agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$identity_source = $user_id > 0 ? 'user:' . $user_id : 'guest:' . $ip . '|' . $agent;
	$identity_hash   = md5( $identity_source );
	$like_lock_key   = 'simple_theme_like_lock_' . $comment_id . '_' . $identity_hash;

	if ( get_transient( $like_lock_key ) ) {
		return new WP_Error( 'already_liked', '你已经点过赞了。', array( 'status' => 429 ) );
	}

	$current = (int) get_comment_meta( $comment_id, 'simple_theme_like_count', true );
	$current++;
	update_comment_meta( $comment_id, 'simple_theme_like_count', $current );
	set_transient( $like_lock_key, 1, 5 * YEAR_IN_SECONDS );

	return new WP_REST_Response(
		array(
			'likes' => $current,
		),
		200
	);
}

/**
 * 杈撳嚭鎸囧畾鑿滃崟浣嶇疆鐨勫鑸暟鎹€? *
 * @param WP_REST_Request $request REST 璇锋眰瀵硅薄.
 * @return WP_REST_Response
 */
function simple_theme_get_navigation( WP_REST_Request $request ) {
	$location  = sanitize_key( (string) $request->get_param( 'location' ) );
	$locations = get_nav_menu_locations();

	if ( empty( $locations[ $location ] ) ) {
		return new WP_REST_Response(
			array(
				'items' => array(),
			),
			200
		);
	}

	$menu_items = wp_get_nav_menu_items( $locations[ $location ] );

	if ( empty( $menu_items ) ) {
		return new WP_REST_Response(
			array(
				'items' => array(),
			),
			200
		);
	}

	return new WP_REST_Response(
		array(
			'items' => simple_theme_format_menu_items( $menu_items ),
		),
		200
	);
}

/**
 * 灏?WordPress 鑿滃崟鏉＄洰杞崲涓哄墠绔彲鐩存帴浣跨敤鐨勬爲褰㈢粨鏋勩€? *
 * @param array<int, WP_Post> $items 鑿滃崟鏉＄洰闆嗗悎.
 * @return array<int, array<string, mixed>>
 */
function simple_theme_format_menu_items( array $items ) {
	$flat_items   = array();
	$children_map = array();

	foreach ( $items as $item ) {
		$item_id   = (int) $item->ID;
		$parent_id = (int) $item->menu_item_parent;

		$menu_icon = get_post_meta( $item_id, '_menu_item_icon', true );

		$flat_items[ $item_id ] = array(
			'id'          => $item_id,
			'title'       => html_entity_decode( wp_strip_all_tags( $item->title ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'url'         => $item->url,
			'path'        => simple_theme_get_internal_path( $item->url ),
			'target'      => $item->target,
			'description' => $item->description,
			'current'     => (bool) $item->current,
			'icon'        => is_string( $menu_icon ) && '' !== $menu_icon ? $menu_icon : '',
		);

		if ( ! isset( $children_map[ $parent_id ] ) ) {
			$children_map[ $parent_id ] = array();
		}

		$children_map[ $parent_id ][] = $item_id;
	}

	return simple_theme_build_menu_tree( $children_map, $flat_items, 0 );
}

/**
 * 閫掑綊鏋勫缓鑿滃崟鏍戙€? *
 * @param array<int, array<int, int>>      $children_map 鐖跺瓙鍏崇郴鏄犲皠.
 * @param array<int, array<string, mixed>> $flat_items 骞抽摵鑿滃崟鏁版嵁.
 * @param int                              $parent_id 褰撳墠鐖惰妭鐐?ID.
 * @return array<int, array<string, mixed>>
 */
function simple_theme_build_menu_tree( array $children_map, array $flat_items, $parent_id = 0 ) {
	$tree = array();

	if ( empty( $children_map[ $parent_id ] ) ) {
		return $tree;
	}

	foreach ( $children_map[ $parent_id ] as $child_id ) {
		if ( empty( $flat_items[ $child_id ] ) ) {
			continue;
		}

		$item             = $flat_items[ $child_id ];
		$item['children'] = simple_theme_build_menu_tree( $children_map, $flat_items, $child_id );
		$tree[]           = $item;
	}

	return $tree;
}

/**
 * 灏嗚彍鍗?URL 杞垚绔欑偣鍐呴儴鍙烦杞矾寰勩€? *
 * @param string $url 鑿滃崟 URL.
 * @return string
 */
function simple_theme_get_internal_path( $url ) {
	if ( empty( $url ) ) {
		return '/';
	}

	$site_parts = wp_parse_url( home_url( '/' ) );
	$url_parts  = wp_parse_url( $url );

	if ( false === $url_parts || false === $site_parts ) {
		return '/';
	}

	$site_host = isset( $site_parts['host'] ) ? $site_parts['host'] : '';
	$url_host  = isset( $url_parts['host'] ) ? $url_parts['host'] : $site_host;

	if ( $site_host !== $url_host ) {
		return $url;
	}

	$site_path = isset( $site_parts['path'] ) ? untrailingslashit( $site_parts['path'] ) : '';
	$path      = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';

	if ( ! empty( $site_path ) && $path === $site_path ) {
		$path = '/';
	} elseif ( ! empty( $site_path ) && 0 === strpos( $path, $site_path . '/' ) ) {
		$path = substr( $path, strlen( $site_path ) );
	}

	$path = '/' . ltrim( (string) $path, '/' );

	if ( ! empty( $url_parts['query'] ) ) {
		$path .= '?' . $url_parts['query'];
	}

	if ( ! empty( $url_parts['fragment'] ) ) {
		$path .= '#' . $url_parts['fragment'];
	}

	return $path;
}

/**
 * 瑙ｆ瀽褰撳墠鍓嶇璺緞瀵瑰簲鐨?WordPress 鍐呭銆? *
 * @param WP_REST_Request $request REST 璇锋眰瀵硅薄.
 * @return WP_REST_Response
 */
function simple_theme_resolve_path( WP_REST_Request $request ) {
	$path = simple_theme_normalize_requested_path( (string) $request->get_param( 'path' ) );

	if ( '/' === $path ) {
		return new WP_REST_Response(
			array(
				'type' => 'home',
			),
			200
		);
	}

	$resolved_url = home_url( $path );
	$post_id      = url_to_postid( $resolved_url );

	if ( $post_id > 0 ) {
		$post = get_post( $post_id );

		if ( $post instanceof WP_Post ) {
			$post_type_object = get_post_type_object( $post->post_type );
			$rest_base        = ( $post_type_object && ! empty( $post_type_object->rest_base ) ) ? $post_type_object->rest_base : $post->post_type;

			return new WP_REST_Response(
				array(
					'type'      => $post->post_type,
					'id'        => $post_id,
					'permalink' => get_permalink( $post_id ),
					'restUrl'   => rest_url( sprintf( 'wp/v2/%s/%d?_embed', $rest_base, $post_id ) ),
				),
				200
			);
		}
	}

	// Step 2: Direct DB slug matching for non-ASCII slugs (url_to_postid fails for Chinese)
	$trimmed = trim( $path, '/' );
	$slug    = basename( $trimmed );

	if ( '' !== $slug ) {
		global $wpdb;

		// 2a: Exact match with URL-encoded slug
		$encoded_slug = urlencode( $slug );
		$db_id        = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status = 'publish' LIMIT 1",
				$encoded_slug
			)
		);

		// 2b: LIKE fallback (sanitize_title_for_query strips non-ASCII, match partial)
		if ( ! $db_id ) {
			$slug_like = $wpdb->esc_like( substr( sanitize_title_for_query( $slug ), 0, 8 ) );
			if ( '' !== $slug_like ) {
				$db_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE %s AND post_status = 'publish' ORDER BY ID ASC LIMIT 1",
						'%' . $slug_like . '%'
					)
				);
			}
		}

		if ( $db_id ) {
			$post = get_post( $db_id );
			if ( $post instanceof WP_Post ) {
				$post_type_object = get_post_type_object( $post->post_type );
				$rest_base        = ( $post_type_object && ! empty( $post_type_object->rest_base ) ) ? $post_type_object->rest_base : $post->post_type;

				return new WP_REST_Response(
					array(
						'type'      => $post->post_type,
						'id'        => $db_id,
						'permalink' => get_permalink( $db_id ),
						'restUrl'   => rest_url( sprintf( 'wp/v2/%s/%d?_embed', $rest_base, $db_id ) ),
					),
					200
				);
			}
		}

		// 2c: get_page_by_path for hierarchical pages
		$hierarchical_types = get_post_types( array( 'public' => true, 'hierarchical' => true ), 'names' );
		$page               = get_page_by_path( $trimmed, OBJECT, $hierarchical_types );
		if ( $page instanceof WP_Post ) {
			$post_type_object = get_post_type_object( $page->post_type );
			$rest_base        = ( $post_type_object && ! empty( $post_type_object->rest_base ) ) ? $post_type_object->rest_base : $page->post_type;

			return new WP_REST_Response(
				array(
					'type'      => $page->post_type,
					'id'        => $page->ID,
					'permalink' => get_permalink( $page->ID ),
					'restUrl'   => rest_url( sprintf( 'wp/v2/%s/%d?_embed', $rest_base, $page->ID ) ),
				),
				200
			);
		}
	}

	$term = simple_theme_path_to_term( $path );

	if ( $term instanceof WP_Term ) {
		$taxonomy  = get_taxonomy( $term->taxonomy );
		$rest_base = ( $taxonomy && ! empty( $taxonomy->rest_base ) ) ? $taxonomy->rest_base : $term->taxonomy;

		return new WP_REST_Response(
			array(
				'type'      => 'term',
				'id'        => (int) $term->term_id,
				'name'      => $term->name,
				'taxonomy'  => $term->taxonomy,
				'permalink' => get_term_link( $term ),
				'restUrl'   => rest_url( sprintf( 'wp/v2/%s/%d', $rest_base, $term->term_id ) ),
			),
			200
		);
	}

	return new WP_REST_Response(
		array(
			'type'    => '404',
			'message' => '未找到对应的 WordPress 内容。',
		),
		404
	);
}

/**
 * 瑙勮寖鍖栧墠绔紶鏉ョ殑璺緞锛屽吋瀹圭粷瀵?URL 涓庣浉瀵硅矾寰勩€? *
 * @param string $path 鍓嶇璺緞.
 * @return string
 */
function simple_theme_normalize_requested_path( $path ) {
	$trimmed_path = trim( $path );

	if ( '' === $trimmed_path ) {
		return '/';
	}

	if ( false !== strpos( $trimmed_path, '://' ) ) {
		$parsed_url = wp_parse_url( $trimmed_path );

		if ( is_array( $parsed_url ) ) {
			$trimmed_path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';

			if ( ! empty( $parsed_url['query'] ) ) {
				$trimmed_path .= '?' . $parsed_url['query'];
			}
		}
	}

	if ( '/' !== $trimmed_path[0] ) {
		$trimmed_path = '/' . $trimmed_path;
	}

	$parts    = explode( '?', $trimmed_path, 2 );
	$parts[0] = preg_replace( '#/+#', '/', $parts[0] );

	return empty( $parts[1] ) ? $parts[0] : $parts[0] . '?' . $parts[1];
}

/**
 * 鏍规嵁璺緞鎺ㄦ柇鍒嗙被鎴栨爣绛惧綊妗ｃ€? *
 * @param string $path 褰撳墠鍓嶇璺緞.
 * @return WP_Term|null
 */
function simple_theme_path_to_term( $path ) {
	$parsed_path = wp_parse_url( home_url( $path ), PHP_URL_PATH );

	if ( ! is_string( $parsed_path ) ) {
		return null;
	}

	$normalized_path = trim( $parsed_path, '/' );
	$site_base       = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$site_base       = is_string( $site_base ) ? trim( $site_base, '/' ) : '';

	if ( '' !== $site_base && $normalized_path === $site_base ) {
		return null;
	}

	if ( '' !== $site_base && 0 === strpos( $normalized_path, $site_base . '/' ) ) {
		$normalized_path = substr( $normalized_path, strlen( $site_base ) + 1 );
	}

	if ( '' === $normalized_path ) {
		return null;
	}

	$category_base = get_option( 'category_base' );
	$tag_base      = get_option( 'tag_base' );

	$taxonomy_map = array(
		'category' => $category_base ? trim( $category_base, '/' ) : 'category',
		'post_tag' => $tag_base ? trim( $tag_base, '/' ) : 'tag',
	);

	foreach ( $taxonomy_map as $taxonomy => $base_slug ) {
		if ( '' === $base_slug || 0 !== strpos( $normalized_path, $base_slug . '/' ) ) {
			continue;
		}

		$term_slug = trim( substr( $normalized_path, strlen( $base_slug ) ), '/' );

		if ( '' === $term_slug ) {
			continue;
		}

		$term = get_term_by( 'slug', $term_slug, $taxonomy );

		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	return null;
}
add_action( 'wp_enqueue_scripts', 'simple_theme_enqueue_assets' );

/**
 * 在 WordPress 导航菜单编辑界面添加「图标」输入字段。
 * 用户可输入图标名称（如 home、chat、archive 等），前端会映射为对应 SVG。
 *
 * @param int      $item_id 菜单项 ID。
 * @param WP_Post  $item    菜单项对象。
 * @param int      $depth   菜单项深度。
 * @param stdClass $args    Walker 参数。
 */
function simple_theme_menu_item_icon_field( $item_id, $item, $depth, $args ) {
	$icon = get_post_meta( $item_id, '_menu_item_icon', true );
	?>
	<p class="field-icon description description-wide">
		<label for="edit-menu-item-icon-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( '图标', 'simple-theme' ); ?><br />
			<input
				type="text"
				id="edit-menu-item-icon-<?php echo esc_attr( $item_id ); ?>"
				class="widefat edit-menu-item-icon"
				name="menu_item_icon[<?php echo esc_attr( $item_id ); ?>]"
				value="<?php echo esc_attr( $icon ); ?>"
				placeholder="<?php esc_attr_e( '例如: home, chat, archive', 'simple-theme' ); ?>"
			/>
		</label>
		<span style="display:block;color:#787c82;font-size:11px;margin-top:4px;">
			<?php esc_html_e( '输入图标名称（home / chat / archive / link / info / star / tag / heart / user / mail / bookmark / settings / music / photo / calendar / map / bell / clock / search / shopping）。为空时自动根据标题匹配。', 'simple-theme' ); ?>
		</span>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'simple_theme_menu_item_icon_field', 10, 4 );

/**
 * 保存导航菜单项的图标字段。
 *
 * @param int   $menu_id         菜单 ID。
 * @param int   $menu_item_db_id 菜单项 ID。
 * @param array $args            菜单项参数。
 */
function simple_theme_save_menu_item_icon( $menu_id, $menu_item_db_id, $args ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce 由 WordPress 原生菜单保存逻辑验证
	if ( isset( $_POST['menu_item_icon'][ $menu_item_db_id ] ) ) {
		$icon = sanitize_text_field( wp_unslash( $_POST['menu_item_icon'][ $menu_item_db_id ] ) );
		update_post_meta( $menu_item_db_id, '_menu_item_icon', $icon );
	} else {
		delete_post_meta( $menu_item_db_id, '_menu_item_icon' );
	}
}
add_action( 'wp_update_nav_menu_item', 'simple_theme_save_menu_item_icon', 10, 3 );

// === Admin Settings Page ===

function simple_theme_add_admin_menu() {
	add_theme_page(
		__( 'Simple Theme 设置', 'simple-theme' ),
		__( 'Simple 设置', 'simple-theme' ),
		'manage_options',
		'simple-theme-settings',
		'simple_theme_render_settings_page'
	);
}
add_action( 'admin_menu', 'simple_theme_add_admin_menu' );

function simple_theme_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$options = get_option( 'simple_theme_options', array() );
	$home_post_count = isset( $options['home_post_count'] ) ? absint( $options['home_post_count'] ) : 6;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Simple Theme 设置', 'simple-theme' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'simple_theme_options_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="home_post_count"><?php esc_html_e( '首页每页文章数', 'simple-theme' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							id="home_post_count"
							name="simple_theme_options[home_post_count]"
							value="<?php echo esc_attr( $home_post_count ); ?>"
							min="3"
							max="20"
							step="1"
						/>
						<p class="description"><?php esc_html_e( '首页每次加载的文章数量（3-20 篇）', 'simple-theme' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
