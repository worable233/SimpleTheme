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
 *
 * @license MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 文章卡片列表：与 Vue 侧 HomeView / TermArchive 的卡片同一套 class，
 * 无 JS 访客、爬虫和离线兜底看到的就是浏览器里那套卡片样式。
 */
$st_render_post_cards = static function () {
	if ( ! have_posts() ) {
		echo '<p class="cf-empty">' . esc_html__( '暂无文章', 'simple-theme' ) . '</p>';
		return;
	}

	echo '<div class="post-list"><div class="post-list__grid">';

	while ( have_posts() ) :
		the_post();

		$st_stats  = function_exists( 'simple_theme_calculate_post_stats' )
			? simple_theme_calculate_post_stats( get_post() )
			: array( 'readingTime' => 0 );
		$st_cats   = get_the_category();
		$st_sticky = is_sticky();
		?>
		<article <?php post_class( 'post-card' ); ?>>
			<?php if ( has_post_thumbnail() ) : ?>
				<a
					href="<?php the_permalink(); ?>"
					class="post-card__cover-link"
					tabindex="-1"
					aria-hidden="true"
				>
					<?php
					echo get_the_post_thumbnail(
						get_the_ID(),
						'large',
						array(
							'class'   => 'post-card__cover',
							'loading' => 'lazy',
							'alt'     => '',
						)
					);
					?>
				</a>
			<?php endif; ?>
			<div class="post-card__meta">
				<?php if ( ! empty( $st_cats ) ) : ?>
					<a class="post-card__meta-item" href="<?php echo esc_url( get_category_link( $st_cats[0]->term_id ) ); ?>">
						<?php echo esc_html( $st_cats[0]->name ); ?>
					</a>
				<?php endif; ?>
				<time class="post-card__meta-item" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php echo esc_html( get_the_date( 'Y/m/d' ) ); ?>
				</time>
				<?php if ( ! empty( $st_stats['readingTime'] ) ) : ?>
					<span class="post-card__meta-item"><?php echo esc_html( $st_stats['readingTime'] ); ?> 分钟</span>
				<?php endif; ?>
			</div>
			<div class="post-card__body">
				<h2 class="post-card__title">
					<?php if ( $st_sticky ) : ?>
						<span class="post-card__sticky">
							<svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor" aria-hidden="true">
								<path d="M15.113 3.21l.094 .083l5.5 5.5a1 1 0 0 1 -1.175 1.59l-3.172 3.171l-1.424 3.797a1 1 0 0 1 -.158 .277l-.07 .08l-1.5 1.5a1 1 0 0 1 -1.32 .082l-.095 -.083l-2.793 -2.792l-3.793 3.792a1 1 0 0 1 -1.497 -1.32l.083 -.094l3.792 -3.793l-2.792 -2.793a1 1 0 0 1 -.083 -1.32l.083 -.094l1.5 -1.5a1 1 0 0 1 .258 -.187l.098 -.042l3.796 -1.425l3.171 -3.17a1 1 0 0 1 1.497 -1.26z" />
							</svg>
							<?php esc_html_e( '置顶', 'simple-theme' ); ?>
						</span>
					<?php endif; ?>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
				<div class="post-card__excerpt"><?php the_excerpt(); ?></div>
			</div>
		</article>
		<?php
	endwhile;

	echo '</div></div>';

	// 与 HomeView 的结尾提示一致：只有一页时告诉读者到底了
	global $wp_query;
	if ( isset( $wp_query->max_num_pages ) && (int) $wp_query->max_num_pages <= 1 ) {
		$st_options  = get_option( 'simple_theme_options', array() );
		$st_end_note = is_array( $st_options ) && ! empty( $st_options['end_note'] )
			? (string) $st_options['end_note']
			: '好像就这么多';
		echo '<p class="end-note">' . esc_html( $st_end_note ) . '</p>';
	}
};

