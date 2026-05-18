<?php
/**
 * Comment Extras — Backward Compatibility Shim
 *
 * All functions have been merged into inc/rest/comments.php.
 * This file is kept for child-theme / plugin code that
 * does direct require_once.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ──────────────────────────────────────────────
// 1. COMMENT CAPTCHA
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_generate_captcha' ) ) {
function simple_theme_generate_captcha(): array {
	$a = wp_rand( 1, 9 );
	$b = wp_rand( 1, 9 );
	$op     = wp_rand( 0, 1 ) ? '+' : '×';
	$answer = ( '+' === $op ) ? ( $a + $b ) : ( $a * $b );
	$seed   = wp_hash( $a . '|' . $op . '|' . $b . '|' . time() );
	set_transient( 'st_captcha_' . $seed, $answer, 600 );
	return array(
		'seed'  => $seed,
		'question' => "{$a} {$op} {$b} = ?",
	);
}
}

if ( ! function_exists( 'simple_theme_verify_captcha' ) ) {
function simple_theme_verify_captcha( string $seed, $answer ): bool {
	$stored = get_transient( 'st_captcha_' . $seed );
	if ( false === $stored ) {
		return false;
	}
	delete_transient( 'st_captcha_' . $seed );
	return (int) $answer === (int) $stored;
}
}

// ──────────────────────────────────────────────
// 2. QQ AVATAR
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_get_qq_avatar_url' ) ) {
function simple_theme_get_qq_avatar_url( string $email_or_qq ): string {
	if ( filter_var( $email_or_qq, FILTER_VALIDATE_EMAIL ) ) {
		if ( preg_match( '/^(\d+)@qq\.com$/i', $email_or_qq, $m ) ) {
			return 'https://q1.qlogo.cn/g?b=qq&nk=' . $m[1] . '&s=100';
		}
		return '';
	}
	if ( preg_match( '/^\d{5,}$/', $email_or_qq ) ) {
		return 'https://q1.qlogo.cn/g?b=qq&nk=' . $email_or_qq . '&s=100';
	}
	return '';
}
}

// ──────────────────────────────────────────────
// 3. PRIVATE COMMENT
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_is_private_comment' ) ) {
function simple_theme_is_private_comment( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_private', true );
}
}

if ( ! function_exists( 'simple_theme_user_can_view_comment' ) ) {
function simple_theme_user_can_view_comment( WP_Comment $comment ): bool {
	if ( ! simple_theme_is_private_comment( $comment->comment_ID ) ) {
		return true;
	}
	if ( is_user_logged_in() && (int) $comment->user_id === get_current_user_id() ) {
		return true;
	}
	if ( current_user_can( 'moderate_comments' ) ) {
		return true;
	}
	return false;
}
}

// ──────────────────────────────────────────────
// 4. EMAIL NOTIFICATION
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_mail_notify' ) ) {
function simple_theme_mail_notify( int $comment_id, WP_Comment $comment ) {
	$parent_id = (int) $comment->comment_parent;
	if ( $parent_id <= 0 ) {
		return;
	}
	$notify = get_comment_meta( $comment->comment_parent, 'st_mail_notify', true );
	if ( '1' !== $notify ) {
		return;
	}
	$parent = get_comment( $parent_id );
	if ( ! $parent || empty( $parent->comment_author_email ) ) {
		return;
	}
	$subject = '[' . get_bloginfo( 'name' ) . '] ' . sprintf(
		/* translators: %s: comment author name */
		__( '%s 回复了你的评论', 'simple-theme' ),
		$comment->comment_author
	);
	$message = sprintf(
		"%s 回复了你在《%s》中的评论:\n\n%s\n\n---\n\n你的评论:\n%s\n\n回复内容:\n%s\n\n%s",
		$comment->comment_author,
		get_the_title( $comment->comment_post_ID ),
		get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id,
		$parent->comment_content,
		$comment->comment_content,
		home_url()
	);
	wp_mail( $parent->comment_author_email, $subject, $message );
}
}

// ──────────────────────────────────────────────
// 5. COMMENT EDITING
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_user_can_edit_comment' ) ) {
function simple_theme_user_can_edit_comment( int $comment_id ): bool {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}
	if ( is_user_logged_in() && (int) $comment->user_id === get_current_user_id() ) {
		return true;
	}
	$cookie = simple_theme_get_commenter_cookie( $comment_id );
	if ( $cookie && hash_equals( $cookie, $comment->comment_author_email ) ) {
		return true;
	}
	return current_user_can( 'moderate_comments' );
}
}

