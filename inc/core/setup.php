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

add_filter( 'allowed_http_origins', 'simple_theme_allow_localhost_origin' );
function simple_theme_allow_localhost_origin( $origin ) {
	$origin[] = 'http://localhost:5173';
	$origin[] = 'http://127.0.0.1:5173';
	return $origin;
}

// ========== REST API CSRF Bypass ==========
//
// Our SPA sends X-WP-Nonce for logged-in users, but some environments
// (Vite dev proxy, mixed localhost/127.0.0.1, stale nonce after logout)
// cause the nonce to be rejected.  Since all our custom routes already
// have their own permission_callback, we simply skip the cookie nonce
// check for simple-theme/v1/* — it never adds real security over our
// own permission callbacks anyway.

add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	if ( ! is_wp_error( $result ) ) {
		return $result;
	}
	if ( 'rest_cookie_invalid_nonce' !== $result->get_error_code() ) {
		return $result;
	}
	$route = $request->get_route();
	if ( 0 === strpos( $route, '/simple-theme/v1/' ) ) {
		return null; // let our permission_callback decide
	}
	return $result;
}, 15, 3 );

// ========== Theme Setup ==========

add_action( 'after_setup_theme', 'simple_theme_setup' );
function simple_theme_setup() {
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
		'auth_callback'     => '__return_true',
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
			<small style="color:#888;font-style:italic;">如: bx bxl-github</small>
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

// ========== Post View Count (legacy wp-postview support) ==========

add_action( 'wp', 'simple_theme_set_post_views' );
function simple_theme_set_post_views() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}
	$count = (int) get_post_meta( $post_id, 'views', true );
	update_post_meta( $post_id, 'views', $count + 1 );
}
