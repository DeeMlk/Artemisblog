<?php
/**
 * Opções do plugin e helpers de leitura.
 *
 * No tema, isto vivia no Customizer via get_theme_mod() — que é preso ao tema ativo.
 * Como plugin, tudo passa a viver num único option array `artemis_settings`
 * (get_option), independente do tema do site que hospeda o blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defaults de todas as configurações. Fonte única da verdade — usada tanto no
 * fallback de leitura quanto na página de ajustes.
 *
 * @return array
 */
function artemis_default_settings() {
	return array(
		/* Cores — paleta neutra padrão (azul profissional + slate cinza). É só um ponto
		 * de partida: tudo é editável no painel "Artemis Blog".
		 * IMPORTANTE: artemis_color_footer_bg é também o fundo dos heros escuros (single/
		 * archive/author) e da seção de relacionados — mantenha-o ESCURO, senão o texto
		 * branco do hero fica ilegível. */
		'artemis_color_primary'         => '#2563EB',
		'artemis_color_primary_hover'   => '#1D4ED8',
		'artemis_color_text'            => '#0F172A',
		'artemis_color_body'            => '#334155',
		'artemis_color_muted'           => '#64748B',
		'artemis_color_bg'              => '#FFFFFF',
		'artemis_color_bg_alt'          => '#F8FAFC',
		'artemis_color_border'          => '#E2E8F0',
		'artemis_color_header_bg'       => '#FFFFFF',
		'artemis_color_header_text'     => '#0F172A',
		'artemis_color_footer_bg'       => '#0F172A',
		'artemis_color_footer_text'     => '#FFFFFF',
		'artemis_color_cta_text'        => '#FFFFFF',
		'artemis_color_cta_header'      => '#2563EB',
		'artemis_color_cta_single_bg'   => '#0F172A',
		'artemis_color_cta_single_text' => '#FFFFFF',

		/* Tipografia */
		'artemis_font_heading'          => '',
		'artemis_font_body'             => '',
		'artemis_font_weights_heading'  => '600;700',
		'artemis_font_weights_body'     => '400;500;600',

		/* CTA global */
		'artemis_cta_url'               => '',
		'artemis_cta_label'             => 'Fale conosco',
		'artemis_cta_new_tab'           => 1,
		'artemis_cta_section_title'     => 'Pronto pra dar o próximo passo?',
		'artemis_cta_section_subtitle'  => 'Fale com a gente e descubra como podemos te ajudar.',
		'artemis_cta_sidebar_title'     => 'Gostou do conteúdo?',
		'artemis_cta_sidebar_text'      => 'Entre em contato e veja como podemos ajudar o seu negócio.',
		'artemis_cta_sidebar_image'     => 0,
		'artemis_cta_section_image'     => 0,

		/* Layout / Home */
		'artemis_featured_count'        => 5,
		'artemis_show_breadcrumbs'      => 1,

		/* Logo próprio do header/footer do canvas (muitos temas não usam o custom-logo do WP) */
		'artemis_logo'                  => 0,
		'artemis_footer_logo'           => 0,
		'artemis_tagline_show'          => 0,
		'artemis_logo_height'           => 48,
		'artemis_logo_height_footer'    => 64,

		/* Rodapé próprio do canvas (templates/footer.php) — estes campos ligam
		   os extras opcionais: WhatsApp e botão "voltar ao topo" saem desligados. */
		'artemis_footer_text'           => '',
		'artemis_footer_credit'         => 'Feito por Artemis',
		'artemis_footer_credit_url'     => 'https://artemis.com.br',
		'artemis_whatsapp_url'          => '',
		'artemis_footer_show_back_top'  => 0,
	);
}

/**
 * Lê uma opção do plugin com fallback.
 *
 * Mantém a MESMA assinatura pública do tema — todos os templates continuam
 * chamando artemis_get_option( 'artemis_color_primary', '#F07A1A' ) sem mudança.
 *
 * @param string $key     Chave da opção.
 * @param mixed  $default Valor padrão se vazio/ausente.
 * @return mixed
 */
function artemis_get_option( $key, $default = '' ) {
	static $opts = null;
	if ( $opts === null ) {
		$stored = get_option( 'artemis_settings', array() );
		$opts   = is_array( $stored ) ? $stored : array();
	}
	$value = isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	return ( $value === '' || $value === null ) ? $default : $value;
}

