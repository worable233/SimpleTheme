<?php
/**
 * Local Avatars — user-uploaded custom avatars instead of Gravatar
 *
 * @package SimpleTheme
 */

defined( 'ABSPATH' ) || exit;

class Simple_Theme_Local_Avatars {

	private $user_key = 'simple_theme_local_avatar';

	public function __construct() {
		$options = get_option( 'simple_theme_options', array() );
		if ( empty( $options['local_avatars_enabled'] ) ) {
			return;
		}

		add_filter( 'pre_get_avatar_data', array( $this, 'get_avatar_data' ), 10, 2 );
		add_action( 'show_user_profile', array( $this, 'edit_user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ) );
		add_action( 'personal_options_update', array( $this, 'edit_user_profile_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ) );
		add_action( 'user_edit_form_tag', array( $this, 'user_edit_form_tag' ) );
		add_action( 'admin_action_remove-simple-theme-avatar', array( $this, 'action_remove_avatar' ) );
		add_action( 'wp_ajax_assign_simple_theme_avatar', array( $this, 'ajax_assign_avatar' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	// ========== Assets ==========

	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
			return;
		}

		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_media();
		}

		$user_id = ( 'profile.php' === $hook )
			? get_current_user_id()
			: (int) filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT );

		wp_register_script( 'simple-theme-local-avatars', false, array( 'jquery' ), '', true );
		wp_enqueue_script( 'simple-theme-local-avatars' );
		wp_add_inline_script( 'simple-theme-local-avatars', $this->inline_script( $user_id ) );
	}

	private function inline_script( $user_id ) {
		$ajaxurl   = wp_json_encode( admin_url( 'admin-ajax.php' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$nonce     = wp_json_encode( wp_create_nonce( 'assign_simple_theme_avatar_nonce' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$del_nonce = wp_json_encode( wp_create_nonce( 'remove_simple_theme_avatar_nonce' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$user_id   = (int) $user_id;

		return <<<JS
(function($) {
	var \$photo  = $('#simple-theme-avatar-photo'),
		\$remove = $('#simple-theme-avatar-remove'),
		\$spin   = $('#simple-theme-avatar-spinner'),
		\$form   = \$photo.closest('form'),
		busy    = false;

	function lock() {
		busy = true;
		\$form.find('input[type=submit]').prop('disabled', true);
		\$spin.show();
	}

	function unlock() {
		busy = false;
		\$form.find('input[type=submit]').prop('disabled', false);
		\$spin.hide();
	}

	$('#simple-theme-avatar-media').on('click', function(e) {
		e.preventDefault();
		if (busy) return;
		var frame = wp.media({ title: '选择头像', multiple: false, library: { type: 'image' } });
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			lock();
		$.post({$ajaxurl}, {
			action: 'assign_simple_theme_avatar',
			user_id: {$user_id},
			media_id: att.id,
			_wpnonce: {$nonce}
			}).done(function(html) {
				if (html) { \$photo.html(html); \$remove.show(); }
			}).always(function() { unlock(); });
		});
		frame.open();
	});

	\$remove.on('click', function(e) {
		e.preventDefault();
		if (busy) return;
		lock();
		$.get({$ajaxurl}, {
			action: 'remove_simple_theme_avatar',
			user_id: {$user_id},
			_wpnonce: {$del_nonce}
		}).done(function(html) {
			if (html) { \$photo.html(html); \$remove.hide(); }
		}).always(function() { unlock(); });
	});

	$('#simple-theme-avatar-file').on('change', function() {
		var f = this.files[0];
		if (!f) return;
		\$photo.find('img').attr('src', URL.createObjectURL(f)).attr('srcset', '');
	});
})(jQuery);
JS;
	}

	// ========== Avatar Data ==========

	public function get_avatar_data( $args, $id_or_email ) {
		if ( ! empty( $args['force_default'] ) ) {
			return $args;
		}

		$url = $this->get_local_avatar_url( $id_or_email, $args['size'] );
		if ( $url ) {
			$args['url'] = $url;
			$args['found_avatar'] = true;
		}

		return $args;
	}

	public function get_local_avatar_url( $id_or_email, $size ) {
		$user_id = $this->get_user_id( $id_or_email );
		if ( ! $user_id ) {
			return '';
		}

		$local_avatars = (array) get_user_meta( $user_id, $this->user_key, true );
		if ( empty( $local_avatars['full'] ) ) {
			return '';
		}

		$size = max( 16, min( 512, (int) $size ) );

		// Return cached size if already generated.
		if ( ! empty( $local_avatars[ $size ] ) ) {
			return $local_avatars[ $size ];
		}

		// Locate the source file.
		$upload_path = wp_upload_dir();
		$avatar_full_path = '';

		if ( ! empty( $local_avatars['media_id'] ) ) {
			$avatar_full_path = get_attached_file( $local_avatars['media_id'] );
			if ( ! $avatar_full_path ) {
				return '';
			}
		} else {
			$avatar_full_path = str_replace(
				$upload_path['baseurl'],
				$upload_path['basedir'],
				$local_avatars['full']
			);
		}

		$upload_base = realpath( $upload_path['basedir'] );
		$source_path = realpath( $avatar_full_path );
		if ( ! $upload_base || ! $source_path || 0 !== strpos( wp_normalize_path( $source_path ), trailingslashit( wp_normalize_path( $upload_base ) ) ) ) {
			return '';
		}
		$avatar_full_path = $source_path;

		// Dynamically generate the requested size.
		$editor = wp_get_image_editor( $avatar_full_path );
		if ( is_wp_error( $editor ) ) {
			return $local_avatars['full'];
		}

		$resized = $editor->resize( $size, $size, true );
		if ( is_wp_error( $resized ) ) {
			return $local_avatars['full'];
		}

		$dest_file = $editor->generate_filename();
		$normalized_dest = wp_normalize_path( $dest_file );
		$normalized_base = trailingslashit( wp_normalize_path( $upload_base ) );
		if ( 0 !== strpos( $normalized_dest, $normalized_base ) ) {
			return $local_avatars['full'];
		}
		$saved     = $editor->save( $dest_file );
		if ( is_wp_error( $saved ) ) {
			return $local_avatars['full'];
		}

		$dest_url = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $dest_file );
		$local_avatars[ $size ] = $dest_url;
		update_user_meta( $user_id, $this->user_key, $local_avatars );

		return $dest_url;
	}

	private function get_user_id( $id_or_email ) {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}

		if ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				return (int) $id_or_email->user_id;
			}
			if ( $id_or_email instanceof WP_Post && ! empty( $id_or_email->post_author ) ) {
				return (int) $id_or_email->post_author;
			}
			if ( $id_or_email instanceof WP_User && ! empty( $id_or_email->ID ) ) {
				return $id_or_email->ID;
			}
			if ( $id_or_email instanceof WP_Comment && ! empty( $id_or_email->comment_author_email ) ) {
				$user = get_user_by( 'email', $id_or_email->comment_author_email );
				return $user ? $user->ID : 0;
			}
		}

		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? $user->ID : 0;
		}

		return 0;
	}

	// ========== Profile UI ==========

	public function edit_user_profile( $profileuser ) {
		$has_avatar = ! empty( get_user_meta( $profileuser->ID, $this->user_key, true )['full'] );
		?>
		<div id="simple-theme-avatar-section">
			<h3><?php esc_html_e( '本地头像', 'simple-theme' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( '头像', 'simple-theme' ); ?></th>
					<td id="simple-theme-avatar-photo">
						<?php echo get_avatar( $profileuser->ID, 96 ); ?>
					</td>
					<td>
						<?php wp_nonce_field( 'simple_theme_avatar_nonce', '_simple_theme_avatar_nonce', false ); ?>

						<?php if ( current_user_can( 'upload_files' ) ) : ?>
							<p style="margin-bottom:8px;">
								<a href="#" class="button" id="simple-theme-avatar-media">
									<?php esc_html_e( '从媒体库中选择', 'simple-theme' ); ?>
								</a>
								<a href="<?php echo esc_url( add_query_arg( array(
									'action'   => 'remove-simple-theme-avatar',
									'user_id'  => $profileuser->ID,
									'_wpnonce' => wp_create_nonce( 'remove_simple_theme_avatar_nonce' ),
								) ) ); ?>" class="button" id="simple-theme-avatar-remove"
								<?php echo $has_avatar ? '' : ' style="display:none;"'; ?>>
									<?php esc_html_e( '删除本地头像', 'simple-theme' ); ?>
								</a>
								<span class="spinner" id="simple-theme-avatar-spinner" style="float:none;margin-top:-2px;"></span>
							</p>
						<?php else : ?>
							<p style="margin-bottom:8px;">
								<input type="file" name="simple-theme-avatar-file" id="simple-theme-avatar-file" accept="image/*" />
								<span class="spinner" id="simple-theme-avatar-spinner" style="float:none;margin-top:-2px;"></span>
							</p>
						<?php endif; ?>

						<p class="description"><?php esc_html_e( '上传自定义头像替代 Gravatar，推荐使用 1:1 正方形图片。', 'simple-theme' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	public function user_edit_form_tag() {
		echo ' enctype="multipart/form-data" ';
	}

	// ========== Profile Update ==========

	public function edit_user_profile_update( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( empty( $_POST['_simple_theme_avatar_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_simple_theme_avatar_nonce'] ) ), 'simple_theme_avatar_nonce' ) ) {
			return;
		}

		if ( empty( $_FILES['simple-theme-avatar-file']['name'] ) ) {
			return;
		}

		$file = $_FILES['simple-theme-avatar-file'];

		// Validate upload error code.
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return;
		}

