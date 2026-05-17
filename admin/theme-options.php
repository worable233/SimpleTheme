<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple Theme Admin Settings Page
 *
 * Uses WordPress Options API for storage.
 */

// ========== Option Read/Write Helpers ==========

/**
 * Get Simple Theme option value
 */
function simple_theme_option( $key = '', $default = null ) {
	$cache = get_option( 'simple_theme_options', array() );

	if ( '' === $key ) {
		return $cache;
	}

	return isset( $cache[ $key ] ) ? $cache[ $key ] : $default;
}

/**
 * Update Simple Theme option value
 */
function simple_theme_update_option( $key, $value ) {
	$options = get_option( 'simple_theme_options', array() );
	$options[ $key ] = $value;
	update_option( 'simple_theme_options', $options, false );
}

/**
 * Batch update Simple Theme options
 */
function simple_theme_update_options( $data ) {
	$options = get_option( 'simple_theme_options', array() );
	$options = array_merge( $options, $data );
	update_option( 'simple_theme_options', $options, false );
}

// ========== Default Values ==========

function simple_theme_get_default_options() {
	return array(
		// Home intro text
						// Appearance
		'primary_color'            => '#333333',
		'body_font'                => '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
		'heading_font'             => '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
		'radius'                   => 'medium',
		'shadow'                   => 'small',
		'background_light'         => '#f5f6f7',
		'background_dark'          => '#1a1a1a',
		'card_light'               => '#ffffff',
		'card_dark'                => '#222222',
		'foreground_light'         => '#333333',
		'foreground_dark'          => '#e0e0e0',
		'accent_light'             => '#f5f5f5',
		'accent_dark'              => '#2a2a2a',
		'border_light'             => '#e2e2e2',
		'border_dark'              => '#333333',
		'container_max_width'      => 1400,
		'article_max_width'        => 900,
	'hero_use_image'           => false,
		'hero_image'               => '',
'hero_show_avatar'         => false,
		'hero_avatar'              => '',
														// Posts and shuoshuo
								'shuoshuo_subtitle'        => '',
		'show_shuoshuo_section'    => true,
		'home_post_count'          => 6,
						// Card meta
		'meta_show_category'       => true,
		'meta_show_publish_date'   => true,
		'meta_show_modified_date'  => false,
		'meta_show_comment_count'  => true,
		'meta_show_view_count'     => true,
		'meta_show_reading_time'   => true,
		'meta_show_word_count'     => false,
		// Footer
		// Comments
		'comment_show_email'       => true,
		'comment_show_url'         => true,
		'comment_show_cookies'     => true,
// About page
		'about_avatar'             => '',
		'about_subtitle_lines'     => '',
		'about_identity_tags'      => '',
		'about_greeting'           => '',
		'about_slogan_block'       => '',
		'about_skills'             => '',
		'about_timeline'           => '',
		'about_mbti_type'          => '',
		'about_mbti_label'         => '',
		'about_mbti_image'         => '',
		'about_mbti_url'           => '',
		'about_games'              => '',
		'about_anime_title'        => '',
		'about_anime_tagline'      => '',
		'about_music_artists'      => '',
		'about_music_url'          => '',
		'about_location'           => '',
		'about_birth_year'         => 0,
		'about_education'          => '',
		'about_occupation'         => '',
		'about_sponsor_total'      => '',
		'about_sponsor_list'       => '',
		'about_sponsor_url'        => '',
		'about_donation_wechat_qr' => '',
		'about_donation_alipay_qr' => '',
		'about_donation_total'     => '',
// Recommended Posts
// User Menu
				// ICP
		'icp_text'                 => '',
		'icp_gov_text'             => '',
'social_links'             => '',
'end_note'                 => '好像就这么多',

	);
}

// ========== Migrate from theme_mod ==========

