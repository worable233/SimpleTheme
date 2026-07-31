<?php
/**
 * REST: Comments CRUD + Extra Endpoints
 *
 * Combines main comment endpoints from functions.php with comment-extras.php
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../lib/Parsedown.php';

// ========== Format / Helpers ==========

function simple_theme_format_comment_item( WP_Comment $comment ) {
	$user_id = (int) $comment->user_id;
	$avatar  = simple_theme_get_comment_avatar( $comment->comment_author_email, $user_id );

	$comment_id = $comment->comment_ID;

	return array(
		'id'            => (int) $comment_id,
		'parent'        => (int) $comment->comment_parent,
		'date'          => $comment->comment_date,
		'authorName'    => $comment->comment_author,
		'authorEmail'   => $comment->comment_author_email,
		'authorUrl'     => $comment->comment_author_url,
		'status'        => $comment->comment_approved,
		'avatar'        => $avatar,
		'content'       => array( 'rendered' => $comment->comment_content ),
		'likes'         => (int) get_comment_meta( $comment_id, 'st_likes', true ),
		'metaInfo'      => array(
			'location' => get_comment_meta( $comment_id, 'st_location', true ) ?: '',
			'browser'  => get_comment_meta( $comment_id, 'st_browser', true ) ?: '',
			'os'       => get_comment_meta( $comment_id, 'st_os', true ) ?: '',
			'ipMask'   => get_comment_meta( $comment_id, 'st_ip_mask', true ) ?: '',
		),
		'children'      => array(),
		'isPinned'      => simple_theme_is_comment_pinned( $comment_id ),
		'isPrivate'     => simple_theme_is_private_comment( $comment_id ),
		'canEdit'       => simple_theme_user_can_edit_comment( $comment_id ),
		'canPin'        => current_user_can( 'moderate_comments' ),
		'useMarkdown'   => simple_theme_comment_uses_markdown( $comment_id ),
		'qqAvatar'      => simple_theme_get_qq_avatar_url( $comment->comment_author_email ),
	);
}

/**
 * Save browser/OS/IP metadata from the current request onto a comment.
 */
function simple_theme_save_comment_meta_info( int $comment_id ): void {
	$ua = '';
	if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ua = strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	$browser = '';
	$os      = '';

	// Detect OS
	if ( strpos( $ua, 'windows nt' ) !== false ) {
		$os = 'Windows';
	} elseif ( strpos( $ua, 'mac os x' ) !== false || strpos( $ua, 'macintosh' ) !== false ) {
		preg_match( '/mac os x (\d+[._]\d+)/', $ua, $m );
		$os = 'macOS' . ( isset( $m[1] ) ? ' ' . str_replace( '_', '.', $m[1] ) : '' );
	} elseif ( strpos( $ua, 'android' ) !== false ) {
		preg_match( '/android (\d+(?:\.\d+)?)/', $ua, $m );
		$os = 'Android' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	} elseif ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false || strpos( $ua, 'ipod' ) !== false ) {
		preg_match( '/os (\d+[._]\d+)/', $ua, $m );
		$os = 'iOS' . ( isset( $m[1] ) ? ' ' . str_replace( '_', '.', $m[1] ) : '' );
	} elseif ( strpos( $ua, 'linux' ) !== false ) {
		$os = 'Linux';
	} elseif ( strpos( $ua, 'cros' ) !== false ) {
		$os = 'ChromeOS';
	}

	// Detect Browser
	if ( strpos( $ua, 'edg/' ) !== false || strpos( $ua, 'edge/' ) !== false ) {
		preg_match( '/(?:edg|edge)\/(\d+(?:\.\d+)?)/', $ua, $m );
		$browser = 'Edge' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	} elseif ( strpos( $ua, 'opr/' ) !== false || strpos( $ua, 'opera/' ) !== false ) {
		preg_match( '/(?:opr|opera)\/(\d+(?:\.\d+)?)/', $ua, $m );
		$browser = 'Opera' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	} elseif ( strpos( $ua, 'firefox/' ) !== false ) {
		preg_match( '/firefox\/(\d+(?:\.\d+)?)/', $ua, $m );
		$browser = 'Firefox' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	} elseif ( strpos( $ua, 'chrome/' ) !== false && strpos( $ua, 'edg/' ) === false ) {
		preg_match( '/chrome\/(\d+(?:\.\d+)?)/', $ua, $m );
		$browser = 'Chrome' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	} elseif ( strpos( $ua, 'safari/' ) !== false && strpos( $ua, 'chrome/' ) === false ) {
		preg_match( '/safari\/(\d+(?:\.\d+)?)/', $ua, $m );
		$browser = 'Safari' . ( isset( $m[1] ) ? ' ' . $m[1] : '' );
	}

	// IP mask (last two octets) + location
	$ip_mask   = '';
	$location  = '';
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts   = explode( '.', $ip );
			$ip_mask = $parts[0] . '.' . $parts[1] . '.*.*';
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts   = explode( ':', $ip );
			$ip_mask = $parts[0] . ':' . $parts[1] . ':*:*';
		}
		if ( function_exists( 'simple_theme_get_ip_location' ) ) {
			$location = simple_theme_get_ip_location( $ip );
		}
	}

	if ( $browser ) {
		update_comment_meta( $comment_id, 'st_browser', $browser );
	}
	if ( $os ) {
		update_comment_meta( $comment_id, 'st_os', $os );
	}
	if ( $ip_mask ) {
		update_comment_meta( $comment_id, 'st_ip_mask', $ip_mask );
	}
	if ( $location ) {
		update_comment_meta( $comment_id, 'st_location', $location );
	}
}