if ( ! function_exists( 'simple_theme_save_edit_history' ) ) {
function simple_theme_save_edit_history( int $comment_id, string $old_content ) {
	$history = get_comment_meta( $comment_id, 'st_edit_history', true ) ?: array();
	$history[] = array(
		'content' => $old_content,
		'time'    => current_time( 'mysql' ),
	);
	if ( count( $history ) > 20 ) {
		array_shift( $history );
	}
	update_comment_meta( $comment_id, 'st_edit_history', $history );
}
}

// ──────────────────────────────────────────────
// 6. PIN COMMENT
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_is_comment_pinned' ) ) {
function simple_theme_is_comment_pinned( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_pinned', true );
}
}

// ──────────────────────────────────────────────
// 7. MARKDOWN TOGGLE
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_comment_uses_markdown' ) ) {
function simple_theme_comment_uses_markdown( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_markdown', true );
}
}

// ──────────────────────────────────────────────
// 8. REST ROUTES
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_register_comment_extra_routes' ) ) {
function simple_theme_register_comment_extra_routes() {
	register_rest_route( 'simple-theme/v1', '/comment-captcha', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_rest_captcha',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'simple-theme/v1', '/comment-edit', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_rest_edit_comment',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'simple-theme/v1', '/comment-history/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_rest_comment_history',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'simple-theme/v1', '/comment-pin', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_rest_pin_comment',
		'permission_callback' => '__return_true',
	) );
}
}

if ( ! function_exists( 'simple_theme_rest_captcha' ) ) {
function simple_theme_rest_captcha() {
	return new WP_REST_Response( simple_theme_generate_captcha(), 200 );
}
}

if ( ! function_exists( 'simple_theme_rest_edit_comment' ) ) {
function simple_theme_rest_edit_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$content    = wp_kses_post( $request->get_param( 'content' ) ?? '' );
	if ( ! $comment_id || ! $content ) {
		return new WP_REST_Response( array( 'error' => '参数不完整' ), 400 );
	}
	if ( ! simple_theme_user_can_edit_comment( $comment_id ) ) {
		return new WP_REST_Response( array( 'error' => '无权编辑' ), 403 );
	}
	$comment = get_comment( $comment_id );
	simple_theme_save_edit_history( $comment_id, $comment->comment_content );
	wp_update_comment( array(
		'comment_ID'      => $comment_id,
		'comment_content' => $content,
	) );
	update_comment_meta( $comment_id, 'st_edited_at', current_time( 'mysql' ) );
	return new WP_REST_Response( simple_theme_format_comment_item( get_comment( $comment_id ) ), 200 );
}
}

if ( ! function_exists( 'simple_theme_rest_comment_history' ) ) {
function simple_theme_rest_comment_history( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'id' );
	$history    = get_comment_meta( $comment_id, 'st_edit_history', true ) ?: array();
	return new WP_REST_Response( $history, 200 );
}
}

if ( ! function_exists( 'simple_theme_rest_pin_comment' ) ) {
function simple_theme_rest_pin_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$pin        = (bool) $request->get_param( 'pin' );
	if ( ! current_user_can( 'moderate_comments' ) ) {
		return new WP_REST_Response( array( 'error' => '无权操作' ), 403 );
	}
	update_comment_meta( $comment_id, 'st_pinned', $pin ? '1' : '0' );
	return new WP_REST_Response( array( 'pinned' => $pin ), 200 );
}
}

// ──────────────────────────────────────────────
// 9. COMMENT META & HOOKS
// ──────────────────────────────────────────────

if ( ! function_exists( 'simple_theme_comment_extra_data' ) ) {
function simple_theme_comment_extra_data( $data, $commentdata ) {
	if ( ! empty( $commentdata['st_private'] ) ) {
		update_comment_meta( $data, 'st_private', '1' );
	}
	if ( ! empty( $commentdata['st_mail_notify'] ) ) {
		update_comment_meta( $data, 'st_mail_notify', '1' );
	}
	return $data;
}
}

if ( ! function_exists( 'simple_theme_filter_private_comments' ) ) {
function simple_theme_filter_private_comments( $comments, $post_id ) {
	return array_filter( $comments, function ( $comment ) {
		return simple_theme_user_can_view_comment( $comment );
	} );
}
}