function simple_theme_migrate_from_customizer() {
	$options = get_option( 'simple_theme_options', array() );

	if ( ! empty( $options ) ) {
		return;
	}

	$migrate_map = array(
		'intro_title'              => 'simple_theme_intro_title',
		'intro_subtitle'           => 'simple_theme_intro_subtitle',
		'home_post_columns'        => 'simple_theme_home_post_columns',
		'primary_color'            => 'simple_theme_primary_color',
		'body_font'                => 'simple_theme_body_font',
		'heading_font'             => 'simple_theme_heading_font',
		'radius'                   => 'simple_theme_radius',
		'shadow'                   => 'simple_theme_shadow',
		'background_light'         => 'simple_theme_background_light',
		'background_dark'          => 'simple_theme_background_dark',
		'card_light'               => 'simple_theme_card_light',
		'card_dark'                => 'simple_theme_card_dark',
		'foreground_light'         => 'simple_theme_foreground_light',
		'foreground_dark'          => 'simple_theme_foreground_dark',
		'accent_light'             => 'simple_theme_accent_light',
		'accent_dark'              => 'simple_theme_accent_dark',
		'border_light'             => 'simple_theme_border_light',
		'border_dark'              => 'simple_theme_border_dark',
		'container_max_width'      => 'simple_theme_container_max_width',
		'article_max_width'        => 'simple_theme_article_max_width',
		'hero_enabled'             => 'simple_theme_hero_enabled',
		'hero_use_image'           => 'simple_theme_hero_use_image',
		'hero_image'               => 'simple_theme_hero_image',
		'hero_show_avatar'         => 'simple_theme_hero_show_avatar',
		'hero_avatar'              => 'simple_theme_hero_avatar',
		'hero_title'               => 'simple_theme_hero_title',
		'hero_subtitle'            => 'simple_theme_hero_subtitle',
		'hero_typewriter_enabled'  => 'simple_theme_hero_typewriter_enabled',
		'hero_typewriter_interval' => 'simple_theme_hero_typewriter_interval',
		'hero_typewriter_texts'    => 'simple_theme_hero_typewriter_texts',
		'posts_title'              => 'simple_theme_posts_title',
		'posts_subtitle'           => 'simple_theme_posts_subtitle',
		'shuoshuo_title'           => 'simple_theme_shuoshuo_title',
		'show_shuoshuo_section'    => 'simple_theme_show_shuoshuo_section',
		'home_post_count'          => 'simple_theme_home_post_count',
		'home_shuoshuo_count'      => 'simple_theme_home_shuoshuo_count',
		'shuoshuo_page_size'       => 'simple_theme_shuoshuo_page_size',
		'meta_show_category'       => 'simple_theme_meta_show_category',
		'meta_show_publish_date'   => 'simple_theme_meta_show_publish_date',
		'meta_show_modified_date'  => 'simple_theme_meta_show_modified_date',
		'meta_show_comment_count'  => 'simple_theme_meta_show_comment_count',
		'meta_show_view_count'     => 'simple_theme_meta_show_view_count',
		'meta_show_reading_time'   => 'simple_theme_meta_show_reading_time',
		'meta_show_word_count'     => 'simple_theme_meta_show_word_count',
		'comment_show_email'       => 'simple_theme_comment_show_email',
		'comment_show_url'         => 'simple_theme_comment_show_url',
		'comment_show_cookies'     => 'simple_theme_comment_show_cookies_optin',
		'enable_boxicons'          => 'simple_theme_enable_boxicons',
	);

	$defaults = simple_theme_get_default_options();
	$migrated = array();

	foreach ( $migrate_map as $new_key => $old_key ) {
	 $value = get_theme_mod( $old_key, null );
	 if ( null !== $value ) {
	  $migrated[ $new_key ] = $value;
	 } else {
	  $migrated[ $new_key ] = $defaults[ $new_key ] ?? '';
	 }
	}

	update_option( 'simple_theme_options', $migrated, false );
}

// ========== Register Admin Menu ==========

add_action( 'admin_menu', 'simple_theme_register_admin_menu' );

function simple_theme_register_admin_menu() {
	add_theme_page(
		__( 'Simple Theme 设置', 'simple-theme' ),
		__( 'Simple Theme', 'simple-theme' ),
		'manage_options',
		'simple-theme-options',
		'simple_theme_render_admin_page'
	);
}

// ========== Register Settings ==========

add_action( 'admin_init', 'simple_theme_register_settings' );

function simple_theme_register_settings() {
	register_setting(
		'simple_theme_options_group',
		'simple_theme_options',
		array(
			'sanitize_callback' => 'simple_theme_sanitize_options',
			'default'           => simple_theme_get_default_options(),
		)
	);
}

