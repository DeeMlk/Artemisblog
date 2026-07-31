<?php
/**
 * Extrator de strings traduzíveis → languages/artemis-blog.pot
 *
 * Existe porque o ambiente de desenvolvimento não tem wp-cli nem xgettext, e o
 * header do plugin promete "Domain Path: /languages" desde sempre — o .pot
 * nunca tinha sido gerado.
 *
 * Usa token_get_all() em vez de regex de propósito: várias strings do painel têm
 * parênteses, aspas e vírgulas no meio (ex.: "Ex.: https://wa.me/5511999999999 —
 * se preencher, ..."), e regex quebraria justamente nelas.
 *
 * Uso (a partir da raiz do plugin):
 *   php tools/make-pot.php
 *   php tools/make-pot.php artemis-convert languages/artemis-convert.pot
 *
 * Limitação conhecida: só extrai chamadas cujos argumentos são literais de
 * string. Chamada com variável (__( $x, 'dominio' )) é ignorada — como no
 * xgettext, é o comportamento correto: não há o que traduzir em tempo de build.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$domain = isset( $argv[1] ) ? $argv[1] : 'artemis-blog';
$out    = isset( $argv[2] ) ? $argv[2] : 'languages/artemis-blog.pot';
$root   = dirname( __DIR__ );

/**
 * Funções de tradução e a posição (0-indexed) de cada argumento que interessa.
 * 'domain' diz em qual argumento mora o text domain — é o filtro que separa as
 * strings do plugin das do subplugin embutido.
 */
$functions = array(
	'__'              => array( 'single' => 0, 'domain' => 1 ),
	'_e'              => array( 'single' => 0, 'domain' => 1 ),
	'esc_html__'      => array( 'single' => 0, 'domain' => 1 ),
	'esc_html_e'      => array( 'single' => 0, 'domain' => 1 ),
	'esc_attr__'      => array( 'single' => 0, 'domain' => 1 ),
	'esc_attr_e'      => array( 'single' => 0, 'domain' => 1 ),
	'_x'              => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'_ex'             => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'esc_html_x'      => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'esc_attr_x'      => array( 'single' => 0, 'context' => 1, 'domain' => 2 ),
	'_n'              => array( 'single' => 0, 'plural' => 1, 'domain' => 3 ),
	'_nx'             => array( 'single' => 0, 'plural' => 1, 'context' => 3, 'domain' => 4 ),
	'_n_noop'         => array( 'single' => 0, 'plural' => 1, 'domain' => 2 ),
	'_nx_noop'        => array( 'single' => 0, 'plural' => 1, 'context' => 2, 'domain' => 3 ),
);

/**
 * Lista os .php do plugin, pulando .git e o subplugin quando ele não é o alvo.
 *
 * @param string $dir
 * @param string $domain
 * @return array
 */
function artemis_pot_files( $dir, $domain ) {
	$files    = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		$path = $file->getPathname();

		if ( substr( $path, -4 ) !== '.php' ) {
			continue;
		}
		if ( strpos( $path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR ) !== false ) {
			continue;
		}
		// tools/ são scripts de build, não código distribuído.
		if ( strpos( $path, DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR ) !== false ) {
			continue;
		}
		$files[] = $path;
	}

	sort( $files );
	return $files;
}

/**
 * Extrai as chamadas de tradução de um arquivo.
 *
 * @param string $path
 * @param array  $functions
 * @param string $domain
 * @return array
 */
function artemis_pot_scan( $path, $functions, $domain ) {
	$found  = array();
	$tokens = token_get_all( file_get_contents( $path ) );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$token = $tokens[ $i ];

		if ( ! is_array( $token ) || $token[0] !== T_STRING || ! isset( $functions[ $token[1] ] ) ) {
			continue;
		}

		// Descarta métodos/propriedades ($obj->__(), Classe::__()) — não são as
		// funções globais do WordPress.
		$prev = $i > 0 ? $tokens[ $i - 1 ] : null;
		if ( is_array( $prev ) && in_array( $prev[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
			continue;
		}

		$spec = $functions[ $token[1] ];
		$line = $token[2];

		// Avança até o "(" de abertura.
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			$j++;
		}
		if ( $j >= $count || $tokens[ $j ] !== '(' ) {
			continue;
		}

		// Coleta os argumentos de primeiro nível. Um argumento só entra como
		// literal se for exatamente uma string constante; qualquer concatenação
		// ou variável vira null e a chamada é descartada adiante.
		$args    = array();
		$current = array( 'literal' => null, 'tokens' => 0 );
		$depth   = 0;

		for ( $k = $j; $k < $count; $k++ ) {
			$t = $tokens[ $k ];

			if ( $t === '(' ) {
				$depth++;
				if ( $depth === 1 ) {
					continue;
				}
			} elseif ( $t === ')' ) {
				$depth--;
				if ( $depth === 0 ) {
					$args[] = $current;
					break;
				}
			}

			if ( $depth === 1 && $t === ',' ) {
				$args[]  = $current;
				$current = array( 'literal' => null, 'tokens' => 0 );
				continue;
			}

			if ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			$current['tokens']++;
			if ( is_array( $t ) && $t[0] === T_CONSTANT_ENCAPSED_STRING && $current['tokens'] === 1 ) {
				$current['literal'] = artemis_pot_unquote( $t[1] );
			} elseif ( $current['tokens'] > 1 ) {
				$current['literal'] = null; // concatenação, sprintf, variável...
			}
		}

		$literal = function ( $index ) use ( $args ) {
			return ( isset( $args[ $index ] ) && $args[ $index ]['tokens'] === 1 ) ? $args[ $index ]['literal'] : null;
		};

		// Filtra pelo text domain — é o que separa artemis-blog de artemis-convert.
		if ( $literal( $spec['domain'] ) !== $domain ) {
			continue;
		}

		$single = $literal( $spec['single'] );
		if ( $single === null || $single === '' ) {
			continue;
		}

		$entry = array(
			'single'  => $single,
			'plural'  => isset( $spec['plural'] ) ? $literal( $spec['plural'] ) : null,
			'context' => isset( $spec['context'] ) ? $literal( $spec['context'] ) : null,
			'line'    => $line,
		);

		$found[] = $entry;
	}

	return $found;
}

