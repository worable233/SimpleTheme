<?php
/**
 * Theme Options: Defaults, Sanitize, Migration, Settings REST Endpoints
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Default Options ==========

function simple_theme_get_default_options() {
	return array(
		// ---- Appearance ----
		'primary_color'            => '#333333',
		'body_font'                => '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
		'code_font'                => 'ui-monospace, "Cascadia Code", "JetBrains Mono", "SF Mono", "Fira Code", Consolas, Menlo, Monaco, "Courier New", monospace',
		'radius'                   => 'medium',
		'shadow'                   => 'small',
		'background_light'         => '#f5f6f7',
		'background_dark'          => '#1a1a1a',
		'card_light'               => '#ffffff',
		'card_dark'                => '#222222',
		'foreground_light'         => '#333333',
		'foreground_dark'          => '#e0e0e0',
		'accent_light'             => '#f5f5f5',
		'accent_dark'              => '#2a2a2a',
		'border_light'             => '#e2e2e2',
		'border_dark'              => '#333333',
		'container_max_width'      => 1400,
		'article_max_width'        => 900,

		// ---- Hero / Cover ----
		'hero_image'               => '',
		'hero_show_avatar'         => false,
		'hero_avatar'              => '',
		'hero_subtitle'            => '',

		// ---- Card Meta ----
		'meta_show_category'       => true,
		'meta_show_publish_date'   => true,
		'meta_show_modified_date'  => false,
		'meta_show_comment_count'  => false,
		'meta_show_view_count'     => false,
		'meta_show_reading_time'     => true,
		'meta_show_word_count'       => true,
		'meta_show_author'          => true,

		// ---- Article Meta ----
		'article_meta_show_category'       => true,
		'article_meta_show_publish_date'   => true,
		'article_meta_show_modified_date'  => false,
		'article_meta_show_comment_count'  => true,
		'article_meta_show_view_count'     => true,
		'article_meta_show_reading_time'   => true,
		'article_meta_show_word_count'     => false,
		'article_meta_show_author'         => true,
		'reading_speed'              => 300,
		'enable_prism_highlight'     => true,
		'sidebar_show_stats'         => true,
		'sidebar_show_heatmap'       => true,
		'sidebar_show_social'        => true,
		'sidebar_show_hitokoto'      => true,
		'hitokoto_api'               => 'https://v1.hitokoto.cn',
		'show_theme_credit'          => true,

		// ---- Footer & Legal ----
		'copyright_style'          => 'detailed',
		'article_license'          => 'cc-by-nc-sa-40',
		'end_note'                 => '好像就这么多',
		'icp_text'                 => '',
		'icp_gov_text'             => '',
		'social_links'             => '',
		'tech_info_items'          => '',

		// ---- Comments ----
		'comment_show_cookies'      => true,
		'comment_captcha_enabled'   => false,
		'gravatar_base_url'         => 'https://weavatar.com/avatar/',
		'comment_show_private'      => true,
		'comment_show_markdown'     => true,
		'comment_image_upload_enabled' => true,
		'ip_location_api'          => 'xinyew',
		'ip_location_cache'        => true,

		// ---- Collections & Home ----
		'show_shuoshuo_section'    => true,
		'home_post_count'          => 6,
		'home_shuoshuo_count'      => 3,
		'shuoshuo_page_size'       => 12,
		'posts_title'              => '最新文章',
		'posts_subtitle'           => '整理过的长文、笔记与项目更新。',
		'shuoshuo_title'           => '最近说说',
		'shuoshuo_subtitle'        => '',
			'suppress_console_warnings' => false,

			// ---- Announcement ----
			'announcement_enabled'        => false,
			'announcement_mode'           => 'modal',
			'announcement_page_id'        => 0,
			'announcement_buttons'        => '',
			'announcement_capsule_title'  => '',
			'announcement_icon'           => '',

			// ---- Cookie Consent ----
			'cookie_consent_enabled'      => false,
			'cookie_consent_message'      => '本站使用 Cookie 以改善您的访问体验。继续浏览即表示您同意我们的 Cookie 使用政策。',

			// ---- Admin Theme ----
			'admin_theme_enabled'     => false,

			// ---- Admin Bar ----
			'hide_admin_bar'          => false,
				// ---- Local Avatars ----
				'local_avatars_enabled'   => false,

			// ---- SMTP ----
			'smtp_enabled'             => false,
			'smtp_host'                => '',
			'smtp_port'                => 587,
			'smtp_encryption'          => 'tls',
			'smtp_auth'                => true,
			'smtp_username'            => '',
			'smtp_password'            => '',
			'smtp_from_email'          => '',
			'smtp_from_name'           => '',
			'smtp_timeout'             => 30,

			// ---- SMTP Queue ----
			'smtp_queue_enabled'       => true,
			'smtp_queue_retry_count'    => 3,
			'smtp_queue_retry_interval' => 300,
	);
}

// ========== Migration from theme_mod ==========

function simple_theme_migrate_from_customizer() {
	$options = get_option( 'simple_theme_options', array() );
	if ( ! empty( $options ) ) {
		return;
	}

	$migrate_map = array(
		'primary_color'            => 'simple_theme_primary_color',
		'body_font'                => 'simple_theme_body_font',
		'code_font'                => 'simple_theme_code_font',
		'heading_font'             => 'simple_theme_heading_font',
		'radius'                   => 'simple_theme_radius',
		'shadow'                   => 'simple_theme_shadow',
		'background_light'         => 'simple_theme_background_light',
		'background_dark'          => 'simple_theme_background_dark',
		'card_light'               => 'simple_theme_card_light',
		'card_dark'                => 'simple_theme_card_dark',
		'foreground_light'         => 'simple_theme_foreground_light',
		'foreground_dark'          => 'simple_theme_foreground_dark',
		'accent_light'             => 'simple_theme_accent_light',
		'accent_dark'              => 'simple_theme_accent_dark',
		'border_light'             => 'simple_theme_border_light',
		'border_dark'              => 'simple_theme_border_dark',
		'container_max_width'      => 'simple_theme_container_max_width',
		'article_max_width'        => 'simple_theme_article_max_width',
		'hero_image'               => 'simple_theme_hero_image',
		'hero_show_avatar'         => 'simple_theme_hero_show_avatar',
		'hero_avatar'              => 'simple_theme_hero_avatar',
		'show_shuoshuo_section'    => 'simple_theme_show_shuoshuo_section',
		'home_post_count'          => 'simple_theme_home_post_count',
		'home_shuoshuo_count'      => 'simple_theme_home_shuoshuo_count',
		'shuoshuo_page_size'       => 'simple_theme_shuoshuo_page_size',
		'meta_show_category'       => 'simple_theme_meta_show_category',
		'meta_show_publish_date'   => 'simple_theme_meta_show_publish_date',
		'meta_show_modified_date'  => 'simple_theme_meta_show_modified_date',
		'meta_show_comment_count'  => 'simple_theme_meta_show_comment_count',
		'meta_show_view_count'     => 'simple_theme_meta_show_view_count',
		'meta_show_reading_time'   => 'simple_theme_meta_show_reading_time',
		'meta_show_word_count'     => 'simple_theme_meta_show_word_count',
		'meta_show_author'          => 'simple_theme_meta_show_author',
		'copyright_style'          => 'simple_theme_copyright_style',
		'article_license'          => 'simple_theme_article_license',
		'end_note'                 => 'simple_theme_end_note',
		'icp_text'                 => 'simple_theme_icp_text',
		'icp_gov_text'             => 'simple_theme_icp_gov_text',
		'comment_show_cookies'     => 'simple_theme_comment_show_cookies',
		'shuoshuo_subtitle'        => 'simple_theme_shuoshuo_subtitle',
		'hero_subtitle'            => 'simple_theme_hero_subtitle',
	);

	$migrated = array();
	$defaults = simple_theme_get_default_options();
	foreach ( $migrate_map as $new_key => $old_mod ) {
		$old_value = get_theme_mod( $old_mod );
		if ( null !== $old_value && '' !== $old_value ) {
			$migrated[ $new_key ] = $old_value;
		} elseif ( isset( $defaults[ $new_key ] ) ) {
			$migrated[ $new_key ] = $defaults[ $new_key ];
		}
	}

	update_option( 'simple_theme_options', $migrated, false );
}

// ========== Sanitize ==========

function simple_theme_sanitize_options( $input ) {
	if ( ! is_array( $input ) ) {
		return simple_theme_get_default_options();
	}

	$defaults = simple_theme_get_default_options();
	$output   = array();

	foreach ( $defaults as $key => $default_value ) {
		if ( ! isset( $input[ $key ] ) ) {
			$output[ $key ] = $default_value;
			continue;
		}

		$value = $input[ $key ];

		if ( is_bool( $default_value ) ) {
			$output[ $key ] = ! empty( $value );
		} elseif ( is_int( $default_value ) ) {
			$output[ $key ] = (int) $value;
		} elseif ( is_float( $default_value ) ) {
			$output[ $key ] = (float) $value;
		} elseif ( is_string( $default_value ) && '#' === substr( $default_value, 0, 1 ) ) {
			$sanitized = sanitize_hex_color( $value );
			$output[ $key ] = $sanitized ?: $default_value;
		} elseif ( 'radius' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'small', 'medium', 'large' ), true ) ? (string) $value : $default_value;
		} elseif ( 'shadow' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'none', 'small', 'medium', 'large' ), true ) ? (string) $value : $default_value;
		} elseif ( 'copyright_style' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'none', 'simple', 'detailed' ), true ) ? (string) $value : $default_value;
		} elseif ( 'article_license' === $key ) {
			$allowed_licenses = array( 'none', 'cc-by-40', 'cc-by-sa-40', 'cc-by-nc-40', 'cc-by-nc-sa-40', 'cc-by-nd-40', 'cc-by-nc-nd-40', 'cc0-10', 'mit', 'arr' );
			$output[ $key ] = in_array( (string) $value, $allowed_licenses, true ) ? (string) $value : $default_value;
		} elseif ( 'social_links' === $key ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				// 自动将单个对象包装为数组
				$normalized = isset( $decoded[0] ) ? $decoded : array( $decoded );
				$output[ $key ] = wp_json_encode( $normalized );
			} else {
				$output[ $key ] = $default_value;
			}
		} elseif ( 'tech_info_items' === $key ) {
			$decoded = json_decode( $value, true );
			$output[ $key ] = is_array( $decoded ) ? $value : $default_value;
		} elseif ( 'smtp_host' === $key ) {
			$output[ $key ] = sanitize_text_field( (string) $value );
		} elseif ( 'smtp_port' === $key ) {
			$output[ $key ] = min( 65535, max( 1, (int) $value ) );
		} elseif ( 'smtp_encryption' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'none', 'ssl', 'tls' ), true ) ? (string) $value : $defaults[ $key ];
		} elseif ( 'smtp_username' === $key ) {
			$output[ $key ] = sanitize_text_field( (string) $value );
		} elseif ( 'smtp_password' === $key ) {
			$output[ $key ] = $value;
		} elseif ( 'smtp_from_email' === $key ) {
			$output[ $key ] = is_email( (string) $value ) ? sanitize_email( (string) $value ) : '';
		} elseif ( 'smtp_from_name' === $key ) {
			$output[ $key ] = sanitize_text_field( (string) $value );
		} elseif ( 'smtp_timeout' === $key ) {
			$output[ $key ] = max( 1, min( 120, (int) $value ) );
		} elseif ( 'smtp_queue_enabled' === $key ) {
			$output[ $key ] = (bool) $value;
		} elseif ( 'smtp_queue_retry_count' === $key ) {
			$output[ $key ] = max( 0, min( 20, (int) $value ) );
		} elseif ( 'smtp_queue_retry_interval' === $key ) {
			$output[ $key ] = max( 60, min( 3600, (int) $value ) );
		} elseif ( 'email_template' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'simple', 'card', 'professional' ), true ) ? (string) $value : 'simple';
		} elseif ( 'announcement_mode' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'modal', 'capsule' ), true ) ? (string) $value : $defaults[ $key ];
		} elseif ( 'announcement_buttons' === $key ) {
			$decoded = json_decode( $value, true );
			$output[ $key ] = is_array( $decoded ) ? $value : $defaults[ $key ];
		} elseif ( 'hitokoto_api' === $key ) {
			$url = esc_url_raw( trim( (string) $value ) );
			// 仅允许 http(s) 地址，非法输入回退默认 API
			$output[ $key ] = ( $url && preg_match( '#^https?://#i', $url ) ) ? $url : $default_value;
		} else {
			$output[ $key ] = sanitize_text_field( (string) $value );
		}
		}

		return $output;
}

// ========== Settings REST Endpoints ==========

function simple_theme_get_settings() {
	$options = get_option( 'simple_theme_options', array() );
	$defaults = function_exists( 'simple_theme_get_default_options' ) ? simple_theme_get_default_options() : array();
	$options = apply_filters( 'simple_theme_after_get_settings', $options );
	return new WP_REST_Response(
		array(
			'settings' => $options,
			'defaults' => $defaults,
		),
		200
	);
}

function simple_theme_save_settings( WP_REST_Request $request ) {
	$new_options = $request->get_json_params();
	if ( ! is_array( $new_options ) ) {
		return new WP_REST_Response( array( 'error' => 'Invalid data' ), 400 );
	}
	$existing = get_option( 'simple_theme_options', array() );
	$new_options = apply_filters( 'simple_theme_pre_save_settings', $new_options, $existing );
	$new_options = simple_theme_sanitize_options( $new_options );
	$merged = array_merge( $existing, $new_options );
	update_option( 'simple_theme_options', $merged, false );
	return new WP_REST_Response( $merged, 200 );
}
