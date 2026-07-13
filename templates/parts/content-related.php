<?php
/**
 * Card de post para a seção "Conteúdos relacionados" (estilo dark).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'artemis-card-dark' ); ?>>
	<a class="card-thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'artemis-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="thumb-placeholder" aria-hidden="true"></span>
		<?php endif; ?>
		<?php
		$cats = get_the_category();
		if ( ! empty( $cats ) ) :
			$cat = $cats[0];
		?>
			<span class="card-cat-badge"><?php echo esc_html( $cat->name ); ?></span>
		<?php endif; ?>
	</a>
	<div class="card-body">
		<h3 class="card-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>
		<a class="card-readmore" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Ler completo', 'artemis-blog' ); ?> <span aria-hidden="true">&rsaquo;</span>
		</a>
	</div>
</article>
