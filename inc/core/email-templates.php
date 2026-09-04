<?php
/**
 * Email Templates — HTML email rendering with multiple styles
 *
 * @package SimpleTheme
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// 1. Apply HTML template to outgoing emails
// ============================================================

add_action( 'phpmailer_init', 'simple_theme_apply_email_template' );
function simple_theme_apply_email_template( $phpmailer ) {
	$options   = get_option( 'simple_theme_options', array() );
	$template  = ! empty( $options['email_template'] ) ? $options['email_template'] : 'simple';

	// Only convert if the email is HTML (or plain text that should be HTML)
	// Skip if already HTML with a full document structure
	$body = $phpmailer->Body;
	if ( empty( $body ) ) {
		return;
	}

	// Don't wrap if it already appears to be a full HTML document
	if ( stripos( $body, '<!DOCTYPE' ) !== false || stripos( $body, '<html' ) !== false ) {
		return;
	}

	$subject  = $phpmailer->Subject;
	$html     = simple_theme_render_email_template( $body, $subject, $template );

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
// 2. Template Styles
// ============================================================

function simple_theme_email_template_simple( $body, $subject ) {
	$site_name   = esc_html( get_bloginfo( 'name' ) );
	$home_url    = esc_url( home_url( '/' ) );
	$subject     = esc_html( $subject );
	$year        = gmdate( 'Y' );
	$header_bg   = '#333333';
	$header_text = '#ffffff';

	return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
<tr><td style="padding:32px 40px 0;border-top:4px solid {$header_bg};">
<h1 style="margin:0;font-size:20px;font-weight:600;color:#333;line-height:1.3;">{$subject}</h1>
</td></tr>
<tr><td style="padding:20px 40px 32px;font-size:15px;line-height:1.8;color:#555;">
{$body}
</td></tr>
<tr><td style="padding:24px 40px;background:#fafafa;border-top:1px solid #eee;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="font-size:13px;color:#999;">
<a href="{$home_url}" style="color:#333;text-decoration:none;font-weight:500;">{$site_name}</a>
<span style="margin:0 6px;">·</span>{$year}
</td></tr>
</table>
</td></tr>
</table>
</td></tr></table>
</body>
</html>
HTML;
}

function simple_theme_email_template_card( $body, $subject ) {
	$site_name   = esc_html( get_bloginfo( 'name' ) );
	$home_url    = esc_url( home_url( '/' ) );
	$subject     = esc_html( $subject );
	$year        = gmdate( 'Y' );
	$accent      = '#4f46e5';

	return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0eff5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:48px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
<tr><td style="padding:0 0 24px;text-align:center;">
<a href="{$home_url}" style="font-size:18px;font-weight:700;color:#333;text-decoration:none;">{$site_name}</a>
</td></tr>
<tr><td style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="height:6px;background:{$accent};"></td></tr>
<tr><td style="padding:28px 36px 0;">
<h1 style="margin:0;font-size:22px;font-weight:700;color:#1a1a2e;line-height:1.3;">{$subject}</h1>
</td></tr>
<tr><td style="padding:24px 36px 32px;font-size:15px;line-height:1.9;color:#444;">
{$body}
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 16px 0;text-align:center;font-size:12px;color:#aaa;">
<a href="{$home_url}" style="color:#888;text-decoration:none;">{$site_name}</a>
<span style="margin:0 4px;">·</span>© {$year}
</td></tr>
</table>
</td></tr></table>
</body>
</html>
HTML;
}

function simple_theme_email_template_professional( $body, $subject ) {
	$site_name   = esc_html( get_bloginfo( 'name' ) );
	$home_url    = esc_url( home_url( '/' ) );
	$subject     = esc_html( $subject );
	$year        = gmdate( 'Y' );

	return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
<tr><td style="padding:48px 40px 16px;border-bottom:2px solid #111;text-align:left;">
<a href="{$home_url}" style="font-size:14px;font-weight:700;color:#111;text-decoration:none;text-transform:uppercase;letter-spacing:2px;">{$site_name}</a>
</td></tr>
<tr><td style="padding:40px 40px 32px;">
<h1 style="margin:0 0 24px;font-size:24px;font-weight:700;color:#111;line-height:1.3;letter-spacing:-0.3px;">{$subject}</h1>
<div style="font-size:15px;line-height:1.9;color:#333;">
{$body}
</div>
</td></tr>
<tr><td style="padding:32px 40px;background:#f8f8f8;border-top:1px solid #eee;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="font-size:12px;color:#999;text-align:center;">
<a href="{$home_url}" style="color:#666;text-decoration:none;">{$site_name}</a>
<span style="margin:0 4px;">·</span>© {$year}
</td></tr>
</table>
</td></tr>
</table>
</td></tr></table>
</body>
</html>
HTML;
}

// ============================================================
// 3. REST endpoint — template preview
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
	$current  = ! empty( get_option( 'simple_theme_options', array() )['email_template'] ) ? get_option( 'simple_theme_options', array() )['email_template'] : 'simple';

	$templates = array(
		array(
			'id'          => 'simple',
			'name'        => '简约',
			'description' => '简洁干净的白色卡片，顶部彩色边框，适合所有类型的邮件。',
		),
		array(
			'id'          => 'card',
			'name'        => '卡片',
			'description' => '现代圆角卡片设计，顶部靛蓝装饰条，视觉效果更突出。',
		),
		array(
			'id'          => 'professional',
			'name'        => '专业',
			'description' => '极简商务风格，无背景色，顶部品牌标识加粗分隔线。',
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
