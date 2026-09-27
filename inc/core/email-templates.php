<?php
/**
 * Email Templates — HTML email rendering, driven by the theme palette.
 *
 * 三个模板全部从站点外观设置（background/card/foreground/accent/border）取色，
 * 与前台视觉保持一致；邮件客户端不支持 CSS 变量，故一律内联。
 *
 * @package SimpleTheme
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// 1. Apply HTML template to outgoing emails
// ============================================================

add_action( 'phpmailer_init', 'simple_theme_apply_email_template' );
function simple_theme_apply_email_template( $phpmailer ) {
	$options  = get_option( 'simple_theme_options', array() );
	$template = ! empty( $options['email_template'] ) ? $options['email_template'] : 'simple';

	$body = $phpmailer->Body;
	if ( empty( $body ) ) {
		return;
	}

	// Don't wrap if it already appears to be a full HTML document
	if ( stripos( $body, '<!DOCTYPE' ) !== false || stripos( $body, '<html' ) !== false ) {
		return;
	}

	$html = simple_theme_render_email_template( $body, $phpmailer->Subject, $template );

	$phpmailer->isHTML( true );
	$phpmailer->Body = $html;

	// Move plain text version to AltBody for email clients that prefer text
	if ( empty( $phpmailer->AltBody ) ) {
		$phpmailer->AltBody = $body;
	}
}

function simple_theme_render_email_template( $message, $subject, $template = 'simple' ) {
	$message = simple_theme_email_format_text( $message );

	switch ( $template ) {
		case 'card':
			return simple_theme_email_template_card( $message, $subject );
		case 'professional':
			return simple_theme_email_template_professional( $message, $subject );
		default:
			return simple_theme_email_template_simple( $message, $subject );
	}
}

function simple_theme_email_format_text( $text ) {
	$text = esc_html( $text );
	$text = make_clickable( $text );
	$text = nl2br( $text );
	return $text;
}

// ============================================================
// 2. Shared helpers — palette / document shell / body block
// ============================================================

/**
 * Palette pulled from the same site-appearance options the frontend uses.
 * Light values only: email clients render a single (light) theme.
 */
function simple_theme_email_palette() {
	static $palette = null;
	if ( null !== $palette ) {
		return $palette;
	}

	$options = get_option( 'simple_theme_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$pick = static function ( $key, $fallback ) use ( $options ) {
		$value = isset( $options[ $key ] ) ? sanitize_hex_color( (string) $options[ $key ] ) : '';
		return $value ? $value : $fallback;
	};

	$palette = array(
		'background' => $pick( 'background_light', '#f5f6f7' ),
		'card'       => $pick( 'card_light', '#ffffff' ),
		'foreground' => $pick( 'foreground_light', '#333333' ),
		'accent'     => $pick( 'accent_light', '#f5f5f5' ),
		'border'     => $pick( 'border_light', '#e2e2e2' ),
		// 主题浅色下 --muted 为固定值
		'muted'      => '#999999',
	);

	return $palette;
}

/** 正文块：统一字号、行高、链接样式。$body 已由 simple_theme_email_format_text 转义。 */
function simple_theme_email_body_block( $body, $foreground ) {
	return '<div class="st-body" style="margin:0;font-size:15px;line-height:1.8;color:' . $foreground . ';">' . $body . '</div>';
}

/**
 * Full HTML document shell. $title / $preheader 需为已转义文本。
 */
function simple_theme_email_document( $title, $background, $preheader, $content ) {
	$lang = esc_attr( str_replace( '_', '-', (string) get_bloginfo( 'language' ) ) );
	$dir  = is_rtl() ? 'rtl' : 'ltr';
	$font = "-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif";

	return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}" dir="{$dir}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no,address=no,email=no">
<title>{$title}</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
  table,td{mso-table-lspace:0pt;mso-table-rspace:0pt;}
  img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none;}
  a{text-decoration:none;}
  .st-body a{color:inherit;text-decoration:underline;text-underline-offset:2px;}
  @media (max-width:620px){
    .st-card{width:100% !important;}
    .st-pad{padding-left:22px !important;padding-right:22px !important;}
  }
</style>
</head>
<body style="margin:0;padding:0;width:100%;background:{$background};font-family:{$font};">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;height:0;width:0;">{$preheader}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{$background};">
<tr><td align="center" style="padding:40px 16px;">
{$content}
</td></tr>
</table>
</body>
</html>
HTML;
}

// ============================================================
// 3. Template styles
// ============================================================

/** 简约：顶部品牌条 + 白卡 + 浅色页脚条。 */
function simple_theme_email_template_simple( $body, $subject ) {
	$p       = simple_theme_email_palette();
	$site    = esc_html( get_bloginfo( 'name' ) );
	$url     = esc_url( home_url( '/' ) );
	$subject = esc_html( $subject );
	$year    = gmdate( 'Y' );
	$body    = simple_theme_email_body_block( $body, $p['foreground'] );

	$content = <<<HTML
<table role="presentation" class="st-card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:{$p['card']};border:1px solid {$p['border']};border-radius:8px;overflow:hidden;">
<tr><td style="height:4px;background:{$p['foreground']};font-size:0;line-height:0;">&nbsp;</td></tr>
<tr><td class="st-pad" style="padding:32px 40px 0;">
<h1 style="margin:0;font-size:20px;font-weight:600;line-height:1.35;color:{$p['foreground']};">{$subject}</h1>
</td></tr>
<tr><td class="st-pad" style="padding:18px 40px 32px;">{$body}</td></tr>
<tr><td class="st-pad" style="padding:22px 40px;background:{$p['accent']};border-top:1px solid {$p['border']};">
<p style="margin:0;font-size:13px;line-height:1.6;color:{$p['muted']};">
<a href="{$url}" style="color:{$p['foreground']};font-weight:500;text-decoration:none;">{$site}</a>
<span style="margin:0 6px;">·</span>{$year}
</p>
</td></tr>
</table>
HTML;

	return simple_theme_email_document( $subject, $p['background'], $subject, $content );
}

