<?php
/**
 * Sakurairo Block Compatibility Layer
 *
 * Registers Sakurairo custom blocks so that content authored in Sakurairo
 * continues to render and be editable after switching to Simple Theme.
 *
 * Blocks:
 *   - sakurairo/notice-block      (callout: task/warning/noway/buy)
 *   - sakurairo/showcard-block    (card with icon + image + link)
 *   - sakurairo/conversations-block (chat/conversation bubble)
 *   - sakurairo/vbilibili          (Bilibili video embed)
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================
// 1. Register "sakurairo" block category
// ============================================================

add_filter(
	'block_categories_all',
	function ( $categories ) {
		// Avoid duplicates if another theme/plugin also adds it.
		$slugs = wp_list_pluck( $categories, 'slug' );
		if ( ! in_array( 'sakurairo', $slugs, true ) ) {
			$categories[] = array(
				'slug'  => 'sakurairo',
				'title' => __( 'Sakurairo', 'simple-theme' ),
				'icon'  => null,
			);
		}
		return $categories;
	}
);

// ============================================================
// 2. Front-end render callbacks
// ============================================================

/**
 * Render a sakurairo/notice-block (callout).
 * Saved HTML: <div class="shortcodestyle task|warning|noway|buy"><i …></i><span class="sc-title">TITLE</span><span class="sc-content">…</span></div>
 */
function simple_theme_render_notice_block( $attributes, $content ) {
	if ( empty( $content ) ) {
		return '';
	}

	// Modern block — already has the full structure (icon + sc-title + sc-content).
	if ( false !== strpos( $content, 'sc-title' ) ) {
		return $content;
	}

	$type = ! empty( $attributes['type'] ) ? sanitize_html_class( $attributes['type'] ) : 'task';

	$titles = array(
		'task'    => 'TASK',
		'warning' => 'WARNING',
		'noway'   => 'DIAALLOWED',
		'buy'     => 'ALLOWED',
	);

	$title = isset( $titles[ $type ] ) ? $titles[ $type ] : $titles['task'];

	// Old block format or plain text — rebuild with title + content.
	// Strip any existing wrapper/icon from old format, keep inner text.
	if ( false !== strpos( $content, 'shortcodestyle' ) ) {
		$content = wp_strip_all_tags( $content );
	}

	return sprintf(
		'<div class="shortcodestyle %s"><span class="sc-title">%s</span><span class="sc-content">%s</span></div>',
		esc_attr( $type ),
		esc_html( $title ),
		wp_kses_post( $content )
	);
}

/**
 * Render a sakurairo/showcard-block.
 * Rebuild the small, attribute-driven card instead of returning saved HTML
 * verbatim. This keeps image/link/icon attributes from becoming an HTML sink.
 */
function simple_theme_render_showcard_block( $attributes, $content ) {
	if ( empty( $attributes ) ) {
		return empty( $content ) ? '' : wp_kses_post( $content );
	}

	$icon_classes = preg_split( '/\s+/', (string) ( $attributes['icon'] ?? 'ti ti-bookmark' ) );
	$icon_classes = array_filter( array_map( 'sanitize_html_class', $icon_classes ) );
	$icon         = implode( ' ', $icon_classes ) ?: 'ti ti-bookmark';
	$title        = esc_html( (string) ( $attributes['title'] ?? '' ) );
	$color        = sanitize_hex_color( (string) ( $attributes['color'] ?? '' ) ) ?: '#ffffff';
	$image        = esc_url( (string) ( $attributes['img'] ?? '' ), array( 'http', 'https' ) );
	$link         = function_exists( 'simple_theme_get_safe_navigation_url' )
		? simple_theme_get_safe_navigation_url( $attributes['link'] ?? '' )
		: esc_url( (string) ( $attributes['link'] ?? '' ), array( 'http', 'https' ) );
	$link = $link ?: '#';
	$style = $image ? ' style="background:url(\'' . esc_url( $image ) . '\') center center / cover no-repeat"' : '';

	return '<div class="showcard"><div class="img"' . $style . '><a href="' . esc_url( $link ) . '"><button class="showcard-button" style="color:' . esc_attr( $color ) . '"><i class="ti ti-play-circle" style="font-size:24px"></i></button></a></div><div class="icon-title"><i class="' . esc_attr( $icon ) . '" style="color:' . esc_attr( $color ) . ' !important;"></i><span class="title">' . $title . '</span></div></div>';
}

/**
 * Render a sakurairo/conversations-block.
 * Saved HTML is self-contained — just return it.
 */
