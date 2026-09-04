<?php
/**
 * WordPress 主题入口模板，交由 Vue 应用接管前台渲染。
 *
 * @package SimpleTheme
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script>
			(function() {
				var theme = localStorage.getItem('theme');
				if (theme !== 'light' && theme !== 'dark') {
					theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
				}
				document.documentElement.setAttribute('data-theme', theme);
				document.documentElement.style.colorScheme = theme;
			})();
		</script>
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'simple-theme-shell' ); ?>>
		<?php wp_body_open(); ?>
		<style>#st-static{display:none}</style>
		<div id="st-static">
			<?php get_template_part( 'templates/parts/static-content' ); ?>
		</div>
		<div id="app"></div>
		<noscript>
			<style>
			#st-static{display:block}
			#st-static{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans SC",sans-serif;color:#333;background:#fff;line-height:1.7;padding:0 20px}
			#st-static .cf-container{max-width:780px;margin:0 auto;padding:20px 0}
			#st-static .cf-header{padding:20px 0;border-bottom:1px solid #eee;margin-bottom:30px}
			#st-static .cf-header__logo{font-size:1.5rem;font-weight:700;color:#000;text-decoration:none}
			#st-static .cf-header__desc{color:#666;font-size:.875rem;margin-top:4px}
			#st-static .cf-page-title{font-size:1.75rem;font-weight:700;margin-bottom:24px}
			#st-static .cf-posts{display:flex;flex-direction:column;gap:24px}
			#st-static .cf-post{padding-bottom:24px;border-bottom:1px solid #f0f0f0}
			#st-static .cf-post__title{font-size:1.25rem;font-weight:600;margin-bottom:6px}
			#st-static .cf-post__title a,#st-static .cf-single__body a{color:#1a0dab;text-decoration:none}
			#st-static .cf-post__meta,#st-static .cf-single__meta{font-size:.8125rem;color:#888;margin-bottom:8px}
			#st-static .cf-post__excerpt{font-size:.9375rem;color:#444}
			#st-static .cf-single__title{font-size:2rem;font-weight:700;margin-bottom:12px;line-height:1.3}
			#st-static .cf-single__body{font-size:1rem;line-height:1.8}
			#st-static .cf-single__body img{max-width:100%;height:auto}
			#st-static .cf-single__body pre{background:#f5f5f5;padding:1em;overflow-x:auto;border-radius:4px;font-size:.875em}
			#st-static .cf-pagination{margin-top:24px;display:flex;gap:16px}
			#st-static .cf-footer{margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:.8125rem;color:#999;text-align:center}
			#st-static .cf-empty{color:#999;font-style:italic}
			</style>
		</noscript>
		<?php wp_footer(); ?>
	</body>
</html>