/**
 * Converte o literal PHP (com aspas) no texto real.
 *
 * @param string $raw
 * @return string
 */
function artemis_pot_unquote( $raw ) {
	$quote = substr( $raw, 0, 1 );
	$body  = substr( $raw, 1, -1 );

	if ( $quote === "'" ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $body );
	}

	// Aspas duplas: resolve os escapes que aparecem em string traduzível.
	return str_replace(
		array( '\\"', '\\n', '\\t', '\\r', '\\$', '\\\\' ),
		array( '"', "\n", "\t", "\r", '$', '\\' ),
		$body
	);
}

/**
 * Escapa uma string para o formato msgid do gettext.
 *
 * @param string $text
 * @return string
 */
function artemis_pot_escape( $text ) {
	$text = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $text );
	$text = str_replace( array( "\t", "\r" ), array( '\\t', '\\r' ), $text );
	return str_replace( "\n", '\\n', $text );
}

/* --------------------------------------------------------------------------
 * Execução.
 * ----------------------------------------------------------------------- */

$entries = array();
$files   = artemis_pot_files( $root, $domain );

foreach ( $files as $path ) {
	$relative = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );
	$relative = str_replace( DIRECTORY_SEPARATOR, '/', $relative );

	foreach ( artemis_pot_scan( $path, $functions, $domain ) as $entry ) {
		// Chave = contexto + singular + plural: é o que o gettext considera
		// uma entrada única (mesmo texto em contextos diferentes = 2 entradas).
		$key = $entry['context'] . "\x04" . $entry['single'] . "\x04" . $entry['plural'];

		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = array(
				'single'    => $entry['single'],
				'plural'    => $entry['plural'],
				'context'   => $entry['context'],
				'locations' => array(),
			);
		}
		$entries[ $key ]['locations'][] = $relative . ':' . $entry['line'];
	}
}

ksort( $entries );

$version = '1.4.0';
if ( preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', file_get_contents( $root . '/artemis-blog.php' ), $m ) ) {
	$version = trim( $m[1] );
}

$pot   = array();
$pot[] = '# Copyright (C) ' . gmdate( 'Y' ) . ' Artemis';
$pot[] = '# This file is distributed under the GPL v2 or later.';
$pot[] = '#';
$pot[] = '# Gerado por tools/make-pot.php — não edite à mão.';
$pot[] = 'msgid ""';
$pot[] = 'msgstr ""';
$pot[] = '"Project-Id-Version: Artemis Blog ' . $version . '\n"';
$pot[] = '"Report-Msgid-Bugs-To: https://artemis.com.br\n"';
$pot[] = '"MIME-Version: 1.0\n"';
$pot[] = '"Content-Type: text/plain; charset=UTF-8\n"';
$pot[] = '"Content-Transfer-Encoding: 8bit\n"';
$pot[] = '"POT-Creation-Date: ' . gmdate( 'Y-m-d\TH:i:s\Z' ) . '\n"';
$pot[] = '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"';
$pot[] = '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"';
$pot[] = '"Language-Team: LANGUAGE <LL@li.org>\n"';
$pot[] = '"Plural-Forms: nplurals=2; plural=(n > 1);\n"';
$pot[] = '"X-Domain: ' . $domain . '\n"';
$pot[] = '';

foreach ( $entries as $entry ) {
	foreach ( $entry['locations'] as $location ) {
		$pot[] = '#: ' . $location;
	}
	if ( $entry['context'] !== null ) {
		$pot[] = 'msgctxt "' . artemis_pot_escape( $entry['context'] ) . '"';
	}
	$pot[] = 'msgid "' . artemis_pot_escape( $entry['single'] ) . '"';
	if ( $entry['plural'] !== null ) {
		$pot[] = 'msgid_plural "' . artemis_pot_escape( $entry['plural'] ) . '"';
		$pot[] = 'msgstr[0] ""';
		$pot[] = 'msgstr[1] ""';
	} else {
		$pot[] = 'msgstr ""';
	}
	$pot[] = '';
}

$target = $root . '/' . ltrim( $out, '/' );
if ( ! is_dir( dirname( $target ) ) ) {
	mkdir( dirname( $target ), 0755, true );
}
file_put_contents( $target, implode( "\n", $pot ) );

printf(
	"%s: %d strings de %d arquivos (domain: %s)\n",
	$out,
	count( $entries ),
	count( $files ),
	$domain
);