function simple_theme_sanitize_options( $input ) {
	if ( ! is_array( $input ) ) {
		return simple_theme_get_default_options();
	}

	$defaults = simple_theme_get_default_options();
	$output   = array();

	foreach ( $defaults as $key => $default_value ) {
		if ( ! isset( $input[ $key ] ) ) {
			$output[ $key ] = $default_value;
			continue;
		}

		$value = $input[ $key ];

		if ( is_bool( $default_value ) ) {
			$output[ $key ] = ! empty( $value );
		} elseif ( is_int( $default_value ) ) {
			$val = (int) $value;
			if ( 0 === $default_value && $key === 'about_birth_year' ) {
				$val = max( 1900, min( (int) date( 'Y' ), $val ) );
			}
			$output[ $key ] = $val;
		} elseif ( is_float( $default_value ) ) {
			$output[ $key ] = (float) $value;
		} elseif ( is_string( $default_value ) && '#' === substr( $default_value, 0, 1 ) ) {
			$sanitized = sanitize_hex_color( $value );
			$output[ $key ] = $sanitized ?: $default_value;
		} elseif ( 'home_post_columns' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( '1', '2', '4' ), true ) ? (string) $value : $default_value;
		} elseif ( 'hero_display_mode' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'full', 'half', 'inset' ), true ) ? (string) $value : $default_value;
		} elseif ( 'radius' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'small', 'medium', 'large' ), true ) ? (string) $value : $default_value;
		} elseif ( 'shadow' === $key ) {
			$output[ $key ] = in_array( (string) $value, array( 'none', 'small', 'medium', 'large' ), true ) ? (string) $value : $default_value;
		} elseif ( 'recommended_post_ids' === $key ) {
				$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
				$output[ $key ] = implode( ',', $ids );
		} else {
			$output[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	return $output;
}

// ========== Render Admin Page ==========

// Auto-migrate on theme activation
add_action( 'after_switch_theme', 'simple_theme_migrate_from_customizer' );

function simple_theme_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '你没有权限访问此页面。', 'simple-theme' ) );
	}

	// Auto-migrate old theme_mod data
	simple_theme_migrate_from_customizer();

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'home_intro';

	$tabs = array(
		'appearance'    => __( '外观样式', 'simple-theme' ),
		'card_meta'     => __( '卡片信息', 'simple-theme' ),
		'footer'        => __( '页脚设置', 'simple-theme' ),
		'hero'          => __( '封面区域', 'simple-theme' ),
		'advanced'      => __( '高级设置', 'simple-theme' ),
		'about'         => __( '关于页', 'simple-theme' ),
	);

	$valid_tabs = array_keys( $tabs );
	if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
		$active_tab = 'home_intro';
	}

	$options = get_option( 'simple_theme_options', simple_theme_get_default_options() );
	?>
	<div class="wrap simple-theme-admin">
		<h1><?php echo esc_html__( 'Simple Theme 设置', 'simple-theme' ); ?></h1>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
				<a
					href="?page=simple-theme-options&tab=<?php echo esc_attr( $tab_key ); ?>"
					class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
				>
					<?php echo esc_html( $tab_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="options.php" class="simple-theme-admin-form">
			<?php settings_fields( 'simple_theme_options_group' ); ?>

			<div class="simple-theme-admin-content">
				<?php
				switch ( $active_tab ) {
					case 'appearance':
						simple_theme_render_tab_appearance( $options );
						break;

					case 'card_meta':
						simple_theme_render_tab_card_meta( $options );
						break;

					case 'hero':
						simple_theme_render_tab_hero( $options );
						break;

					case 'footer':
						simple_theme_render_tab_footer( $options );
						break;

					case 'advanced':
						simple_theme_render_tab_advanced( $options );
						break;

					case 'about':
						simple_theme_render_tab_about( $options );
						break;
				}
				?>
			</div>

			<?php submit_button( __( '保存设置', 'simple-theme' ) ); ?>
		</form>
	</div>
	<?php
}

// ========== Tab: Home Intro ==========



