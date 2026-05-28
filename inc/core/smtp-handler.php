<?php
/**
 * SMTP Mail Handler — configure PHPMailer via theme settings
 *
 * @package SimpleTheme
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// 1. Apply SMTP settings to PHPMailer
// ============================================================

add_action( 'phpmailer_init', 'simple_theme_smtp_phpmailer' );
function simple_theme_smtp_phpmailer( $phpmailer ) {
	$options = get_option( 'simple_theme_options', array() );

	if ( empty( $options['smtp_enabled'] ) || empty( $options['smtp_host'] ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host = $options['smtp_host'];
	$phpmailer->Port = ! empty( $options['smtp_port'] ) ? min( 65535, max( 1, (int) $options['smtp_port'] ) ) : 587;
	$phpmailer->Timeout = ! empty( $options['smtp_timeout'] ) ? max( 1, min( 120, (int) $options['smtp_timeout'] ) ) : 30;

	// Encryption
	$encryption = ! empty( $options['smtp_encryption'] ) ? $options['smtp_encryption'] : 'tls';
	if ( 'ssl' === $encryption ) {
		$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
	} elseif ( 'tls' === $encryption ) {
		$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
	} else {
		$phpmailer->SMTPSecure = '';
		$phpmailer->SMTPAutoTLS = false;
	}

	// Windows PHP 缺失 CA 证书包时跳过 SSL 验证（仅开发环境使用）
	if ( defined( 'SIMPLE_THEME_SMTP_DEBUG_SSL' ) && SIMPLE_THEME_SMTP_DEBUG_SSL ) {
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);
	}

	// Authentication
	if ( ! empty( $options['smtp_auth'] ) ) {
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = ! empty( $options['smtp_username'] ) ? $options['smtp_username'] : '';
		$password = simple_theme_decrypt_smtp_password( $options['smtp_password'] ?? '' );
		if ( '' !== $password ) {
			$phpmailer->Password = $password;
		}
	}

	// Custom From address
	if ( ! empty( $options['smtp_from_email'] ) ) {
		$phpmailer->From = $options['smtp_from_email'];
		if ( ! empty( $options['smtp_from_name'] ) ) {
			$phpmailer->FromName = $options['smtp_from_name'];
		}
	}
}

// ============================================================
// 2. SMTP password encryption / decryption
// ============================================================

function simple_theme_encrypt_smtp_password( $plain ) {
	if ( '' === $plain ) {
		return '';
	}
	$key       = wp_salt( 'auth' );
	$iv        = openssl_random_pseudo_bytes( 16 );
	$encrypted = openssl_encrypt( $plain, 'aes-256-cbc', $key, 0, $iv );
	if ( false === $encrypted ) {
		return '';
	}
	return base64_encode( $iv . $encrypted );
}

function simple_theme_decrypt_smtp_password( $encrypted ) {
	if ( '' === $encrypted ) {
		return '';
	}
	$key  = wp_salt( 'auth' );
	$data = base64_decode( $encrypted );
	if ( strlen( $data ) < 16 ) {
		return '';
	}
	$iv        = substr( $data, 0, 16 );
	$ciphertext = substr( $data, 16 );
	$decrypted = openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, 0, $iv );
	return false === $decrypted ? '' : $decrypted;
}

// ============================================================
// 3. Hook into settings save — encrypt password before storage
// ============================================================

add_filter( 'simple_theme_pre_save_settings', 'simple_theme_smtp_pre_save', 10, 2 );
function simple_theme_smtp_pre_save( $new_options, $existing ) {
	if ( ! isset( $new_options['smtp_password'] ) ) {
		return $new_options;
	}

	$submitted = $new_options['smtp_password'];

	// If the password is the masked sentinel, keep the existing one
	if ( '********' === $submitted ) {
		unset( $new_options['smtp_password'] );
		return $new_options;
	}

	// Empty string means clear the password
	if ( '' === $submitted ) {
		$new_options['smtp_password'] = '';
		return $new_options;
	}

	// Encrypt the new password
	$encrypted = simple_theme_encrypt_smtp_password( $submitted );
	if ( '' !== $encrypted ) {
		$new_options['smtp_password'] = $encrypted;
	}

	return $new_options;
}

// ============================================================
// 4. Override the settings read — mask password in API response
// ============================================================

add_filter( 'simple_theme_after_get_settings', 'simple_theme_smtp_after_get' );
function simple_theme_smtp_after_get( $options ) {
	if ( isset( $options['smtp_password'] ) && '' !== $options['smtp_password'] ) {
		$options['smtp_password'] = '********';
	}
	return $options;
}

// ============================================================
// 5. REST endpoint — send test email
// ============================================================

add_action( 'rest_api_init', 'simple_theme_register_smtp_test_route' );
function simple_theme_register_smtp_test_route() {
	register_rest_route( 'simple-theme/v1', '/smtp-test', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'simple_theme_smtp_test',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'to' => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => function ( $value ) {
					return is_email( $value );
				},
			),
		),
	) );
}

function simple_theme_smtp_test( WP_REST_Request $request ) {
	$to      = $request->get_param( 'to' );
	$subject = 'SMTP Test — ' . get_bloginfo( 'name' );
	$message = 'This is a test email sent from your WordPress site via the Simple Theme SMTP configuration.';
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$options = get_option( 'simple_theme_options', array() );

	// Test emails bypass the queue — send immediately
	if ( ! defined( 'SIMPLE_THEME_MAIL_QUEUE_PROCESSING' ) ) {
		define( 'SIMPLE_THEME_MAIL_QUEUE_PROCESSING', true );
	}
	$sent = wp_mail( $to, $subject, $message, $headers );

	// Record test email in queue history
	simple_theme_insert_mail_queue_record( array(
		'to'      => $to,
		'subject' => $subject,
		'message' => $message,
		'headers' => $headers,
	), $sent ? 'sent' : 'failed' );

	if ( $sent ) {
		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Test email sent. Please check your inbox.' ),
		), 200 );
	}

	// Capture PHPMailer error info
	global $phpmailer;
	$debug_info = '';
	if ( isset( $phpmailer ) ) {
		$debug_info = $phpmailer->ErrorInfo;
		if ( empty( $debug_info ) && method_exists( $phpmailer, 'getSMTPInstance' ) ) {
			$smtp = $phpmailer->getSMTPInstance();
			if ( $smtp && method_exists( $smtp, 'getError' ) ) {
				$error = $smtp->getError();
				$debug_info = ! empty( $error['error'] ) ? $error['error'] : '';
			}
		}
	}

	// Fix garbled Chinese characters from SMTP server responses (GBK/GB2312 → UTF-8)
	if ( '' !== $debug_info && function_exists( 'mb_detect_encoding' ) ) {
		$detected = mb_detect_encoding( $debug_info, array( 'UTF-8', 'GBK', 'GB2312', 'ISO-8859-1', 'Windows-1252' ), true );
		if ( $detected && 'UTF-8' !== $detected ) {
			$converted = mb_convert_encoding( $debug_info, 'UTF-8', $detected );
			if ( false !== $converted ) {
				$debug_info = $converted;
			}
		}
	}

	// Detect connection timeout
	$timeout_hint = '';
	if (
		false !== stripos( $debug_info, '10060' ) ||
		false !== stripos( $debug_info, 'timed out' ) ||
		false !== stripos( $debug_info, 'timeout' ) ||
		false !== stripos( $debug_info, '连接超时' ) ||
		false !== stripos( $debug_info, '超时' )
	) {
		$timeout_hint = '连接超时：服务器无法连接到 SMTP 主机 ' . $options['smtp_host'] . ':' . $options['smtp_port'] . '。请检查：1) 服务器防火墙是否阻止了出站 SMTP 连接；2) 云厂商是否封锁了 SMTP 端口（常见于阿里云/腾讯云/AWS）；3) SMTP 主机地址是否正确；4) 可尝试增加连接超时时间。';
	}

	// Detect SSL CA certificate issue (common on Windows PHP)
	$ssl_ca_hint = '';
	if ( false !== stripos( $debug_info, 'ssl' ) && ! $timeout_hint ) {
		$ssl_ca_hint = '当前服务器（Windows）缺少 CA 证书包，导致 SSL 握手失败。请在 wp-config.php 中添加 define(\'SIMPLE_THEME_SMTP_DEBUG_SSL\', true); 以临时跳过 SSL 验证（仅开发/测试环境使用，生产环境请配置 CA 证书）。';
	}

	return new WP_REST_Response( array(
		'success'      => false,
		'message'      => __( 'Failed to send test email.' ),
		'debug'        => $debug_info,
		'ssl_ca_hint'  => $ssl_ca_hint,
		'timeout_hint' => $timeout_hint,
	), 500 );
}


// ============================================================
// 6. Mail Queue — table, override, cron, REST
// ============================================================

/**
 * Ensure the mail queue database table exists.
 */