/* ---------------------------------------------------------------------------
 * Helpers de CTA global.
 * ------------------------------------------------------------------------- */

/**
 * URL do CTA global. Vazio se não configurado.
 */
function artemis_cta_url() {
	return esc_url( artemis_get_option( 'artemis_cta_url', '' ) );
}

/**
 * Label do CTA global.
 */
function artemis_cta_label() {
	return esc_html( artemis_get_option( 'artemis_cta_label', __( 'Saiba mais', 'artemis-blog' ) ) );
}

/**
 * Target do CTA (_blank / _self).
 */
function artemis_cta_target() {
	return artemis_get_option( 'artemis_cta_new_tab', 1 ) ? '_blank' : '_self';
}

/**
 * rel do CTA (noopener quando abre em nova aba).
 */
function artemis_cta_rel() {
	return artemis_get_option( 'artemis_cta_new_tab', 1 ) ? 'noopener noreferrer' : '';
}

/* ---------------------------------------------------------------------------
 * Helpers de tipografia.
 * ------------------------------------------------------------------------- */

/**
 * Fonte de títulos (fallback Montserrat).
 */
function artemis_font_heading() {
	$f = trim( (string) artemis_get_option( 'artemis_font_heading', '' ) );
	return $f !== '' ? $f : 'Montserrat';
}

/**
 * Fonte de corpo (fallback Montserrat).
 */
function artemis_font_body() {
	$f = trim( (string) artemis_get_option( 'artemis_font_body', '' ) );
	return $f !== '' ? $f : 'Montserrat';
}

/* ---------------------------------------------------------------------------
 * Rodapé (usado só se o rodapé opcional do blog estiver ligado).
 * ------------------------------------------------------------------------- */

/**
 * Texto do rodapé com atalhos {year} e {site}.
 */
function artemis_footer_text() {
	$raw = artemis_get_option( 'artemis_footer_text', '' );
	if ( $raw === '' ) {
		$raw = '© {year} {site}. ' . __( 'Todos os direitos reservados.', 'artemis-blog' );
	}
	$replacements = array(
		'{year}' => date_i18n( 'Y' ),
		'{site}' => get_bloginfo( 'name' ),
	);
	return wp_kses_post( strtr( $raw, $replacements ) );
}

/**
 * ID da página de posts (a "página do blog" marcada nos ajustes de Leitura).
 *
 * @return int 0 se não configurada.
 */
function artemis_blog_page_id() {
	return (int) get_option( 'page_for_posts', 0 );
}

/**
 * Predefinição "tema dark pronto" — usada pelo botão do painel.
 *
 * Devolve SÓ aparência: uma paleta dark coerente (incluindo o footer_bg escuro
 * que os heros e a seção de relacionados exigem) e dois flags de layout.
 *
 * Deliberadamente NÃO mexe em:
 * - URLs (CTA e WhatsApp) — são dados do cliente; esta é uma distribuição
 *   genérica e chumbar o link de alguém aqui já causou exatamente esse bug;
 * - textos de CTA — artemis_default_settings() já traz versões neutras, e
 *   sobrescrever o que o usuário escreveu seria pior que não fazer nada;
 * - logos/imagens (são mídias do site) nem a página do blog.
 *
 * @return array
 */
function artemis_config_preset() {
	return array(
		// Paleta dark coerente.
		'artemis_color_primary'         => '#3B82F6',
		'artemis_color_primary_hover'   => '#2563EB',
		'artemis_color_text'            => '#FFFFFF',
		'artemis_color_body'            => '#C7D2E0',
		'artemis_color_muted'           => '#8A99AD',
		'artemis_color_bg'              => '#12294A',
		'artemis_color_bg_alt'          => '#0A1C33',
		'artemis_color_border'          => '#1E3A5F',
		'artemis_color_header_bg'       => '#0A1C33',
		'artemis_color_header_text'     => '#FFFFFF',
		'artemis_color_footer_bg'       => '#081627',
		'artemis_color_footer_text'     => '#FFFFFF',
		'artemis_color_cta_text'        => '#FFFFFF',
		'artemis_color_cta_header'      => '#3B82F6',
		'artemis_color_cta_single_bg'   => '#12294A',
		'artemis_color_cta_single_text' => '#FFFFFF',

		// Layout que acompanha a paleta.
		'artemis_footer_show_back_top'  => 1,
		'artemis_tagline_show'          => 0,
	);
}