function simple_theme_get_comment_avatar( string $email, int $user_id = 0 ) {
	if ( $user_id > 0 ) {
		$avatar = get_avatar_url( $user_id, array( 'size' => 64 ) );
		if ( $avatar ) {
			return $avatar;
		}
	}
	$qq_url = simple_theme_get_qq_avatar_url( $email );
	if ( $qq_url ) {
		return $qq_url;
	}
	$hash = md5( strtolower( trim( $email ) ) );
	return rest_url( "simple-theme/v1/avatar-proxy?hash={$hash}&s=64" );
}

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

function simple_theme_user_can_edit_comment( int $comment_id ): bool {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}
	if ( is_user_logged_in() && (int) $comment->user_id === get_current_user_id() ) {
		return true;
	}
	$cookie = simple_theme_get_commenter_cookie();
	if ( $cookie && hash_equals( $cookie, $comment->comment_author_email ) ) {
		return true;
	}
	return current_user_can( 'moderate_comments' );
}

/**
 * 读取 WordPress 原生评论者 cookie 中的邮箱（wp_set_comment_cookies 写入）。
 */
function simple_theme_get_commenter_cookie(): string {
	if ( ! isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		return '';
	}
	return sanitize_email( wp_unslash( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) );
}

function simple_theme_is_private_comment( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_private', true );
}

function simple_theme_user_can_view_comment( WP_Comment $comment ): bool {
	if ( ! simple_theme_is_private_comment( $comment->comment_ID ) ) {
		return true;
	}
	if ( is_user_logged_in() && (int) $comment->user_id === get_current_user_id() ) {
		return true;
	}
	// 匿名作者通过评论者 cookie 识别自己的私密评论
	$cookie = simple_theme_get_commenter_cookie();
	if ( $cookie && $comment->comment_author_email && hash_equals( $cookie, $comment->comment_author_email ) ) {
		return true;
	}
	if ( current_user_can( 'moderate_comments' ) ) {
		return true;
	}
	return false;
}

function simple_theme_is_comment_pinned( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_pinned', true );
}

function simple_theme_comment_uses_markdown( int $comment_id ): bool {
	return '1' === get_comment_meta( $comment_id, 'st_markdown', true );
}

// ========== Comment Mail Notification (async via cron) ==========

/**
 * Schedule an async mail notification when a reply gets published.
 * 仅在回复已是“已批准”状态时发送；待审核评论在批准时再通过
 * comment_unapproved_to_approved 补发，st_notified 防重复。
 */
function simple_theme_maybe_schedule_mail_notify( WP_Comment $comment ) {
	$parent_id = (int) $comment->comment_parent;
	if ( $parent_id <= 0 ) {
		return;
	}
	if ( '1' !== (string) $comment->comment_approved ) {
		return;
	}
	if ( '1' === get_comment_meta( $comment->comment_ID, 'st_notified', true ) ) {
		return;
	}
	$notify = get_comment_meta( $parent_id, 'st_mail_notify', true );
	if ( '1' !== $notify ) {
		return;
	}
	$parent = get_comment( $parent_id );
	if ( ! $parent || empty( $parent->comment_author_email ) ) {
		return;
	}
	update_comment_meta( $comment->comment_ID, 'st_notified', '1' );
	// Schedule a single cron event to send the email asynchronously.
	// This prevents SMTP connection delay from blocking the comment submission.
	wp_schedule_single_event(
		time(),
		'simple_theme_async_mail_notify',
		array( (int) $comment->comment_ID )
	);
}

