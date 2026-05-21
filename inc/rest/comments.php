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

// ========== Comment Mail Notification ==========

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
add_action( 'wp_insert_comment', 'simple_theme_mail_notify', 10, 2 );

// ========== REST Endpoints ==========

function simple_theme_get_comments( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$page    = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = min( 50, max( 5, (int) $request->get_param( 'per_page' ) ?: 20 ) );

	if ( ! $post_id ) {
		return new WP_REST_Response( array( 'error' => 'Post ID required' ), 400 );
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
		return new WP_REST_Response( array( 'error' => 'Invalid post' ), 400 );
	}

	if ( ! comments_open( $post_id ) ) {
		return new WP_REST_Response( array( 'error' => '评论已关闭' ), 403 );
	}

	$comment_data = array(
		'comment_post_ID'      => $post_id,
		'comment_parent'       => max( 0, (int) ( $params['parent'] ?? 0 ) ),
		'comment_content'      => wp_kses_post( $params['content'] ?? '' ),
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

	// CAPTCHA check
	if ( simple_theme_option( 'comment_captcha_enabled', true ) ) {
		$captcha_seed  = sanitize_text_field( $params['captchaSeed'] ?? '' );
		$captcha_answer = sanitize_text_field( $params['captchaAnswer'] ?? '' );
		if ( ! is_user_logged_in() ) {
			if ( ! $captcha_seed || ! $captcha_answer || ! simple_theme_verify_captcha( $captcha_seed, $captcha_answer ) ) {
				return new WP_REST_Response( array( 'error' => '验证码错误' ), 403 );
			}
		}
	}

	$comment_id = wp_new_comment( $comment_data, true );

	if ( is_wp_error( $comment_id ) ) {
		return new WP_REST_Response( array( 'error' => $comment_id->get_error_message() ), 400 );
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
		return new WP_REST_Response( array( 'error' => 'Invalid comment' ), 400 );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ip    = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' );
	$liked = get_comment_meta( $comment_id, 'st_liked_ips', true ) ?: array();
	if ( ! is_array( $liked ) ) {
		$liked = array();
	}
	if ( in_array( $ip, $liked, true ) ) {
		return new WP_REST_Response( array( 'error' => '已经点过赞了' ), 403 );
	}

	$liked[] = $ip;
	$liked = array_slice( $liked, -100 );
	update_comment_meta( $comment_id, 'st_liked_ips', $liked );

	$count = (int) get_comment_meta( $comment_id, 'st_likes', true );
	$count++;
	update_comment_meta( $comment_id, 'st_likes', $count );

	return new WP_REST_Response( array( 'likes' => $count ), 200 );
}

// ========== Comment Extras (comment-extras.php merge) ==========

function simple_theme_generate_captcha(): array {
	$a = wp_rand( 1, 20 );
	$b = wp_rand( 1, 20 );
	$op = wp_rand( 0, 1 ) ? '+' : '×';
	$answer = ( '+' === $op ) ? ( $a + $b ) : ( $a * $b );
	$seed   = wp_hash( $a . '|' . $op . '|' . $b . '|' . time() );
	set_transient( 'st_captcha_' . $seed, $answer, 600 );
	return array(
		'seed'  => $seed,
		'question' => "{$a} {$op} {$b} = ?",
	);
}

function simple_theme_verify_captcha( string $seed, $answer ): bool {
	$stored = get_transient( 'st_captcha_' . $seed );
	if ( false === $stored ) {
		return false;
	}
	delete_transient( 'st_captcha_' . $seed );
	return (int) $answer === (int) $stored;
}

function simple_theme_register_comment_extra_routes() {
	// CAPTCHA
	register_rest_route( 'simple-theme/v1', '/comment-captcha', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_rest_captcha',
		'permission_callback' => '__return_true',
	) );

	// EDIT COMMENT
	register_rest_route( 'simple-theme/v1', '/comment-edit', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_rest_edit_comment',
		'permission_callback' => '__return_true',
	) );

	// EDIT HISTORY
	register_rest_route( 'simple-theme/v1', '/comment-history/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'simple_theme_rest_comment_history',
		'permission_callback' => '__return_true',
	) );

	// PIN / UNPIN
	register_rest_route( 'simple-theme/v1', '/comment-pin', array(
		'methods'             => 'POST',
		'callback'            => 'simple_theme_rest_pin_comment',
		'permission_callback' => '__return_true',
	) );
}

function simple_theme_rest_captcha() {
	return new WP_REST_Response( simple_theme_generate_captcha(), 200 );
}

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

	return new WP_REST_Response( array( 'item' => simple_theme_format_comment_item( get_comment( $comment_id ) ) ), 200 );
}

function simple_theme_rest_comment_history( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'id' );
	$history    = get_comment_meta( $comment_id, 'st_edit_history', true ) ?: array();
	return new WP_REST_Response( array( 'history' => $history ), 200 );
}

function simple_theme_rest_pin_comment( WP_REST_Request $request ) {
	$comment_id = (int) $request->get_param( 'commentId' );
	$pin        = (bool) $request->get_param( 'pin' );

	if ( ! current_user_can( 'moderate_comments' ) ) {
		return new WP_REST_Response( array( 'error' => '无权操作' ), 403 );
	}

	update_comment_meta( $comment_id, 'st_pinned', $pin ? '1' : '0' );
	return new WP_REST_Response( array( 'pinned' => $pin, 'id' => $comment_id ), 200 );
}

// ========== Comment meta & hooks ==========

function simple_theme_comment_extra_data( $data, $commentdata ) {
	if ( ! empty( $commentdata['st_private'] ) ) {
		update_comment_meta( $data, 'st_private', '1' );
	}
	if ( ! empty( $commentdata['st_mail_notify'] ) ) {
		update_comment_meta( $data, 'st_mail_notify', '1' );
	}
	return $data;
}
add_action( 'wp_insert_comment', 'simple_theme_comment_extra_data', 10, 2 );

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