function simple_theme_ensure_mail_queue_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'simple_theme_mail_queue';
	$version_option = 'simple_theme_mail_queue_db_version';
	$current_version = (int) get_option( $version_option, 0 );

	if ( $current_version >= 1 ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		to_email VARCHAR(255) NOT NULL,
		subject TEXT NOT NULL,
		message LONGTEXT NOT NULL,
		headers TEXT,
		attachments TEXT,
		retry_count INT UNSIGNED DEFAULT 0,
		max_retries INT UNSIGNED DEFAULT 3,
		retry_interval INT UNSIGNED DEFAULT 300,
		status VARCHAR(20) DEFAULT 'pending',
		error_message TEXT,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		next_retry_at DATETIME DEFAULT NULL,
		INDEX idx_status (status),
		INDEX idx_next_retry (next_retry_at)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( $version_option, 1, false );
}

add_action( 'admin_init', 'simple_theme_ensure_mail_queue_table' );

// ============================================================
// 7. Intercept wp_mail — queue instead of sending directly
// ============================================================

add_filter( 'pre_wp_mail', 'simple_theme_maybe_queue_mail', 1, 2 );
function simple_theme_maybe_queue_mail( $return, $atts ) {
	// Bypass when the cron processor is calling wp_mail
	if ( defined( 'SIMPLE_THEME_MAIL_QUEUE_PROCESSING' ) && SIMPLE_THEME_MAIL_QUEUE_PROCESSING ) {
		return $return;
	}

	$options = get_option( 'simple_theme_options', array() );
	if ( empty( $options['smtp_enabled'] ) || empty( $options['smtp_queue_enabled'] ) ) {
		return $return;
	}

	simple_theme_insert_mail_queue( $atts );
	return true;
}

