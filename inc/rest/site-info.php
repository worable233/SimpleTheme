<?php
/**
 * REST: Site Info Endpoint
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simple_theme_get_site_info() {
	$theme_options     = get_option( 'simple_theme_options', array() );
	$comment_show_email   = true;
	$comment_show_url     = true;
	$comment_show_cookies = (bool) ( $theme_options['comment_show_cookies'] ?? (bool) get_option( 'show_comments_cookies_opt_in' ) );
	$social_links  = array();
	if ( ! empty( $theme_options['social_links'] ) ) {
		$decoded = json_decode( $theme_options['social_links'], true );
		if ( is_array( $decoded ) ) {
			// 如果用户只填了单个对象 {...}，自动包装成数组 [{...}]
			$social_links = isset( $decoded[0] ) ? $decoded : array( $decoded );
		}
	}
	$icp_text     = ! empty( $theme_options['icp_text'] ) ? $theme_options['icp_text'] : '';
	$icp_gov_text = ! empty( $theme_options['icp_gov_text'] ) ? $theme_options['icp_gov_text'] : '';
	$stats          = simple_theme_compute_site_stats();
	$theme_version  = wp_get_theme()->get( 'Version' ) ?: '';
	$tech_info_items = array();
	if ( ! empty( $theme_options['tech_info_items'] ) ) {
		$decoded = json_decode( $theme_options['tech_info_items'], true );
		if ( is_array( $decoded ) ) {
			$tech_info_items = $decoded;
		}
	}

	return new WP_REST_Response(
		array(
			'wpVersion'     => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '',
			'phpVersion'    => PHP_VERSION,
			'serverOs'      => PHP_OS,
			'restApiVersion' => 'v1',
			'name'          => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'description'   => html_entity_decode( get_bloginfo( 'description' ), ENT_QUOTES, 'UTF-8' ),
			'url'           => home_url( '/' ),
			'siteIcon'      => get_site_icon_url() ?: '',
			'hero'          => array(
				'image'      => (string) ( $theme_options['hero_image'] ?? '' ),
				'showAvatar' => (bool) ( $theme_options['hero_show_avatar'] ?? false ),
				'avatar'     => (string) ( $theme_options['hero_avatar'] ?? '' ),
				'subtitle'   => (string) ( $theme_options['hero_subtitle'] ?? '' ),
			),
			'theme'         => array(
				'primaryColor'    => sanitize_hex_color( (string) ( $theme_options['primary_color'] ?? '#333333' ) ) ?: '#333333',
				'bodyFont'        => (string) ( $theme_options['body_font'] ?? '' ) ?: '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
				'codeFont'        => (string) ( $theme_options['code_font'] ?? '' ) ?: "'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'SF Mono', 'Consolas', 'Menlo', Monaco, monospace",
				'radius'          => simple_theme_sanitize_choice( $theme_options['radius'] ?? 'medium', array( 'small', 'medium', 'large' ), 'medium' ),
				'shadow'          => simple_theme_sanitize_choice( $theme_options['shadow'] ?? 'small', array( 'none', 'small', 'medium', 'large' ), 'small' ),
				'backgroundLight' => sanitize_hex_color( (string) ( $theme_options['background_light'] ?? '#f5f6f7' ) ) ?: '#f5f6f7',
				'backgroundDark'  => sanitize_hex_color( (string) ( $theme_options['background_dark'] ?? '#1a1a1a' ) ) ?: '#1a1a1a',
				'cardLight'       => sanitize_hex_color( (string) ( $theme_options['card_light'] ?? '#ffffff' ) ) ?: '#ffffff',
				'cardDark'        => sanitize_hex_color( (string) ( $theme_options['card_dark'] ?? '#222222' ) ) ?: '#222222',
				'foregroundLight' => sanitize_hex_color( (string) ( $theme_options['foreground_light'] ?? '#333333' ) ) ?: '#333333',
				'foregroundDark'  => sanitize_hex_color( (string) ( $theme_options['foreground_dark'] ?? '#e0e0e0' ) ) ?: '#e0e0e0',
				'accentLight'     => sanitize_hex_color( (string) ( $theme_options['accent_light'] ?? '#f5f5f5' ) ) ?: '#f5f5f5',
				'accentDark'      => sanitize_hex_color( (string) ( $theme_options['accent_dark'] ?? '#2a2a2a' ) ) ?: '#2a2a2a',
				'borderLight'     => sanitize_hex_color( (string) ( $theme_options['border_light'] ?? '#e2e2e2' ) ) ?: '#e2e2e2',
				'borderDark'      => sanitize_hex_color( (string) ( $theme_options['border_dark'] ?? '#333333' ) ) ?: '#333333',
				'containerMaxWidth'=> simple_theme_get_option_number( 'container_max_width', 1400, 960, 1680 ),
				'articleMaxWidth' => simple_theme_get_option_number( 'article_max_width', 900, 680, 1200 ),
				'copyrightStyle' => (string) ( $theme_options['copyright_style'] ?? 'detailed' ),
				'articleLicense' => (string) ( $theme_options['article_license'] ?? 'cc-by-nc-sa-40' ),
				'showCredit'        => (bool) ( $theme_options['show_theme_credit'] ?? true ),
				'prismEnabled'      => (bool) ( $theme_options['enable_prism_highlight'] ?? true ),
				'cardMeta'        => array(
					'showCategory'      => (bool) ( $theme_options['meta_show_category'] ?? true ),
					'showPublishDate'   => (bool) ( $theme_options['meta_show_publish_date'] ?? true ),
					'showModifiedDate'  => (bool) ( $theme_options['meta_show_modified_date'] ?? false ),
					'showCommentCount'  => (bool) ( $theme_options['meta_show_comment_count'] ?? false ),
					'showViewCount'     => (bool) ( $theme_options['meta_show_view_count'] ?? false ),
					'showReadingTime'   => (bool) ( $theme_options['meta_show_reading_time'] ?? true ),
					'showWordCount'     => (bool) ( $theme_options['meta_show_word_count'] ?? true ),
					'showAuthor'      => (bool) ( $theme_options['meta_show_author'] ?? true ),
				),
					'articleMeta'        => array(
						'showCategory'      => (bool) ( $theme_options['article_meta_show_category'] ?? true ),
						'showPublishDate'   => (bool) ( $theme_options['article_meta_show_publish_date'] ?? true ),
						'showModifiedDate'  => (bool) ( $theme_options['article_meta_show_modified_date'] ?? false ),
						'showCommentCount'  => (bool) ( $theme_options['article_meta_show_comment_count'] ?? true ),
						'showViewCount'     => (bool) ( $theme_options['article_meta_show_view_count'] ?? true ),
						'showReadingTime'   => (bool) ( $theme_options['article_meta_show_reading_time'] ?? true ),
						'showWordCount'     => (bool) ( $theme_options['article_meta_show_word_count'] ?? false ),
						'showAuthor'      => (bool) ( $theme_options['article_meta_show_author'] ?? true ),
					),
			),
			'comments'      => array(
				'requireNameEmail' => (bool) get_option( 'require_name_email' ),
				'registrationOnly' => (bool) get_option( 'comment_registration' ),
				'showEmailField'   => $comment_show_email,
				'showUrlField'     => $comment_show_url,
				'showCookiesOptIn' => $comment_show_cookies,
				'captchaEnabled'   => (bool) ( $theme_options['comment_captcha_enabled'] ?? false ),
				'showPrivateOption' => (bool) ( $theme_options['comment_show_private'] ?? true ),
				'showMarkdownOption' => (bool) ( $theme_options['comment_show_markdown'] ?? true ),
			'showImageUpload' => (bool) ( $theme_options['comment_image_upload_enabled'] ?? true ),
			),
			'collections'   => array(
				'postsTitle'         => (string) ( $theme_options['posts_title'] ?? '最新文章' ),
				'postsSubtitle'      => (string) ( $theme_options['posts_subtitle'] ?? '整理过的长文、笔记与项目更新。' ),
				'shuoshuoTitle'      => (string) ( $theme_options['shuoshuo_title'] ?? '最近说说' ),
				'shuoshuoSubtitle'   => (string) ( $theme_options['shuoshuo_subtitle'] ?? '' ),
				'showShuoshuoSection'=> (bool) ( $theme_options['show_shuoshuo_section'] ?? true ),
				'homePostCount'      => simple_theme_get_option_number( 'home_post_count', 6, 3, 20 ),
				'homeShuoshuoCount'  => simple_theme_get_option_number( 'home_shuoshuo_count', 3, 0, 12 ),
				'shuoshuoPageSize'   => simple_theme_get_option_number( 'shuoshuo_page_size', 12, 6, 24 ),
			),
			'stats'          => $stats,
			'socialLinks'    => $social_links,
			'loginUrl'       => wp_login_url(),
			'icp'            => $icp_text,
			'icpGov'         => $icp_gov_text,
			'endNote'         => ! empty( $theme_options['end_note'] ) ? $theme_options['end_note'] : '',
			'currentUser'     => simple_theme_get_current_commenter(),
			'themeVersion'    => $theme_version,
			'techInfoItems'   => $tech_info_items,
		),
		200
	);
}

function simple_theme_compute_site_stats() {
	global $wpdb;

	$cache_key   = 'simple_theme_site_stats_v2';
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$total_posts     = (int) wp_count_posts( 'post' )->publish;
	$total_shuoshuo  = (int) wp_count_posts( 'shuoshuo' )->publish;
	$total_words     = (int) $wpdb->get_var( "SELECT COALESCE( SUM( LENGTH( post_content ) - LENGTH( REPLACE( post_content, ' ', '' ) ) + 1 ), 0 ) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'" );
	$total_comments  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'" );
	$total_tags      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'post_tag'" );
	$total_cats      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'category'" );
	$last_updated    = $wpdb->get_var( "SELECT post_modified FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_modified DESC LIMIT 1" );
	$first_post_date = $wpdb->get_var( "SELECT post_date FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date ASC LIMIT 1" );
	$heatmap_raw     = $wpdb->get_results( "SELECT DATE(post_date) AS day, COUNT(*) AS count FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date >= DATE_SUB( CURDATE(), INTERVAL 365 DAY ) GROUP BY day ORDER BY day ASC" );

	$stats = array(
		'postCount'        => $total_posts,
		'shuoshuoCount'    => $total_shuoshuo,
		'categoryCount'    => $total_cats,
		'tagCount'         => $total_tags,
		'totalWordCount'   => max( 0, $total_words ),
		'commentCount'     => $total_comments,
		'registeredDate'   => $first_post_date ? gmdate( 'c', strtotime( $first_post_date ) ) : '',
		'lastActivityDate' => $last_updated ? gmdate( 'c', strtotime( $last_updated ) ) : '',
		'heatmapData'      => $heatmap_raw,
	);

	set_transient( $cache_key, $stats, HOUR_IN_SECONDS );
	return $stats;
}
