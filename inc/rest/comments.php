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

function simple_theme_build_comment_tree( array $items, $parent_id = 0, $max_depth = 3, $current_depth = 0 ) {
	$branch = array();
	foreach ( $items as $item ) {
		if ( (int) $item['parent'] === $parent_id ) {
			$children = array();
			if ( $current_depth < $max_depth ) {
				$children = simple_theme_build_comment_tree( $items, $item['id'], $max_depth, $current_depth + 1 );
			}
			$item['children'] = $children;
			$branch[] = $item;
		}
	}
	return $branch;
}

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

function simple_theme_get_commenter_cookie( int $comment_id ): ?string {
	if ( ! isset( $_COOKIE['comment_author_email_' . COOKIEHASH] ) ) {
		return null;
	}
	return sanitize_email( wp_unslash( $_COOKIE['comment_author_email_' . COOKIEHASH] ) );
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
 * Schedule an async mail notification when a comment receives a reply.
 * Hooked to wp_insert_comment so the REST endpoint returns immediately.
 */
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
	// Schedule a single cron event to send the email asynchronously.
	// This prevents SMTP connection delay from blocking the comment submission.
	wp_schedule_single_event(
		time(),
		'simple_theme_async_mail_notify',
		array( $comment_id )
	);
}
add_action( 'wp_insert_comment', 'simple_theme_mail_notify', 10, 2 );

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

function simple_theme_get_comments( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$page    = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = min( 50, max( 5, (int) $request->get_param( 'per_page' ) ?: 20 ) );

	if ( ! $post_id ) {
		return new WP_REST_Response( array( 'message' => 'Post ID required' ), 400 );
	}

	$comments = get_comments( array(
		'post_id' => $post_id,
		'status'  => 'approve',
		'order'   => 'ASC',
		'number'  => $per_page,
		'paged'   => $page,
	) );

	$total = (int) get_comments( array(
		'post_id' => $post_id,
		'status'  => 'approve',
		'count'   => true,
	) );

	$filtered = array();
	foreach ( $comments as $comment ) {
		if ( ! simple_theme_user_can_view_comment( $comment ) ) {
			continue;
		}
		$filtered[] = simple_theme_format_comment_item( $comment );
	}

	$pinned = array();
	foreach ( $filtered as $i => $item ) {
		if ( $item['isPinned'] ) {
			$pinned[] = $item;
			unset( $filtered[ $i ] );
		}
	}
	$filtered = array_values( $filtered );

	return new WP_REST_Response( array(
		'items'      => array_merge( $pinned, simple_theme_build_comment_tree( $filtered ) ),
		'total'      => $total,
		'page'       => $page,
		'perPage'    => $per_page,
		'totalPages' => $per_page > 0 ? max( 1, (int) ceil( $total / $per_page ) ) : 1,
	), 200 );
}

