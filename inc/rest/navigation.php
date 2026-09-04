<?php
/**
 * REST: Navigation Menu Endpoints
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a menu-safe URL or an empty string for unsupported protocols.
 * Menu values can be custom links, so they must not be trusted as href values.
 */
function simple_theme_get_safe_navigation_url( $url ) {
	$url = trim( wp_strip_all_tags( (string) $url ) );
	if ( '' === $url ) {
		return '';
	}

	// Protocol-relative URLs (//host/path) are external URLs in browsers, not
	// local paths, and must not bypass the host checks below.
	if ( 0 === strpos( $url, '//' ) ) {
		return '';
	}
	if ( 0 === strpos( $url, '/' ) || 0 === strpos( $url, '?' ) || 0 === strpos( $url, '#' ) ) {
		return $url;
	}

	$parsed = wp_parse_url( $url );
	if ( ! $parsed || empty( $parsed['scheme'] ) ) {
		return '';
	}

	$scheme = strtolower( $parsed['scheme'] );
	if ( ! in_array( $scheme, array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	if ( in_array( $scheme, array( 'http', 'https' ), true ) && empty( $parsed['host'] ) ) {
		return '';
	}

	return esc_url_raw( $url, array( 'http', 'https', 'mailto', 'tel' ) );
}

function simple_theme_get_navigation( WP_REST_Request $request ) {
	$location = $request->get_param( 'location' );
	if ( ! in_array( $location, array( 'primary', 'footer' ), true ) ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	$locations = get_nav_menu_locations();
	if ( ! isset( $locations[ $location ] ) ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	$menu_id = $locations[ $location ];
	if ( ! $menu_id ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	$menu_items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish' ) );
	if ( ! $menu_items ) {
		return new WP_REST_Response( array( 'items' => array() ), 200 );
	}

	return new WP_REST_Response( array( 'items' => simple_theme_format_menu_items( $menu_items ) ), 200 );
}

function simple_theme_format_menu_items( array $items ) {
	$formatted = array();
	foreach ( $items as $item ) {
		$icon = get_post_meta( $item->ID, '_menu_item_icon', true );
		$raw_url = trim( (string) $item->url );
		$url  = '' === $raw_url ? '#' : simple_theme_get_safe_navigation_url( $raw_url );
		if ( ! $url ) {
			continue;
		}

		$formatted[] = array(
			'id'          => $item->ID,
			'title'       => $item->title,
			'url'         => $url,
			'path'        => simple_theme_get_internal_path( $url ),
			'description' => $item->description ?: '',
			'current'     => false,
			'target'      => in_array( $item->target, array( '_self', '_blank', '_parent', '_top' ), true ) ? $item->target : '_self',
			'parent'      => (int) $item->menu_item_parent,
			'icon'        => $icon ?: '',
			'order'       => (int) $item->menu_order,
		);
	}

	// Keep items whose parent was removed by WordPress as top-level entries so
	// one malformed menu item cannot hide an otherwise valid subtree.
	$ids = array_fill_keys( array_map( function ( $item ) { return $item['id']; }, $formatted ), true );
	foreach ( $formatted as &$item ) {
		if ( $item['parent'] && ! isset( $ids[ $item['parent'] ] ) ) {
			$item['parent'] = 0;
		}
	}
	unset( $item );

	return simple_theme_build_menu_tree( simple_theme_build_children_map( $formatted ), 0 );
}

function simple_theme_build_children_map( array $items ) {
	$map = array();
	foreach ( $items as $item ) {
		$parent = $item['parent'];
		if ( ! isset( $map[ $parent ] ) ) {
			$map[ $parent ] = array();
		}
		$map[ $parent ][] = $item;
	}
	return $map;
}

function simple_theme_build_menu_tree( array $children_map, $parent_id, array $ancestors = array() ) {
	$branch = array();
	if ( ! isset( $children_map[ $parent_id ] ) ) {
		return $branch;
	}
	foreach ( $children_map[ $parent_id ] as $item ) {
		if ( isset( $ancestors[ $item['id'] ] ) ) {
			continue;
		}
		$next_ancestors = $ancestors;
		$next_ancestors[ $item['id'] ] = true;
		$children = simple_theme_build_menu_tree( $children_map, $item['id'], $next_ancestors );
		if ( ! empty( $children ) ) {
			$item['children'] = $children;
		}
		$branch[] = $item;
	}
	return $branch;
}
