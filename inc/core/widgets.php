<?php
/**
 * Sidebar & Widgets — 标准 WordPress 小工具体系
 *
 * 用 register_sidebar + WP_Widget 替代原先硬编码的右侧栏卡片，
 * 用户可在「外观 → 小工具」自由增删/排序，也能放入核心/区块小工具。
 * 前端 SPA 通过 site-info REST 读取结构化 sidebar 数据渲染。
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========== Register Sidebar ==========

add_action( 'widgets_init', 'simple_theme_register_sidebars' );
function simple_theme_register_sidebars() {
	register_sidebar( array(
		'name'          => __( '右侧栏', 'simple-theme' ),
		'id'            => 'sidebar-right',
		'description'   => __( '显示在文章右侧的小工具区域，支持主题专属卡片与所有核心/区块小工具。', 'simple-theme' ),
		'before_widget' => '<div id="%1$s" class="aside-card widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="aside-card__title">',
		'after_title'   => '</h3>',
	) );

	register_widget( 'Simple_Theme_Profile_Widget' );
	register_widget( 'Simple_Theme_Hitokoto_Widget' );
	register_widget( 'Simple_Theme_TechInfo_Widget' );
}

// ========== Theme Widgets ==========

/**
 * 资料卡小工具 — 头像/站名/格言 + 可选统计、热力图、社交链接。
 */
class Simple_Theme_Profile_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'simple_profile',
			__( '主题：个人资料卡', 'simple-theme' ),
			array( 'description' => __( '显示站点头像、名称、格言，以及可选的统计、贡献热力图、社交链接。', 'simple-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		// 前端由 Vue 渲染，此处仅输出容器占位以兼容经典渲染场景。
		echo $args['before_widget'];
		echo '<div class="aside-card__placeholder" data-widget="profile"></div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$show_stats   = ! isset( $instance['show_stats'] ) || $instance['show_stats'];
		$show_heatmap = ! isset( $instance['show_heatmap'] ) || $instance['show_heatmap'];
		$show_social  = ! isset( $instance['show_social'] ) || $instance['show_social'];
		?>
		<p>
			<input type="checkbox" class="checkbox" <?php checked( $show_stats ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'show_stats' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_stats' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_stats' ) ); ?>"><?php esc_html_e( '显示站点统计', 'simple-theme' ); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox" <?php checked( $show_heatmap ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'show_heatmap' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_heatmap' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_heatmap' ) ); ?>"><?php esc_html_e( '显示贡献热力图', 'simple-theme' ); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox" <?php checked( $show_social ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_social' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>"><?php esc_html_e( '显示社交链接', 'simple-theme' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( '头像、格言、社交链接等内容在「侧边栏」设置中配置。', 'simple-theme' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'show_stats'   => ! empty( $new_instance['show_stats'] ),
			'show_heatmap' => ! empty( $new_instance['show_heatmap'] ),
			'show_social'  => ! empty( $new_instance['show_social'] ),
		);
	}
}

/**
 * 一言小工具 — 从配置的 API 拉取随机句子（前端异步请求）。
 */
class Simple_Theme_Hitokoto_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'simple_hitokoto',
			__( '主题：一言', 'simple-theme' ),
			array( 'description' => __( '显示一条随机句子，支持 hitokoto.cn 及兼容 API。', 'simple-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		echo '<div class="aside-card__placeholder" data-widget="hitokoto"></div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$api = isset( $instance['api'] ) ? $instance['api'] : 'https://v1.hitokoto.cn';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'api' ) ); ?>"><?php esc_html_e( 'API 地址', 'simple-theme' ); ?></label>
			<input class="widefat" type="url"
				id="<?php echo esc_attr( $this->get_field_id( 'api' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'api' ) ); ?>"
				value="<?php echo esc_attr( $api ); ?>"
				placeholder="https://v1.hitokoto.cn" />
			<small class="description"><?php esc_html_e( '支持 hitokoto JSON 或纯文本响应。', 'simple-theme' ); ?></small>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'api' => esc_url_raw( $new_instance['api'] ?? '' ) ?: 'https://v1.hitokoto.cn',
		);
	}
}

/**
 * 信息卡小工具 — 显示技术信息与运行环境版本。
 */
