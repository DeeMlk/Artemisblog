<?php
/**
 * Página do autor — hero com avatar + nome + bio, grid abaixo.
 *
 * Modo canvas (artemis_header()/artemis_footer()); miolo sob .artemis-blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

artemis_header();
$author = get_queried_object();
?>

	<header class="page-hero page-hero--author">
		<div class="artemis-container">
			<?php artemis_breadcrumbs(); ?>
			<div class="author-hero">
				<div class="author-hero-avatar"><?php echo get_avatar( $author->ID, 96 ); ?></div>
				<div class="author-hero-info">
					<p class="page-hero-eyebrow"><?php esc_html_e( 'Autor', 'artemis-blog' ); ?></p>
					<h1 class="page-hero-title"><?php echo esc_html( $author->display_name ); ?></h1>
					<?php
					$bio = get_the_author_meta( 'description', $author->ID );
					if ( $bio ) {
						echo '<p class="page-hero-desc">' . esc_html( $bio ) . '</p>';
					}

					$url = get_the_author_meta( 'user_url', $author->ID );
					if ( $url ) {
						echo '<p class="author-hero-url"><a href="' . esc_url( $url ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $url ) . '</a></p>';
					}
					?>
				</div>
			</div>
		</div>
	</header>

	<section class="archive-list">
		<div class="artemis-container" data-posts-target>
			<header class="section-header">
				<h2 class="section-title"><?php esc_html_e( 'Publicações deste autor', 'artemis-blog' ); ?></h2>
			</header>

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
