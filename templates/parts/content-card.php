<?php
/**
 * Card padrão de post (grids: home, archive, search, author).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'artemis-card' ); ?>>
	<a class="card-thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'artemis-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="thumb-placeholder" aria-hidden="true"></span>
		<?php endif; ?>
	</a>

	<div class="card-body">
		<?php
		$cats = get_the_category();
		if ( ! empty( $cats ) ) :
			$cat = $cats[0];
		?>
			<a class="card-category" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endif; ?>

		<h3 class="card-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>

		<a class="card-readmore" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Ver mais', 'artemis-blog' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</article>