function simple_theme_mail_notify( int $comment_id, WP_Comment $comment ) {
	simple_theme_maybe_schedule_mail_notify( $comment );
}
add_action( 'wp_insert_comment', 'simple_theme_mail_notify', 10, 2 );
add_action( 'comment_unapproved_to_approved', 'simple_theme_maybe_schedule_mail_notify' );

/**
 * Actually send the reply notification email.
 * Runs via wp-cron, not during the HTTP request that created the comment.
 */
function simple_theme_send_mail_notify( int $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}
	$parent_id = (int) $comment->comment_parent;
	if ( $parent_id <= 0 ) {
		return;
	}
	$parent = get_comment( $parent_id );
	if ( ! $parent || empty( $parent->comment_author_email ) ) {
		return;
	}
	$subject = '[' . get_bloginfo( 'name' ) . '] ' . sprintf(
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
add_action( 'simple_theme_async_mail_notify', 'simple_theme_send_mail_notify' );

// ========== REST Endpoints ==========

/*
 * 评论列表/创建均使用 WordPress 核心端点 /wp/v2/comments（见文件尾部的
 * rest_* 钩子），主题只保留核心不提供的能力：点赞、验证码、置顶、
 * 删除自己的待审核评论、拉取自己的待审核评论。
 */

function simple_theme_like_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	if ( ! $comment_id || ! get_comment( $comment_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Invalid comment' ), 400 );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ip    = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' );
	$liked = get_comment_meta( $comment_id, 'st_liked_ips', true ) ?: array();
	if ( ! is_array( $liked ) ) {
		$liked = array();
	}
	$count = (int) get_comment_meta( $comment_id, 'st_likes', true );

	if ( in_array( $ip, $liked, true ) ) {
		// Unlike — remove IP, decrement
		$liked = array_values( array_filter( $liked, function( $v ) use ( $ip ) { return $v !== $ip; } ) );
		$count = max( 0, $count - 1 );
	} else {
		// Like — add IP, increment
		$liked[] = $ip;
		$liked = array_slice( $liked, -100 );
		$count++;
	}

	update_comment_meta( $comment_id, 'st_liked_ips', $liked );
	update_comment_meta( $comment_id, 'st_likes', $count );

	return new WP_REST_Response( array( 'likes' => $count ), 200 );
}

// ========== Comment Extras (comment-extras.php merge) ==========

// ========== ALTCHA (Proof-of-Work CAPTCHA) ==========
//
// 按官方 ALTCHA 协议实现（widget 求解 sha256(salt + number) === challenge）：
// - salt 携带 ?expires= 过期时间，签名覆盖 challenge，无法篡改
// - 验证成功后 challenge 记入 transient 防重放

define( 'ALTCHA_HMAC_KEY', wp_salt( 'secure_auth' ) );
define( 'ALTCHA_MAX_NUMBER', 50000 );
define( 'ALTCHA_TTL', 600 ); // 验证码 10 分钟有效

function simple_theme_generate_captcha(): array {
	$secret    = random_int( 0, ALTCHA_MAX_NUMBER );
	$salt      = bin2hex( random_bytes( 12 ) ) . '?expires=' . ( time() + ALTCHA_TTL );
	$challenge = hash( 'sha256', $salt . $secret );
	return array(
		'algorithm' => 'SHA-256',
		'challenge' => $challenge,
		'maxnumber' => ALTCHA_MAX_NUMBER,
		'salt'      => $salt,
		'signature' => hash_hmac( 'sha256', $challenge, ALTCHA_HMAC_KEY ),
	);
}

function simple_theme_verify_altcha( string $payload ): bool {
	$data = json_decode( base64_decode( $payload ), true );
	if ( ! is_array( $data ) || ! isset( $data['challenge'], $data['number'], $data['salt'], $data['signature'] ) ) {
		return false;
	}

	// 过期检查（expires 嵌在 salt 中，受签名保护）
	$query = wp_parse_url( (string) $data['salt'], PHP_URL_QUERY );
	parse_str( (string) $query, $args );
	if ( empty( $args['expires'] ) || time() > (int) $args['expires'] ) {
		return false;
	}

	// 防重放：每个 challenge 只能验证通过一次
	$used_key = 'st_altcha_' . md5( (string) $data['challenge'] );
	if ( get_transient( $used_key ) ) {
		return false;
	}

	// HMAC 签名校验
	$expected = hash_hmac( 'sha256', (string) $data['challenge'], ALTCHA_HMAC_KEY );
	if ( ! hash_equals( $expected, (string) $data['signature'] ) ) {
		return false;
	}

	// PoW 校验：sha256(salt + number) 必须等于 challenge
	if ( ! hash_equals( (string) $data['challenge'], hash( 'sha256', $data['salt'] . $data['number'] ) ) ) {
		return false;
	}

	set_transient( $used_key, 1, ALTCHA_TTL );
	return true;
}

function simple_theme_register_comment_extra_routes() {
	// CAPTCHA
	register_rest_route( 'simple-theme/v1', '/comment-captcha', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_rest_captcha',
		'permission_callback' => '__return_true',
	) );

	// DELETE pending comment (own)
	register_rest_route( 'simple-theme/v1', '/comment-delete', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_delete_comment',
		'permission_callback' => '__return_true',
	) );

	// PIN / UNPIN
	register_rest_route( 'simple-theme/v1', '/comment-pin', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_rest_pin_comment',
		'permission_callback' => '__return_true',
	) );

	// GET pending comments for current user
	register_rest_route( 'simple-theme/v1', '/user-pending-comments', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_get_user_pending_comments',
		'permission_callback' => '__return_true',
	) );
}

