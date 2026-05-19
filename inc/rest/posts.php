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

	$args = array(
		'post_type'      => 'post',
		'posts_per_page' => $post_count,
		'paged'          => max( 1, $page ),
		'post_status'    => 'publish',
		'ignore_sticky_posts' => true,
	);

	if ( $page > 1 ) {
		// Paginated request — use WP_Query to compute total/found_posts correctly
		$query  = new WP_Query( $args );
		$posts  = $query->posts;
		$total  = (int) $query->found_posts;
		$total_pages = (int) $query->max_num_pages;
	} else {
		$posts = get_posts( $args );
		$total = $total_posts;
	}

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
		return new WP_REST_Response( array( 'error' => 'Invalid post ID' ), 400 );
	}
	$count = (int) get_post_meta( $post_id, 'views', true );
	update_post_meta( $post_id, 'views', $count + 1 );
	return new WP_REST_Response( array( 'viewCount' => $count + 1 ), 200 );
}

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
			'rendered' => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
		),
		'categories'     => wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) ),
		'tags'           => wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) ),
		'comment_status' => $post->comment_status,
		'comment_count'  => (int) $post->comment_count,
		'viewCount'      => max( 0, (int) get_post_meta( $post->ID, 'views', true ) ),
		'wordCount'      => $stats['wordCount'],
		'readingTime'    => $stats['readingTime'],
	);
}
