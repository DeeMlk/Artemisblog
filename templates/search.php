<?php
/**
 * Resultados de busca — hero com termo + formulário, grid abaixo.
 *
 * Modo canvas (artemis_header()/artemis_footer()); miolo sob .artemis-blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

artemis_header(); ?>

	<header class="page-hero">
		<div class="artemis-container">
			<?php artemis_breadcrumbs(); ?>
			<p class="page-hero-eyebrow"><?php esc_html_e( 'Busca', 'artemis-blog' ); ?></p>
			<h1 class="page-hero-title">
				<?php printf( esc_html__( 'Resultados para "%s"', 'artemis-blog' ), esc_html( get_search_query() ) ); ?>
			</h1>
			<?php if ( have_posts() ) :
				global $wp_query;
			?>
				<p class="page-hero-desc">
					<?php
					printf(
						esc_html( _n( '%d post encontrado.', '%d posts encontrados.', $wp_query->found_posts, 'artemis-blog' ) ),
						(int) $wp_query->found_posts
					);
					?>
				</p>
			<?php endif; ?>

			<div class="page-hero-search">
				<?php artemis_get_search_form(); ?>
			</div>
		</div>
	</header>

	<section class="archive-list search-results">
		<div class="artemis-container" data-posts-target>
			<?php if ( have_posts() ) : ?>
				<div class="artemis-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						artemis_get_part( 'content', 'card' );
					endwhile;
					?>
				</div>
				<?php artemis_pagination(); ?>
			<?php else : ?>
				<?php artemis_get_part( 'content', 'none' ); ?>
			<?php endif; ?>
		</div>
	</section>

<?php artemis_footer();