class Simple_Theme_TechInfo_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'simple_tech_info',
			__( '主题：站点信息', 'simple-theme' ),
			array( 'description' => __( '显示文章许可、技术栈版本等站点信息。', 'simple-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		echo '<div class="aside-card__placeholder" data-widget="techInfo"></div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		?>
		<p class="description"><?php esc_html_e( '技术信息条目在「侧边栏」设置中配置。', 'simple-theme' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array();
	}
}

// ========== Sidebar Data Export (consumed by REST + inline config) ==========

/**
 * 将 sidebar-right 区域的小工具序列转成结构化数组供前端渲染。
 *
 * 主题小工具 → 结构化 { type, settings }，前端用原生 Vue 组件渲染（保留交互）。
 * 核心/区块/第三方小工具 → { type:'html', idBase, html }，前端 v-html 渲染。
 *
 * @return array
 */
function simple_theme_get_sidebar_data() {
	global $wp_registered_widgets, $wp_registered_sidebars;

	$sidebars = wp_get_sidebars_widgets();
	$widget_ids = isset( $sidebars['sidebar-right'] ) && is_array( $sidebars['sidebar-right'] )
		? $sidebars['sidebar-right']
		: array();

	$sidebar_args = isset( $wp_registered_sidebars['sidebar-right'] )
		? $wp_registered_sidebars['sidebar-right']
		: array();

	$out = array();

	foreach ( $widget_ids as $widget_id ) {
		// 解析 id_base 与实例编号，例如 "simple_profile-2" → base=simple_profile, number=2
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $m ) ) {
			continue;
		}
		$id_base = $m[1];
		$number  = (int) $m[2];

		// 主题小工具：直接读取实例设置，输出结构化数据
		if ( 'simple_profile' === $id_base ) {
			$instance = simple_theme_get_widget_instance( 'simple_profile', $number );
			$out[] = array(
				'type'     => 'profile',
				'settings' => array(
					'showStats'   => ! isset( $instance['show_stats'] ) || (bool) $instance['show_stats'],
					'showHeatmap' => ! isset( $instance['show_heatmap'] ) || (bool) $instance['show_heatmap'],
					'showSocial'  => ! isset( $instance['show_social'] ) || (bool) $instance['show_social'],
				),
			);
			continue;
		}
		if ( 'simple_hitokoto' === $id_base ) {
			$instance = simple_theme_get_widget_instance( 'simple_hitokoto', $number );
			$out[] = array(
				'type'     => 'hitokoto',
				'settings' => array(
					'api' => esc_url_raw( (string) ( $instance['api'] ?? 'https://v1.hitokoto.cn' ) ) ?: 'https://v1.hitokoto.cn',
				),
			);
			continue;
		}
		if ( 'simple_tech_info' === $id_base ) {
			$out[] = array( 'type' => 'techInfo', 'settings' => new stdClass() );
			continue;
		}

		// 其余小工具（核心/区块/第三方）：执行回调抓取 HTML
		if ( ! isset( $wp_registered_widgets[ $widget_id ] ) ) {
			continue;
		}
		$html = simple_theme_render_widget_html( $widget_id, $sidebar_args );
		if ( '' !== trim( $html ) ) {
			$out[] = array(
				'type'   => 'html',
				'idBase' => $id_base,
				'html'   => $html,
			);
		}
	}

	return $out;
}

/**
 * 读取指定小工具的单个实例设置。
 */
function simple_theme_get_widget_instance( $id_base, $number ) {
	$all = get_option( 'widget_' . $id_base, array() );
	return isset( $all[ $number ] ) && is_array( $all[ $number ] ) ? $all[ $number ] : array();
}

/**
 * 执行单个已注册小工具的回调，捕获其输出 HTML。
 * 逻辑参考 WordPress 核心 dynamic_sidebar() 的单个小工具渲染流程。
 */
function simple_theme_render_widget_html( $widget_id, $sidebar ) {
	global $wp_registered_widgets;

	$widget = $wp_registered_widgets[ $widget_id ];
	$callback = $widget['callback'];
	if ( ! is_callable( $callback ) ) {
		return '';
	}

	$params = array_merge(
		array(
			array_merge(
				$sidebar,
				array(
					'widget_id'   => $widget_id,
					'widget_name' => $widget['name'],
				)
			),
		),
		(array) $widget['params']
	);

	// 兼容核心过滤器（如 the_widget 的动态类名）
	$params = apply_filters( 'dynamic_sidebar_params', $params );

	ob_start();
	call_user_func_array( $callback, $params );
	return ob_get_clean();
}

// ========== One-time Migration from legacy sidebar_show_* options ==========

add_action( 'after_switch_theme', 'simple_theme_migrate_sidebar_widgets' );
function simple_theme_migrate_sidebar_widgets() {
	$sidebars = wp_get_sidebars_widgets();

	// 已有小工具则不覆盖用户配置
	if ( ! empty( $sidebars['sidebar-right'] ) ) {
		return;
	}

	$options = get_option( 'simple_theme_options', array() );

	// 按旧显隐选项预填资料卡实例
	$profile_instance = array(
		1 => array(
			'show_stats'   => (bool) ( $options['sidebar_show_stats'] ?? true ),
			'show_heatmap' => (bool) ( $options['sidebar_show_heatmap'] ?? true ),
			'show_social'  => (bool) ( $options['sidebar_show_social'] ?? true ),
		),
		'_multiwidget' => 1,
	);
	update_option( 'widget_simple_profile', $profile_instance );

	$assigned = array( 'simple_profile-1' );

	// 一言：旧开关开启才预填
	if ( (bool) ( $options['sidebar_show_hitokoto'] ?? true ) ) {
		update_option( 'widget_simple_hitokoto', array(
			1 => array( 'api' => esc_url_raw( (string) ( $options['hitokoto_api'] ?? 'https://v1.hitokoto.cn' ) ) ?: 'https://v1.hitokoto.cn' ),
			'_multiwidget' => 1,
		) );
		$assigned[] = 'simple_hitokoto-1';
	}

	// 信息卡始终预填
	update_option( 'widget_simple_tech_info', array( 1 => array(), '_multiwidget' => 1 ) );
	$assigned[] = 'simple_tech_info-1';

	$sidebars['sidebar-right'] = $assigned;
	wp_set_sidebars_widgets( $sidebars );
}
