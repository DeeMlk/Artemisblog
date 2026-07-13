<?php
/**
 * Carregamento de CSS/JS e variáveis dinâmicas.
 *
 * Diferença central em relação ao tema: os assets do Artemis só entram nas telas
 * do blog (home de posts, single de post, arquivos, busca). No resto do site
 * — home institucional, páginas do builder, CPTs do tema anfitrião — nada é
 * enfileirado, pra não competir/pesar com o tema do site.
 *
 * As variáveis de cor/fonte são escopadas em .artemis-blog (não em :root), já que
 * o miolo do blog vive dentro de um wrapper .artemis-blog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A request atual é uma tela do universo "blog"?
 *
 * Filtrável via 'artemis_is_blog_context' para casos-limite do site anfitrião.
 *
 * @return bool
 */
function artemis_is_blog_context() {
	$is = (
		is_home()                 // página de posts marcada em Leitura
		|| is_singular( 'post' )  // post aberto
		|| is_category()
		|| is_tag()
		|| is_author()
		|| is_date()
		|| is_search()
	);
	return (bool) apply_filters( 'artemis_is_blog_context', $is );
}

/**
 * Monta a URL do Google Fonts (CSS2) a partir das fontes configuradas.
 * Retorna '' se nada precisar ser carregado.
 *
 * @return string
 */
function artemis_google_fonts_url() {
	$heading = artemis_font_heading();
	$body    = artemis_font_body();

	$heading_w = artemis_get_option( 'artemis_font_weights_heading', '600;700' );
	$body_w    = artemis_get_option( 'artemis_font_weights_body', '400;500;600' );

	$encode_family = function ( $name, $weights ) {
		$name = trim( $name );
		if ( $name === '' ) {
			return '';
		}
		$encoded = str_replace( ' ', '+', $name );
		$weights = preg_replace( '/[^0-9;]/', '', $weights );
		if ( $weights === '' ) {
			$weights = '400';
		}
		return 'family=' . $encoded . ':wght@' . $weights;
	};

	$families   = array();
	$families[] = $encode_family( $heading, $heading_w );
	if ( strcasecmp( $heading, $body ) !== 0 ) {
		$families[] = $encode_family( $body, $body_w );
	}
	$families = array_filter( $families );

	if ( empty( $families ) ) {
		return '';
	}

	return 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap';
}

/**
 * Enfileira estilos e scripts do blog (só nas telas do blog).
 */
