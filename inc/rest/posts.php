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
	$theme_options = get_option( 'simple_theme_options', array() );
	$post_count    = simple_theme_get_option_number( 'home_post_count', 6, 3, 20 );
	$shuoshuo_count = simple_theme_get_option_number( 'home_shuoshuo_count', 3, 0, 12 );
	$show_shuoshuo = (bool) ( $theme_options['show_shuoshuo_section'] ?? true );

	$posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => $post_count,
		'post_status'    => 'publish',
		'ignore_sticky_posts' => true,
	) );

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
		'posts'             => array_map( 'simple_theme_format_post_item', $posts ),
		'shuoshuoPosts'     => array_map( 'simple_theme_format_post_item', $shuoshuo_posts ),
		'totalPosts'        => (int) wp_count_posts( 'post' )->publish,
		'showShuoshuoSection' => $show_shuoshuo,
	);

	return new WP_REST_Response( $data, 200 );
}

function simple_theme_get_home_posts( WP_REST_Request $request ) {
	$page     = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = simple_theme_get_option_number( 'home_post_count', 6, 3, 20 );

	$posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'post_status'    => 'publish',
		'ignore_sticky_posts' => true,
	) );

	$total   = (int) wp_count_posts( 'post' )->publish;
	$has_more = ( $page * $per_page ) < $total;

	return new WP_REST_Response( array(
		'posts'    => array_map( 'simple_theme_format_post_item', $posts ),
		'hasMore'  => $has_more,
		'page'     => $page,
		'total'    => $total,
	), 200 );
}

function simple_theme_track_post_view( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'postId' );
	if ( ! $post_id ) {
		return new WP_REST_Response( array( 'error' => 'Invalid post ID' ), 400 );
	}
	$count = (int) get_post_meta( $post_id, 'views', true );
	update_post_meta( $post_id, 'views', $count + 1 );
	return new WP_REST_Response( array( 'views' => $count + 1 ), 200 );
}

function simple_theme_format_post_item( WP_Post $post ) {
	$attachment_id = get_post_thumbnail_id( $post->ID );
	$featured      = null;
	if ( $attachment_id ) {
		$featured = array(
			'url'    => wp_get_attachment_image_url( $attachment_id, 'full' ),
			'alt'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: $post->post_title,
		);
	}

	$stats = simple_theme_calculate_post_stats( $post );

	return array(
		'id'             => $post->ID,
		'title'          => array( 'rendered' => get_the_title( $post ) ),
		'slug'           => $post->post_name,
		'type'           => $post->post_type,
		'date'           => $post->post_date,
		'modified'       => $post->post_modified,
		'featuredImage'  => $featured,
		'excerpt'        => array(
			'rendered' => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
		),
		'categories'     => simple_theme_get_post_term_names( $post->ID, 'category' ),
		'tags'           => simple_theme_get_post_term_names( $post->ID, 'post_tag' ),
		'comment_status' => $post->comment_status,
		'comment_count'  => (int) $post->comment_count,
		'viewCount'      => max( 0, (int) get_post_meta( $post->ID, 'views', true ) ),
		'wordCount'      => $stats['wordCount'],
		'readingTime'    => $stats['readingTime'],
	);
}
