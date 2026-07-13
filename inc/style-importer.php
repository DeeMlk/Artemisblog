<?php
/**
 * Seed de estilos do site anfitrião.
 *
 * Preenche as cores e fontes do blog a partir da identidade visual do PRÓPRIO
 * site onde o plugin está instalado — sem configurar nada na mão. O botão
 * "Importar estilo do site" (painel Artemis Blog) lê, nesta ordem:
 *
 *   1. Paleta global do Elementor (kit ativo: system_colors + tipografia);
 *   2. Estilos globais do tema (theme.json / Global Styles);
 *   3. CSS público da home (variáveis CSS, frequência de cores, Google Fonts).
 *
 * Com o que encontrar, monta uma paleta coerente pro blog (primária, textos,
 * fundos, cabeçalho, rodapé, CTA) e grava em `artemis_settings`. Só toca em
 * chaves de identidade visual — textos de CTA, logos, WhatsApp etc. ficam
 * como estão. Tudo continua editável no painel depois de importar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * Helpers de cor.
 * ------------------------------------------------------------------------- */

/**
 * Normaliza uma cor pra hex #rrggbb. Aceita #rgb, #rrggbb, #rrggbbaa e
 * rgb()/rgba() (alpha < 1 é descartado — costuma ser sombra/overlay).
 *
 * @param string $c
 * @return string '' se não for uma cor sólida utilizável.
 */
function artemis_style_norm_hex( $c ) {
	$c = strtolower( trim( (string) $c ) );
	if ( $c === '' ) {
		return '';
	}

	if ( preg_match( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([0-9.]+)\s*)?\)/', $c, $m ) ) {
		if ( isset( $m[4] ) && $m[4] !== '' && (float) $m[4] < 0.99 ) {
			return '';
		}
		return sprintf( '#%02x%02x%02x', min( 255, (int) $m[1] ), min( 255, (int) $m[2] ), min( 255, (int) $m[3] ) );
	}

	if ( $c[0] !== '#' ) {
		return '';
	}
	$h = substr( $c, 1 );
	if ( strlen( $h ) === 8 ) {
		$h = substr( $h, 0, 6 ); // ignora alpha
	}
	if ( strlen( $h ) === 4 ) {
		$h = substr( $h, 0, 3 );
	}
	if ( strlen( $h ) === 3 ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	if ( strlen( $h ) !== 6 || ! ctype_xdigit( $h ) ) {
		return '';
	}
	return '#' . $h;
}

/**
 * Converte hex pra HSL.
 *
 * @param string $hex #rrggbb.
 * @return array [h 0..360, s 0..1, l 0..1]
 */
function artemis_style_hsl( $hex ) {
	$r = hexdec( substr( $hex, 1, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 3, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 5, 2 ) ) / 255;

	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l   = ( $max + $min ) / 2;
	$d   = $max - $min;

	if ( $d == 0 ) {
		return array( 0, 0, $l );
	}

	$s = $d / ( 1 - abs( 2 * $l - 1 ) );

	if ( $max === $r ) {
		$h = 60 * fmod( ( $g - $b ) / $d, 6 );
	} elseif ( $max === $g ) {
		$h = 60 * ( ( ( $b - $r ) / $d ) + 2 );
	} else {
		$h = 60 * ( ( ( $r - $g ) / $d ) + 4 );
	}
	if ( $h < 0 ) {
		$h += 360;
	}

	return array( $h, $s, $l );
}

/**
 * Luminância relativa (WCAG) — 0 preto, 1 branco. Diferente da lightness HSL,
 * reflete o brilho percebido (verdes/cianos "claros" têm luminância alta).
 *
 * @param string $hex #rrggbb.
 * @return float
 */
