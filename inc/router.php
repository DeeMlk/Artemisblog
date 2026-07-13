<?php
/**
 * Roteamento de templates.
 *
 * Um único filtro template_include troca o template do tema anfitrião pelos
 * templates do plugin — mas SÓ nas telas do blog. Qualquer outra coisa (home
 * institucional, páginas do builder, CPTs do tema) segue renderizando normal.
 *
 * Os templates do plugin chamam get_header()/get_footer() do tema ANFITRIÃO
 * (herança do cabeçalho/rodapé do Hangcha) e usam os helpers abaixo para puxar
 * os parciais/sidebar/busca de dentro do plugin — já que get_template_part() e
 * get_sidebar() nativos só procurariam no tema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Troca o template pelas telas do blog do plugin.
 *
 * @param string $template Caminho resolvido pelo WordPress.
 * @return string
 */
function artemis_template_include( $template ) {
	if ( is_admin() || ! artemis_is_blog_context() ) {
		return $template;
	}

	$dir = ARTEMIS_PLUGIN_DIR . 'templates/';

	if ( is_singular( 'post' ) ) {
		$candidate = $dir . 'single.php';
	} elseif ( is_home() ) {
		$candidate = $dir . 'blog-home.php';
	} elseif ( is_author() ) {
		$candidate = $dir . 'author.php';
	} elseif ( is_search() ) {
		$candidate = $dir . 'search.php';
	} else { // is_category() || is_tag() || is_date()
		$candidate = $dir . 'archive.php';
	}

	return file_exists( $candidate ) ? $candidate : $template;
}
add_filter( 'template_include', 'artemis_template_include', 99 );

/**
 * Alinha a query principal da página de posts com o grid do Artemis (6/pág).
 *
 * Sem isto, a página de posts paginaria pela config de Leitura (padrão 10/pág):
 * pedir /blog/page/3/ quando a query principal só tem 2 páginas resultaria em 404.
 * Igualando a 6, a query principal sempre cobre as páginas que o grid gera.
 *
 * @param WP_Query $q
 */
function artemis_adjust_blog_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_home() ) {
		return;
	}
	$q->set( 'posts_per_page', 6 );
}
add_action( 'pre_get_posts', 'artemis_adjust_blog_query' );

/**
 * Equivalente ao get_template_part() do tema, mas resolvendo em templates/parts/
 * dentro do plugin. Aceita $args (disponíveis como $args no parcial, WP 5.5+).
 *
 * @param string $slug Ex.: 'content'.
 * @param string $name Ex.: 'card' → parts/content-card.php.
 * @param array  $args Variáveis passadas ao parcial.
 */
function artemis_get_part( $slug, $name = '', $args = array() ) {
	$file = ARTEMIS_PLUGIN_DIR . 'templates/parts/' . $slug . ( '' !== $name ? '-' . $name : '' ) . '.php';
	if ( file_exists( $file ) ) {
		load_template( $file, false, $args );
	}
}

/**
 * Sidebar do single, carregada de dentro do plugin.
 */
function artemis_get_sidebar() {
	$file = ARTEMIS_PLUGIN_DIR . 'templates/parts/sidebar.php';
	if ( file_exists( $file ) ) {
		load_template( $file, false );
	}
}

/**
 * Formulário de busca do plugin.
 *
 * Usado diretamente pelos templates (em vez de get_search_form()) para não
 * substituir o formulário de busca do tema anfitrião em nenhum outro lugar.
 *
 * @param bool $echo Ecoa (true) ou retorna (false).
 * @return string
 */
function artemis_get_search_form( $echo = true ) {
	ob_start();
	load_template( ARTEMIS_PLUGIN_DIR . 'templates/parts/searchform.php', false );
	$html = ob_get_clean();

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup próprio já escapado no parcial.
		return '';
	}
	return $html;
}
