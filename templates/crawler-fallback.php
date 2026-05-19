<?php
/**
 * Crawler Fallback Template
 *
 * Served to known search engine crawlers when the SPA shell
 * would otherwise hide the content. Renders full static HTML
 * so bots can index the actual page content.
 *
 * @package SimpleTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'crawler-fallback' ); ?>>
<?php wp_body_open(); ?>

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
						<?php the_content(); ?>
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

<?php
/**
 * Inline minimal CSS for the crawler fallback.
 *
 * Purposefully simple and lightweight — not meant to match the SPA theme's
 * visual design. Crawlers only need clean semantic HTML; these styles
 * improve readability if a human or tool renders the page in a browser.
 */
?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px;line-height:1.7;-webkit-text-size-adjust:100%}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans SC",sans-serif;color:#333;background:#fff;padding:0 20px}
.cf-container{max-width:780px;margin:0 auto;padding:20px 0}
.cf-header{padding:20px 0;border-bottom:1px solid #eee;margin-bottom:30px}
.cf-header__logo{font-size:1.5rem;font-weight:700;color:#000;text-decoration:none}
.cf-header__desc{color:#666;font-size:.875rem;margin-top:4px}
.cf-page-title{font-size:1.75rem;font-weight:700;margin-bottom:24px}
.cf-posts{display:flex;flex-direction:column;gap:24px}
.cf-post{padding-bottom:24px;border-bottom:1px solid #f0f0f0}
.cf-post__title{font-size:1.25rem;font-weight:600;margin-bottom:6px}
.cf-post__title a{color:#1a0dab;text-decoration:none}
.cf-post__title a:hover{text-decoration:underline}
.cf-post__meta{font-size:.8125rem;color:#888;margin-bottom:8px}
.cf-post__meta a{color:#555;text-decoration:none}
.cf-post__meta a:hover{text-decoration:underline}
.cf-post__cats::before{content:"· ";margin-left:2px}
.cf-post__excerpt{font-size:.9375rem;color:#444}
.cf-single__title{font-size:2rem;font-weight:700;margin-bottom:12px;line-height:1.3}
.cf-single__meta{font-size:.8125rem;color:#888;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #eee}
.cf-single__meta a{color:#555;text-decoration:none}
.cf-single__meta a:hover{text-decoration:underline}
.cf-single__body{font-size:1rem;line-height:1.8;color:#333}
.cf-single__body h2,.cf-single__body h3,.cf-single__body h4{margin-top:1.5em;margin-bottom:.5em}
.cf-single__body p{margin-bottom:1em}
.cf-single__body img{max-width:100%;height:auto}
.cf-single__body a{color:#1a0dab}
.cf-single__body ul,.cf-single__body ol{padding-left:1.5em;margin-bottom:1em}
.cf-single__body blockquote{border-left:3px solid #ddd;padding-left:1em;margin:1em 0;color:#666}
.cf-single__body pre{background:#f5f5f5;padding:1em;overflow-x:auto;border-radius:4px;font-size:.875em}
.cf-single__body code{background:#f5f5f5;padding:2px 6px;border-radius:3px;font-size:.875em}
.cf-single__body pre code{background:none;padding:0}
.cf-single__tags::before{content:"· tags: ";margin-left:2px}
.cf-term-desc{font-size:.9375rem;color:#555;margin-bottom:20px}
.cf-footer{margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:.8125rem;color:#999;text-align:center}
.cf-empty{color:#999;font-style:italic}
@media(max-width:600px){.cf-single__title{font-size:1.5rem}}
</style>

<?php wp_footer(); ?>
</body>
</html>