function artemis_style_luminance( $hex ) {
	$lin = array();
	foreach ( array( 1, 3, 5 ) as $i ) {
		$c     = hexdec( substr( $hex, $i, 2 ) ) / 255;
		$lin[] = $c <= 0.04045 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
}

/**
 * Clareia ($pct > 0, em direção ao branco) ou escurece ($pct < 0) uma cor.
 *
 * @param string $hex #rrggbb.
 * @param int    $pct -100..100.
 * @return string
 */
function artemis_style_shade( $hex, $pct ) {
	$out = '#';
	foreach ( array( 1, 3, 5 ) as $i ) {
		$c = hexdec( substr( $hex, $i, 2 ) );
		if ( $pct > 0 ) {
			$c = $c + ( 255 - $c ) * $pct / 100;
		} else {
			$c = $c * ( 1 + $pct / 100 );
		}
		$out .= sprintf( '%02x', max( 0, min( 255, (int) round( $c ) ) ) );
	}
	return $out;
}

/**
 * Escolhe da tabela de frequência a cor mais recorrente aprovada pelo filtro.
 *
 * @param array    $freq      hex => contagem.
 * @param callable $accept    fn( array $hsl, string $hex, int $n ): bool.
 * @param int      $min_count Contagem mínima pra considerar.
 * @return string '' se nada passou.
 */
function artemis_style_pick( $freq, $accept, $min_count = 2 ) {
	arsort( $freq );
	foreach ( $freq as $hex => $n ) {
		if ( $n < $min_count ) {
			continue;
		}
		if ( call_user_func( $accept, artemis_style_hsl( $hex ), $hex, (int) $n ) ) {
			return $hex;
		}
	}
	return '';
}

/**
 * Extrai o primeiro nome de família utilizável de um stack CSS de font-family.
 * Descarta genéricos, var() e fontes de ícone.
 *
 * @param string $stack Ex.: "'Poppins', sans-serif".
 * @return string
 */
function artemis_style_clean_font( $stack ) {
	$stack = (string) $stack;
	if ( strpos( $stack, 'var(' ) !== false ) {
		return '';
	}
	$parts = explode( ',', $stack );
	$first = trim( $parts[0], " \t\n\r\"'" );
	if ( $first === '' ) {
		return '';
	}
	$generic = array( 'inherit', 'initial', 'unset', 'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy', 'system-ui', '-apple-system', 'blinkmacsystemfont', 'segoe ui', 'roboto' );
	if ( in_array( strtolower( $first ), $generic, true ) ) {
		return '';
	}
	if ( preg_match( '/icon|dashicon|awesome|eicons|woocommerce|swiper|star|genericon/i', $first ) ) {
		return '';
	}
	return $first;
}

/* ---------------------------------------------------------------------------
 * Fontes de estilo (cada uma só preenche o que ainda estiver vazio).
 * ------------------------------------------------------------------------- */

/**
 * 1) Paleta global do Elementor (kit ativo).
 *
 * No Elementor, "Accent" é a cor de ação (botões/links), "Primary" costuma
 * ser a cor de títulos e "Text" a do corpo — o mapeamento aqui segue isso.
 *
 * @param array $found Acumulador por referência.
 * @return bool true se achou algo.
 */
function artemis_style_from_elementor( &$found ) {
	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit_id ) {
		return false;
	}
	$s = get_post_meta( $kit_id, '_elementor_page_settings', true );
	if ( ! is_array( $s ) || ! $s ) {
		return false;
	}

	$hit   = false;
	$roles = array();
	foreach ( array( 'system_colors', 'custom_colors' ) as $key ) {
		if ( empty( $s[ $key ] ) || ! is_array( $s[ $key ] ) ) {
			continue;
		}
		foreach ( $s[ $key ] as $c ) {
			$hex = artemis_style_norm_hex( isset( $c['color'] ) ? $c['color'] : '' );
			if ( ! $hex ) {
				continue;
			}
			$hit = true;
			$id  = isset( $c['_id'] ) ? strtolower( (string) $c['_id'] ) : '';
			if ( $id !== '' && ! isset( $roles[ $id ] ) ) {
				$roles[ $id ] = $hex;
			}
			// Cores customizadas do kit pesam na frequência (são da marca).
			$found['freq'][ $hex ] = ( isset( $found['freq'][ $hex ] ) ? $found['freq'][ $hex ] : 0 ) + 3;
		}
	}

	if ( $found['primary'] === '' && isset( $roles['accent'] ) ) {
		$found['primary'] = $roles['accent'];
	}
	if ( $found['heading'] === '' && isset( $roles['primary'] ) ) {
		$found['heading'] = $roles['primary'];
	}
	if ( $found['body'] === '' && isset( $roles['text'] ) ) {
		$found['body'] = $roles['text'];
	}
	if ( $found['secondary'] === '' && isset( $roles['secondary'] ) ) {
		$found['secondary'] = $roles['secondary'];
	}

	if ( ! empty( $s['system_typography'] ) && is_array( $s['system_typography'] ) ) {
		foreach ( $s['system_typography'] as $t ) {
			$id  = isset( $t['_id'] ) ? strtolower( (string) $t['_id'] ) : '';
			$fam = artemis_style_clean_font( isset( $t['typography_font_family'] ) ? $t['typography_font_family'] : '' );
			if ( ! $fam ) {
				continue;
			}
			$hit = true;
			if ( $id === 'primary' && $found['font_heading'] === '' ) {
				$found['font_heading'] = $fam;
			}
			if ( $id === 'text' && $found['font_body'] === '' ) {
				$found['font_body'] = $fam;
			}
		}
	}

	return $hit;
}

