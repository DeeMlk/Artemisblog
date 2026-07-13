<?php
/**
 * Card de destaque (hero ou lateral) da home.
 *
 * Espera $args['variant'] = 'hero' | 'side'.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variant    = isset( $args['variant'] ) ? $args['variant'] : 'hero';
$thumb_size = $variant === 'hero' ? 'artemis-hero' : 'artemis-card';
?>
<article <?php post_class( 'artemis-featured artemis-featured--' . esc_attr( $variant ) ); ?>>
	<a class="featured-thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( $thumb_size, array( 'loading' => $variant === 'hero' ? 'eager' : 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="thumb-placeholder" aria-hidden="true"></span>
		<?php endif; ?>
	</a>

	<div class="featured-body">
		<?php
		$cats = get_the_category();
		if ( ! empty( $cats ) ) :
			$cat = $cats[0];
		?>
			<a class="featured-category" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endif; ?>

		<h2 class="featured-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<?php if ( $variant === 'hero' ) : ?>
			<p class="featured-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
		<?php endif; ?>

		<a class="card-readmore" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Ver mais', 'artemis-blog' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</article>
