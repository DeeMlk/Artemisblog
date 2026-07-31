<?php
/**
 * Setup mínimo que o miolo do blog precisa do lado do WordPress.
 *
 * O modo canvas já cuida de <title>, menu e logo por conta própria (ver
 * templates/header.php), então aqui só resta o que depende do core: suporte a
 * imagem destacada e os tamanhos de corte usados pelos cards/heros. Tudo
 * idempotente — se o tema anfitrião já declarou o mesmo, não faz mal repetir.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Garante post-thumbnails e registra os tamanhos de imagem do Artemis.
 */
function artemis_plugin_setup() {
	add_theme_support( 'post-thumbnails' );

	// Mesmos cortes do tema original.
	add_image_size( 'artemis-hero', 1200, 720, true );
	add_image_size( 'artemis-card', 720, 480, true );
}
add_action( 'after_setup_theme', 'artemis_plugin_setup' );

/**
 * Expõe os tamanhos do Artemis no seletor de tamanho da biblioteca de mídia,
 * caso o editor queira inseri-los manualmente.
 *
 * @param array $sizes
 * @return array
 */
function artemis_plugin_image_size_names( $sizes ) {
	return array_merge( $sizes, array(
		'artemis-hero' => __( 'Artemis — Hero (1200×720)', 'artemis-blog' ),
		'artemis-card' => __( 'Artemis — Card (720×480)', 'artemis-blog' ),
	) );
}
add_filter( 'image_size_names_choose', 'artemis_plugin_image_size_names' );
