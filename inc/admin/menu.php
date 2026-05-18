<?php
/**
 * Admin Menu & Page Registration
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Admin Menu ==========

add_action( 'admin_menu', 'simple_theme_register_admin_menu' );
function simple_theme_register_admin_menu() {
	add_menu_page(
		__( 'Simple Theme', 'simple-theme' ),
		__( 'Simple Theme', 'simple-theme' ),
		'manage_options',
		'simple-theme',
		'simple_theme_render_admin_page',
		'dashicons-layout',
		30
	);
}

// ========== Settings Registration ==========

add_action( 'admin_init', 'simple_theme_register_settings' );
function simple_theme_register_settings() {
	register_setting(
		'simple_theme_options_group',
		'simple_theme_options',
		array(
			'sanitize_callback' => 'simple_theme_sanitize_options',
			'default'           => simple_theme_get_default_options(),
		)
	);
}

// ========== Admin Page Render ==========

// Auto-migrate on theme activation
add_action( 'after_switch_theme', 'simple_theme_migrate_from_customizer' );

function simple_theme_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '你没有权限访问此页面。', 'simple-theme' ) );
	}
	simple_theme_migrate_from_customizer();
	echo '<div id="simple-theme-admin-app" class="sta-root-shell"></div>';
}
