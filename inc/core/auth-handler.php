<?php
/**
 * Auth Handler — 登录/注册/密码重置 REST API + wp-login.php 拦截
 *
 * 拦截 wp-login.php 的所有操作，重定向到前台弹窗。
 * 通过 simple-theme/v1/auth/* REST 路由处理所有认证操作，
 * 使用 WordPress 原生函数（wp_signon、register_new_user 等）。
 *
 * @package SimpleTheme
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// 1. 拦截 wp-login.php，重定向到前台
// ============================================================

add_action( 'login_init', 'simple_theme_intercept_wp_login' );
function simple_theme_intercept_wp_login() {
	$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';

	// 允许 logout 正常执行（必须走 WP 原生逻辑）
	if ( 'logout' === $action ) {
		return;
	}

	// 如果已经有 $_POST（正在提交表单），让 WP 处理（我们会挂钩到 login_form_*）
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && ! empty( $_POST ) ) {
		// POST 提交由 WP 处理，我们通过 login_form_* 钩子拦截错误信息
		return;
	}

	// 密码重置链接（含 key 和 login 参数）：重定向到前台并携带参数
	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) && isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {
		$redirect_url = add_query_arg(
			array(
				'action'    => 'resetpass',
				'key'       => sanitize_text_field( wp_unslash( $_GET['key'] ) ),
				'login'     => sanitize_user( wp_unslash( $_GET['login'] ) ),
			),
			home_url( '/' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	// 其他操作（login、register、lostpassword、checkemail 等）：重定向到首页，让弹窗显示
	$redirect_url = home_url( '/' );

	// 如果是后台确认管理员邮箱，跳转到后台（Vue admin shell 会覆盖）
	if ( 'confirm_admin_email' === $action ) {
		return; // 让 WP 正常处理
	}

	// 如果是 post password，不拦截
	if ( 'postpass' === $action ) {
		return;
	}

	// 如果是 confirmaction（隐私请求确认），不拦截
	if ( 'confirmaction' === $action ) {
		return;
	}

	// 携带 checkemail 状态
	if ( isset( $_GET['checkemail'] ) ) {
		$redirect_url = add_query_arg( 'checkemail', sanitize_text_field( wp_unslash( $_GET['checkemail'] ) ), $redirect_url );
	}

	// 携带 loggedout 状态
	if ( isset( $_GET['loggedout'] ) ) {
		$redirect_url = add_query_arg( 'loggedout', 'true', $redirect_url );
	}

	// 携带 registration=disabled 状态
	if ( isset( $_GET['registration'] ) ) {
		$redirect_url = add_query_arg( 'registration', sanitize_text_field( wp_unslash( $_GET['registration'] ) ), $redirect_url );
	}

	wp_safe_redirect( $redirect_url );
	exit;
}

// ============================================================
// 2. 密码重置邮件自定义链接（使用前台地址）
// ============================================================

add_filter( 'retrieve_password_title', function ( $title ) {
	return '[' . get_bloginfo( 'name' ) . '] ' . __( 'Password Reset' );
} );

add_filter( 'retrieve_password_message', 'simple_theme_retrieve_password_message', 10, 4 );
function simple_theme_retrieve_password_message( $message, $key, $user_login, $user_data ) {
	// 构建指向我们前台的密码重置链接（不带 site_url，带 action=resetpass）
	$reset_url = add_query_arg(
		array(
			'action' => 'resetpass',
			'key'    => $key,
			'login'  => $user_login,
		),
		home_url( '/' )
	);

	$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, ignore this email and nothing will happen.' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
	$message .= $reset_url . "\r\n";

	return $message;
}

// ============================================================
// 3. REST API 路由
// ============================================================

add_action( 'rest_api_init', 'simple_theme_register_auth_routes' );
function simple_theme_register_auth_routes() {
	// 登录
	register_rest_route( 'simple-theme/v1', '/auth/login', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_auth_login',
		'permission_callback' => '__return_true',
		'args'                => array(
			'log'        => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_user',
			),
			'pwd'        => array(
				'required'          => true,
				'type'              => 'string',
			),
			'rememberme' => array(
				'type' => 'boolean',
				'default' => false,
			),
		),
	) );

	// 注册
	register_rest_route( 'simple-theme/v1', '/auth/register', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_auth_register',
		'permission_callback' => '__return_true',
		'args'                => array(
			'user_login' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_user',
			),
			'user_email' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
			),
		),
	) );

	// 忘记密码
	register_rest_route( 'simple-theme/v1', '/auth/lost-password', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_auth_lost_password',
		'permission_callback' => '__return_true',
		'args'                => array(
			'user_login' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );

	// 验证重置密钥
	register_rest_route( 'simple-theme/v1', '/auth/validate-reset-key', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_auth_validate_reset_key',
		'permission_callback' => '__return_true',
		'args'                => array(
			'key'   => array(
				'required' => true,
				'type'     => 'string',
			),
			'login' => array(
				'required' => true,
				'type'     => 'string',
			),
		),
	) );

	// 重置密码
	register_rest_route( 'simple-theme/v1', '/auth/reset-password', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_auth_reset_password',
		'permission_callback' => '__return_true',
		'args'                => array(
			'key'   => array(
				'required' => true,
				'type'     => 'string',
			),
			'login' => array(
				'required' => true,
				'type'     => 'string',
			),
			'pass1' => array(
				'required' => true,
				'type'     => 'string',
			),
			'pass2' => array(
				'required' => true,
				'type'     => 'string',
			),
		),
	) );

	// 获取当前用户信息
	register_rest_route( 'simple-theme/v1', '/auth/me', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_auth_me',
		'permission_callback' => '__return_true',
	) );
}

/**
 * Apply a short-lived, site-specific IP limit to unauthenticated auth actions.
 * The raw address is never persisted; only the HMAC-backed transient key is used.
 */