/**
 * 2) Estilos globais do tema (theme.json / Global Styles).
 *
 * @param array $found Acumulador por referência.
 * @return bool true se achou algo.
 */
function artemis_style_from_global_styles( &$found ) {
	if ( ! function_exists( 'wp_get_global_settings' ) ) {
		return false;
	}

	$hit   = false;
	$slugs = array();

	$palette = wp_get_global_settings( array( 'color', 'palette' ) );
	foreach ( array( 'theme', 'custom' ) as $origin ) { // custom (usuário) sobrescreve theme
		if ( empty( $palette[ $origin ] ) || ! is_array( $palette[ $origin ] ) ) {
			continue;
		}
		foreach ( $palette[ $origin ] as $c ) {
			if ( empty( $c['slug'] ) || empty( $c['color'] ) ) {
				continue;
			}
			$hex = artemis_style_norm_hex( $c['color'] );
			if ( ! $hex ) {
				continue;
			}
			$hit = true;
			$slugs[ strtolower( (string) $c['slug'] ) ] = $hex;
			$found['freq'][ $hex ] = ( isset( $found['freq'][ $hex ] ) ? $found['freq'][ $hex ] : 0 ) + 2;
		}
	}

	// Resolve valores "var:preset|color|slug" / "var(--wp--preset--color--slug)".
	$resolve = function ( $v ) use ( $slugs ) {
		$v = (string) $v;
		if ( preg_match( '/var:preset\|color\|([a-z0-9-]+)/i', $v, $m ) || preg_match( '/--wp--preset--color--([a-z0-9-]+)/i', $v, $m ) ) {
			$slug = strtolower( $m[1] );
			return isset( $slugs[ $slug ] ) ? $slugs[ $slug ] : '';
		}
		return artemis_style_norm_hex( $v );
	};

	foreach ( array( 'primary', 'accent', 'accent-1', 'brand' ) as $slug ) {
		if ( $found['primary'] === '' && isset( $slugs[ $slug ] ) ) {
			$found['primary'] = $slugs[ $slug ];
		}
	}
	foreach ( array( 'contrast', 'foreground' ) as $slug ) {
		if ( $found['heading'] === '' && isset( $slugs[ $slug ] ) ) {
			$found['heading'] = $slugs[ $slug ];
		}
	}
	foreach ( array( 'base', 'background' ) as $slug ) {
		if ( $found['background'] === '' && isset( $slugs[ $slug ] ) ) {
			$found['background'] = $slugs[ $slug ];
		}
	}
	if ( $found['secondary'] === '' && isset( $slugs['secondary'] ) ) {
		$found['secondary'] = $slugs['secondary'];
	}

	if ( function_exists( 'wp_get_global_styles' ) ) {
		$gs = wp_get_global_styles( array( 'color' ) );
		if ( is_array( $gs ) ) {
			if ( $found['body'] === '' && ! empty( $gs['text'] ) ) {
				$hex = $resolve( $gs['text'] );
				if ( $hex ) {
					$found['body'] = $hex;
					$hit           = true;
				}
			}
			if ( $found['background'] === '' && ! empty( $gs['background'] ) ) {
				$hex = $resolve( $gs['background'] );
				if ( $hex ) {
					$found['background'] = $hex;
					$hit                 = true;
				}
			}
		}

		$body_font = wp_get_global_styles( array( 'typography', 'fontFamily' ) );
		if ( $found['font_body'] === '' && is_string( $body_font ) ) {
			$fam = artemis_style_clean_font( $body_font );
			if ( $fam ) {
				$found['font_body'] = $fam;
				$hit                = true;
			}
		}
		$head_font = wp_get_global_styles( array( 'elements', 'heading', 'typography', 'fontFamily' ) );
		if ( $found['font_heading'] === '' && is_string( $head_font ) ) {
			$fam = artemis_style_clean_font( $head_font );
			if ( $fam ) {
				$found['font_heading'] = $fam;
				$hit                   = true;
			}
		}
	}

	return $hit;
}