function artemis_enqueue_assets() {
	if ( ! artemis_is_blog_context() ) {
		return;
	}

	// Preconnect pro Google Fonts (reduz TTFB do font load).
	add_action( 'wp_head', function () {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}, 1 );

	$fonts_url = artemis_google_fonts_url();
	if ( $fonts_url ) {
		wp_enqueue_style( 'artemis-google-fonts', $fonts_url, array(), null );
	}

	wp_enqueue_style( 'artemis-main', ARTEMIS_PLUGIN_URL . 'assets/css/main.css', array(), ARTEMIS_PLUGIN_VERSION );
	wp_add_inline_style( 'artemis-main', artemis_dynamic_css() );

	wp_enqueue_script( 'artemis-main', ARTEMIS_PLUGIN_URL . 'assets/js/main.js', array(), ARTEMIS_PLUGIN_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'artemis_enqueue_assets' );

/**
 * Isola o canvas do tema anfitrião.
 *
 * Nas telas do blog, retira da fila os stylesheets do TEMA ativo (ex.: Woodmart),
 * que senão vazam para dentro do blog: larguras de #site-header/#site-footer,
 * cores de p/h2, estilos de ícones. Remove só o que está sob a pasta do tema —
 * CSS do core, de blocos e de outros plugins continua intacto.
 */
function artemis_dequeue_theme_styles() {
	if ( ! artemis_is_blog_context() ) {
		return;
	}
	$styles = wp_styles();
	if ( ! ( $styles instanceof WP_Styles ) ) {
		return;
	}
	$theme_uris = array_unique( array(
		trailingslashit( get_template_directory_uri() ),
		trailingslashit( get_stylesheet_directory_uri() ),
	) );
	foreach ( (array) $styles->queue as $handle ) {
		if ( empty( $styles->registered[ $handle ] ) ) {
			continue;
		}
		$src = (string) $styles->registered[ $handle ]->src;
		if ( '' === $src ) {
			continue;
		}
		foreach ( $theme_uris as $uri ) {
			if ( false !== strpos( $src, $uri ) ) {
				wp_dequeue_style( $handle );
				break;
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'artemis_dequeue_theme_styles', 999 );

/**
 * Variáveis CSS (cores + fontes) escopadas no wrapper .artemis-blog.
 *
 * @return string
 */
function artemis_dynamic_css() {
	$primary         = artemis_get_option( 'artemis_color_primary', '#2563EB' );
	$primary_hover   = artemis_get_option( 'artemis_color_primary_hover', '#1D4ED8' );
	$text            = artemis_get_option( 'artemis_color_text', '#0F172A' );
	$body            = artemis_get_option( 'artemis_color_body', '#334155' );
	$muted           = artemis_get_option( 'artemis_color_muted', '#64748B' );
	$bg              = artemis_get_option( 'artemis_color_bg', '#FFFFFF' );
	$bg_alt          = artemis_get_option( 'artemis_color_bg_alt', '#F8FAFC' );
	$border          = artemis_get_option( 'artemis_color_border', '#E2E8F0' );
	$header_bg       = artemis_get_option( 'artemis_color_header_bg', '#FFFFFF' );
	$header_text     = artemis_get_option( 'artemis_color_header_text', '#0F172A' );
	$footer_bg       = artemis_get_option( 'artemis_color_footer_bg', '#0F172A' );
	$footer_text     = artemis_get_option( 'artemis_color_footer_text', '#FFFFFF' );
	$cta_text        = artemis_get_option( 'artemis_color_cta_text', '#FFFFFF' );
	$cta_header      = artemis_get_option( 'artemis_color_cta_header', '#2563EB' );
	$cta_single_bg   = artemis_get_option( 'artemis_color_cta_single_bg', '#0F172A' );
	$cta_single_text = artemis_get_option( 'artemis_color_cta_single_text', '#FFFFFF' );

	$heading_font = artemis_font_heading();
	$body_font    = artemis_font_body();

	$logo_h        = max( 16, (int) artemis_get_option( 'artemis_logo_height', 48 ) );
	$logo_h_footer = max( 16, (int) artemis_get_option( 'artemis_logo_height_footer', 64 ) );

	return ".artemis-blog{
		--artemis-color-primary: {$primary};
		--artemis-color-primary-hover: {$primary_hover};
		--artemis-color-text: {$text};
		--artemis-color-body: {$body};
		--artemis-color-muted: {$muted};
		--artemis-color-bg: {$bg};
		--artemis-color-bg-alt: {$bg_alt};
		--artemis-color-border: {$border};
		--artemis-color-header-bg: {$header_bg};
		--artemis-color-header-text: {$header_text};
		--artemis-color-footer-bg: {$footer_bg};
		--artemis-color-footer-text: {$footer_text};
		--artemis-color-cta-text: {$cta_text};
		--artemis-color-cta-header: {$cta_header};
		--artemis-color-cta-single-bg: {$cta_single_bg};
		--artemis-color-cta-single-text: {$cta_single_text};
		--artemis-font-heading: '{$heading_font}', 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
		--artemis-font-body: '{$body_font}', 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
		--artemis-logo-height: {$logo_h}px;
		--artemis-logo-height-footer: {$logo_h_footer}px;
	}
	/* Canvas: neutraliza margens/limites de largura que o tema anfitrião possa impor. */
	body.artemis-canvas{ margin: 0; padding: 0; }
	body.artemis-canvas #site-header, body.artemis-canvas #site-footer, body.artemis-canvas .site-header, body.artemis-canvas .site-footer{ width: 100% !important; max-width: none !important; margin-left: 0 !important; margin-right: 0 !important; float: none !important; }
		body.artemis-canvas #site-content, body.artemis-canvas .site-content{ width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
	body.artemis-canvas .single-hero, body.artemis-canvas .page-hero, body.artemis-canvas .artemis-related{ width: 100%; max-width: none; }
	/* Contraste em fundos escuros — a primária é escura, então
	   destaques/hover sobre hero/related precisam virar claros. */
	.artemis-blog .search-form .search-submit{ color: #fff; }
	.artemis-blog .search-form .search-submit svg{ stroke: #fff; }
	.artemis-blog .single-hero-content .artemis-breadcrumbs a:hover,
	.artemis-blog .page-hero .artemis-breadcrumbs a:hover,
	.artemis-blog .single-cat-tag:hover{ color: #fff; }
	.artemis-blog .related-arrow{ background: transparent; border: 1.5px solid rgba(255,255,255,.32); color: #fff; }
	.artemis-blog .related-arrow:hover:not(:disabled){ background: #fff; color: var(--artemis-color-primary); border-color: #fff; }
	.artemis-blog .related-arrow:disabled{ opacity: .35; }
		/* Cores sempre vindas das variáveis do painel (vencem o CSS do tema anfitrião) */
		.artemis-blog.artemis-canvas .entry-content, .artemis-blog.artemis-canvas .entry-content p, .artemis-blog.artemis-canvas .entry-content li, .artemis-blog.artemis-canvas .entry-content blockquote{ color: var(--artemis-color-body) !important; }
		.artemis-blog.artemis-canvas .entry-content h1, .artemis-blog.artemis-canvas .entry-content h2, .artemis-blog.artemis-canvas .entry-content h3, .artemis-blog.artemis-canvas .entry-content h4, .artemis-blog.artemis-canvas .entry-content strong, .artemis-blog.artemis-canvas .entry-content b{ color: var(--artemis-color-text) !important; }
		.artemis-blog.artemis-canvas .entry-content a{ color: var(--artemis-color-primary) !important; }
		.artemis-blog.artemis-canvas button{ font-family: inherit; }
		.artemis-blog.artemis-canvas svg{ vertical-align: middle; }
		.artemis-blog .header-search-submit{ color: var(--artemis-color-cta-text); }
		.artemis-blog .header-search-submit svg{ stroke: currentColor; }
		/* Imagem opcional na faixa de CTA da home */
		.artemis-blog .cta-section-image{ flex: 0 0 auto; width: 220px; height: 150px; border-radius: 14px; overflow: hidden; }
		.artemis-blog .cta-section-image img{ width: 100%; height: 100%; object-fit: cover; display: block; }
		@media (max-width: 860px){ .artemis-blog .cta-section-image{ width: 100%; height: 200px; } }";
}

/**
 * Excerpt mais curto e com reticências limpas — só afeta o loop do blog.
 */
function artemis_excerpt_length( $length ) {
	return artemis_is_blog_context() ? 22 : $length;
}
add_filter( 'excerpt_length', 'artemis_excerpt_length', 999 );

function artemis_excerpt_more( $more ) {
	return artemis_is_blog_context() ? '…' : $more;
}
add_filter( 'excerpt_more', 'artemis_excerpt_more' );
