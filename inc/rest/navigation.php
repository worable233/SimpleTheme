<?php
/**
 * REST: Navigation Menu Endpoints
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

		$formatted[] = array(
			'id'     => $item->ID,
			'title'  => $item->title,
			'url'    => $item->url,
			'target' => $item->target ?: '_self',
			'parent' => (int) $item->menu_item_parent,
			'icon'   => $icon ?: '',
			'order'  => (int) $item->menu_order,
		);
	}
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

function simple_theme_build_menu_tree( array $children_map, $parent_id ) {
	$branch = array();
	if ( ! isset( $children_map[ $parent_id ] ) ) {
		return $branch;
	}
	foreach ( $children_map[ $parent_id ] as $item ) {
		$children = simple_theme_build_menu_tree( $children_map, $item['id'] );
		if ( ! empty( $children ) ) {
			$item['children'] = $children;
		}
		$branch[] = $item;
	}
	return $branch;
}