function simple_theme_rest_captcha() {
	return new WP_REST_Response( simple_theme_generate_captcha(), 200 );
}


function simple_theme_rest_pin_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$pin        = (bool) $request->get_param( 'pin' );

	if ( ! current_user_can( 'moderate_comments' ) ) {
		return new WP_REST_Response( array( 'message' => '无权操作' ), 403 );
	}

	update_comment_meta( $comment_id, 'st_pinned', $pin ? '1' : '0' );
	return new WP_REST_Response( array( 'pinned' => $pin, 'id' => $comment_id ), 200 );
}

// ========== Comment meta & hooks ==========

function simple_theme_filter_private_comments( $comments, $post_id ) {
	return array_filter( $comments, function( $comment ) {
		return simple_theme_user_can_view_comment( $comment );
	} );
}
add_filter( 'comments_array', 'simple_theme_filter_private_comments', 10, 2 );

// ========== Delete Comment (own pending only) ==========

function simple_theme_delete_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$comment = get_comment( $comment_id );

	if ( ! $comment ) {
		return new WP_REST_Response( array( 'message' => '评论不存在' ), 404 );
	}

	// Only allow deleting pending (unapproved) comments
	if ( $comment->comment_approved !== '0' ) {
		return new WP_REST_Response( array( 'message' => '只能删除待审核的评论' ), 403 );
	}

	// Check ownership
	$can_delete = false;
	if ( is_user_logged_in() ) {
		$can_delete = (int) $comment->user_id === get_current_user_id();
	} else {
		$email = simple_theme_get_commenter_cookie();
		$can_delete = $email && $comment->comment_author_email === $email;
	}

	if ( ! $can_delete && ! current_user_can( 'moderate_comments' ) ) {
		return new WP_REST_Response( array( 'message' => '无权删除' ), 403 );
	}

	wp_delete_comment( $comment_id, true );
	return new WP_REST_Response( array( 'deleted' => true ), 200 );
}

// ========== User Pending Comments ==========

function simple_theme_get_user_pending_comments( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	if ( ! $post_id ) {
		return new WP_REST_Response( array( 'message' => 'Post ID required' ), 400 );
	}

	// Identify current user via auth or cookie
	$email = '';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$email = $user->user_email;
	} else {
		$email = simple_theme_get_commenter_cookie();
	}

	if ( ! $email ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	$pending = get_comments( array(
		'post_id'      => $post_id,
		'status'       => 'hold',
		'author_email' => $email,
		'number'       => 20,
	) );

	$items = array();
	foreach ( $pending as $comment ) {
		$items[] = simple_theme_format_comment_item( $comment );
	}

	return new WP_REST_Response( array( 'items' => $items ), 200 );
}

// ========== WP REST API Integration ==========

/**
 * Filter comment query to exclude private comments from users who can't view them.
 * 登录用户按 user_id 匹配；匿名作者按 WordPress 评论者 cookie 的邮箱匹配。
 */
add_filter( 'comments_clauses', function( $clauses ) {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
		return $clauses;
	}
	global $wpdb;

	if ( ! current_user_can( 'moderate_comments' ) ) {
		$current_user_id = get_current_user_id();
		$cookie_email    = simple_theme_get_commenter_cookie();
		$clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} AS st_priv ON ( {$wpdb->comments}.comment_ID = st_priv.comment_id AND st_priv.meta_key = 'st_private' )";
		$clauses['where'] .= $wpdb->prepare(
			" AND ( st_priv.meta_id IS NULL OR st_priv.meta_value <> '1'" .
			" OR ( %d > 0 AND {$wpdb->comments}.user_id = %d )" .
			" OR ( %s <> '' AND {$wpdb->comments}.comment_author_email = %s ) )",
			$current_user_id,
			$current_user_id,
			$cookie_email,
			$cookie_email
		);
	}
	return $clauses;
} );

