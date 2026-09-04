<?php
/**
 * REST Route Registration
 *
 * Each module registers its own routes via rest_api_init.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Core Site Info ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/site-info', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_site_info',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Navigation ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/navigation/(?P<location>[a-zA-Z0-9_-]+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_navigation',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Path Resolution ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/resolve-url', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_resolve_path',
		'permission_callback' => '__return_true',
		'args'                => array(
			'path' => array(
				'required'          => true,
				'type'              => 'string',
			),
		),
	) );
} );

// ========== Comments (like only; create/fetch via /wp/v2/comments) ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/comment-like', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_like_comment',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Comment Extras ==========

add_action( 'rest_api_init', 'simple_theme_register_comment_extra_routes' );

// ========== Post Collection ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/collection', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_collection',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'simple-theme/v1', '/home-posts', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_home_posts',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'simple-theme/v1', '/track-view', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_track_post_view',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Links ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/links', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_links',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Illustration ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/illustration/(?P<name>[^/]+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_serve_illustration',
		'permission_callback' => '__return_true',
		'args'                => array(
			'name' => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_file_name',
			),
		),
	) );
} );

// ========== Settings (Admin) ==========

/** Permission callback for admin-only endpoints. */
function simple_theme_settings_permission() {
	return current_user_can( 'manage_options' );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/settings', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_settings',
		'permission_callback' => 'simple_theme_settings_permission',
	) );

	register_rest_route( 'simple-theme/v1', '/settings', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_save_settings',
		'permission_callback' => 'simple_theme_settings_permission',
	) );
} );

// ========== About Info ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/about', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_about',
		'permission_callback' => '__return_true',
	) );
} );

// ========== Avatar Proxy ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/avatar-proxy', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_avatar_proxy',
		'permission_callback' => '__return_true',
		'args'                => array(
			'qq' => array(
				'required'          => false,
				'validate_callback' => function( $param ) {
					return is_numeric( $param );
				},
			),
			'hash' => array(
				'required'          => false,
				'validate_callback' => function( $param ) {
					return is_string( $param ) && preg_match( '/^[a-f0-9]{32}$/i', $param );
				},
			),
			's' => array(
				'required'          => false,
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && $param >= 40 && $param <= 200;
				},
			),
		),
	) );
} );

// ========== Pages List (for admin dropdown) ==========

add_action( 'rest_api_init', function () {
	register_rest_route( 'simple-theme/v1', '/pages', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_get_pages_list',
		'permission_callback' => 'simple_theme_settings_permission',
	) );
} );

function simple_theme_get_pages_list() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	$items = array();
	foreach ( $pages as $page ) {
		$items[] = array(
			'id'    => $page->ID,
			'title' => get_the_title( $page ),
		);
	}

	return new WP_REST_Response( $items, 200 );
}
