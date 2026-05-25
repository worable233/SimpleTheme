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
 * Saved HTML: <div class="shortcodestyle task|warning|noway|buy"><i …></i><span>…</span></div>
 */
function simple_theme_render_notice_block( $attributes, $content ) {
	if ( empty( $content ) ) {
		return '';
	}
	$type = ! empty( $attributes['type'] ) ? sanitize_html_class( $attributes['type'] ) : 'task';
	// Ensure the class is present — if the content already has it, we just return as-is.
	if ( false !== strpos( $content, 'shortcodestyle' ) ) {
		return $content;
	}
	return sprintf(
		'<div class="shortcodestyle %s">%s</div>',
		esc_attr( $type ),
		wp_kses_post( $content )
	);
}

/**
 * Render a sakurairo/showcard-block.
 * Saved HTML is self-contained — just return it.
 */
function simple_theme_render_showcard_block( $attributes, $content ) {
	return empty( $content ) ? '' : $content;
}

/**
 * Render a sakurairo/conversations-block.
 * Saved HTML is self-contained — just return it.
 */
function simple_theme_render_conversations_block( $attributes, $content ) {
	return empty( $content ) ? '' : $content;
}

/**
 * Render a sakurairo/vbilibili block.
 * Saved HTML is self-contained — just return it.
 */
function simple_theme_render_vbilibili_block( $attributes, $content ) {
	return empty( $content ) ? '' : $content;
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
	$version    = '3.0.10';

	if ( file_exists( $asset_file ) ) {
		$asset  = include $asset_file;
		$deps   = $asset['dependencies'] ?? $deps;
		$version = $asset['version'] ?? $version;
	}

	// Pass locale data to the editor script (Sakurairo blocks use iroBlockEditor.language).
	wp_add_inline_script(
		'wp-blocks',
		'window.iroBlockEditor = window.iroBlockEditor || ' . json_encode( array(
			'siteTitle' => get_bloginfo( 'name' ),
			'language'  => get_locale(),
			'user'      => wp_get_current_user()->user_login,
		) ) . ';',
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
		'3.0.10'
	);
}

// ============================================================
// 7. FontAwesome → Boxicons icon conversion
// ============================================================

/**
 * Convert Font Awesome icons to Boxicons in HTML content.
 *
 * Matches <i class="...fa-*..."> elements and replaces FA icon
 * classes with equivalent bx bx-* classes via a mapping table.
 * Skips FA style prefixes (fa-solid, fa-regular, fa-brands, etc.).
 *
 * @param string $html HTML content.
 * @return string HTML with FA icons replaced by Boxicons.
 */
function simple_theme_fa_to_bx( $html ) {
	$fa_to_bx = array(
		'triangle-exclamation' => 'bx bx-error',
		'exclamation-triangle' => 'bx bx-error',
		'warning'              => 'bx bx-error',
		'check'                => 'bx bx-check',
		'check-circle'         => 'bx bx-check-circle',
		'times'                => 'bx bx-x',
		'xmark'                => 'bx bx-x',
		'ban'                  => 'bx bxs-ban',
		'shopping-cart'        => 'bx bx-cart',
		'shopping-bag'         => 'bx bx-shopping-bag',
		'gift'                 => 'bx bx-gift',
		'bookmark'             => 'bx bx-bookmark',
		'info-circle'          => 'bx bx-info-circle',
		'question-circle'      => 'bx bx-question-mark',
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
