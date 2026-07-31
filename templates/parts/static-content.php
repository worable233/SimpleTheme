<?php
/**
 * Server-Rendered Static Content (Unified Rendering)
 *
 * Embedded inside the SPA shell's <div id="app"> for every visitor.
 * Browser users never see it (hidden attr + Vue mount replaces #app),
 * search engines and no-JS users get full semantic HTML at the same URL.
 *
 * Uses the main query only — no secondary queries, no wp_reset_postdata().
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="cf-container">

	<header class="cf-header">
		<div class="cf-header__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cf-header__logo" rel="home">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</a>
			<p class="cf-header__desc"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>
	</header>

	<main class="cf-main">

		<?php if ( is_home() || is_front_page() ) : ?>
			<?php /* ── Home / Front Page ── */ ?>
			<h1 class="cf-page-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<div class="cf-posts">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'cf-post' ); ?>>
							<h2 class="cf-post__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="cf-post__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
								<?php
								$categories = get_the_category();
								if ( ! empty( $categories ) ) :
									?>
									<span class="cf-post__cats">
										<?php
										$cat_links = array();
										foreach ( $categories as $cat ) {
											$cat_links[] = '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
										}
										echo implode( ', ', $cat_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</span>
								<?php endif; ?>
							</div>
							<div class="cf-post__excerpt">
								<?php the_excerpt(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="cf-empty"><?php esc_html_e( '暂无文章', 'simple-theme' ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php elseif ( is_singular() ) : ?>
			<?php /* ── Single Post / Page ── */ ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'cf-single' ); ?>>
					<h1 class="cf-single__title"><?php the_title(); ?></h1>
					<?php if ( 'post' === get_post_type() ) : ?>
						<div class="cf-single__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_html( get_the_date() ); ?>
							</time>
							<?php
							$categories = get_the_category();
							if ( ! empty( $categories ) ) :
								?>
								<span class="cf-single__cats">
									<?php
									$cat_links = array();
									foreach ( $categories as $cat ) {
										$cat_links[] = '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
									}
									echo implode( ', ', $cat_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</span>
							<?php endif; ?>
							<?php if ( has_tag() ) : ?>
								<span class="cf-single__tags">
									<?php the_tags( '', ', ', '' ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="cf-single__body">
						<?php if ( post_password_required() ) : ?>
							<p class="cf-empty"><?php esc_html_e( '此内容受密码保护。', 'simple-theme' ); ?></p>
						<?php else : ?>
							<?php the_content(); ?>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>

		<?php elseif ( is_category() || is_tag() || is_tax() ) : ?>
			<?php /* ── Archive: Category / Tag / Taxonomy ── */ ?>
			<h1 class="cf-page-title">
				<?php echo esc_html( single_term_title( '', false ) ); ?>
			</h1>
			<?php if ( term_description() ) : ?>
				<div class="cf-term-desc"><?php echo term_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<div class="cf-posts">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'cf-post' ); ?>>
							<h2 class="cf-post__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="cf-post__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							</div>
							<div class="cf-post__excerpt">
								<?php the_excerpt(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="cf-empty"><?php esc_html_e( '该分类暂无文章', 'simple-theme' ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php elseif ( is_archive() ) : ?>
			<?php /* ── Date / Author / Other Archives ── */ ?>
			<h1 class="cf-page-title">
				<?php
				if ( is_day() ) {
					printf( esc_html__( '每日归档：%s', 'simple-theme' ), get_the_date() );
				} elseif ( is_month() ) {
					printf( esc_html__( '每月归档：%s', 'simple-theme' ), get_the_date( 'F Y' ) );
				} elseif ( is_year() ) {
					printf( esc_html__( '每年归档：%s', 'simple-theme' ), get_the_date( 'Y' ) );
				} elseif ( is_author() ) {
					printf( esc_html__( '作者：%s', 'simple-theme' ), get_the_author() );
				} else {
					esc_html_e( '归档', 'simple-theme' );
				}
				?>
			</h1>
			<div class="cf-posts">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'cf-post' ); ?>>
							<h2 class="cf-post__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="cf-post__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							</div>
							<div class="cf-post__excerpt">
								<?php the_excerpt(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="cf-empty"><?php esc_html_e( '暂无内容', 'simple-theme' ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php elseif ( is_search() ) : ?>
			<?php /* ── Search Results ── */ ?>
			<h1 class="cf-page-title">
				<?php printf( esc_html__( '搜索结果：%s', 'simple-theme' ), get_search_query() ); ?>
			</h1>
			<div class="cf-posts">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'cf-post' ); ?>>
							<h2 class="cf-post__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="cf-post__excerpt">
								<?php the_excerpt(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="cf-empty"><?php esc_html_e( '未找到相关结果', 'simple-theme' ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php else : ?>
			<?php /* ── 404 or Unknown (fallback) ── */ ?>
			<h1 class="cf-page-title"><?php esc_html_e( '页面未找到', 'simple-theme' ); ?></h1>
			<p><?php esc_html_e( '抱歉，没有找到您要的内容。', 'simple-theme' ); ?></p>

		<?php endif; ?>

	</main>

	<footer class="cf-footer">
		<div class="cf-footer__inner">
			<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		</div>
	</footer>

</div>
