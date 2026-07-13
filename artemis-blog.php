<?php
/**
 * Plugin Name:       Artemis Blog
 * Plugin URI:        https://artemis.com.br
 * Description:        Estrutura de blog da Artemis (home, posts e arquivos) empacotada como plugin. Renderiza as telas do blog em modo "canvas" — cabeçalho e rodapé próprios, isolados do tema/builder do site — e deixa TODA a identidade editável no painel "Artemis Blog": cores de cabeçalho, miolo e rodapé, fontes, logos, CTA global, WhatsApp e rodapé. Instale, marque a página do blog e ajuste o estilo na mão. Versão genérica, sem identidade de cliente embutida.
 * Version:           1.3.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Artemis
 * Author URI:        https://artemis.com.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       artemis-blog
 * Domain Path:       /languages
 *
 * @package Artemis_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proteção contra conflito com uma cópia antiga (pasta "artemis-plugin").
 *
 * Se as funções/constantes já existem, é porque a versão antiga carregou primeiro
 * (isso acontece justamente no request em que esta nova versão é ativada). Nesse
 * caso: desativa a cópia antiga na hora e aborta este carregamento, evitando o
 * erro fatal por redeclaração. A partir do próximo request, só esta versão carrega.
 */
if ( defined( 'ARTEMIS_PLUGIN_VERSION' ) || function_exists( 'artemis_get_option' ) ) {
	if ( is_admin() ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( (array) get_option( 'active_plugins', array() ) as $old ) {
			if ( strpos( $old, 'artemis-plugin/' ) === 0 ) {
				deactivate_plugins( $old, true ); // silencioso
			}
		}
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>Artemis Blog:</strong> ' .
				esc_html__( 'a versão antiga (pasta "artemis-plugin") foi desativada automaticamente para evitar conflito. Recarregue a página — você pode excluir a pasta antiga com segurança.', 'artemis-blog' ) .
				'</p></div>';
		} );
	}
	return;
}

define( 'ARTEMIS_PLUGIN_VERSION', '1.3.4' );
define( 'ARTEMIS_PLUGIN_FILE', __FILE__ );
define( 'ARTEMIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );   // com barra final
define( 'ARTEMIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );    // com barra final

/**
 * Módulos do plugin.
 *
 * options.php vem primeiro: define artemis_get_option() e os helpers de CTA/fonte
 * que todos os outros arquivos consomem.
 */
require_once ARTEMIS_PLUGIN_DIR . 'inc/options.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/migrations.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/template-tags.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/setup.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/assets.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/canvas.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/router.php';
require_once ARTEMIS_PLUGIN_DIR . 'inc/convert-loader.php'; // Artemis Convert embutido (CTAs + métricas)
require_once ARTEMIS_PLUGIN_DIR . 'inc/demo-seeder.php';    // Importação manual de conteúdo de exemplo

if ( is_admin() ) {
	require_once ARTEMIS_PLUGIN_DIR . 'inc/admin-settings.php';
}

/**
 * Carrega as traduções do plugin.
 */
function artemis_plugin_load_textdomain() {
	load_plugin_textdomain( 'artemis-blog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'artemis_plugin_load_textdomain' );

/**
 * Ativação: garante os defaults das opções e regenera as rewrite rules
 * (o Artemis Convert registra um CPT próprio que precisa de permalinks frescos).
 */
function artemis_plugin_activate() {
	if ( get_option( 'artemis_settings' ) === false ) {
		add_option( 'artemis_settings', artemis_default_settings() );
		// Instalação nova já nasce na versão atual → não roda migrações de dados
		// legados (elas só transformam configs de instalações antigas, ex.: Hangcha).
		if ( get_option( 'artemis_db_version' ) === false ) {
			add_option( 'artemis_db_version', ARTEMIS_PLUGIN_VERSION );
		}
	}
	if ( get_option( 'artemis_convert_analytics_enabled' ) === false ) {
		add_option( 'artemis_convert_analytics_enabled', '1' );
	}

	// Versão genérica: NÃO semeia conteúdo de exemplo na ativação (evita despejar posts
	// de template num site de produção). Para importar, use "Artemis Blog → Conteúdo de
	// exemplo" manualmente.

	// Regenera permalinks (CPT do Convert + posts recém-criados).
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'artemis_plugin_activate' );

/**
 * Desativação: limpa rewrite rules deixadas pelo CPT do Convert.
 */
function artemis_plugin_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'artemis_plugin_deactivate' );
