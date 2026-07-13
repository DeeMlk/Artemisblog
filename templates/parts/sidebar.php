<?php
/**
 * Sidebar do single: busca + relacionados + CTA dark.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="sidebar-search">
	<?php artemis_get_search_form(); ?>
</div>

<?php
$cats    = wp_get_post_categories( get_the_ID() );
$related = new WP_Query( array(
	'category__in'        => $cats,
	'post__not_in'        => array( get_the_ID() ),
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => 1,
	'no_found_rows'       => true,
) );
if ( $related->have_posts() ) :
?>
	<div class="sidebar-block sidebar-related">
		<h3 class="sidebar-title"><?php esc_html_e( 'Relacionados', 'artemis-blog' ); ?></h3>
		<ul class="sidebar-related-list">
			<?php while ( $related->have_posts() ) : $related->the_post(); ?>
				<li>
					<a class="sidebar-related-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					<a class="sidebar-related-link" href="<?php the_permalink(); ?>">
						<?php esc_html_e( 'Ler conteúdo', 'artemis-blog' ); ?>
						<span aria-hidden="true">&raquo;</span>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	</div>
<?php
	wp_reset_postdata();
endif;
?>

<?php artemis_get_part( 'cta', 'sidebar' ); ?>
