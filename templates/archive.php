<?php
/**
 * Archive (categoria, tag, data) — hero + grid.
 *
 * Dentro do header/rodapé do tema anfitrião; miolo sob .artemis-blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

artemis_header();

$eyebrow = '';
if ( is_category() ) {
	$eyebrow = __( 'Categoria', 'artemis-blog' );
} elseif ( is_tag() ) {
	$eyebrow = __( 'Tag', 'artemis-blog' );
} elseif ( is_date() ) {
	$eyebrow = __( 'Arquivo', 'artemis-blog' );
} elseif ( is_tax() ) {
	$eyebrow = __( 'Arquivo', 'artemis-blog' );
}
?>

	<header class="page-hero">
		<div class="artemis-container">
			<?php artemis_breadcrumbs(); ?>
			<?php if ( $eyebrow ) : ?>
				<p class="page-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="page-hero-title"><?php echo wp_kses_post( get_the_archive_title() ); ?></h1>
			<?php
			$desc = get_the_archive_description();
			if ( $desc ) {
				echo '<div class="page-hero-desc">' . wp_kses_post( $desc ) . '</div>';
			}
			?>
		</div>
	</header>

	<section class="archive-list">
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