/** 卡片：居中品牌 + 大圆角卡 + 居中页脚。 */
function simple_theme_email_template_card( $body, $subject ) {
	$p       = simple_theme_email_palette();
	$site    = esc_html( get_bloginfo( 'name' ) );
	$url     = esc_url( home_url( '/' ) );
	$subject = esc_html( $subject );
	$year    = gmdate( 'Y' );
	$body    = simple_theme_email_body_block( $body, $p['foreground'] );

	$content = <<<HTML
<table role="presentation" width="600" class="st-card" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
<tr><td align="center" style="padding:0 0 20px;">
<a href="{$url}" style="font-size:18px;font-weight:700;color:{$p['foreground']};text-decoration:none;">{$site}</a>
</td></tr>
<tr><td style="background:{$p['card']};border:1px solid {$p['border']};border-radius:12px;overflow:hidden;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="height:4px;background:{$p['foreground']};font-size:0;line-height:0;">&nbsp;</td></tr>
<tr><td class="st-pad" style="padding:28px 36px 0;">
<h1 style="margin:0;font-size:22px;font-weight:700;line-height:1.35;color:{$p['foreground']};">{$subject}</h1>
</td></tr>
<tr><td class="st-pad" style="padding:22px 36px 30px;">{$body}</td></tr>
</table>
</td></tr>
<tr><td align="center" style="padding:18px 16px 0;font-size:12px;line-height:1.6;color:{$p['muted']};">
<a href="{$url}" style="color:{$p['muted']};text-decoration:none;">{$site}</a><span style="margin:0 4px;">·</span>© {$year}
</td></tr>
</table>
HTML;

	return simple_theme_email_document( $subject, $p['background'], $subject, $content );
}

/** 专业：无外背景，顶部字母间距品牌 + 粗分隔线，左对齐。 */
function simple_theme_email_template_professional( $body, $subject ) {
	$p       = simple_theme_email_palette();
	$site    = esc_html( get_bloginfo( 'name' ) );
	$url     = esc_url( home_url( '/' ) );
	$subject = esc_html( $subject );
	$year    = gmdate( 'Y' );
	$body    = simple_theme_email_body_block( $body, $p['foreground'] );

	$content = <<<HTML
<table role="presentation" class="st-card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:{$p['card']};border:1px solid {$p['border']};border-radius:4px;overflow:hidden;">
<tr><td class="st-pad" style="padding:36px 40px 16px;border-bottom:2px solid {$p['foreground']};">
<a href="{$url}" style="font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:{$p['foreground']};text-decoration:none;">{$site}</a>
</td></tr>
<tr><td class="st-pad" style="padding:32px 40px 28px;">
<h1 style="margin:0 0 20px;font-size:22px;font-weight:700;line-height:1.35;letter-spacing:-0.2px;color:{$p['foreground']};">{$subject}</h1>
{$body}
</td></tr>
<tr><td class="st-pad" style="padding:24px 40px;background:{$p['accent']};border-top:1px solid {$p['border']};">
<p style="margin:0;font-size:12px;line-height:1.6;color:{$p['muted']};text-align:center;">
<a href="{$url}" style="color:{$p['muted']};text-decoration:none;">{$site}</a><span style="margin:0 4px;">·</span>© {$year}
</p>
</td></tr>
</table>
HTML;

	return simple_theme_email_document( $subject, $p['card'], $subject, $content );
}

// ============================================================
// 4. REST endpoints — template list & preview
// ============================================================

add_action( 'rest_api_init', 'simple_theme_register_email_template_routes' );
function simple_theme_register_email_template_routes() {
	register_rest_route( 'simple-theme/v1', '/email-templates', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_email_templates_list',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	register_rest_route( 'simple-theme/v1', '/email-template-preview', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_email_template_preview',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'template' => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => function ( $value ) {
					return in_array( $value, array( 'simple', 'card', 'professional' ), true );
				},
			),
		),
	) );
}

function simple_theme_email_templates_list() {
	$options = get_option( 'simple_theme_options', array() );
	$current = ! empty( $options['email_template'] ) ? $options['email_template'] : 'simple';

	$templates = array(
		array(
			'id'          => 'simple',
			'name'        => '简约',
			'description' => '白卡 + 顶部品牌细条，浅色页脚，适合所有类型的邮件。',
		),
		array(
			'id'          => 'card',
			'name'        => '卡片',
			'description' => '居中品牌标识 + 大圆角卡片，视觉更突出。',
		),
		array(
			'id'          => 'professional',
			'name'        => '专业',
			'description' => '无外背景，字母间距品牌 + 粗分隔线，左对齐商务风。',
		),
	);

	return new WP_REST_Response( array(
		'templates' => $templates,
		'current'   => $current,
	), 200 );
}

function simple_theme_email_template_preview( WP_REST_Request $request ) {
	$template_id = $request->get_param( 'template' );

	$sample_subject = '[' . get_bloginfo( 'name' ) . '] 张三回复了你的评论';
	$sample_body    = "张三 回复了你在《如何打造个人品牌》中的评论：\n\n"
		. "你的评论：\n写得真好，很有启发！\n\n"
		. "回复内容：\n谢谢支持！欢迎常来交流～\n\n"
		. home_url();

	return new WP_REST_Response( array(
		'html' => simple_theme_render_email_template( $sample_body, $sample_subject, $template_id ),
	), 200 );
}