/**
 * 3) CSS público da home.
 *
 * Baixa a home do próprio site (request de loopback), coleta os stylesheets
 * do mesmo domínio (priorizando os kits do Elementor e o CSS do tema) e
 * extrai: variáveis CSS com papel declarado, frequência de cores e famílias
 * do Google Fonts / font-family.
 *
 * @param array $found Acumulador por referência.
 * @return bool true se achou algo.
 */
function artemis_style_from_frontend( &$found ) {
	$args = array(
		'timeout'    => 15,
		'sslverify'  => apply_filters( 'https_local_ssl_verify', false ),
		'user-agent' => 'ArtemisBlog/' . ARTEMIS_PLUGIN_VERSION . ' style-import; ' . home_url( '/' ),
	);

	$resp = wp_remote_get( home_url( '/' ), $args );
	if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
		return false;
	}
	$html = (string) wp_remote_retrieve_body( $resp );
	if ( $html === '' ) {
		return false;
	}

	// Google Fonts referenciados na página (1ª família = títulos, 2ª = corpo).
	if ( preg_match_all( '#https?://fonts\.googleapis\.com/css2?\?[^"\'\s>]+#i', $html, $m ) ) {
		$families = array();
		foreach ( $m[0] as $u ) {
			$u = html_entity_decode( $u );
			if ( preg_match_all( '/family=([^:&]+)/i', $u, $fm ) ) {
				foreach ( $fm[1] as $f ) {
					$f = trim( urldecode( str_replace( '+', ' ', $f ) ) );
					if ( $f !== '' ) {
						$families[] = $f;
					}
				}
			}
		}
		$families = array_values( array_unique( $families ) );
		if ( $families ) {
			if ( $found['font_heading'] === '' ) {
				$found['font_heading'] = $families[0];
			}
			if ( $found['font_body'] === '' ) {
				$found['font_body'] = isset( $families[1] ) ? $families[1] : $families[0];
			}
		}
	}

	$css = '';
	if ( preg_match_all( '#<style[^>]*>(.*?)</style>#si', $html, $m ) ) {
		$css .= implode( "\n", $m[1] );
	}

	// Stylesheets do mesmo domínio.
	$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
	$urls      = array();
	if ( preg_match_all( '#<link\b[^>]*>#i', $html, $lm ) ) {
		foreach ( $lm[0] as $tag ) {
			if ( ! preg_match( '/rel=["\'][^"\']*stylesheet/i', $tag ) || ! preg_match( '/href=["\']([^"\']+)["\']/i', $tag, $hm ) ) {
				continue;
			}
			$href = html_entity_decode( $hm[1] );
			if ( strpos( $href, '//' ) === 0 ) {
				$href = ( is_ssl() ? 'https:' : 'http:' ) . $href;
			} elseif ( strpos( $href, '/' ) === 0 ) {
				$href = home_url( $href );
			}
			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( ! $host || $host !== $home_host ) {
				continue;
			}
			if ( preg_match( '#wp-includes/|admin-bar|dashicons#i', $href ) ) {
				continue;
			}
			$urls[] = $href;
		}
	}

	// Os kits do Elementor e o CSS do tema carregam a identidade — vêm primeiro.
	$score = function ( $u ) {
		if ( preg_match( '#uploads/elementor/css/(global|post)#i', $u ) ) {
			return 0;
		}
		if ( strpos( $u, '/themes/' ) !== false ) {
			return 1;
		}
		return 2;
	};
	$urls = array_values( array_unique( $urls ) );
	usort( $urls, function ( $a, $b ) use ( $score ) {
		return $score( $a ) - $score( $b );
	} );
	$urls = array_slice( $urls, 0, 8 );

	foreach ( $urls as $u ) {
		$r = wp_remote_get( $u, $args );
		if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
			continue;
		}
		$css .= "\n" . substr( (string) wp_remote_retrieve_body( $r ), 0, 400000 );
		if ( strlen( $css ) > 2000000 ) {
			break;
		}
	}

	if ( trim( $css ) === '' ) {
		return false;
	}

	// Variáveis CSS com papel declarado (Elementor imprime a paleta global aqui).
	$role_vars = array(
		'primary'    => array( 'e-global-color-accent', 'wp--preset--color--primary', 'color-primary', 'primary-color', 'primary', 'accent-color', 'accent', 'brand-color', 'main-color' ),
		'heading'    => array( 'e-global-color-primary', 'heading-color', 'color-heading', 'title-color' ),
		'body'       => array( 'e-global-color-text', 'text-color', 'color-text', 'body-color' ),
		'secondary'  => array( 'e-global-color-secondary', 'wp--preset--color--secondary', 'secondary-color', 'color-secondary' ),
		'background' => array( 'wp--preset--color--background', 'background-color', 'body-bg', 'bg-color' ),
	);
	foreach ( $role_vars as $role => $names ) {
		if ( $found[ $role ] !== '' ) {
			continue;
		}
		foreach ( $names as $name ) {
			if ( preg_match( '/--' . preg_quote( $name, '/' ) . '\s*:\s*([^;}]+)[;}]/i', $css, $vm ) ) {
				$hex = artemis_style_norm_hex( trim( $vm[1] ) );
				if ( $hex ) {
					$found[ $role ] = $hex;
					break;
				}
			}
		}
	}

	// Frequência de cores (hex + rgb sólidos).
	if ( preg_match_all( '/#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $css, $cm ) ) {
		foreach ( $cm[0] as $raw ) {
			$hex = artemis_style_norm_hex( $raw );
			if ( $hex ) {
				$found['freq'][ $hex ] = ( isset( $found['freq'][ $hex ] ) ? $found['freq'][ $hex ] : 0 ) + 1;
			}
		}
	}
	if ( preg_match_all( '/rgba?\([^)]*\)/', $css, $rm ) ) {
		foreach ( $rm[0] as $raw ) {
			$hex = artemis_style_norm_hex( $raw );
			if ( $hex ) {
				$found['freq'][ $hex ] = ( isset( $found['freq'][ $hex ] ) ? $found['freq'][ $hex ] : 0 ) + 1;
			}
		}
	}

	// Famílias declaradas no CSS (fallback quando não há Google Fonts na página).
	if ( preg_match_all( '/font-family\s*:\s*([^;}]+)/i', $css, $fm ) ) {
		foreach ( $fm[1] as $stack ) {
			$fam = artemis_style_clean_font( $stack );
			if ( $fam ) {
				$found['font_freq'][ $fam ] = ( isset( $found['font_freq'][ $fam ] ) ? $found['font_freq'][ $fam ] : 0 ) + 1;
			}
		}
	}

	return true;
}