function simple_theme_render_tab_advanced( $options ) {
	?>
	<h2><?php echo esc_html__( '高级设置', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '配置图标库和其他高级选项。', 'simple-theme' ); ?></p>

	<h3><?php echo esc_html__( '图标库', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="enable_boxicons"><?php echo esc_html__( '启用 Boxicons', 'simple-theme' ); ?></label>
			</th>
			<td>
				<label>
					<input type="hidden" name="simple_theme_options[enable_boxicons]" value="0" />
					<input
						type="checkbox"
						id="enable_boxicons"
						name="simple_theme_options[enable_boxicons]"
						value="1"
						<?php checked( ! empty( $options['enable_boxicons'] ) ); ?>
					/>
					<?php echo esc_html__( '在前端加载 Boxicons 图标库。', 'simple-theme' ); ?>
				</label>
				<p class="st-section-desc">
					<?php echo esc_html__( 'Boxicons 提供超过 1500 个免费矢量图标。', 'simple-theme' ); ?>
					<br/>
					<?php echo esc_html__( 'When enabled, you can use icon tags like <i class=\'bx bx-xxx\'></i> in your content.', 'simple-theme' ); ?>
					<br/>
					<a href="https://boxicons.com/" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( '浏览 Boxicons 图标库', 'simple-theme' ); ?>
					</a>
				</p>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '首页底部', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="end_note"><?php echo esc_html__( '底部寄语', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="end_note"
					name="simple_theme_options[end_note]"
					class="large-text"
					value="<?php echo esc_attr( $options['end_note'] ); ?>"
				/>
				<p class="st-section-desc">
					<?php echo esc_html__( '首页文章列表末尾显示的文字，支持 HTML 标签。', 'simple-theme' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '社交链接', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="social_links"><?php echo esc_html__( '社交链接（JSON）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<textarea
					id="social_links"
					name="simple_theme_options[social_links]"
					class="large-text code"
					rows="6"
				placeholder='[{"label":"GitHub","url":"https://github.com/username","icon":"bx bxl-github"}]'
				><?php echo esc_textarea( $options['social_links'] ); ?></textarea>
				<p class="st-section-desc">
					<?php echo esc_html__( '社交链接的 JSON 数组。每条格式：{ "label": "...", "url": "...", "icon": "图标 CSS class" }', 'simple-theme' ); ?>
					<br/>
					<?php echo esc_html__( '示例：bx bxl-github、bx bxl-telegram、bx bxl-bilibili（支持任意图标库）', 'simple-theme' ); ?>
				</p>
			</td>
		</tr>
	</table>
<?php
}

// ========== Tab: Appearance ==========

function simple_theme_render_tab_appearance( $options ) {
	?>
	<h2><?php echo esc_html__( '外观样式', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '调整列数、配色、字体、圆角、阴影等外观设置。', 'simple-theme' ); ?></p>

	<table class="form-table">
		
		<tr>
			<th scope="row">
				<label for="primary_color"><?php echo esc_html__( '主题主色', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="primary_color"
					name="simple_theme_options[primary_color]"
					value="<?php echo esc_attr( $options['primary_color'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#8a5a44"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="body_font"><?php echo esc_html__( '正文字体', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="body_font"
					name="simple_theme_options[body_font]"
					value="<?php echo esc_attr( $options['body_font'] ); ?>"
					class="regular-text"
				/>
				<p class="st-section-desc"><?php echo esc_html__( 'font-family 值，例如：Noto Sans SC, sans-serif', 'simple-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="heading_font"><?php echo esc_html__( '标题字体', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="heading_font"
					name="simple_theme_options[heading_font]"
					value="<?php echo esc_attr( $options['heading_font'] ); ?>"
					class="regular-text"
				/>
				<p class="st-section-desc"><?php echo esc_html__( '标题 font-family 值。', 'simple-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '圆角大小', 'simple-theme' ); ?></th>
			<td>
				<fieldset>
					<?php
					$radius_choices = array(
						'small'  => __( '小', 'simple-theme' ),
						'medium' => __( '中', 'simple-theme' ),
						'large'  => __( '大', 'simple-theme' ),
					);
					foreach ( $radius_choices as $value => $label ) :
						?>
						<label>
							<input
								type="radio"
								name="simple_theme_options[radius]"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( $options['radius'], $value ); ?>
							/>
							<?php echo esc_html( $label ); ?>
						</label><br />
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '阴影强度', 'simple-theme' ); ?></th>
			<td>
				<fieldset>
					<?php
					$shadow_choices = array(
						'none'   => __( '无', 'simple-theme' ),
						'small'  => __( '轻', 'simple-theme' ),
						'medium' => __( '中', 'simple-theme' ),
						'large'  => __( '重', 'simple-theme' ),
					);
					foreach ( $shadow_choices as $value => $label ) :
						?>
						<label>
							<input
								type="radio"
								name="simple_theme_options[shadow]"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( $options['shadow'], $value ); ?>
							/>
							<?php echo esc_html( $label ); ?>
						</label><br />
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '配色方案', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="background_light"><?php echo esc_html__( '背景色（浅色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="background_light"
					name="simple_theme_options[background_light]"
					value="<?php echo esc_attr( $options['background_light'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#fcfbf7"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="background_dark"><?php echo esc_html__( '背景色（深色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="background_dark"
					name="simple_theme_options[background_dark]"
					value="<?php echo esc_attr( $options['background_dark'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#111315"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="card_light"><?php echo esc_html__( '卡片背景（浅色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="card_light"
					name="simple_theme_options[card_light]"
					value="<?php echo esc_attr( $options['card_light'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#ffffff"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="card_dark"><?php echo esc_html__( '卡片背景（深色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="card_dark"
					name="simple_theme_options[card_dark]"
					value="<?php echo esc_attr( $options['card_dark'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#171a1d"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="foreground_light"><?php echo esc_html__( '文字颜色（浅色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="foreground_light"
					name="simple_theme_options[foreground_light]"
					value="<?php echo esc_attr( $options['foreground_light'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#1f2937"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="foreground_dark"><?php echo esc_html__( '文字颜色（深色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="foreground_dark"
					name="simple_theme_options[foreground_dark]"
					value="<?php echo esc_attr( $options['foreground_dark'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#f7f7f2"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="accent_light"><?php echo esc_html__( '强调色（浅色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="accent_light"
					name="simple_theme_options[accent_light]"
					value="<?php echo esc_attr( $options['accent_light'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#f3ecdf"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="accent_dark"><?php echo esc_html__( '强调色（深色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="accent_dark"
					name="simple_theme_options[accent_dark]"
					value="<?php echo esc_attr( $options['accent_dark'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#22282d"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="border_light"><?php echo esc_html__( '边框色（浅色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="border_light"
					name="simple_theme_options[border_light]"
					value="<?php echo esc_attr( $options['border_light'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#e5d8c5"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="border_dark"><?php echo esc_html__( '边框色（深色）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="border_dark"
					name="simple_theme_options[border_dark]"
					value="<?php echo esc_attr( $options['border_dark'] ); ?>"
					class="simple-theme-color-picker"
					data-default-color="#343c44"
				/>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '布局', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="container_max_width"><?php echo esc_html__( '容器最大宽度（px）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="number"
					id="container_max_width"
					name="simple_theme_options[container_max_width]"
					value="<?php echo esc_attr( $options['container_max_width'] ); ?>"
					class="small-text"
					min="960"
					max="1680"
					step="10"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="article_max_width"><?php echo esc_html__( '文章最大宽度（px）', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="number"
					id="article_max_width"
					name="simple_theme_options[article_max_width]"
					value="<?php echo esc_attr( $options['article_max_width'] ); ?>"
					class="small-text"
					min="680"
					max="1200"
					step="10"
				/>
			</td>
		</tr>
	</table>

	<?php
}

// ========== Tab: Hero ==========

function simple_theme_render_tab_hero( $options ) {
	?>
	<h2><?php echo esc_html__( '封面区域', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '配置首页顶部的封面区域。', 'simple-theme' ); ?></p>

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html__( '启用封面', 'simple-theme' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="simple_theme_options[hero_enabled]" value="0" />
					<input
						type="checkbox"
						name="simple_theme_options[hero_enabled]"
						value="1"
						<?php checked( $options['hero_enabled'], true ); ?>
					/>
					<?php echo esc_html__( '在首页顶部显示封面区域。', 'simple-theme' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '显示模式', 'simple-theme' ); ?></th>
			<td>
				<fieldset>
					<?php
					$mode_choices = array(
						'full'  => __( '全宽', 'simple-theme' ),
						'half'  => __( '半宽', 'simple-theme' ),
						'inset' => __( '嵌入', 'simple-theme' ),
					);
					foreach ( $mode_choices as $value => $label ) :
						?>
						<label>
							<input
								type="radio"
								name="simple_theme_options[hero_display_mode]"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( $options['hero_display_mode'], $value ); ?>
							/>
							<?php echo esc_html( $label ); ?>
						</label><br />
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '使用背景图', 'simple-theme' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="simple_theme_options[hero_use_image]" value="0" />
					<input
						type="checkbox"
						name="simple_theme_options[hero_use_image]"
						value="1"
						<?php checked( $options['hero_use_image'], true ); ?>
					/>
					<?php echo esc_html__( '启用封面背景图片。', 'simple-theme' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_image"><?php echo esc_html__( '背景图 URL', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="url"
					id="hero_image"
					name="simple_theme_options[hero_image]"
					value="<?php echo esc_attr( $options['hero_image'] ); ?>"
					class="regular-text"
					placeholder="https://example.com/image.jpg"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '显示头像', 'simple-theme' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="simple_theme_options[hero_show_avatar]" value="0" />
					<input
						type="checkbox"
						name="simple_theme_options[hero_show_avatar]"
						value="1"
						<?php checked( $options['hero_show_avatar'], true ); ?>
					/>
					<?php echo esc_html__( '在封面区域显示头像。', 'simple-theme' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_avatar"><?php echo esc_html__( '头像 URL', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="url"
					id="hero_avatar"
					name="simple_theme_options[hero_avatar]"
					value="<?php echo esc_attr( $options['hero_avatar'] ); ?>"
					class="regular-text"
					placeholder="https://example.com/avatar.jpg"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_title"><?php echo esc_html__( '封面标题', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="hero_title"
					name="simple_theme_options[hero_title]"
					value="<?php echo esc_attr( $options['hero_title'] ); ?>"
					class="regular-text"
					placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				/>
				<p class="st-section-desc"><?php echo esc_html__( '封面区域显示的主标题。', 'simple-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_subtitle"><?php echo esc_html__( '封面副标题', 'simple-theme' ); ?></label>
			</th>
			<td>
				<textarea
					id="hero_subtitle"
					name="simple_theme_options[hero_subtitle]"
					class="large-text"
					rows="2"
					placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"
				><?php echo esc_textarea( $options['hero_subtitle'] ); ?></textarea>
				<p class="st-section-desc"><?php echo esc_html__( '显示在封面标题下方的副标题。', 'simple-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( '打字机效果', 'simple-theme' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="simple_theme_options[hero_typewriter_enabled]" value="0" />
					<input
						type="checkbox"
						name="simple_theme_options[hero_typewriter_enabled]"
						value="1"
						<?php checked( $options['hero_typewriter_enabled'], true ); ?>
					/>
					<?php echo esc_html__( '在封面区域启用打字机动画。', 'simple-theme' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_typewriter_texts"><?php echo esc_html__( '打字内容', 'simple-theme' ); ?></label>
			</th>
			<td>
				<textarea
					id="hero_typewriter_texts"
					name="simple_theme_options[hero_typewriter_texts]"
					class="large-text"
					rows="4"
					placeholder="One text per line&#10;The typewriter will cycle through them"
				><?php echo esc_textarea( $options['hero_typewriter_texts'] ); ?></textarea>
				<p class="st-section-desc"><?php echo esc_html__( '每行一条，打字机将自动循环显示。', 'simple-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="hero_typewriter_interval"><?php echo esc_html__( '打字速度', 'simple-theme' ); ?></label>
			</th>
			<td>
				<input
					type="number"
					id="hero_typewriter_interval"
					name="simple_theme_options[hero_typewriter_interval]"
					value="<?php echo esc_attr( $options['hero_typewriter_interval'] ); ?>"
					class="small-text"
					min="30"
					max="500"
					step="10"
				/>
				<p class="st-section-desc"><?php echo esc_html__( '数值越小打字越快（30-500），默认 110。', 'simple-theme' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

// ========== Tab: Card Meta ==========

function simple_theme_render_tab_card_meta( $options ) {
	?>
	<h2><?php echo esc_html__( '卡片信息', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '控制首页文章卡片上显示哪些元信息。', 'simple-theme' ); ?></p>

	<table class="form-table">
		<?php
		$meta_fields = array(
			'meta_show_category'      => __( '分类', 'simple-theme' ),
			'meta_show_publish_date'  => __( '发布日期', 'simple-theme' ),
			'meta_show_modified_date' => __( '修改日期', 'simple-theme' ),
			'meta_show_comment_count' => __( '评论数', 'simple-theme' ),
			'meta_show_view_count'    => __( '浏览量', 'simple-theme' ),
			'meta_show_reading_time'  => __( '阅读时间', 'simple-theme' ),
			'meta_show_word_count'    => __( '字数', 'simple-theme' ),
		);
		foreach ( $meta_fields as $key => $label ) :
			?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<td>
					<label>
						<input type="hidden" name="simple_theme_options[<?php echo esc_attr( $key ); ?>]" value="0" />
						<input
							type="checkbox"
							name="simple_theme_options[<?php echo esc_attr( $key ); ?>]"
							value="1"
							<?php checked( $options[ $key ], true ); ?>
						/>
						<?php echo esc_html( sprintf( __( '显示 %s', 'simple-theme' ), $label ) ); ?>
					</label>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

// ========== Tab: Footer ==========


function simple_theme_render_tab_footer( $options ) {
	?><h2><?php echo esc_html__( '页脚设置', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '配置页脚版权信息和显示样式。', 'simple-theme' ); ?></p>

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html__( '版权信息样式', 'simple-theme' ); ?></th>
			<td>
				<select name="simple_theme_options[copyright_style]" id="copyright_style">
					<option value="detailed" <?php selected( $options['copyright_style'], 'detailed' ); ?>><?php echo esc_html__( '详细 - Copyright © 2026 站点名称 All Rights Reserved.', 'simple-theme' ); ?></option>
					<option value="simple" <?php selected( $options['copyright_style'], 'simple' ); ?>><?php echo esc_html__( '简洁 - 2026 © 站点名称.', 'simple-theme' ); ?></option>
					<option value="none" <?php selected( $options['copyright_style'], 'none' ); ?>><?php echo esc_html__( '不显示', 'simple-theme' ); ?></option>
				</select>
				<p class="st-section-desc"><?php echo esc_html__( '选择侧边栏底部版权信息的显示格式。', 'simple-theme' ); ?></p>
			</td>
		</tr>
	</table>

	<?php
}

function simple_theme_render_tab_about( $options ) {
	?>
	<h2><?php echo esc_html__( '关于页设置', 'simple-theme' ); ?></h2>
	<p class="st-section-desc"><?php echo esc_html__( '配置关于页面的内容。', 'simple-theme' ); ?></p>

	<h3><?php echo esc_html__( '封面区域', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_avatar"><?php echo esc_html__( '头像 URL', 'simple-theme' ); ?></label></th>
			<td>
				<input type="text" id="about_avatar" name="simple_theme_options[about_avatar]" value="<?php echo esc_attr( $options['about_avatar'] ); ?>" class="regular-text" placeholder="https://example.com/avatar.jpg" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="about_subtitle_lines"><?php echo esc_html__( '副标题行', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_subtitle_lines" name="simple_theme_options[about_subtitle_lines]" class="large-text" rows="4" placeholder="A dreamer building eternal ideals"><?php echo esc_textarea( $options['about_subtitle_lines'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="about_identity_tags"><?php echo esc_html__( '身份标签', 'simple-theme' ); ?></label></th>
			<td>
				<input type="text" id="about_identity_tags" name="simple_theme_options[about_identity_tags]" value="<?php echo esc_attr( $options['about_identity_tags'] ); ?>" class="regular-text" placeholder="Blogger, Full-stack Developer" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="about_greeting"><?php echo esc_html__( '问候语', 'simple-theme' ); ?></label></th>
			<td>
				<input type="text" id="about_greeting" name="simple_theme_options[about_greeting]" value="<?php echo esc_attr( $options['about_greeting'] ); ?>" class="regular-text" placeholder="Hello, nice to meet you" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="about_slogan_block"><?php echo esc_html__( '标语块', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_slogan_block" name="simple_theme_options[about_slogan_block]" class="large-text" rows="3" placeholder="In constant exploration"><?php echo esc_textarea( $options['about_slogan_block'] ); ?></textarea>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '技能', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_skills"><?php echo esc_html__( '技能（逗号分隔）', 'simple-theme' ); ?></label></th>
			<td>
				<input type="text" id="about_skills" name="simple_theme_options[about_skills]" value="<?php echo esc_attr( $options['about_skills'] ); ?>" class="large-text" placeholder="Java, Docker, Vue, TypeScript" />
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '时间线', 'simple-theme' ); ?></h3>
	<p class="st-section-desc"><?php echo esc_html__( 'JSON format: [{"period":"...","title":"...","subtitle":"...","image":""}]', 'simple-theme' ); ?></p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_timeline"><?php echo esc_html__( '时间线数据', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_timeline" name="simple_theme_options[about_timeline]" class="large-text code" rows="5"><?php echo esc_textarea( $options['about_timeline'] ); ?></textarea>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '性格 / MBTI', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_mbti_type"><?php echo esc_html__( 'MBTI 类型', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_mbti_type" name="simple_theme_options[about_mbti_type]" value="<?php echo esc_attr( $options['about_mbti_type'] ); ?>" placeholder="INFP-T" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_mbti_label"><?php echo esc_html__( 'MBTI 标签', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_mbti_label" name="simple_theme_options[about_mbti_label]" value="<?php echo esc_attr( $options['about_mbti_label'] ); ?>" placeholder="Mediator" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_mbti_image"><?php echo esc_html__( 'MBTI 图片 URL', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_mbti_image" name="simple_theme_options[about_mbti_image]" value="<?php echo esc_attr( $options['about_mbti_image'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_mbti_url"><?php echo esc_html__( 'MBTI 参考链接', 'simple-theme' ); ?></label></th>
			<td><input type="url" id="about_mbti_url" name="simple_theme_options[about_mbti_url]" value="<?php echo esc_attr( $options['about_mbti_url'] ); ?>" class="regular-text" placeholder="https://www.16personalities.com/" /></td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '游戏', 'simple-theme' ); ?></h3>
	<p class="st-section-desc"><?php echo esc_html__( 'JSON format: [{"name":"...","icon":"","uid":"..."}]', 'simple-theme' ); ?></p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_games"><?php echo esc_html__( '游戏数据', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_games" name="simple_theme_options[about_games]" class="large-text code" rows="5"><?php echo esc_textarea( $options['about_games'] ); ?></textarea>
			</td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '动漫与音乐', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_anime_title"><?php echo esc_html__( '最喜欢的动漫', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_anime_title" name="simple_theme_options[about_anime_title]" value="<?php echo esc_attr( $options['about_anime_title'] ); ?>" class="regular-text" placeholder="Link Click" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_anime_tagline"><?php echo esc_html__( '动漫标语', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_anime_tagline" name="simple_theme_options[about_anime_tagline]" value="<?php echo esc_attr( $options['about_anime_tagline'] ); ?>" class="regular-text" placeholder="Beyond the past, beyond the future" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_music_artists"><?php echo esc_html__( '最喜欢的艺术家', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_music_artists" name="simple_theme_options[about_music_artists]" value="<?php echo esc_attr( $options['about_music_artists'] ); ?>" class="regular-text" placeholder="Artist Name" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_music_url"><?php echo esc_html__( '音乐链接', 'simple-theme' ); ?></label></th>
			<td><input type="url" id="about_music_url" name="simple_theme_options[about_music_url]" value="<?php echo esc_attr( $options['about_music_url'] ); ?>" class="regular-text" /></td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '个人信息', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_location"><?php echo esc_html__( '位置', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_location" name="simple_theme_options[about_location]" value="<?php echo esc_attr( $options['about_location'] ); ?>" placeholder="Beijing, China" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_birth_year"><?php echo esc_html__( '出生年份', 'simple-theme' ); ?></label></th>
			<td><input type="number" id="about_birth_year" name="simple_theme_options[about_birth_year]" value="<?php echo esc_attr( $options['about_birth_year'] ); ?>" placeholder="2003" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_education"><?php echo esc_html__( '教育', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_education" name="simple_theme_options[about_education]" value="<?php echo esc_attr( $options['about_education'] ); ?>" class="regular-text" placeholder="University name, major" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_occupation"><?php echo esc_html__( '职业', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_occupation" name="simple_theme_options[about_occupation]" value="<?php echo esc_attr( $options['about_occupation'] ); ?>" class="regular-text" placeholder="Job title" /></td>
		</tr>
	</table>

	<h3><?php echo esc_html__( '赞助', 'simple-theme' ); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="about_sponsor_total"><?php echo esc_html__( '总额', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_sponsor_total" name="simple_theme_options[about_sponsor_total]" value="<?php echo esc_attr( $options['about_sponsor_total'] ); ?>" placeholder="¥ 1180.44" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_sponsor_list"><?php echo esc_html__( '赞助列表 (JSON)', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_sponsor_list" name="simple_theme_options[about_sponsor_list]" class="large-text code" rows="5" placeholder='[{"name":"Sponsor Name","amount":"20"}]'><?php echo esc_textarea( $options['about_sponsor_list'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="about_sponsor_url"><?php echo esc_html__( '赞助链接', 'simple-theme' ); ?></label></th>
			<td><input type="url" id="about_sponsor_url" name="simple_theme_options[about_sponsor_url]" value="<?php echo esc_attr( $options['about_sponsor_url'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_donation_wechat_qr"><?php echo esc_html__( '微信收款码 URL', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_donation_wechat_qr" name="simple_theme_options[about_donation_wechat_qr]" value="<?php echo esc_attr( $options['about_donation_wechat_qr'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_donation_alipay_qr"><?php echo esc_html__( '支付宝收款码 URL', 'simple-theme' ); ?></label></th>
			<td><input type="text" id="about_donation_alipay_qr" name="simple_theme_options[about_donation_alipay_qr]" value="<?php echo esc_attr( $options['about_donation_alipay_qr'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="about_donation_total"><?php echo esc_html__( '收款总额', 'simple-theme' ); ?></label></th>
			<td>
				<textarea id="about_donation_total" name="simple_theme_options[about_donation_total]" class="large-text" rows="2"><?php echo esc_textarea( $options['about_donation_total'] ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}