function simple_theme_render_conversations_block( $attributes, $content ) {
	if ( empty( $attributes ) ) {
		return empty( $content ) ? '' : wp_kses_post( $content );
	}

	$avatar    = esc_url( (string) ( $attributes['avatar'] ?? '' ), array( 'http', 'https' ) );
	$direction = in_array( $attributes['direction'] ?? 'row', array( 'row', 'row-reverse' ), true ) ? $attributes['direction'] : 'row';
	$text      = wp_kses_post( (string) ( $attributes['content'] ?? $content ) );
	$avatar_html = $avatar ? '<img src="' . esc_url( $avatar ) . '" alt="">' : '';

	return '<div class="conversations-code" style="display:flex;flex-direction:' . esc_attr( $direction ) . ';">' . $avatar_html . '<div class="conversations-code-text">' . $text . '</div></div>';
}

/**
 * Render a sakurairo/vbilibili block.
 * Saved HTML is self-contained — just return it.
 */
function simple_theme_render_vbilibili_block( $attributes, $content ) {
	$video_id = trim( (string) ( $attributes['videoId'] ?? '' ) );
	$src = '';
	if ( preg_match( '/^av(\d+)$/i', $video_id, $matches ) ) {
		$src = 'https://player.bilibili.com/player.html?avid=' . $matches[1] . '&page=1&autoplay=0&danmaku=0';
	} elseif ( preg_match( '/^BV[a-zA-Z0-9]+$/', $video_id ) ) {
		$src = 'https://player.bilibili.com/player.html?bvid=' . rawurlencode( $video_id ) . '&page=1&autoplay=0&danmaku=0';
	}
	if ( ! $src ) {
		return '';
	}

	return '<div class="vbilibili" style="position:relative;padding:56.25% 0 0 0"><iframe src="' . esc_url( $src, array( 'https' ) ) . '" sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts" allowfullscreen style="position:absolute;width:100%;height:100%;left:0;top:0;border:none;overflow:hidden"></iframe></div>';
}

// ============================================================
// 3. Register blocks (server-side)
// ============================================================