function simple_theme_auth_rate_limited( $action, $limit, $window ) {
	$ip_hash = function_exists( 'simple_theme_hash_ip' ) ? simple_theme_hash_ip() : '';
	if ( ! $ip_hash ) {
		return false;
	}

	$key   = 'simple_theme_auth_' . sanitize_key( $action ) . '_' . substr( $ip_hash, 0, 32 );
	$count = get_transient( $key );
	$count = false === $count ? 0 : (int) $count;
	if ( $count >= $limit ) {
		return true;
	}

	set_transient( $key, $count + 1, $window );
	return false;
}

function simple_theme_auth_rate_limit_response( $message = '' ) {
	$response = new WP_REST_Response( array(
		'success' => false,
		'code'    => 'rate_limited',
		'message' => $message ?: __( 'Too many attempts. Please try again later.' ),
	), 429 );
	$response->header( 'Retry-After', (string) ( 15 * MINUTE_IN_SECONDS ) );
	return $response;
}

// ============================================================
// 4. 登录回调
// ============================================================

function simple_theme_auth_login( WP_REST_Request $request ) {
	$log        = $request->get_param( 'log' );
	$pwd        = $request->get_param( 'pwd' );
	$rememberme = (bool) $request->get_param( 'rememberme' );

	if ( simple_theme_auth_rate_limited( 'login', 10, 15 * MINUTE_IN_SECONDS ) ) {
		return simple_theme_auth_rate_limit_response();
	}

	$credentials = array(
		'user_login'    => $log,
		'user_password' => $pwd,
		'remember'      => $rememberme,
	);

	// 使用 WordPress 原生 wp_signon
	$user = wp_signon( $credentials, is_ssl() );

	if ( is_wp_error( $user ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'invalid_credentials',
			'message' => __( 'Invalid username or password.' ),
		), 401 );
	}

	// 设置当前用户
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, $rememberme );

	// 生成 REST API nonce
	$nonce = wp_create_nonce( 'wp_rest' );

	$response = new WP_REST_Response( array(
		'success'       => true,
		'user'          => array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 80 ) ),
			'email'        => $user->user_email,
			'url'          => $user->user_url,
		),
		'rest_nonce'    => $nonce,
		'redirect_to'   => admin_url(),
		'logout_url'    => wp_logout_url( home_url( '/' ) ),
	), 200 );
	$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
	$response->header( 'Vary', 'Cookie' );
	return $response;
}

// ============================================================
// 5. 注册回调
// ============================================================

function simple_theme_auth_register( WP_REST_Request $request ) {
	// 检查是否允许注册
	if ( ! get_option( 'users_can_register' ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'registration_disabled',
			'message' => __( 'User registration is currently not allowed.' ),
		), 403 );
	}

	if ( simple_theme_auth_rate_limited( 'register', 5, HOUR_IN_SECONDS ) ) {
		return simple_theme_auth_rate_limit_response( __( 'Too many registration attempts. Please try again later.' ) );
	}

	$user_login = $request->get_param( 'user_login' );
	$user_email = $request->get_param( 'user_email' );

	// 使用 WordPress 原生 register_new_user
	$result = register_new_user( $user_login, $user_email );

	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'registration_failed',
			'message' => __( 'Registration could not be completed. Please check your details and try again.' ),
		), 400 );
	}

	return new WP_REST_Response( array(
		'success'              => true,
		'message'              => __( 'Registration complete. Please check your email, then log in.' ),
	), 200 );
}

// ============================================================
// 6. 忘记密码回调
// ============================================================

function simple_theme_auth_lost_password( WP_REST_Request $request ) {
	$user_login = $request->get_param( 'user_login' );

	if ( simple_theme_auth_rate_limited( 'lost_password', 5, HOUR_IN_SECONDS ) ) {
		return simple_theme_auth_rate_limit_response( __( 'Too many password reset attempts. Please try again later.' ) );
	}

	// 使用 WordPress 原生 retrieve_password
	retrieve_password( $user_login );

	return new WP_REST_Response( array(
		'success' => true,
		'message' => __( 'If an account matches the information provided, a password reset email will be sent shortly.' ),
	), 200 );
}

// ============================================================
// 7. 验证重置密钥回调
// ============================================================

