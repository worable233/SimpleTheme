<?php
/**
 * REST: Posts / Collection Endpoints
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simple_theme_get_collection( WP_REST_Request $request ) {
	// If taxonomy params are provided, delegate to home-posts logic for filtered paginated results
	$taxonomy = $request->get_param( 'taxonomy' );
	$term_id  = (int) ( $request->get_param( 'termId' ) ?: 0 );
	$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
	$limit    = (int) ( $request->get_param( 'limit' ) ?: 0 );
	$type     = $request->get_param( 'type' );

	if ( $taxonomy && $term_id > 0 ) {
		return simple_theme_get_home_posts( $request );
	}

	// If type is 'shuoshuo', query only shuoshuo posts (e.g. /shuoshuo page)
	if ( 'shuoshuo' === $type ) {
		$shuoshuo_limit = $limit > 0 ? $limit : 12;
		$shuoshuo_posts = get_posts( array(
			'post_type'      => 'shuoshuo',
			'posts_per_page' => $shuoshuo_limit,
			'paged'          => max( 1, $page ),
			'post_status'    => 'publish',
			'ignore_sticky_posts' => true,
		) );
		$total_shuoshuo  = (int) wp_count_posts( 'shuoshuo' )->publish;

		return new WP_REST_Response( array(
			'items'      => array_map( 'simple_theme_format_post_item', $shuoshuo_posts ),
			'total'      => $total_shuoshuo,
			'totalPages' => $shuoshuo_limit > 0 ? max( 1, (int) ceil( $total_shuoshuo / $shuoshuo_limit ) ) : 1,
			'page'       => max( 1, $page ),
			'perPage'    => $shuoshuo_limit,
		), 200 );
	}

	$theme_options = get_option( 'simple_theme_options', array() );
	$post_count    = $limit > 0 ? $limit : simple_theme_get_option_number( 'home_post_count', 6, 3, 20 );
	$shuoshuo_count = simple_theme_get_option_number( 'home_shuoshuo_count', 3, 0, 12 );
	$show_shuoshuo = (bool) ( $theme_options['show_shuoshuo_section'] ?? True );
	$total_posts   = (int) wp_count_posts( 'post' )->publish;
	$total_pages   = $post_count > 0 ? max( 1, (int) ceil( $total_posts / $post_count ) ) : 1;

	// WordPress 原生置顶文章：第一页置顶置顶，后续页不重复。
	// （REST 自定义查询非 is_home，WP_Query 不会自动提升 sticky，故手动处理）
	$sticky_ids   = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
	$sticky_posts = array();
	if ( ! empty( $sticky_ids ) ) {
		$sticky_posts = get_posts( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'post__in'            => $sticky_ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => count( $sticky_ids ),
			'ignore_sticky_posts' => true,
		) );
	}
	$sticky_count = count( $sticky_posts );

	// 常规流（所有页）排除置顶，避免重复；用偏移量保证分页连续。
	$regular_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => $sticky_ids,
		'ignore_sticky_posts' => true,
		'suppress_filters'    => false,
	);

	if ( $page > 1 ) {
		$regular_args['posts_per_page'] = $post_count;
		$regular_args['offset']         = $post_count * ( $page - 1 ) - $sticky_count;
		$posts = get_posts( $regular_args );
	} else {
		$regular_args['posts_per_page'] = max( 1, $post_count - $sticky_count );
		$regular_args['offset']         = 0;
		$regular = $sticky_count >= $post_count ? array() : get_posts( $regular_args );
		$posts   = array_slice( array_merge( $sticky_posts, $regular ), 0, $post_count );
	}
	$total = $total_posts;

	$shuoshuo_posts = array();
	if ( $show_shuoshuo && $shuoshuo_count > 0 ) {
		$shuoshuo_posts = get_posts( array(
			'post_type'      => 'shuoshuo',
			'posts_per_page' => $shuoshuo_count,
			'post_status'    => 'publish',
			'ignore_sticky_posts' => true,
		) );
	}

	$data = array(
		'postsTitle'        => (string) ( $theme_options['posts_title'] ?? '最新文章' ),
		'postsSubtitle'     => (string) ( $theme_options['posts_subtitle'] ?? '' ),
		'shuoshuoTitle'     => (string) ( $theme_options['shuoshuo_title'] ?? '最近说说' ),
		'shuoshuoSubtitle'  => (string) ( $theme_options['shuoshuo_subtitle'] ?? '' ),
		'items'             => array_map( 'simple_theme_format_post_item', $posts ),
		'shuoshuoPosts'     => array_map( 'simple_theme_format_post_item', $shuoshuo_posts ),
		'total'             => $total,
		'totalPages'        => $total_pages,
		'page'              => max( 1, $page ),
		'perPage'           => $post_count,
		'showShuoshuoSection' => $show_shuoshuo,
	);

	return new WP_REST_Response( $data, 200 );
}

function simple_theme_get_home_posts( WP_REST_Request $request ) {
	$type     = $request->get_param( 'type' ) ?: 'post';
	$page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
	$limit    = (int) ( $request->get_param( 'limit' ) ?: simple_theme_get_option_number( 'home_post_count', 6, 3, 20 ) );
	$limit    = max( 1, min( 50, $limit ) );
	$taxonomy = $request->get_param( 'taxonomy' );
	$term_id  = (int) ( $request->get_param( 'termId' ) ?: 0 );

	$args = array(
		'post_type'         => $type,
		'posts_per_page'    => $limit,
		'paged'             => $page,
		'post_status'       => 'publish',
		'ignore_sticky_posts' => true,
	);

	if ( $taxonomy && $term_id > 0 ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);
	}

	$query  = new WP_Query( $args );
	$posts  = $query->posts;
	$total  = (int) $query->found_posts;
	$total_pages = (int) $query->max_num_pages;

	return new WP_REST_Response( array(
		'items'      => array_map( 'simple_theme_format_post_item', $posts ),
		'total'      => $total,
		'totalPages' => $total_pages,
		'page'       => $page,
		'perPage'    => $limit,
	), 200 );
}

function simple_theme_track_post_view( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'postId' );
	if ( ! $post_id ) {
		return new WP_REST_Response( array( 'message' => 'Invalid post ID' ), 400 );
	}
	$count = (int) get_post_meta( $post_id, 'views', true );
	update_post_meta( $post_id, 'views', $count + 1 );
	return new WP_REST_Response( array( 'viewCount' => $count + 1 ), 200 );
}

/**
 * 在 wp/v2 响应中追加 editUrl。
 * get_edit_post_link() 自带 current_user_can( 'edit_post' ) 权限检查，
 * 只有能编辑该文章的用户（作者/编辑/管理员，需携带 REST nonce）才会拿到。
 */