function simple_theme_insert_mail_queue( $atts ) {
	global $wpdb;

	$defaults = array(
		'to'          => '',
		'subject'     => '',
		'message'     => '',
		'headers'     => '',
		'attachments' => array(),
	);
	$atts = wp_parse_args( $atts, $defaults );

	$options = get_option( 'simple_theme_options', array() );
	$max_retries    = ! empty( $options['smtp_queue_retry_count'] ) ? min( 20, max( 0, (int) $options['smtp_queue_retry_count'] ) ) : 3;
	$retry_interval = ! empty( $options['smtp_queue_retry_interval'] ) ? min( 3600, max( 60, (int) $options['smtp_queue_retry_interval'] ) ) : 300;

	$wpdb->insert(
		$wpdb->prefix . 'simple_theme_mail_queue',
		array(
			'to_email'      => is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'],
			'subject'       => $atts['subject'],
			'message'       => $atts['message'],
			'headers'       => is_array( $atts['headers'] ) ? maybe_serialize( $atts['headers'] ) : $atts['headers'],
			'attachments'   => is_array( $atts['attachments'] ) ? maybe_serialize( $atts['attachments'] ) : $atts['attachments'],
			'max_retries'   => $max_retries,
			'retry_interval' => $retry_interval,
			'next_retry_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
	);
}

/**
 * Insert a mail record directly with a given status (for test emails).
 */
function simple_theme_insert_mail_queue_record( $atts, $status = 'sent' ) {
	global $wpdb;

	$defaults = array(
		'to'          => '',
		'subject'     => '',
		'message'     => '',
		'headers'     => '',
		'attachments' => array(),
	);
	$atts = wp_parse_args( $atts, $defaults );

	$options       = get_option( 'simple_theme_options', array() );
	$max_retries    = ! empty( $options['smtp_queue_retry_count'] ) ? min( 20, max( 0, (int) $options['smtp_queue_retry_count'] ) ) : 3;
	$retry_interval = ! empty( $options['smtp_queue_retry_interval'] ) ? min( 3600, max( 60, (int) $options['smtp_queue_retry_interval'] ) ) : 300;

	$wpdb->insert(
		$wpdb->prefix . 'simple_theme_mail_queue',
		array(
			'to_email'       => is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'],
			'subject'        => $atts['subject'],
			'message'        => $atts['message'],
			'headers'        => is_array( $atts['headers'] ) ? maybe_serialize( $atts['headers'] ) : $atts['headers'],
			'attachments'    => is_array( $atts['attachments'] ) ? maybe_serialize( $atts['attachments'] ) : $atts['attachments'],
			'max_retries'    => $max_retries,
			'retry_interval' => $retry_interval,
			'status'         => $status,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
	);
}

// ============================================================
// 8. Queue helpers
// ============================================================

function simple_theme_get_pending_mails( $limit = 5 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'simple_theme_mail_queue';
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE status = 'pending'
			  AND next_retry_at <= %s
			ORDER BY created_at ASC
			LIMIT %d",
			current_time( 'mysql' ),
			(int) $limit
		)
	);
}