/* ---------------------------------------------------------------------------
 * Montagem da paleta.
 * ------------------------------------------------------------------------- */

/**
 * Transforma o material coletado numa paleta coerente pro blog.
 *
 * @param array $found Cores/fontes coletadas.
 * @return array Chaves de artemis_settings (vazio se nada utilizável).
 */
function artemis_style_build_settings( $found ) {
	$freq = $found['freq'];

	// Primária (cor de ação): papel declarado > cor saturada mais frequente.
	$brand = $found['primary'];
	if ( $brand === '' && $found['secondary'] !== '' ) {
		$hsl = artemis_style_hsl( $found['secondary'] );
		if ( $hsl[1] >= 0.25 && $hsl[2] > 0.12 && $hsl[2] < 0.88 ) {
			$brand = $found['secondary'];
		}
	}
	if ( $brand === '' ) {
		$brand = artemis_style_pick( $freq, function ( $hsl ) {
			return $hsl[1] >= 0.25 && $hsl[2] > 0.12 && $hsl[2] < 0.88;
		} );
	}
	if ( $brand === '' ) {
		return array(); // sem cor de marca não dá pra montar nada coerente
	}

	$brand_hsl = artemis_style_hsl( $brand );
	$dark_site = $found['background'] !== '' && artemis_style_hsl( $found['background'] )[2] < 0.35;

	$set = array(
		'artemis_color_primary'       => $brand,
		'artemis_color_primary_hover' => $brand_hsl[2] > 0.25 ? artemis_style_shade( $brand, -14 ) : artemis_style_shade( $brand, 14 ),
	);

	if ( $dark_site ) {
		$bg = $found['background'];

		$set['artemis_color_bg']          = $bg;
		$set['artemis_color_bg_alt']      = artemis_style_shade( $bg, -35 );
		$set['artemis_color_border']      = artemis_style_shade( $bg, 18 );
		$set['artemis_color_text']        = '#FFFFFF';
		$set['artemis_color_body']        = '#CBD5E1';
		$set['artemis_color_muted']       = '#94A3B8';
		$set['artemis_color_header_bg']   = artemis_style_shade( $bg, -35 );
		$set['artemis_color_header_text'] = '#FFFFFF';
		$footer_bg                        = artemis_style_shade( $bg, -55 );
	} else {
		// Títulos: papel declarado (se escuro o bastante) > neutro mais escuro do CSS.
		$text = '';
		if ( $found['heading'] !== '' && artemis_style_hsl( $found['heading'] )[2] < 0.4 ) {
			$text = $found['heading'];
		}
		if ( $text === '' ) {
			$text = artemis_style_pick( $freq, function ( $hsl ) {
				return $hsl[2] < 0.25 && $hsl[2] > 0.03;
			} );
		}
		if ( $text === '' ) {
			$text = '#0F172A';
		}

		$body = '';
		if ( $found['body'] !== '' ) {
			$l = artemis_style_hsl( $found['body'] )[2];
			if ( $l > 0.12 && $l < 0.55 ) {
				$body = $found['body'];
			}
		}
		if ( $body === '' ) {
			$body = artemis_style_shade( $text, 22 );
		}

		$muted = artemis_style_pick( $freq, function ( $hsl ) {
			return $hsl[1] < 0.2 && $hsl[2] > 0.35 && $hsl[2] < 0.65;
		}, 3 );

		$bg_alt = '';
		if ( $found['background'] !== '' && artemis_style_hsl( $found['background'] )[2] > 0.85 && strtolower( $found['background'] ) !== '#ffffff' ) {
			$bg_alt = $found['background'];
		}
		if ( $bg_alt === '' ) {
			$bg_alt = artemis_style_pick( $freq, function ( $hsl, $hex ) {
				return $hsl[2] > 0.9 && $hsl[2] < 0.985 && strtolower( $hex ) !== '#ffffff';
			}, 3 );
		}

		$border = artemis_style_pick( $freq, function ( $hsl ) {
			return $hsl[1] < 0.25 && $hsl[2] > 0.8 && $hsl[2] < 0.94;
		}, 3 );

		$set['artemis_color_bg']          = '#FFFFFF';
		$set['artemis_color_bg_alt']      = $bg_alt ? $bg_alt : '#F8FAFC';
		$set['artemis_color_border']      = $border ? $border : '#E2E8F0';
		$set['artemis_color_text']        = $text;
		$set['artemis_color_body']        = $body;
		$set['artemis_color_muted']       = $muted ? $muted : '#64748B';
		$set['artemis_color_header_bg']   = '#FFFFFF';
		$set['artemis_color_header_text'] = $text;

		// Rodapé precisa ser ESCURO (é também o fundo dos heros — ver options.php).
		$footer_bg = '';
		foreach ( array( $found['secondary'], $found['heading'] ) as $cand ) {
			if ( $cand !== '' && artemis_style_hsl( $cand )[2] < 0.28 ) {
				$footer_bg = $cand;
				break;
			}
		}
		if ( $footer_bg === '' ) {
			$footer_bg = artemis_style_pick( $freq, function ( $hsl ) {
				return $hsl[2] < 0.22 && $hsl[2] > 0.02;
			}, 3 );
		}
		if ( $footer_bg === '' ) {
			$footer_bg = $brand_hsl[2] < 0.35 ? artemis_style_shade( $brand, -45 ) : '#0F172A';
		}
	}

	$set['artemis_color_footer_bg']       = $footer_bg;
	$set['artemis_color_footer_text']     = '#FFFFFF';
	$set['artemis_color_cta_header']      = $brand;
	$set['artemis_color_cta_text']        = artemis_style_luminance( $brand ) > 0.35 ? '#0F172A' : '#FFFFFF';
	$set['artemis_color_cta_single_bg']   = $footer_bg;
	$set['artemis_color_cta_single_text'] = '#FFFFFF';

	// Fontes: papel declarado > mais frequente no CSS.
	$font_heading = $found['font_heading'];
	$font_body    = $found['font_body'];
	if ( $font_body === '' && ! empty( $found['font_freq'] ) ) {
		arsort( $found['font_freq'] );
		$font_body = (string) key( $found['font_freq'] );
	}
	if ( $font_heading === '' ) {
		$font_heading = $font_body;
	}
	if ( $font_heading !== '' ) {
		$set['artemis_font_heading'] = $font_heading;
	}
	if ( $font_body !== '' ) {
		$set['artemis_font_body'] = $font_body;
	}

	return $set;
}

