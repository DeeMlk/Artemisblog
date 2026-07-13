<?php
/**
 * Modo canvas: o plugin renderiza o documento HTML inteiro (header + footer
 * próprios), ignorando o tema/builder do site nas telas do blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra a área de menu própria do blog.
 */
function artemis_register_menu() {
	register_nav_menu( 'artemis_primary', __( 'Artemis Blog — Menu principal', 'artemis-blog' ) );
}
add_action( 'after_setup_theme', 'artemis_register_menu' );

/**
 * Header canvas (documento completo até abrir o <main>).
 */
function artemis_header() {
	load_template( ARTEMIS_PLUGIN_DIR . 'templates/header.php', false );
}

/**
 * Footer canvas (fecha o documento).
 */
function artemis_footer() {
	load_template( ARTEMIS_PLUGIN_DIR . 'templates/footer.php', false );
}

/**
 * Menu do header.
 *
 * Ordem de preferência:
 * 1) menu atribuído à área própria "Artemis Blog — Menu principal";
 * 2) menu já atribuído a uma área comum do tema anfitrião (reaproveita o menu do
 *    site — vem "pronto" sem configurar nada);
 * 3) lista de páginas como último recurso.
 */
function artemis_header_menu() {
	$common = array(
		'menu_id'     => 'primary-menu',
		'container'   => false,
		'fallback_cb' => false,
		'depth'       => 2,
	);

	if ( has_nav_menu( 'artemis_primary' ) ) {
		wp_nav_menu( array_merge( $common, array( 'theme_location' => 'artemis_primary' ) ) );
		return;
	}

	$locations = get_nav_menu_locations();
	if ( ! empty( $locations ) ) {
		$menu_id = 0;
		foreach ( array( 'primary', 'main', 'menu-1', 'principal', 'header', 'top' ) as $pref ) {
			if ( ! empty( $locations[ $pref ] ) ) {
				$menu_id = $locations[ $pref ];
				break;
			}
		}
		if ( ! $menu_id ) {
			$menu_id = (int) reset( $locations );
		}
		if ( $menu_id ) {
			wp_nav_menu( array_merge( $common, array( 'menu' => $menu_id ) ) );
			return;
		}
	}

	if ( function_exists( 'wp_page_menu' ) ) {
		wp_page_menu( array( 'menu_class' => 'menu', 'container' => false ) );
	}
}