function simple_theme_auth_validate_reset_key( WP_REST_Request $request ) {
	$key   = $request->get_param( 'key' );
	$login = $request->get_param( 'login' );

	if ( simple_theme_auth_rate_limited( 'validate_reset_key', 20, HOUR_IN_SECONDS ) ) {
		return simple_theme_auth_rate_limit_response();
	}

	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) ) {
		$code = $user->get_error_code();
		$message = '';
		if ( 'expired_key' === $code ) {
			$message = __( 'Your password reset link has expired. Please request a new link.' );
		} elseif ( 'invalid_key' === $code ) {
			$message = __( 'Your password reset link appears to be invalid. Please request a new link.' );
		} else {
			$message = __( 'Invalid password reset link.' );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'code'    => $code,
			'message' => $message,
		), 400 );
	}

	return new WP_REST_Response( array(
		'success' => true,
		'message' => __( 'Valid reset key. You can now reset your password.' ),
	), 200 );
}

// ============================================================
// 8. 重置密码回调
// ============================================================

function simple_theme_auth_reset_password( WP_REST_Request $request ) {
	$key   = $request->get_param( 'key' );
	$login = $request->get_param( 'login' );
	$pass1 = $request->get_param( 'pass1' );
	$pass2 = $request->get_param( 'pass2' );

	if ( simple_theme_auth_rate_limited( 'reset_password', 10, HOUR_IN_SECONDS ) ) {
		return simple_theme_auth_rate_limit_response();
	}

	// 验证两次密码一致
	if ( $pass1 !== $pass2 ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'password_mismatch',
			'message' => __( 'The passwords do not match.' ),
		), 400 );
	}

	// 密码不能为空
	if ( empty( trim( $pass1 ) ) ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'empty_password',
			'message' => __( 'The password cannot be empty.' ),
		), 400 );
	}

	if ( strlen( $pass1 ) < 6 || strlen( $pass1 ) > 4096 ) {
		return new WP_REST_Response( array(
			'success' => false,
			'code'    => 'invalid_password',
			'message' => __( 'The password must be between 6 and 4096 characters.' ),
		), 400 );
	}

	// 验证密钥
	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) ) {
		$code = $user->get_error_code();
		$message = '';
		if ( 'expired_key' === $code ) {
			$message = __( 'Your password reset link has expired. Please request a new link.' );
		} else {
			$message = __( 'Invalid password reset link.' );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'code'    => $code,
			'message' => $message,
		), 400 );
	}

	// 使用 WordPress 原生 reset_password
	reset_password( $user, $pass1 );

	return new WP_REST_Response( array(
		'success' => true,
		'message' => __( 'Your password has been reset.' ) . ' ' . __( 'You can now log in with your new password.' ),
	), 200 );
}

// ============================================================
// 9. 获取当前用户信息
// ============================================================

function simple_theme_auth_me() {
	// REST cookie authentication normally waits for X-WP-Nonce before it
	// populates the current user. This endpoint is the bootstrap that provides
	// that nonce, so validate the existing login cookie explicitly first.
	if ( ! is_user_logged_in() ) {
		$user_id = wp_validate_auth_cookie( '', 'logged_in' );
		if ( $user_id ) {
			wp_set_current_user( $user_id );
		}
	}

	if ( ! is_user_logged_in() ) {
		$response = new WP_REST_Response( array(
			'logged_in' => false,
			'user'      => null,
		), 200 );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'Vary', 'Cookie' );
		return $response;
	}

	$user = wp_get_current_user();

	$response = new WP_REST_Response( array(
		'logged_in'     => true,
		'user'          => array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 80 ) ),
			'email'        => $user->user_email,
			'url'          => $user->user_url,
		),
		'rest_nonce'    => wp_create_nonce( 'wp_rest' ),
		'admin_url'     => admin_url(),
		'logout_url'    => wp_logout_url( home_url( '/' ) ),
	), 200 );
	$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
	$response->header( 'Vary', 'Cookie' );
	return $response;
}

// ============================================================
// 10. WordPress cookie 登录端点 — 让前台 AJAX 也能设置 cookie
// ============================================================

/**
 * 处理 password-protected post password 表单
 * wp-login.php 中的 postpass action 也需要拦截
 */
add_action( 'login_form_postpass', 'simple_theme_intercept_postpass' );
function simple_theme_intercept_postpass() {
	// 直接返回，让原生逻辑处理
	return;
}

/**
 * 当用户访问 wp-login.php?action=register 但注册关闭时，
 * 我们不拦截 POST（由 WP 处理），只拦截 GET 显示。
 * 但是我们已经在 login_init 中拦截了所有 GET，
 * 所以注册关闭的情况会被重定向到首页，附带 registration=disabled 参数。
 */

// 控制 WordPress Admin Bar 显示（主题设置中可关闭）
add_filter( 'show_admin_bar', function ( $show ) {
	if ( is_user_logged_in() && ! is_admin() ) {
		$options = get_option( 'simple_theme_options', array() );
		if ( ! empty( $options['hide_admin_bar'] ) ) {
			return false;
		}
	}
	return $show;
} );