function simple_theme_update_mail_status( $id, $status, $retry_count = null, $error_message = '' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'simple_theme_mail_queue';

	$data = array( 'status' => $status );
	$types = array( '%s' );

	if ( null !== $retry_count ) {
		$data['retry_count'] = (int) $retry_count;
		$types[] = '%d';
	}

	if ( '' !== $error_message ) {
		$data['error_message'] = $error_message;
		$types[] = '%s';
	}

	$wpdb->update( $table, $data, array( 'id' => (int) $id ), $types, array( '%d' ) );
}

function simple_theme_get_queue_stats() {
	global $wpdb;
	$table = $wpdb->prefix . 'simple_theme_mail_queue';
	$results = $wpdb->get_results(
		"SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status",
		OBJECT_K
	);

	return array(
		'pending'    => isset( $results['pending'] ) ? (int) $results['pending']->cnt : 0,
		'processing' => isset( $results['processing'] ) ? (int) $results['processing']->cnt : 0,
		'sent'       => isset( $results['sent'] ) ? (int) $results['sent']->cnt : 0,
		'failed'     => isset( $results['failed'] ) ? (int) $results['failed']->cnt : 0,
	);
}

// ============================================================
// 9. Cron — process queue every minute
// ============================================================

add_action( 'init', 'simple_theme_schedule_mail_queue_cron' );
function simple_theme_schedule_mail_queue_cron() {
	if ( ! wp_next_scheduled( 'simple_theme_process_mail_queue' ) ) {
		wp_schedule_event( time(), 'every_minute', 'simple_theme_process_mail_queue' );
	}
}

// Register custom cron interval
add_filter( 'cron_schedules', 'simple_theme_mail_queue_cron_interval' );
function simple_theme_mail_queue_cron_interval( $schedules ) {
	$schedules['every_minute'] = array(
		'interval' => 60,
		'display'  => __( 'Every Minute' ),
	);
	return $schedules;
}