function simple_theme_create_comment( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	$post_id = (int) ( $params['post'] ?? 0 );

	if ( ! $post_id || ! get_post( $post_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Invalid post' ), 400 );
	}

	if ( ! comments_open( $post_id ) ) {
		return new WP_REST_Response( array( 'message' => '评论已关闭' ), 403 );
	}

	$use_markdown_req = ! empty( $params['useMarkdown'] );

	$raw_content = $params['content'] ?? '';

	if ( $use_markdown_req ) {
		$parsedown = new Parsedown();
		$parsedown->setSafeMode( true );
		$raw_content = $parsedown->text( $raw_content );
	} else {
		$raw_content = htmlspecialchars( $raw_content, ENT_NOQUOTES, 'UTF-8' );
	}

	$comment_data = array(
		'comment_post_ID'      => $post_id,
		'comment_parent'       => max( 0, (int) ( $params['parent'] ?? 0 ) ),
		'comment_content'      => wp_kses_post( $raw_content ),
		'comment_author'       => sanitize_text_field( $params['author_name'] ?? '' ),
		'comment_author_email'  => sanitize_email( $params['author_email'] ?? '' ),
		'comment_author_url'   => esc_url_raw( $params['author_url'] ?? '' ),
		'comment_type'         => 'comment',
	);

	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$comment_data['user_id'] = $user->ID;
		if ( empty( $comment_data['comment_author'] ) ) {
			$comment_data['comment_author'] = $user->display_name;
		}
		if ( empty( $comment_data['comment_author_email'] ) ) {
			$comment_data['comment_author_email'] = $user->user_email;
		}
	}

	// CAPTCHA check (Altcha PoW)
	if ( simple_theme_option( 'comment_captcha_enabled', false ) ) {
		$captcha_payload = sanitize_text_field( $params['captchaPayload'] ?? '' );
		if ( ! is_user_logged_in() ) {
			if ( ! $captcha_payload || ! simple_theme_verify_altcha( $captcha_payload ) ) {
				return new WP_REST_Response( array( 'message' => '验证码错误' ), 400 );
			}
		}
	}

	$comment_id = wp_new_comment( $comment_data, true );

	if ( is_wp_error( $comment_id ) ) {
		return new WP_REST_Response( array( 'message' => $comment_id->get_error_message() ), 400 );
	}

	// Save custom meta
	if ( ! empty( $params['isPrivate'] ) ) {
		update_comment_meta( $comment_id, 'st_private', '1' );
	}
	if ( ! empty( $params['mailNotify'] ) ) {
		update_comment_meta( $comment_id, 'st_mail_notify', '1' );
	}
	if ( ! empty( $params['useMarkdown'] ) ) {
		update_comment_meta( $comment_id, 'st_markdown', '1' );
	}

	// Save browser / OS / IP metadata from the request
	simple_theme_save_comment_meta_info( $comment_id );

	$comment = get_comment( $comment_id );
	return new WP_REST_Response( array( 'item' => simple_theme_format_comment_item( $comment ) ), 201 );
}

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

define( 'ALTCHA_HMAC_KEY', wp_salt( 'secure_auth' ) );

function simple_theme_generate_captcha(): array {
	$salt      = bin2hex( random_bytes( 16 ) );
	$challenge = bin2hex( random_bytes( 32 ) );
	$data      = array(
		'algorithm' => 'SHA-256',
		'challenge' => $challenge,
		'salt'      => $salt,
		'signature' => hash_hmac( 'sha256', $challenge . $salt, ALTCHA_HMAC_KEY ),
		'maxnumber' => 50000,
	);
	return array(
		'challenge' => base64_encode( wp_json_encode( $data ) ),
	);
}

define( 'ALTCHA_QUEUE_MAX', 3 );
define( 'ALTCHA_QUEUE_TTL', 180 ); // 3 分钟，PoW 足够宽裕

/**
 * Get the captcha queue from transient.
 * Returns an array of entries, cleaned of stale ones.
 */
function simple_theme_captcha_get_queue(): array {
	$queue = get_transient( 'simple_theme_captcha_queue' );
	if ( ! is_array( $queue ) ) {
		$queue = array();
	}
	// Remove expired entries
	$now    = time();
	$active = array();
	foreach ( $queue as $entry ) {
		if ( ( $entry['time'] ?? 0 ) + ALTCHA_QUEUE_TTL > $now ) {
			$active[] = $entry;
		}
	}
	return $active;
}

/**
 * Save the queue back to transient.
 */
function simple_theme_captcha_save_queue( array $queue ): void {
	set_transient( 'simple_theme_captcha_queue', $queue, ALTCHA_QUEUE_TTL );
}

/**
 * Remove a specific challenge from the queue (used after successful verification).
 */
function simple_theme_captcha_remove_challenge( string $challenge ): void {
	$queue = simple_theme_captcha_get_queue();
	$found = false;
	foreach ( $queue as $i => $entry ) {
		if ( ( $entry['challenge'] ?? '' ) === $challenge ) {
			unset( $queue[ $i ] );
			$found = true;
			break;
		}
	}
	if ( $found ) {
		simple_theme_captcha_save_queue( array_values( $queue ) );
	}
}

/**
 * Get a stable IP hash for the current request.
 */
function simple_theme_captcha_ip_hash(): string {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	return hash( 'sha256', $ip . '::captcha_queue' );
}