		// Validate MIME type.
		$allowed = array( 'image/jpeg', 'image/gif', 'image/png', 'image/webp' );
		$detected = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		) );
		if ( ! $detected['type'] || ! in_array( $detected['type'], $allowed, true ) ) {
			return;
		}

		// Validate file size (capped at 2 MB).
		if ( $file['size'] > 2 * MB_IN_BYTES ) {
			return;
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$avatar_id = media_handle_upload(
			'simple-theme-avatar-file',
			0,
			array(),
			array(
				'mimes'     => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif'          => 'image/gif',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
				),
				'test_form' => false,
			)
		);

		if ( ! is_wp_error( $avatar_id ) ) {
			$this->assign_new_avatar( $avatar_id, $user_id );
		}
	}

	// ========== AJAX Actions ==========

	public function action_remove_avatar() {
		$user_id = (int) filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT );
		$nonce   = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( ! $user_id || ! $nonce || ! wp_verify_nonce( $nonce, 'remove_simple_theme_avatar_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( esc_html__( '权限不足。', 'simple-theme' ) );
		}

		$this->avatar_delete( $user_id );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			echo get_avatar( $user_id, 96 );
			die;
		}

		wp_safe_redirect( add_query_arg( 'updated', '1', wp_get_referer() ) );
		exit;
	}

	public function ajax_assign_avatar() {
		$user_id  = (int) filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT );
		$media_id = (int) filter_input( INPUT_POST, 'media_id', FILTER_SANITIZE_NUMBER_INT );
		$nonce    = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( ! $user_id || ! $media_id || ! $nonce ||
			! wp_verify_nonce( $nonce, 'assign_simple_theme_avatar_nonce' ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'edit_post', $media_id ) ) {
			wp_die();
		}

		if ( 'attachment' === get_post_type( $media_id ) && wp_attachment_is_image( $media_id ) ) {
			$this->assign_new_avatar( $media_id, $user_id );
		}

		echo get_avatar( $user_id, 96 );
		wp_die();
	}

	// ========== Avatar CRUD ==========

	public function assign_new_avatar( $url_or_media_id, $user_id ) {
		$this->avatar_delete( $user_id );

		$meta_value = array( 'blog_id' => get_current_blog_id() );

		if ( is_numeric( $url_or_media_id ) ) {
			$meta_value['media_id'] = (int) $url_or_media_id;
			$meta_value['full']     = wp_get_attachment_url( (int) $url_or_media_id );
		} else {
			$meta_value['full'] = $url_or_media_id;
		}

		update_user_meta( $user_id, $this->user_key, $meta_value );
	}

	public function avatar_delete( $user_id ) {
		$old = (array) get_user_meta( $user_id, $this->user_key, true );
		if ( empty( $old ) ) {
			return;
		}

		// If sourced from media library, only remove generated sizes, not the original attachment.
		if ( ! empty( $old['media_id'] ) ) {
			unset( $old['media_id'], $old['full'] );
		}

		if ( ! empty( $old ) ) {
			$upload_path = wp_upload_dir();
			$upload_base = realpath( $upload_path['basedir'] );
			foreach ( $old as $path ) {
				if ( is_string( $path ) && 0 === strpos( $path, $upload_path['baseurl'] ) ) {
					$file = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $path );
					$real_file = realpath( $file );
					if ( $upload_base && $real_file && 0 === strpos( wp_normalize_path( $real_file ), trailingslashit( wp_normalize_path( $upload_base ) ) ) ) {
						wp_delete_file( $real_file );
					}
				}
			}
		}

		delete_user_meta( $user_id, $this->user_key );
	}

	// ========== REST API ==========

	public function register_rest_fields() {
		register_rest_field( 'user', 'simple_theme_local_avatar', array(
			'get_callback'    => array( $this, 'rest_get_avatar' ),
			'schema'          => array(
				'description' => '本地头像 URL 和媒体 ID',
				'type'        => 'object',
				'properties'  => array(
					'full'     => array( 'type' => 'string' ),
					'media_id' => array( 'type' => 'integer' ),
				),
			),
		) );
	}

	public function rest_get_avatar( $user ) {
		return get_user_meta( $user['id'], $this->user_key, true ) ?: null;
	}
}

new Simple_Theme_Local_Avatars();