add_action( 'simple_theme_process_mail_queue', 'simple_theme_process_mail_queue' );
function simple_theme_process_mail_queue() {
	if ( defined( 'SIMPLE_THEME_MAIL_QUEUE_PROCESSING' ) && SIMPLE_THEME_MAIL_QUEUE_PROCESSING ) {
		return;
	}

	// Prevent concurrent cron runs
	if ( get_transient( 'simple_theme_mail_queue_locked' ) ) {
		return;
	}
	set_transient( 'simple_theme_mail_queue_locked', 1, 120 );

	define( 'SIMPLE_THEME_MAIL_QUEUE_PROCESSING', true );

	// Recover mails stuck in 'processing' from a previous crashed run
	global $wpdb;
	$queue_table = $wpdb->prefix . 'simple_theme_mail_queue';
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$queue_table} SET status = 'pending', next_retry_at = %s WHERE status = 'processing'",
			current_time( 'mysql' )
		)
	);

	$options = get_option( 'simple_theme_options', array() );
	if ( empty( $options['smtp_enabled'] ) || empty( $options['smtp_queue_enabled'] ) ) {
		return;
	}

	// Process up to 20 emails per cron run
	$max_total = 20;
	$processed = 0;

	while ( $processed < $max_total ) {
		$mails = simple_theme_get_pending_mails( 5 );
		if ( empty( $mails ) ) {
			break;
		}

		foreach ( $mails as $mail ) {
			if ( $processed >= $max_total ) {
				break 2;
			}

			simple_theme_update_mail_status( $mail->id, 'processing' );

			$headers = $mail->headers ? maybe_unserialize( $mail->headers ) : array();
			$attachments = $mail->attachments ? maybe_unserialize( $mail->attachments ) : array();

			$sent = wp_mail( $mail->to_email, $mail->subject, $mail->message, $headers, $attachments );

			if ( $sent ) {
				simple_theme_update_mail_status( $mail->id, 'sent' );
			} else {
				$new_retry = (int) $mail->retry_count + 1;
				if ( $new_retry >= (int) $mail->max_retries ) {
					simple_theme_update_mail_status( $mail->id, 'failed', $new_retry, __( 'Max retries reached.' ) );
				} else {
					$next = time() + (int) $mail->retry_interval;
					$wpdb->update(
						$queue_table,
						array(
							'status'        => 'pending',
							'retry_count'   => $new_retry,
							'next_retry_at' => wp_date( 'Y-m-d H:i:s', $next ),
						),
						array( 'id' => (int) $mail->id ),
						array( '%s', '%d', '%s' ),
						array( '%d' )
					);
				}
			}

			$processed++;
		}
	}
}

// Cleanup cron on theme switch
add_action( 'switch_theme', 'simple_theme_clear_mail_queue_cron' );
function simple_theme_clear_mail_queue_cron() {
	$timestamp = wp_next_scheduled( 'simple_theme_process_mail_queue' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'simple_theme_process_mail_queue' );
	}
}

// ============================================================
// 10. REST — queue status & management
// ============================================================

add_action( 'rest_api_init', 'simple_theme_register_mail_queue_routes' );
function simple_theme_register_mail_queue_routes() {
	register_rest_route( 'simple-theme/v1', '/mail-queue', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'simple_theme_mail_queue_list',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	register_rest_route( 'simple-theme/v1', '/mail-queue/retry/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::EDITABLE,
		'callback'            => 'simple_theme_mail_queue_retry',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'id' => array(
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => function ( $value ) {
					return is_numeric( $value ) && (int) $value > 0;
				},
			),
		),
	) );

	register_rest_route( 'simple-theme/v1', '/mail-queue/clear', array(
		'methods'             => WP_REST_Server::EDITABLE,
		'callback'            => 'simple_theme_mail_queue_clear',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );
}

function simple_theme_mail_queue_list() {
	global $wpdb;
	$table = $wpdb->prefix . 'simple_theme_mail_queue';

	$stats = simple_theme_get_queue_stats();

	$items = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, to_email, subject, retry_count, max_retries, status, error_message, created_at
			FROM {$table}
			ORDER BY created_at DESC
			LIMIT %d",
			50
		)
	);

	return new WP_REST_Response( array(
		'stats' => $stats,
		'items' => $items,
	), 200 );
}

function simple_theme_mail_queue_retry( WP_REST_Request $request ) {
	global $wpdb;
	$id    = (int) $request->get_param( 'id' );
	$table = $wpdb->prefix . 'simple_theme_mail_queue';

	$mail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	if ( ! $mail ) {
		return new WP_REST_Response( array( 'error' => 'Mail not found' ), 404 );
	}

	$wpdb->update(
		$table,
		array(
			'status'        => 'pending',
			'retry_count'   => 0,
			'error_message' => '',
			'next_retry_at' => current_time( 'mysql' ),
		),
		array( 'id' => $id ),
		array( '%s', '%d', '%s', '%s' ),
		array( '%d' )
	);

	return new WP_REST_Response( array( 'success' => true ), 200 );
}

function simple_theme_mail_queue_clear( WP_REST_Request $request ) {
	global $wpdb;
	$table = $wpdb->prefix . 'simple_theme_mail_queue';

	// Only clear sent + failed
	$wpdb->query( "DELETE FROM {$table} WHERE status IN ('sent', 'failed')" );

	$stats = simple_theme_get_queue_stats();
	return new WP_REST_Response( array(
		'success' => true,
		'stats'   => $stats,
	), 200 );
}
