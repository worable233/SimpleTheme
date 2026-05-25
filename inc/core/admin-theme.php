<?php
/**
 * 鍏ㄥ眬鍚庡彴灏忕孩涔︾編鍖?鈥?Vue Admin Shell
 *
 * 閫氳繃 Vue 缁勪欢瀹屽叏鏇挎崲 WP 鍚庡彴鐨勫乏渚ц彍鍗曟爮鍜岄《閮ㄥ鑸爮锛? * 鏍峰紡涓庝富棰樺墠绔畬鍏ㄤ竴鑷达紙鐩稿悓鐨?CSS 鑷畾涔夊睘鎬э級銆? *
 * CSS 鍔犺浇绛栫暐锛? *   - 閫氳繃 admin_enqueue_scripts 鍔犺浇锛屾帓鍦?WP 鍘熺敓鏍峰紡涔嬪悗
 *   - source-order 鑳滃嚭锛堢浉鍚岀壒寮傛€т笅锛屽悗鍔犺浇鐨勬牱寮忕敓鏁堬級
 *   - CSS 鑷畾涔夊睘鎬?--wp-admin-theme-color 瑕嗙洊 WP 鎸夐挳鑹? *
 * @package SimpleTheme
 */

defined('ABSPATH') || exit;

/**
 * 妫€鏌ュ悗鍙扮編鍖栨槸鍚﹀惎鐢? */
function simple_theme_is_admin_theme_enabled(): bool {
	$options = get_option('simple_theme_options', []);
	return !empty($options['admin_theme_enabled']);
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁搴旇烦杩囩編鍖? */
function simple_theme_should_skip_admin_theme(): bool {
	$screen = get_current_screen();
	if (!$screen) return false;

	// 璺宠繃鍧楃紪杈戝櫒銆佽嚜瀹氫箟鍣ㄣ€佺珯鐐圭紪杈戝櫒
	if ($screen->is_block_editor() || $screen->id === 'customize' || $screen->id === 'site-editor') {
		return true;
	}

	// 璺宠繃涓婚鑷韩 Vue SPA 璁剧疆椤碉紙鍏?UI 宸茬嫭绔嬭璁★級
	if ($screen->id === 'toplevel_page_simple-theme') {
		return true;
	}

	return false;
}

/**
 * 鍏ラ槦缇庡寲 CSS锛坅dmin + login锛? */
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
 * 鍏ラ槦 Admin Shell Vue App + 鏆楄壊妯″紡鍚屾
 */
add_action('admin_enqueue_scripts', function () {
	if (!simple_theme_is_admin_theme_enabled()) return;
	if (simple_theme_should_skip_admin_theme()) return;

	// 璇诲彇 manifest 鑾峰彇 admin-shell 浜х墿鐨勫疄闄呮枃浠跺悕
	$manifest_file = get_theme_file_path('dist/.vite/manifest.json');
	if (!file_exists($manifest_file)) return;
	$manifest = json_decode(file_get_contents($manifest_file), true);
	if (empty($manifest['src/admin/shell-entry.ts'])) return;

	$entry = $manifest['src/admin/shell-entry.ts'];

	// 鍏ラ槦 CSS
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

	// 鍏ラ槦 JS
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
 * 鏆楄壊妯″紡鍚屾锛氳鍙栧墠绔?localStorage 鐨?theme 璁剧疆
 * 鍦?admin_head 涓敞鍏ュ唴鑱旇剼鏈? */
add_action('admin_head', function () {
		if (!simple_theme_is_admin_theme_enabled()) return;
		if (simple_theme_should_skip_admin_theme()) return;
	?>
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
 * 渚ц竟鏍忓搧鐗屾爣璇?鈥?閫氳繃 JS 灏嗙珯鐐瑰悕绉版敞鍏?#adminmenu 鐨?data-xhs-title 灞炴€? */
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