/**
 * Roda a importação completa: coleta das três fontes e monta a paleta.
 *
 * @return array { settings: array, found: array, sources: string[] }
 */
function artemis_style_import() {
	$found = array(
		'primary'      => '',
		'secondary'    => '',
		'heading'      => '',
		'body'         => '',
		'background'   => '',
		'font_heading' => '',
		'font_body'    => '',
		'freq'         => array(),
		'font_freq'    => array(),
	);

	$sources = array();
	if ( artemis_style_from_elementor( $found ) ) {
		$sources[] = __( 'paleta global do Elementor', 'artemis-blog' );
	}
	if ( artemis_style_from_global_styles( $found ) ) {
		$sources[] = __( 'estilos globais do tema (theme.json)', 'artemis-blog' );
	}
	if ( artemis_style_from_frontend( $found ) ) {
		$sources[] = __( 'CSS público da página inicial', 'artemis-blog' );
	}

	$settings = artemis_style_build_settings( $found );

	/**
	 * Permite ajustar o resultado da importação antes de salvar.
	 *
	 * @param array $settings Chaves de artemis_settings prontas pra gravar.
	 * @param array $found    Material bruto coletado.
	 */
	$settings = apply_filters( 'artemis_style_import_settings', $settings, $found );

	return array(
		'settings' => $settings,
		'found'    => $found,
		'sources'  => $sources,
	);
}

