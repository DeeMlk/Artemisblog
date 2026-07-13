<?php
/**
 * Single post — hero dark split + corpo 2 colunas (conteúdo + sidebar) + relacionados.
 *
 * Dentro do header/rodapé do tema anfitrião; miolo sob .artemis-blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

artemis_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

	<?php $has_thumb = has_post_thumbnail(); ?>
	<header class="single-hero <?php echo $has_thumb ? '' : 'single-hero--no-image'; ?>">
		<div class="single-hero-inner">

			<div class="single-hero-content">
				<div class="artemis-container">
					<?php artemis_breadcrumbs(); ?>
					<?php
					$cats = get_the_category();
					if ( ! empty( $cats ) ) :
						$cat = $cats[0];
					?>
						<a class="single-cat-tag" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
								<line x1="7" y1="7" x2="7.01" y2="7"></line>
							</svg>
							<?php echo esc_html( $cat->name ); ?>
						</a>
					<?php endif; ?>

					<h1 class="single-title"><?php the_title(); ?></h1>

					<div class="single-author">
						<div class="single-author-avatar"><?php echo get_avatar( get_the_author_meta( 'ID' ), 48 ); ?></div>
						<div class="single-author-info">
							<a class="single-author-name" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
								<?php echo esc_html( get_the_author() ); ?>
							</a>
							<div class="single-author-meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
								<span aria-hidden="true">•</span>
								<?php
								$word_count = str_word_count( wp_strip_all_tags( get_the_content() ) );
								$minutes    = max( 1, (int) ceil( $word_count / 200 ) );
								?>
								<span><?php printf( esc_html__( '%d min de leitura', 'artemis-blog' ), $minutes ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ( $has_thumb ) : ?>
				<div class="single-hero-image">
					<?php the_post_thumbnail( 'artemis-hero', array( 'loading' => 'eager', 'alt' => '' ) ); ?>
				</div>
			<?php endif; ?>

		</div>
	</header>

	<article class="single-post">
		<div class="artemis-container">

			<div class="single-layout">

				<div class="single-main">
					<div class="entry-content">
						<?php
						the_content();
						wp_link_pages( array(
							'before' => '<nav class="page-links">' . esc_html__( 'Páginas:', 'artemis-blog' ),
							'after'  => '</nav>',
						) );
						?>
					</div>

					<?php
					$tags = get_the_tag_list( '<div class="entry-tags"><span class="tags-label">' . esc_html__( 'Tags:', 'artemis-blog' ) . '</span>', '', '</div>' );
					if ( $tags ) {
						echo $tags;
					}
					?>

					<?php artemis_get_part( 'social', 'share' ); ?>
					<?php artemis_get_part( 'author', 'box' ); ?>
				</div>

				<aside class="single-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'artemis-blog' ); ?>">
					<?php artemis_get_sidebar(); ?>
				</aside>

			</div>
		</div>

		<?php artemis_related_posts( 6 ); ?>

	</article>

<?php endwhile; ?>

<?php artemis_footer();
