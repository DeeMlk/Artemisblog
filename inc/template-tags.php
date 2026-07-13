<?php
/**
 * Tags de template auxiliares (breadcrumbs, paginação, meta, relacionados).
 *
 * Portado do tema sem mudanças de comportamento — a única diferença é usar
 * artemis_get_part() em vez de get_template_part().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imprime o botão de CTA global.
 *
 * @param array $args { class, label_override }
 */
function artemis_cta_button( $args = array() ) {
	$url = artemis_cta_url();
	if ( ! $url ) {
		return;
	}
	$defaults = array(
		'class'          => 'artemis-btn artemis-btn-primary',
		'label_override' => '',
	);
	$args  = wp_parse_args( $args, $defaults );
	$label = $args['label_override'] !== '' ? esc_html( $args['label_override'] ) : artemis_cta_label();
	$rel   = artemis_cta_rel();

	printf(
		'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
		esc_attr( $args['class'] ),
		esc_url( $url ),
		esc_attr( artemis_cta_target() ),
		$rel ? ' rel="' . esc_attr( $rel ) . '"' : '',
		$label
	);
}

/**
 * Meta do post (data + autor + tempo de leitura aproximado).
 */
function artemis_post_meta() {
	$date       = get_the_date();
	$author     = get_the_author();
	$author_url = get_author_posts_url( get_the_author_meta( 'ID' ) );

	$word_count = str_word_count( wp_strip_all_tags( get_the_content() ) );
	$minutes    = max( 1, (int) ceil( $word_count / 200 ) );

	echo '<div class="artemis-post-meta">';
	echo '<span class="meta-author"><a href="' . esc_url( $author_url ) . '">' . esc_html( $author ) . '</a></span>';
	echo '<span class="meta-sep" aria-hidden="true">•</span>';
	echo '<time class="meta-date" datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( $date ) . '</time>';
	echo '<span class="meta-sep" aria-hidden="true">•</span>';
	echo '<span class="meta-reading">' . sprintf( esc_html__( '%d min de leitura', 'artemis-blog' ), $minutes ) . '</span>';
	echo '</div>';
}

/**
 * Categorias do post em formato chip.
 */
function artemis_post_categories() {
	$cats = get_the_category();
	if ( empty( $cats ) ) {
		return;
	}
	echo '<div class="artemis-post-categories">';
	foreach ( $cats as $cat ) {
		printf(
			'<a class="cat-chip" href="%s">%s</a>',
			esc_url( get_category_link( $cat->term_id ) ),
			esc_html( $cat->name )
		);
	}
	echo '</div>';
}

/**
 * Breadcrumbs simples (Início / Categoria / Post).
 */
function artemis_breadcrumbs() {
	if ( ! artemis_get_option( 'artemis_show_breadcrumbs', 1 ) ) {
		return;
	}
	if ( is_front_page() && is_home() ) {
		return;
	}

	$sep = '<span class="crumb-sep" aria-hidden="true">/</span>';
	echo '<nav class="artemis-breadcrumbs" aria-label="' . esc_attr__( 'Você está em', 'artemis-blog' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Início', 'artemis-blog' ) . '</a>';

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$cat = $cats[0];
			echo $sep . '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
		}
		echo $sep . '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_category() || is_tag() || is_tax() ) {
		echo $sep . '<span aria-current="page">' . esc_html( single_term_title( '', false ) ) . '</span>';
	} elseif ( is_author() ) {
		echo $sep . '<span aria-current="page">' . esc_html( get_the_author() ) . '</span>';
	} elseif ( is_search() ) {
		echo $sep . '<span aria-current="page">' . sprintf( esc_html__( 'Busca por "%s"', 'artemis-blog' ), esc_html( get_search_query() ) ) . '</span>';
	} elseif ( is_archive() ) {
		echo $sep . '<span aria-current="page">' . esc_html( get_the_archive_title() ) . '</span>';
	}
	echo '</nav>';
}

/**
 * Paginação numerada (arquivos/autor/busca — usa a query principal).
 */
function artemis_pagination() {
	$pagination = paginate_links( array(
		'mid_size'  => 1,
		'prev_text' => '&larr; ' . __( 'Anterior', 'artemis-blog' ),
		'next_text' => __( 'Próxima', 'artemis-blog' ) . ' &rarr;',
		'type'      => 'array',
	) );
	if ( empty( $pagination ) ) {
		return;
	}
	echo '<nav class="artemis-pagination" aria-label="' . esc_attr__( 'Paginação', 'artemis-blog' ) . '"><ul>';
	foreach ( $pagination as $page ) {
		echo '<li>' . $page . '</li>';
	}
	echo '</ul></nav>';
}

/**
 * Posts relacionados (mesma categoria, exclui o atual).
 *
 * @param int $count Quantos exibir.
 */
function artemis_related_posts( $count = 6 ) {
	$count   = (int) $count;
	$exclude = array( get_the_ID() );
	$cats    = wp_get_post_categories( get_the_ID() );

	$ids = array();
	if ( ! empty( $cats ) ) {
		$ids = get_posts( array(
			'category__in'        => $cats,
			'post__not_in'        => $exclude,
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
		) );
	}

	// Completa com posts recentes de outras categorias se faltar.
	if ( count( $ids ) < $count ) {
		$fill = get_posts( array(
			'post__not_in'        => array_merge( $exclude, $ids ),
			'posts_per_page'      => $count - count( $ids ),
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
		) );
		$ids = array_merge( $ids, $fill );
	}

	if ( empty( $ids ) ) {
		return;
	}

	$q = new WP_Query( array(
		'post__in'            => $ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => 1,
	) );
	if ( ! $q->have_posts() ) {
		return;
	}
	?>
	<section class="artemis-related">
		<div class="artemis-container">
			<header class="related-header">
				<h2 class="related-title"><span class="related-title-thin"><?php esc_html_e( 'Conteúdos', 'artemis-blog' ); ?></span> <span class="related-title-bold"><?php esc_html_e( 'relacionados', 'artemis-blog' ); ?></span></h2>
				<div class="related-nav">
					<button type="button" class="related-arrow related-prev" aria-label="<?php esc_attr_e( 'Anterior', 'artemis-blog' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button type="button" class="related-arrow related-next" aria-label="<?php esc_attr_e( 'Próximo', 'artemis-blog' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				</div>
			</header>
			<div class="related-slider" data-related-slider>
				<div class="related-track">
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();
						artemis_get_part( 'content', 'related' );
					endwhile;
					?>
				</div>
			</div>
		</div>
	</section>
	<?php
	wp_reset_postdata();
}