/* ---------------------------------------------------------------------------
 * Handler + UI no painel.
 * ------------------------------------------------------------------------- */

/**
 * Botão "Importar estilo do site": roda a importação, grava por cima das
 * configurações atuais (só chaves de cor/fonte) e volta pro painel com aviso.
 */
function artemis_handle_style_import() {
	if ( ! isset( $_POST['artemis_apply_site_styles'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'artemis_apply_site_styles' ) ) {
		return;
	}

	$res = artemis_style_import();
	$ok  = ! empty( $res['settings'] );

	if ( $ok ) {
		$current = wp_parse_args( get_option( 'artemis_settings', array() ), artemis_default_settings() );
		update_option( 'artemis_settings', array_merge( $current, $res['settings'] ) );
	}

	set_transient( 'artemis_style_import_report', $res, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( array( 'page' => 'artemis-blog', 'artemis_styles' => $ok ? '1' : '0' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'artemis_handle_style_import' );

/**
 * Caixa com o botão de importação (renderizada no topo do painel).
 */
function artemis_style_import_form() {
	?>
	<form method="post" style="margin:16px 0;padding:14px 18px;border:1px solid #c3c4c7;border-left:4px solid #7c3aed;background:#fff;">
		<?php wp_nonce_field( 'artemis_apply_site_styles' ); ?>
		<p style="margin:0 0 10px;"><strong><?php esc_html_e( 'Importar estilo do site', 'artemis-blog' ); ?></strong> — <?php esc_html_e( 'lê a identidade visual deste site (paleta do Elementor, estilos globais do tema e CSS da página inicial) e preenche as cores e fontes do blog automaticamente. Não mexe em textos de CTA, logos nem na página do blog. Revise e ajuste abaixo depois de importar.', 'artemis-blog' ); ?></p>
		<button type="submit" name="artemis_apply_site_styles" value="1" class="button button-primary">🎨 <?php esc_html_e( 'Importar estilo do site', 'artemis-blog' ); ?></button>
	</form>
	<?php
}

/**
 * Aviso pós-importação, com as amostras do que foi capturado.
 */
function artemis_style_import_notice() {
	if ( ! isset( $_GET['artemis_styles'] ) ) {
		return;
	}
	$res = get_transient( 'artemis_style_import_report' );
	delete_transient( 'artemis_style_import_report' );

	if ( '1' !== $_GET['artemis_styles'] || empty( $res['settings'] ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' .
			esc_html__( 'Não foi possível detectar a identidade visual do site (nenhuma cor de marca encontrada no Elementor, no tema ou no CSS da home). Configure as cores manualmente abaixo — ou verifique se a página inicial do site está acessível.', 'artemis-blog' ) .
			'</p></div>';
		return;
	}

	$set      = $res['settings'];
	$swatches = '';
	$labels   = array(
		'artemis_color_primary'   => __( 'Primária', 'artemis-blog' ),
		'artemis_color_text'      => __( 'Títulos', 'artemis-blog' ),
		'artemis_color_body'      => __( 'Texto', 'artemis-blog' ),
		'artemis_color_bg_alt'    => __( 'Fundo', 'artemis-blog' ),
		'artemis_color_footer_bg' => __( 'Rodapé', 'artemis-blog' ),
	);
	foreach ( $labels as $key => $label ) {
		if ( empty( $set[ $key ] ) ) {
			continue;
		}
		$swatches .= sprintf(
			'<span style="display:inline-flex;align-items:center;gap:6px;margin-right:14px;"><span style="width:18px;height:18px;border-radius:4px;border:1px solid #c3c4c7;background:%1$s;display:inline-block;"></span>%2$s <code>%1$s</code></span>',
			esc_attr( $set[ $key ] ),
			esc_html( $label )
		);
	}

	$fonts = array();
	if ( ! empty( $set['artemis_font_heading'] ) ) {
		$fonts[] = sprintf( __( 'títulos: %s', 'artemis-blog' ), $set['artemis_font_heading'] );
	}
	if ( ! empty( $set['artemis_font_body'] ) ) {
		$fonts[] = sprintf( __( 'corpo: %s', 'artemis-blog' ), $set['artemis_font_body'] );
	}

	echo '<div class="notice notice-success is-dismissible">';
	echo '<p><strong>' . esc_html__( 'Estilo do site importado!', 'artemis-blog' ) . '</strong> ';
	if ( ! empty( $res['sources'] ) ) {
		echo esc_html( sprintf( __( 'Fontes lidas: %s.', 'artemis-blog' ), implode( ', ', $res['sources'] ) ) ) . ' ';
	}
	echo esc_html__( 'Revise abaixo e ajuste o que quiser.', 'artemis-blog' ) . '</p>';
	if ( $swatches ) {
		echo '<p>' . $swatches . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- montado acima com esc_*
	}
	if ( $fonts ) {
		echo '<p>' . esc_html( sprintf( __( 'Fontes: %s.', 'artemis-blog' ), implode( ' · ', $fonts ) ) ) . '</p>';
	}
	echo '</div>';
}
