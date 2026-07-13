<?php
/**
 * Migrações de dados entre versões do plugin.
 *
 * Roda no init comparando a versão salva no banco (artemis_db_version) com a
 * versão atual do código — assim aplica mesmo quando o usuário apenas substitui
 * os arquivos do plugin (sem desativar/reativar).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ponto de entrada das migrações.
 */
function artemis_maybe_migrate() {
	$saved = get_option( 'artemis_db_version', '0' );
	if ( version_compare( $saved, ARTEMIS_PLUGIN_VERSION, '>=' ) ) {
		return;
	}

	// As migrações abaixo são específicas da 1ª instalação (Hangcha). Numa instalação
	// genérica nova elas não têm o que fazer, e o guard de host garante que os efeitos
	// colaterais do Hangcha (CTA/#produtos, logo) nunca vazem para outro site.
	if ( artemis_is_legacy_hangcha_site() ) {
		artemis_migrate_hangcha_palette();
		artemis_migrate_hangcha_defaults();
	}

	update_option( 'artemis_db_version', ARTEMIS_PLUGIN_VERSION );
}
add_action( 'init', 'artemis_maybe_migrate', 1 );

/**
 * A instalação atual é a do primeiro cliente (Hangcha)?
 *
 * As migrações legadas só devem rodar lá; numa instalação genérica ficam inertes.
 *
 * @return bool
 */
function artemis_is_legacy_hangcha_site() {
	return ( false !== strpos( strtolower( (string) home_url() ), 'hangcha' ) );
}

/**
 * 1.1.0 — Aplica a paleta Hangcha nas cores que ainda estão no default laranja
 * legado. Preserva qualquer cor que o usuário já tenha alterado manualmente.
 */
function artemis_migrate_hangcha_palette() {
	$settings = get_option( 'artemis_settings' );
	if ( ! is_array( $settings ) ) {
		return; // Sem configurações salvas: os novos defaults já são Hangcha.
	}

	// Mapa: chave => [ valor_legado_laranja, valor_novo_hangcha ].
	$map = array(
		'artemis_color_primary'       => array( '#F07A1A', '#1A2B36' ),
		'artemis_color_primary_hover' => array( '#D86610', '#0F1D26' ),
		'artemis_color_text'          => array( '#0B0F19', '#012830' ),
		'artemis_color_bg_alt'        => array( '#F5EFE6', '#F4F5F6' ),
		'artemis_color_border'        => array( '#EDE6DA', '#E6E8EB' ),
		'artemis_color_header_text'   => array( '#1A1A1A', '#012830' ),
		'artemis_color_footer_bg'     => array( '#F5F5F5', '#101010' ),
		'artemis_color_footer_text'   => array( '#5A5A5A', '#FFFFFF' ),
		'artemis_color_cta_header'    => array( '#1A1A1A', '#1A2B36' ),
		'artemis_color_cta_single_bg' => array( '#F07A1A', '#1A2B36' ),
	);

	$changed = false;
	foreach ( $map as $key => $pair ) {
		list( $legacy, $new ) = $pair;
		if ( isset( $settings[ $key ] ) && strtoupper( $settings[ $key ] ) === strtoupper( $legacy ) ) {
			$settings[ $key ] = $new;
			$changed          = true;
		}
	}

	if ( $changed ) {
		update_option( 'artemis_settings', $settings );
	}
}

/**
 * 1.2.2 — Ajustes pedidos pelo Hangcha: CTA apontando para a seção de
 * empilhadeiras e tagline do header desligada.
 */
function artemis_migrate_hangcha_defaults() {
	$settings = get_option( 'artemis_settings' );
	if ( ! is_array( $settings ) ) {
		return;
	}
	$changed = false;

	// CTA → âncora "Nossas Empilhadeiras" do menu do site, se ainda não configurado.
	if ( empty( $settings['artemis_cta_url'] ) ) {
		$settings['artemis_cta_url'] = home_url( '/' ) . '#produtos';
		$changed                     = true;
	}
	if ( empty( $settings['artemis_cta_label'] ) || $settings['artemis_cta_label'] === 'Fale conosco' ) {
		$settings['artemis_cta_label'] = 'Nossas empilhadeiras';
		$changed                       = true;
	}

	// Remove a tagline do header (pedido do cliente).
	if ( ! isset( $settings['artemis_tagline_show'] ) || (int) $settings['artemis_tagline_show'] !== 0 ) {
		$settings['artemis_tagline_show'] = 0;
		$changed                          = true;
	}

	// Logo do header: tenta puxar o logo do Hangcha da biblioteca de mídia
	// (best-effort — se não achar, o admin seleciona no painel).
	if ( empty( $settings['artemis_logo'] ) ) {
		$logo_id = artemis_find_attachment_by_slug( array( 'hangcha-empilhadeiras', 'hangcha-empilhadeiras-sao-paulo', 'logo-empilhadeiras-sp' ) );
		if ( $logo_id ) {
			$settings['artemis_logo'] = $logo_id;
			$changed                  = true;
		}
	}

	if ( $changed ) {
		update_option( 'artemis_settings', $settings );
	}
}

/**
 * Procura um attachment de imagem por uma lista de slugs (post_name).
 *
 * @param array $slugs
 * @return int ID ou 0.
 */
function artemis_find_attachment_by_slug( $slugs ) {
	foreach ( (array) $slugs as $slug ) {
		$found = get_posts( array(
			'post_type'        => 'attachment',
			'post_mime_type'   => 'image',
			'post_status'      => 'inherit',
			'posts_per_page'   => 1,
			'name'             => sanitize_title( $slug ),
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		if ( ! empty( $found ) ) {
			return (int) $found[0];
		}
	}
	return 0;
}
