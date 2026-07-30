<?php
/**
 * Home do blog (página de posts) — destaques + CTA + filtros + grid 6/pág.
 *
 * Renderizada em modo canvas: artemis_header()/artemis_footer() montam o
 * documento inteiro, sem o tema anfitrião. Todo o miolo vive sob .artemis-blog
 * para o CSS ficar escopado. Filtro por categoria via ?artemis_cat=slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

artemis_header();

$current_cat    = isset( $_GET['artemis_cat'] ) ? sanitize_key( wp_unslash( $_GET['artemis_cat'] ) ) : '';
$is_filtered    = ( $current_cat !== '' );
$is_first_page  = ( ! is_paged() );
$featured_count = max( 1, (int) artemis_get_option( 'artemis_featured_count', 5 ) );

// IDs dos destaques — computados SEMPRE (não só na 1ª página) para excluí-los do
// grid de forma consistente em todas as páginas; senão os posts se repetem entre
// a página 1 e as seguintes. O bloco visual de destaque só aparece na página 1.
$featured_ids = ( ! $is_filtered )
	? get_posts( array(
		'posts_per_page'      => $featured_count,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
		'fields'              => 'ids',
	) )
	: array();

// Base da paginação = URL da página de posts (fallback: home).
$blog_page_id = artemis_blog_page_id();
$blog_base    = $blog_page_id ? trailingslashit( get_permalink( $blog_page_id ) ) : trailingslashit( home_url( '/' ) );
$cat_obj      = $is_filtered ? get_term_by( 'slug', $current_cat, 'category' ) : null;
?>

	<?php
	if ( $is_first_page && ! $is_filtered && ! empty( $featured_ids ) ) {
		$featured = new WP_Query( array(
			'post__in'            => $featured_ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => $featured_count,
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => true,
		) );
		if ( $featured->have_posts() ) : ?>
			<section class="home-hero">
				<div class="artemis-container">
					<header class="section-header section-header--inline">
						<h1 class="section-title"><?php esc_html_e( 'Conteúdos em destaque', 'artemis-blog' ); ?></h1>
					</header>
					<div class="hero-grid hero-grid--<?php echo esc_attr( $featured_count ); ?>">
						<?php
						$i            = 0;
						$opened_stack = false;
						while ( $featured->have_posts() ) :
							$featured->the_post();
							$variant = ( $i === 0 ) ? 'hero' : 'side';

							if ( $variant === 'side' && ! $opened_stack ) {
								echo '<div class="hero-side-stack">';
								$opened_stack = true;
							}

							artemis_get_part( 'content', 'featured', array( 'variant' => $variant ) );
							$i++;
						endwhile;
						if ( $opened_stack ) {
							echo '</div>';
						}
						?>
					</div>
				</div>
			</section>
			<?php
			wp_reset_postdata();
		endif;

		artemis_get_part( 'cta', 'section' );
	}
	?>

	<section class="home-latest">
		<div class="artemis-container" data-posts-target>
			<header class="section-header">
				<h2 class="section-title">
					<?php
					if ( $is_filtered ) {
						printf( esc_html__( 'Conteúdos em %s', 'artemis-blog' ), esc_html( $cat_obj ? $cat_obj->name : $current_cat ) );
					} else {
						esc_html_e( 'Últimos conteúdos', 'artemis-blog' );
					}
					?>
				</h2>
			</header>

			<?php
			$categories = get_categories( array(
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );

			if ( ! empty( $categories ) ) :
			?>
				<nav class="artemis-filters" aria-label="<?php esc_attr_e( 'Filtros de categoria', 'artemis-blog' ); ?>">
					<a href="<?php echo esc_url( $blog_base ); ?>" class="filter-chip <?php echo $is_filtered ? '' : 'is-active'; ?>">
						<?php esc_html_e( 'Todos', 'artemis-blog' ); ?>
					</a>
					<?php foreach ( $categories as $cat ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'artemis_cat', $cat->slug, $blog_base ) ); ?>" class="filter-chip <?php echo $current_cat === $cat->slug ? 'is-active' : ''; ?>">
							<?php echo esc_html( $cat->name ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			$args  = array(
				'posts_per_page' => 6,
				'paged'          => $paged,
			);
			if ( $is_filtered ) {
				$args['category_name'] = $current_cat;
			} elseif ( ! empty( $featured_ids ) ) {
				$args['post__not_in'] = $featured_ids;
			}
			$latest = new WP_Query( $args );

			if ( $latest->have_posts() ) : ?>
				<div class="artemis-grid">
					<?php
					while ( $latest->have_posts() ) :
						$latest->the_post();
						artemis_get_part( 'content', 'card' );
					endwhile;
					?>
				</div>

				<?php
				$base = $is_filtered
					? add_query_arg( 'artemis_cat', $current_cat, $blog_base . 'page/%#%/' )
					: $blog_base . 'page/%#%/';

				$pagination = paginate_links( array(
					'base'      => $base,
					'format'    => '',
					'current'   => $paged,
					'total'     => $latest->max_num_pages,
					'mid_size'  => 1,
					'prev_text' => '&larr; ' . __( 'Anterior', 'artemis-blog' ),
					'next_text' => __( 'Próxima', 'artemis-blog' ) . ' &rarr;',
					'type'      => 'array',
				) );
				if ( ! empty( $pagination ) ) :
				?>
					<nav class="artemis-pagination" aria-label="<?php esc_attr_e( 'Paginação', 'artemis-blog' ); ?>">
						<ul>
							<?php foreach ( $pagination as $p ) : ?>
								<li><?php echo $p; ?></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="no-results">
					<h3 class="no-results-title">
						<?php
						if ( $is_filtered ) {
							printf( esc_html__( 'Nenhum post em "%s" por enquanto.', 'artemis-blog' ), esc_html( $cat_obj ? $cat_obj->name : $current_cat ) );
						} else {
							esc_html_e( 'Nada por aqui ainda.', 'artemis-blog' );
						}
						?>
					</h3>
					<p class="no-results-text"><?php esc_html_e( 'Volte em alguns dias ou explore outras categorias.', 'artemis-blog' ); ?></p>
					<?php if ( $is_filtered ) : ?>
						<a class="artemis-btn artemis-btn-ghost" href="<?php echo esc_url( $blog_base ); ?>">
							<?php esc_html_e( 'Ver todos os conteúdos', 'artemis-blog' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; wp_reset_postdata(); ?>
		</div>
	</section>

<?php artemis_footer();
