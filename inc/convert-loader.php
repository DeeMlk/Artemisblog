<?php
/**
 * Carrega o Artemis Convert embutido (CTAs com métricas de views/cliques/CTR).
 *
 * O código do plugin vive em inc/plugins/artemis-convert/ sem modificações —
 * este loader define as constantes apontando pro plugin Artemis, registra o
 * autoloader e inicializa.
 *
 * Se o plugin standalone "Artemis Convert" estiver instalado e ativo, ele define
 * ARTEMIS_CONVERT_VERSION antes; neste caso NÃO carregamos a versão embutida
 * (evita declarações duplicadas).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin standalone ativo? Aborta a versão embutida.
if ( defined( 'ARTEMIS_CONVERT_VERSION' ) ) {
	return;
}

define( 'ARTEMIS_CONVERT_VERSION', '1.3.0' );
define( 'ARTEMIS_CONVERT_PLUGIN_DIR', ARTEMIS_PLUGIN_DIR . 'inc/plugins/artemis-convert/' );
define( 'ARTEMIS_CONVERT_PLUGIN_URL', ARTEMIS_PLUGIN_URL . 'inc/plugins/artemis-convert/' );
/*
 * O subplugin usa esta constante num lugar só: dirname() dela vira o caminho
 * (relativo a WP_PLUGIN_DIR) de onde load_plugin_textdomain() lê os .mo. O valor
 * literal "artemis-convert/artemis-convert.php" apontava para uma pasta que NÃO
 * existe quando o Convert roda embutido — as traduções dele nunca carregavam.
 *
 * Derivamos do arquivo principal para cair no languages/ deste plugin (e não de
 * um nome de pasta chutado: o usuário pode ter renomeado a pasta na instalação).
 */
define( 'ARTEMIS_CONVERT_PLUGIN_BASENAME', dirname( plugin_basename( ARTEMIS_PLUGIN_FILE ) ) . '/artemis-convert.php' );
// O tema original não definia isto — em PHP 8 o uso de constante indefinida é fatal.
define( 'ARTEMIS_CONVERT_TEXT_DOMAIN', 'artemis-convert' );

/**
 * Autoloader das classes Artemis_Convert\* (mesma lógica do plugin original).
 */
spl_autoload_register(
	function ( $class_name ) {
		if ( strpos( $class_name, 'Artemis_Convert\\' ) !== 0 ) {
			return;
		}
		$parts = explode( '\\', $class_name );
		array_shift( $parts );
		if ( empty( $parts ) ) {
			return;
		}
		$class_file     = array_pop( $parts );
		$class_file     = 'class-' . strtolower( str_replace( '_', '-', $class_file ) ) . '.php';
		$path_under_inc = array_slice( $parts, 1 );
		$path_case      = implode( DIRECTORY_SEPARATOR, $path_under_inc );
		$path_lower     = strtolower( $path_case );
		$base           = ARTEMIS_CONVERT_PLUGIN_DIR . 'inc' . DIRECTORY_SEPARATOR;
		$candidates     = array(
			$base . $path_case . DIRECTORY_SEPARATOR . $class_file,
			$base . $path_lower . DIRECTORY_SEPARATOR . $class_file,
		);
		foreach ( array_unique( $candidates ) as $filepath ) {
			if ( file_exists( $filepath ) ) {
				require_once $filepath;
				return;
			}
		}
	}
);

// Inicializa (CPT, meta box, settings, shortcode, the_content, REST, redirect de clique).
\Artemis_Convert\Inc\Core\Init::init();