function simple_theme_verify_altcha( string $payload ): bool {
	$data = json_decode( base64_decode( $payload ), true );
	if ( ! $data || ! isset( $data['algorithm'], $data['challenge'], $data['number'], $data['salt'], $data['signature'] ) ) {
		return false;
	}

	// Verify HMAC signature
	$expected = hash_hmac( 'sha256', $data['challenge'] . $data['salt'], ALTCHA_HMAC_KEY );
	if ( ! hash_equals( $expected, $data['signature'] ) ) {
		return false;
	}

	// Verify Proof-of-Work: SHA-256(challenge + number) must start with "0"
	$hash = hash( 'sha256', $data['challenge'] . $data['number'] );
	if ( $hash[0] !== '0' ) {
		return false;
	}

	// Optional expiry
	if ( ! empty( $data['expires'] ) && time() > $data['expires'] ) {
		return false;
	}

	// Remove from queue — successfully verified
	simple_theme_captcha_remove_challenge( $data['challenge'] );

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
	$ip_hash = simple_theme_captcha_ip_hash();
	$queue   = simple_theme_captcha_get_queue();

	// ① IP 去重 — 检查该 IP 是否已有 pending 的验证码
	foreach ( $queue as $entry ) {
		if ( ( $entry['ip'] ?? '' ) === $ip_hash ) {
			return new WP_REST_Response( array(
				'code'    => 'captcha_pending',
				'message' => '已有验证码等待处理，请完成当前验证后再请求',
			), 429 );
		}
	}

	// ② 队列满 — 最多 3 个并发 pending
	if ( count( $queue ) >= ALTCHA_QUEUE_MAX ) {
		return new WP_REST_Response( array(
			'code'    => 'captcha_queue_full',
			'message' => '当前验证码请求过多，请稍后再试',
		), 429 );
	}

	// ③ 生成验证码
	$result    = simple_theme_generate_captcha();
	$challenge = '';

	// 从 base64 包裹的 JSON 中提取 challenge 值
	$inner = json_decode( base64_decode( $result['challenge'] ), true );
	if ( $inner && ! empty( $inner['challenge'] ) ) {
		$challenge = $inner['challenge'];
	}

	// ④ 加入队列
	$queue[] = array(
		'ip'        => $ip_hash,
		'challenge' => $challenge,
		'time'      => time(),
	);
	simple_theme_captcha_save_queue( $queue );

	return new WP_REST_Response( $result, 200 );
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

/**
 * Meta fields (isPrivate, mailNotify, markdown) are saved directly
 * inside simple_theme_create_comment() after wp_new_comment(),
 * so no wp_insert_comment hook is needed here.
 */

function simple_theme_extend_comment_item( array $item, WP_Comment $comment ) {
	$item['isPinned'] = simple_theme_is_comment_pinned( $comment->comment_ID );
	return $item;
}
add_filter( 'rest_prepare_comment', function( $response ) {
	if ( $response->data ) {
		$comment = get_comment( $response->data['id'] ?? 0 );
		if ( $comment ) {
			$response->data['isPinned'] = simple_theme_is_comment_pinned( $comment->comment_ID );
		}
	}
	return $response;
}, 10, 1 );

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
	} elseif ( isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		$email = sanitize_email( wp_unslash( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) );
		$can_delete = $comment->comment_author_email === $email;
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
	} elseif ( isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		$email = sanitize_email( wp_unslash( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) );
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
 */
add_filter( 'comments_clauses', function( $clauses ) {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
		return $clauses;
	}
	global $wpdb;

	if ( ! current_user_can( 'moderate_comments' ) ) {
		$current_user_id = get_current_user_id();
		$clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} AS st_priv ON ( {$wpdb->comments}.comment_ID = st_priv.comment_id AND st_priv.meta_key = 'st_private' )";
		$clauses['where'] .= $wpdb->prepare(
			" AND ( st_priv.meta_id IS NULL OR ( st_priv.meta_value = '1' AND {$wpdb->comments}.user_id = %d AND %d > 0 ) )",
			$current_user_id,
			$current_user_id
		);
	}
	return $clauses;
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

	// QQ number → qq.com email conversion
	// sanitize_email() strips pure-digit strings, so append @qq.com first
	if ( ! empty( $prepared_comment['comment_author_email'] ) && preg_match( '/^\d{5,}$/', $prepared_comment['comment_author_email'] ) ) {
		$prepared_comment['comment_author_email'] = $prepared_comment['comment_author_email'] . '@qq.com';
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