function simple_theme_add_edit_url( $response, $post ) {
	$edit_url = get_edit_post_link( $post->ID, 'raw' );
	if ( $edit_url ) {
		$data = $response->get_data();
		$data['editUrl'] = $edit_url;
		$response->set_data( $data );
	}
	return $response;
}
add_filter( 'rest_prepare_post', 'simple_theme_add_edit_url', 10, 2 );
add_filter( 'rest_prepare_page', 'simple_theme_add_edit_url', 10, 2 );
add_filter( 'rest_prepare_shuoshuo', 'simple_theme_add_edit_url', 10, 2 );

function simple_theme_format_post_item( WP_Post $post ) {
	$attachment_id = get_post_thumbnail_id( $post->ID );
	$featured      = null;
	if ( $attachment_id ) {
		$featured = wp_get_attachment_image_url( $attachment_id, 'full' );
	}

	$stats = simple_theme_calculate_post_stats( $post );

	return array(
		'id'             => $post->ID,
		'title'          => array( 'rendered' => get_the_title( $post ) ),
		'slug'           => $post->post_name,
		'type'           => $post->post_type,
		'link'           => get_permalink( $post ),
		'date'           => $post->post_date,
		'modified'       => $post->post_modified,
		'featuredImage'  => $featured,
		'excerpt'        => array(
			'rendered' => get_the_excerpt( $post ),
		),
		'categories'     => wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) ),
		'tags'           => wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) ),
		'comment_status' => $post->comment_status,
		'comment_count'  => (int) $post->comment_count,
		'viewCount'      => max( 0, (int) get_post_meta( $post->ID, 'views', true ) ),
		'wordCount'      => $stats['wordCount'],
		'readingTime'    => $stats['readingTime'],
		'isSticky'       => is_sticky( $post->ID ),
	);
}