/** 分类筛选条：与 HomeView 的筛选按钮同构，无 JS 时也能点击跳转 */
$st_render_category_filter = static function () {
	?>
	<div class="home-content__filters">
		<a class="st-button st-button--primary st-button--small st-button--round" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( '全部', 'simple-theme' ); ?>
		</a>
		<?php foreach ( get_categories( array( 'hide_empty' => true ) ) as $st_category ) : ?>
			<a
				class="st-button st-button--default st-button--small st-button--round"
				href="<?php echo esc_url( get_category_link( $st_category->term_id ) ); ?>"
			>
				<?php echo esc_html( $st_category->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
};

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
			<div class="home-content">
				<header class="section-header">
					<h1>
						<span class="section-header__title"><?php esc_html_e( '首页', 'simple-theme' ); ?></span>
						<span class="section-header__subtitle">Home.</span>
					</h1>
				</header>
				<section>
					<?php $st_render_category_filter(); ?>
					<?php $st_render_post_cards(); ?>
				</section>
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
						</div>
					<?php endif; ?>
					<div class="cf-single__body prose-content">
						<?php if ( post_password_required() ) : ?>
							<p class="cf-empty"><?php esc_html_e( '此内容受密码保护。', 'simple-theme' ); ?></p>
						<?php else : ?>
							<?php
							// 静态副本对爬虫可见，但不应在首屏就下载全文图片：
							// 补 lazy、去掉 fetchpriority="high"。
							ob_start();
							the_content();
							echo simple_theme_static_lazy_images( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						<?php endif; ?>
					</div>
					<?php $st_license = simple_theme_get_article_license(); ?>
					<?php if ( $st_license ) : ?>
						<div class="cf-single__license">
							<div class="cf-single__license-head">
								<a class="cf-single__license-title" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
								<a class="cf-single__license-url" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( rawurldecode( get_permalink() ) ); ?></a>
							</div>
							<dl class="cf-single__license-meta">
								<div>
									<dt><?php esc_html_e( '作者', 'simple-theme' ); ?></dt>
									<dd><?php echo esc_html( get_the_author() ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( '发布于', 'simple-theme' ); ?></dt>
									<dd><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y年n月j日' ) ); ?></time></dd>
								</div>
								<?php if ( get_the_modified_date( 'Ymd' ) !== get_the_date( 'Ymd' ) ) : ?>
									<div>
										<dt><?php esc_html_e( '更新于', 'simple-theme' ); ?></dt>
										<dd><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'Y年n月j日' ) ); ?></time></dd>
									</div>
								<?php endif; ?>
								<div>
									<dt><?php esc_html_e( '许可协议', 'simple-theme' ); ?></dt>
									<dd>
										<?php if ( '' !== $st_license['url'] ) : ?>
											<a href="<?php echo esc_url( $st_license['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $st_license['label'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $st_license['label'] ); ?>
										<?php endif; ?>
									</dd>
								</div>
							</dl>
							<p class="cf-single__license-name"><?php echo esc_html( $st_license['full_name'] ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( has_tag() ) : ?>
						<span class="cf-single__tags">
							<?php the_tags( '', ', ', '' ); ?>
						</span>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>

		<?php elseif ( is_category() || is_tag() || is_tax() ) : ?>
			<?php /* ── Archive: Category / Tag / Taxonomy ── */ ?>
			<div class="archive-content">
				<header class="section-header">
					<h1>
						<span class="section-header__title"><?php echo esc_html( single_term_title( '', false ) ); ?></span>
						<span class="section-header__subtitle"><?php esc_html_e( '分类', 'simple-theme' ); ?></span>
					</h1>
				</header>
				<?php if ( term_description() ) : ?>
					<div class="cf-term-desc"><?php echo term_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<?php $st_render_post_cards(); ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php elseif ( is_archive() ) : ?>
			<?php /* ── Date / Author / Other Archives ── */ ?>
			<div class="archive-content">
				<header class="section-header">
					<h1>
						<span class="section-header__title">
							<?php
							if ( is_day() ) {
								echo esc_html( get_the_date() );
							} elseif ( is_month() ) {
								echo esc_html( get_the_date( 'F Y' ) );
							} elseif ( is_year() ) {
								echo esc_html( get_the_date( 'Y' ) );
							} elseif ( is_author() ) {
								echo esc_html( get_the_author() );
							} else {
								esc_html_e( '归档', 'simple-theme' );
							}
							?>
						</span>
						<span class="section-header__subtitle">Archive.</span>
					</h1>
				</header>
				<?php $st_render_post_cards(); ?>
			</div>
			<nav class="cf-pagination">
				<?php previous_posts_link( '&laquo; 上一页' ); ?>
				<?php next_posts_link( '下一页 &raquo;' ); ?>
			</nav>

		<?php elseif ( is_search() ) : ?>
			<?php /* ── Search Results ── */ ?>
			<div class="archive-content">
				<header class="section-header">
					<h1>
						<span class="section-header__title"><?php echo esc_html( get_search_query() ); ?></span>
						<span class="section-header__subtitle">Search.</span>
					</h1>
				</header>
				<?php $st_render_post_cards(); ?>
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
			<?php
			// 页脚内容与 Vue 的 SiteFooter 保持一致：版权样式 + 备案号，
			// 保证无 JS 访客与爬虫看到的和浏览器渲染结果相同。
			$st_footer_options  = get_option( 'simple_theme_options', array() );
			$st_copyright_style = is_array( $st_footer_options ) && in_array( (string) ( $st_footer_options['copyright_style'] ?? 'detailed' ), array( 'none', 'simple', 'detailed' ), true )
				? (string) $st_footer_options['copyright_style']
				: 'detailed';
			$st_icp_text        = is_array( $st_footer_options ) && ! empty( $st_footer_options['icp_text'] ) ? trim( (string) $st_footer_options['icp_text'] ) : '';
			$st_icp_gov_text    = is_array( $st_footer_options ) && ! empty( $st_footer_options['icp_gov_text'] ) ? trim( (string) $st_footer_options['icp_gov_text'] ) : '';
			?>
			<?php if ( 'none' !== $st_copyright_style ) : ?>
				<p>
					<?php if ( 'simple' === $st_copyright_style ) : ?>
						<?php echo esc_html( date_i18n( 'Y' ) ); ?> &copy; <?php echo esc_html( get_bloginfo( 'name' ) ); ?>.
					<?php else : ?>
						Copyright &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> All Rights Reserved.
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( '' !== $st_icp_text || '' !== $st_icp_gov_text ) : ?>
				<p class="cf-footer__icp">
					<?php if ( '' !== $st_icp_text ) : ?>
						<a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $st_icp_text ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $st_icp_gov_text ) : ?>
						<a href="https://beian.mps.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $st_icp_gov_text ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</footer>

</div>
