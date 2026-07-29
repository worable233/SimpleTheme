<?php
/**
 * 全局后台美化 — Vue Admin Shell
 *
 * 通过 Vue 组件完全替换 WP 后台的左侧菜单栏和顶部导航栏，
 * 样式与主题前端完全一致（相同的 CSS 自定义属性）。
 *
 * CSS 加载策略：
 *   - 通过 admin_enqueue_scripts 加载，排在 WP 原生样式之后
 *   - source-order 胜出（相同特异性下，后加载的样式生效）
 *   - CSS 自定义属性 --wp-admin-theme-color 覆盖 WP 按钮色
 *
 * @package SimpleTheme
 */

defined('ABSPATH') || exit;

/**
 * 检查后台美化是否启用
 */
function simple_theme_is_admin_theme_enabled(): bool {
	$options = get_option('simple_theme_options', []);
	return !empty($options['admin_theme_enabled']);
}

/**
 * 判断当前页面是否应跳过美化
 */
function simple_theme_should_skip_admin_theme(): bool {
	$screen = get_current_screen();
	if (!$screen) return false;

	// 跳过块编辑器、自定义器、站点编辑器
	if ($screen->is_block_editor() || $screen->id === 'customize' || $screen->id === 'site-editor') {
		return true;
	}

	return false;
}

/**
 * 后台美化模式：不注销任何核心 CSS（避免破坏依赖链导致 WP 6.9+ _doing_it_wrong），
 * 而是靠 admin-theme.css 的 CSS 规则（display:none）隐藏原生侧边栏和顶栏。
 * 所有核心样式正常加载，浏览器的 HTTP 缓存确保几乎无额外开销。
 *
 * 注意：必须确保 dashicons 等被依赖的样式仍然注册，否则 WP 6.9+ 会发出
 * _doing_it_wrong 警告（thickbox 依赖 dashicons 等）。
 */
add_action('admin_enqueue_scripts', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	if (simple_theme_should_skip_admin_theme()) return;

	// 不注销任何 handle —— 保持核心样式注册状态，避免依赖报错
	// 原生 admin menu + admin bar 由 CSS display:none 隐藏

	// 显式注册 dashicons——某些 WP 构建/环境可能未预注册，导致 thickbox 等依赖报错
	if (!wp_style_is('dashicons', 'registered')) {
		wp_register_style('dashicons', includes_url('/css/dashicons.min.css'), [], '6.9.1');
	}
}, 1);

/**
 * 入队美化 CSS（admin + login）
 */
add_action('admin_enqueue_scripts', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	if (simple_theme_should_skip_admin_theme()) return;

	$version = defined('WP_DEBUG') && WP_DEBUG ? time() : (defined('SIMPLE_THEME_VERSION') ? SIMPLE_THEME_VERSION : '1.0');
	wp_enqueue_style(
		'simple-theme-admin-theme',
		get_template_directory_uri() . '/assets/admin-theme.css',
		[],
		$version
	);
});

/**
 * 给 body 添加 sta-theme-active class，供 CSS Grid 布局使用
 */
add_filter('admin_body_class', function (string $classes): string {
	if (!simple_theme_is_admin_theme_enabled()) return $classes;
	if (simple_theme_should_skip_admin_theme()) return $classes;
	return trim($classes . ' sta-theme-active');
});

add_action('login_enqueue_scripts', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	$version = defined('WP_DEBUG') && WP_DEBUG ? time() : (defined('SIMPLE_THEME_VERSION') ? SIMPLE_THEME_VERSION : '1.0');
	wp_enqueue_style(
		'simple-theme-admin-theme',
		get_template_directory_uri() . '/assets/admin-theme.css',
		[],
		$version
	);
});

/**
 * 入队 Admin Shell Vue App + 暗色模式同步
 */
add_action('admin_enqueue_scripts', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	if (simple_theme_should_skip_admin_theme()) return;

	// 读取 manifest 获取 admin-shell 产物的实际文件名
	$manifest_file = get_theme_file_path('dist/.vite/manifest.json');
	if (!file_exists($manifest_file)) return;
	$manifest = json_decode(file_get_contents($manifest_file), true);
	if (empty($manifest['src/admin/shell-entry.ts'])) return;

	$entry = $manifest['src/admin/shell-entry.ts'];

	// 入队 CSS
	if (!empty($entry['css']) && is_array($entry['css'])) {
		foreach ($entry['css'] as $index => $css_file) {
			$url = get_template_directory_uri() . '/dist/' . ltrim($css_file, '/');
			$ver = filemtime(get_theme_file_path('dist/' . ltrim($css_file, '/')));
			wp_enqueue_style(
				'simple-theme-admin-shell-' . $index,
				$url,
				[],
				$ver
			);
		}
	}

	// 入队 JS
	if (!empty($entry['file'])) {
		$script_uri = get_template_directory_uri() . '/dist/' . ltrim($entry['file'], '/');
		$script_ver = filemtime(get_theme_file_path('dist/' . ltrim($entry['file'], '/')));
		$handle = 'simple-theme-admin-shell';

		wp_enqueue_script(
			$handle,
			$script_uri,
			[],
			$script_ver,
			true
		);

		add_filter('script_loader_tag', function ($tag, $tag_handle, $src) use ($handle) {
			if ($handle === $tag_handle) {
				return '<script type="module" src="' . esc_url($src) . '"></script>';
			}
			return $tag;
		}, 10, 3);
	}
});

/**
 * 暗色模式同步：读取前端 localStorage 的 theme 设置，
 * 在 admin_head 中注入内联脚本
 */
add_action('admin_head', function () {
		if (!simple_theme_is_admin_theme_enabled()) return;
		if (simple_theme_should_skip_admin_theme()) return;
	?>
		<style>#wpfooter{display:none!important}</style>
	<script>
	(function() {
		var theme = localStorage.getItem('theme');
		if (theme === 'dark') {
			document.documentElement.setAttribute('data-theme', 'dark');
			document.documentElement.style.colorScheme = 'dark';
		} else if (theme === 'light') {
			document.documentElement.setAttribute('data-theme', 'light');
			document.documentElement.style.colorScheme = 'light';
		}
	})();
	</script>
	<?php
});

/**
 * 侧边栏品牌标识 — 通过 JS 将站点名称注入 #adminmenu 的 data-xhs-title 属性
 */
add_action('admin_head', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	if (simple_theme_should_skip_admin_theme()) return;
	?>
	<script>
	document.getElementById('adminmenu')?.setAttribute(
		'data-xhs-title',
		<?php echo wp_json_encode(get_bloginfo('name')); ?>
	);
	</script>
	<?php
});
