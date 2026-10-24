<?php
/**
 * SPA 路由的服务端映射
 *
 * 前端把分类页放在 `/categories/<slug>`（HomeView 的筛选路由）。WordPress 并不认识
 * 这个路径，于是：
 *   - 直接访问 /categories/xx/ 会拿到 404 状态码（对 SEO 不友好）；
 *   - 服务端预渲染的静态内容只能输出「页面未找到」，离线兜底时那一页是空的。
 *
 * 这里在解析请求阶段把它映射成正常的分类归档查询，让状态码、标题、静态内容都正确，
 * 同时保持前端 URL 不变（屏蔽 canonical 跳转到 /category/xx/）。
 *
 * @package SimpleTheme
 *
 * @license MIT
 */

/**
 * 当前请求是否被识别为 SPA 的分类路由。
 *
 * @var bool
 */
$GLOBALS['simple_theme_spa_category_route'] = false;

/**
 * 把 /categories/<slug>/ 映射成分类归档查询。
 *
 * @param array $query_vars 解析出的查询变量。
 * @return array
 */
function simple_theme_map_spa_category_route( $query_vars ) {
	if ( is_admin() || ! is_array( $query_vars ) || empty( $query_vars['pagename'] ) ) {
		return $query_vars;
	}

	$pagename = trim( (string) $query_vars['pagename'], '/' );
	if ( 0 !== strpos( $pagename, 'categories/' ) ) {
		return $query_vars;
	}

	$slug = substr( $pagename, strlen( 'categories/' ) );
	// 只处理 /categories/<slug> 一层，更深或更短的路径照常走 404
	if ( '' === $slug || false !== strpos( $slug, '/' ) ) {
		return $query_vars;
	}

	$slug = rawurldecode( $slug );
	if ( ! get_category_by_slug( $slug ) ) {
		return $query_vars;
	}

	$GLOBALS['simple_theme_spa_category_route'] = true;

	return array(
		'category_name' => $slug,
		'post_type'     => 'post',
	);
}
add_filter( 'request', 'simple_theme_map_spa_category_route' );

/**
 * 保持 SPA 的 URL 形态：不要 301 到 /category/<slug>/。
 *
 * @param string|false $redirect_url  Canonical 目标地址。
 * @param string       $requested_url 用户请求的地址。
 * @return string|false
 */
function simple_theme_keep_spa_category_url( $redirect_url, $requested_url ) {
	if ( ! empty( $GLOBALS['simple_theme_spa_category_route'] ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'simple_theme_keep_spa_category_url', 10, 2 );