add_action( 'init', 'simple_theme_register_sakurairo_blocks' );
function simple_theme_register_sakurairo_blocks() {
	// Only register if Gutenberg is available.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		'sakurairo/notice-block',
		array(
			'render_callback' => 'simple_theme_render_notice_block',
			'attributes'      => array(
				'content'   => array(
					'type'    => 'string',
					'source'  => 'html',
					'selector' => 'span',
				),
				'type'      => array(
					'type'    => 'string',
					'default' => 'task',
				),
				'isExample' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_block_type(
		'sakurairo/showcard-block',
		array(
			'render_callback' => 'simple_theme_render_showcard_block',
			'attributes'      => array(
				'icon'      => array(
					'type'    => 'string',
					'default' => 'fa-regular fa-bookmark',
				),
				'title'     => array(
					'type'    => 'string',
					'default' => '',
				),
				'img'       => array(
					'type'    => 'string',
					'default' => '',
				),
				'color'     => array(
					'type'    => 'string',
					'default' => '#ffffff',
				),
				'link'      => array(
					'type'    => 'string',
					'default' => '',
				),
				'isExample' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_block_type(
		'sakurairo/conversations-block',
		array(
			'render_callback' => 'simple_theme_render_conversations_block',
			'attributes'      => array(
				'avatar'    => array(
					'type'    => 'string',
					'default' => '',
				),
				'direction' => array(
					'type'    => 'string',
					'default' => 'row',
				),
				'content'   => array(
					'type'    => 'string',
					'source'  => 'html',
					'selector' => '.conversations-code-text',
				),
				'isExample' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_block_type(
		'sakurairo/vbilibili',
		array(
			'render_callback' => 'simple_theme_render_vbilibili_block',
			'attributes'      => array(
				'videoId'   => array(
					'type' => 'string',
				),
				'isExample' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);
}

// ============================================================
// 4. Add hljs language class to core/code blocks authored in Sakurairo
// ============================================================

add_filter( 'render_block', 'simple_theme_sakurairo_code_language', 10, 2 );
function simple_theme_sakurairo_code_language( $block_content, $block ) {
	if ( 'core/code' === $block['blockName'] && ! empty( $block['attrs']['language'] ) ) {
		$block_content = preg_replace(
			'/<code(.*?)>/',
			'<code$1 class="language-' . esc_attr( $block['attrs']['language'] ) . '">',
			$block_content
		);
	}
	return $block_content;
}

// ============================================================
// 5. Editor assets (enqueue only in post editor)
// ============================================================

add_action( 'enqueue_block_editor_assets', 'simple_theme_sakurairo_editor_assets' );
function simple_theme_sakurairo_editor_assets() {
	$asset_file = __DIR__ . '/sakurairo-editor.asset.php';
	$deps       = array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-hooks', 'wp-dom-ready' );
	$version    = '3.0.12';

	if ( file_exists( $asset_file ) ) {
		$asset  = include $asset_file;
		$deps   = $asset['dependencies'] ?? $deps;
		$version = $asset['version'] ?? $version;
	}

	// Pass locale data to the editor script (Sakurairo blocks use iroBlockEditor.language).
	wp_add_inline_script(
		'wp-blocks',
		'window.iroBlockEditor = window.iroBlockEditor || ' . wp_json_encode( array(
				'siteTitle' => get_bloginfo( 'name' ),
				'language'  => get_locale(),
				'user'      => wp_get_current_user()->user_login,
			), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
			'before'
		);

	wp_enqueue_script(
		'simple-theme-sakurairo-blocks-editor',
		get_theme_file_uri( '/inc/blocks/sakurairo-editor.js' ),
		$deps,
		$version,
		true
	);


	// Enqueue block styles in the editor.
	wp_enqueue_style(
		'simple-theme-sakurairo-blocks-editor-css',
		get_theme_file_uri( '/inc/blocks/notice-block.css' ),
		array(),
		$version
	);

	// Match editor body font to the theme's general text font.
	wp_add_inline_style(
		'simple-theme-sakurairo-blocks-editor-css',
		'body { font-family: \'MiSans VF\',\'OPPO Sans\',\'SF Pro SC\',HarmonyOS_Regular,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,\'PingFang SC\',\'Segoe UI\',\'Noto Sans\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'; }'
	);
}

// ============================================================
// 6. Front-end assets (block styles on the front end)
// ============================================================

add_action( 'wp_enqueue_scripts', 'simple_theme_sakurairo_frontend_assets' );
function simple_theme_sakurairo_frontend_assets() {
	wp_enqueue_style(
		'simple-theme-sakurairo-blocks',
		get_theme_file_uri( '/inc/blocks/notice-block.css' ),
		array(),
		'3.0.12'
	);
}

// ============================================================
// 7. FontAwesome → Tabler icon conversion
// ============================================================

/**
 * Convert Font Awesome icons to Tabler classes in HTML content.
 *
 * Matches <i class="...fa-*..."> elements and replaces FA icon
 * classes with equivalent ti ti-* classes via a mapping table.
 * Skips FA style prefixes (fa-solid, fa-regular, fa-brands, etc.).
 * The SPA front-end converts these ti classes into inline SVG.
 *
 * @param string $html HTML content.
 * @return string HTML with FA icons replaced by Tabler classes.
 */
function simple_theme_fa_to_bx( $html ) {
	$fa_to_bx = array(
		'triangle-exclamation' => 'ti ti-error',
		'exclamation-triangle' => 'ti ti-error',
		'warning'              => 'ti ti-error',
		'check'                => 'ti ti-check',
		'check-circle'         => 'ti ti-check-circle',
		'times'                => 'ti ti-x',
		'xmark'                => 'ti ti-x',
		'ban'                  => 'ti ti-ban',
		'shopping-cart'        => 'ti ti-cart',
		'shopping-bag'         => 'ti ti-shopping-bag',
		'gift'                 => 'ti ti-gift',
		'bookmark'             => 'ti ti-bookmark',
		'info-circle'          => 'ti ti-info-circle',
		'question-circle'      => 'ti ti-question-mark',
	);

	// FA modifier/style prefixes to skip (not icon names).
	$style_prefixes = array( 'solid', 'regular', 'brands', 'fw', 'xs', 'sm', 'lg', '2x', '3x', '5x' );

	return preg_replace_callback(
		'/<i\s+[^>]*class\s*=\s*"([^"]*)"[^>]*>/i',
		function ( $matches ) use ( $fa_to_bx, $style_prefixes ) {
			$classes     = explode( ' ', $matches[1] );
			$fa_icon     = '';
			$new_classes = array();

			foreach ( $classes as $class ) {
				$class = trim( $class );
				if ( strpos( $class, 'fa-' ) === 0 ) {
					$icon_name = substr( $class, 3 );
					if ( ! in_array( $icon_name, $style_prefixes, true ) && isset( $fa_to_bx[ $icon_name ] ) ) {
						$fa_icon = $fa_to_bx[ $icon_name ];
					}
				} else {
					// Keep non-FA classes (e.g. custom classes, bx classes already present).
					$new_classes[] = $class;
				}
			}

			if ( $fa_icon ) {
				$new_classes[] = $fa_icon;
			}

			if ( empty( $new_classes ) ) {
				return $matches[0];
			}

			return '<i class="' . esc_attr( implode( ' ', $new_classes ) ) . '">';
		},
		$html
	);
}

add_filter( 'render_block', 'simple_theme_render_block_fa_to_bx', 10, 2 );
function simple_theme_render_block_fa_to_bx( $block_content, $block ) {
	if ( ! empty( $block['blockName'] ) && strpos( $block['blockName'], 'sakurairo/' ) === 0 ) {
		$block_content = simple_theme_fa_to_bx( $block_content );
	}
	return $block_content;
}
