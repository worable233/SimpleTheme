<?php
/**
 * Theme Setup & Core Registration
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== CORS ==========
//
// 仅向已允许的源下发 CORS 头（复用 WordPress 原生白名单：同源
// + allowed_http_origins 过滤器里登记的开发源），避免无条件反射
// 任意 Origin 同时携带凭据造成的 CSRF 风险。

add_action( 'init', 'simple_theme_cors_handler' );
function simple_theme_cors_handler() {
	if ( empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		return;
	}
	$origin = esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
	if ( ! is_allowed_http_origin( $origin ) ) {
		return;
	}
	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Vary: Origin', false );
	header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Type, X-Requested-With' );
	if ( 'OPTIONS' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		status_header( 204 );
		exit;
	}
}

// WordPress 核心的 rest_send_cors_headers() 会无条件反射任意 Origin 且带
// Access-Control-Allow-Credentials:true，构成 CSRF 风险。替换为仅对
// is_allowed_http_origin() 白名单内的源下发头（同源 + 上面登记的开发源）。
add_action( 'rest_api_init', 'simple_theme_replace_rest_cors', 15 );
function simple_theme_replace_rest_cors() {
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', 'simple_theme_rest_cors_headers', 11 );
}
function simple_theme_rest_cors_headers( $value ) {
	$origin = get_http_origin();
	if ( $origin && is_allowed_http_origin( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Vary: Origin', false );
	} elseif ( 'null' !== get_http_origin() ) {
		header( 'Vary: Origin', false );
	}
	return $value;
}

// ========== Theme Setup ==========

add_action( 'after_setup_theme', 'simple_theme_setup' );
function simple_theme_setup() {
	load_theme_textdomain( 'simple-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 80,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-spacing' );
	add_theme_support( 'link-color' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'simple-theme' ),
		'footer'  => __( 'Footer Menu', 'simple-theme' ),
	) );

	// Remove default admin bar margin on front-end
	add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );
}

// ========== Shuoshuo (微言/说说) Custom Post Type ==========

add_action( 'init', 'simple_theme_register_shuoshuo_post_type' );
function simple_theme_register_shuoshuo_post_type() {
	register_post_type( 'shuoshuo', array(
		'labels'             => array(
			'name'          => __( '说说', 'simple-theme' ),
			'singular_name' => __( '说说', 'simple-theme' ),
			'add_new'       => __( '发说说', 'simple-theme' ),
			'edit_item'     => __( '编辑说说', 'simple-theme' ),
			'view_item'     => __( '查看说说', 'simple-theme' ),
			'search_items'  => __( '搜索说说', 'simple-theme' ),
			'not_found'     => __( '没有找到说说', 'simple-theme' ),
			'menu_name'     => __( '说说', 'simple-theme' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'shuoshuo', 'with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-format-status',
		'supports'           => array( 'title', 'editor', 'author', 'custom-fields', 'comments' ),
		'show_in_rest'       => true,
		'rest_base'          => 'shuoshuo',
	) );
}

// ========== Menu Item Icon Meta ==========

add_action( 'init', 'simple_theme_register_menu_item_meta' );
function simple_theme_register_menu_item_meta() {
	register_meta( 'post', '_menu_item_icon', array(
		'object_subtype'    => 'nav_menu_item',
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function () {
			return current_user_can( 'edit_theme_options' );
		},
		'show_in_rest'      => true,
	) );
}

add_action( 'wp_nav_menu_item_custom_fields', 'simple_theme_menu_item_icon_field', 10, 4 );
function simple_theme_menu_item_icon_field( $item_id, $item, $depth, $args ) {
	$icon = get_post_meta( $item_id, '_menu_item_icon', true );
	?>
	<p class="field-icon description description-wide">
		<label for="edit-menu-item-icon-<?php echo esc_attr( $item_id ); ?>">
			<?php esc_html_e( '图标 CSS 类名', 'simple-theme' ); ?><br>
			<input
				type="text"
				id="edit-menu-item-icon-<?php echo esc_attr( $item_id ); ?>"
				class="widefat edit-menu-item-icon"
				name="menu_item_icon[<?php echo esc_attr( $item_id ); ?>]"
				value="<?php echo esc_attr( $icon ); ?>"
			/>
			<small style="color:#888;font-style:italic;">如: ti ti-brand-github、home、star</small>
		</label>
	</p>
	<?php
}

add_action( 'wp_update_nav_menu_item', 'simple_theme_save_menu_item_icon', 10, 3 );
function simple_theme_save_menu_item_icon( $menu_id, $menu_item_db_id, $args ) {
	if ( isset( $_POST['menu_item_icon'][ $menu_item_db_id ] ) ) {
		update_post_meta( $menu_item_db_id, '_menu_item_icon', sanitize_text_field( $_POST['menu_item_icon'][ $menu_item_db_id ] ) );
	}
}

// ========== Post View Count ==========
//
// 浏览量统一由前端 REST（simple-theme/v1/track-view）在真实访问时 +1。
// 不再用 wp 钩子计数——那只在服务器渲染（爬虫直出页）时触发，
// 会导致 bot 刷量与 SPA 双重计数。