/**
 * Allow anonymous comments via WP REST API (wp/v2/comments POST).
 *
 * Core rejects anonymous REST comments (rest_comment_login_required) unless a
 * theme/plugin opts in via this filter. The frontend comment form posts to the
 * core endpoint, so without this filter guests can never comment.
 * WP still enforces its own checks afterwards (require_name_email,
 * comment_registration, moderation, KSES, etc.).
 */
add_filter( 'rest_allow_anonymous_comments', function () {
	// 仍尊重“用户必须注册并登录才可以发表评论”设置
	return ! get_option( 'comment_registration' );
} );

/**
 * Verify CAPTCHA before creating a comment via WP REST API.
 */
add_filter( 'rest_pre_insert_comment', function( $prepared_comment, $request ) {
	// CAPTCHA check
	if ( simple_theme_option( 'comment_captcha_enabled', false ) && ! is_user_logged_in() ) {
		$captcha_payload = sanitize_text_field( $request->get_param( 'captchaPayload' ) ?? '' );
		if ( ! $captcha_payload || ! simple_theme_verify_altcha( $captcha_payload ) ) {
			return new WP_Error( 'captcha_failed', '验证码错误', array( 'status' => 400 ) );
		}
	}

	// Markdown→HTML conversion (before KSES in wp_new_comment)
	if ( ! empty( $request->get_param( 'useMarkdown' ) ) && isset( $prepared_comment['comment_content'] ) ) {
		$parsedown = new Parsedown();
		$parsedown->setSafeMode( true );
		$prepared_comment['comment_content'] = $parsedown->text( $prepared_comment['comment_content'] );
	}

	return $prepared_comment;
}, 10, 2 );

/**
 * Save custom meta after a comment is created via WP REST API.
 */
add_action( 'rest_after_insert_comment', function( $comment, $request ) {
	$comment_id = $comment->comment_ID;

	if ( ! empty( $request->get_param( 'isPrivate' ) ) ) {
		update_comment_meta( $comment_id, 'st_private', '1' );
	}
	if ( ! empty( $request->get_param( 'mailNotify' ) ) ) {
		update_comment_meta( $comment_id, 'st_mail_notify', '1' );
	}
	if ( ! empty( $request->get_param( 'useMarkdown' ) ) ) {
		update_comment_meta( $comment_id, 'st_markdown', '1' );
	}

	simple_theme_save_comment_meta_info( $comment_id );

	// 用 WordPress 原生机制设置评论者 cookie（核心 REST 端点自己不设），
	// 后续的“查看/删除自己的待审核评论”、私密评论自见都依赖它。
	if ( ! is_user_logged_in() ) {
		$consent = $request->get_param( 'cookiesConsent' );
		wp_set_comment_cookies( $comment, wp_get_current_user(), null === $consent ? true : (bool) $consent );
	}
}, 10, 2 );

/**
 * Add custom fields to WP REST API comment response.
 */
add_filter( 'rest_prepare_comment', function( $response, $comment ) {
	$data      = $response->get_data();
	$comment_id = $comment->comment_ID;
	$user_id   = (int) $comment->user_id;

	$data['likes']     = (int) get_comment_meta( $comment_id, 'st_likes', true );
	$data['metaInfo']  = array(
		'location' => get_comment_meta( $comment_id, 'st_location', true ) ?: '',
		'browser'  => get_comment_meta( $comment_id, 'st_browser', true ) ?: '',
		'os'       => get_comment_meta( $comment_id, 'st_os', true ) ?: '',
		'ipMask'   => get_comment_meta( $comment_id, 'st_ip_mask', true ) ?: '',
	);
	$data['isPinned']  = '1' === get_comment_meta( $comment_id, 'st_pinned', true );
	$data['isPrivate'] = '1' === get_comment_meta( $comment_id, 'st_private', true );
	$data['canEdit']   = simple_theme_user_can_edit_comment( $comment_id );
	$data['canPin']    = current_user_can( 'moderate_comments' );
	$data['useMarkdown'] = '1' === get_comment_meta( $comment_id, 'st_markdown', true );
	$data['qqAvatar']  = simple_theme_get_qq_avatar_url( $comment->comment_author_email );
	$data['avatar']    = simple_theme_get_comment_avatar( $comment->comment_author_email, $user_id );
	$data['children']  = array();

	// Keep raw content so frontend renderCommentContent() handles emoji/markdown rendering
	$data['content']['rendered'] = $comment->comment_content;

	$response->set_data( $data );
	return $response;
}, 10, 2 );

