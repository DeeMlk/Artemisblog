<?php
/**
 * Importação de conteúdo de exemplo (opcional, manual).
 *
 * Diferente do tema, NÃO roda sozinho na ativação — num site em produção como o
 * anfitrião, ninguém quer 8 posts fantasmas aparecendo. Fica só o botão em
 * "Artemis Blog → Conteúdo de exemplo". Nunca duplica (pula slugs existentes).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARTEMIS_DEMO_DIR', ARTEMIS_PLUGIN_DIR . 'inc/demo' );

/**
 * Importa o conteúdo de exemplo a partir do WXR embutido.
 *
 * @return array Resumo: created, skipped, errors.
 */
function artemis_demo_import() {
	$result = array( 'created' => 0, 'skipped' => 0, 'errors' => array() );

	$xml_file = ARTEMIS_DEMO_DIR . '/content.xml';
	if ( ! file_exists( $xml_file ) ) {
		$result['errors'][] = 'content.xml não encontrado.';
		return $result;
	}

	$xml = simplexml_load_file( $xml_file );
	if ( ! $xml || ! isset( $xml->channel ) ) {
		$result['errors'][] = 'Falha ao ler o XML.';
		return $result;
	}

	$ns_wp      = 'http://wordpress.org/export/1.2/';
	$ns_content = 'http://purl.org/rss/1.0/modules/content/';
	$ns_excerpt = 'http://wordpress.org/export/1.2/excerpt/';

	// 1) Mapa attachment_id (do XML) -> URL da imagem.
	$attach_urls = array();
	foreach ( $xml->channel->item as $item ) {
		$wp = $item->children( $ns_wp );
		if ( (string) $wp->post_type === 'attachment' ) {
			$attach_urls[ (string) $wp->post_id ] = (string) $wp->attachment_url;
		}
	}

	// 2) Cria os posts.
	foreach ( $xml->channel->item as $item ) {
		$wp = $item->children( $ns_wp );
		if ( (string) $wp->post_type !== 'post' || (string) $wp->status !== 'publish' ) {
			continue;
		}

		$slug = (string) $wp->post_name;
		if ( $slug && get_page_by_path( $slug, OBJECT, 'post' ) ) {
			$result['skipped']++;
			continue;
		}

		$title   = (string) $item->title;
		$content = (string) $item->children( $ns_content )->encoded;
		$excerpt = (string) $item->children( $ns_excerpt )->encoded;
		$date    = (string) $wp->post_date;

		// Categorias (cria se não existir).
		$cat_ids = array();
		foreach ( $item->category as $cat ) {
			if ( (string) $cat['domain'] !== 'category' ) {
				continue;
			}
			$name     = (string) $cat;
			$existing = term_exists( $name, 'category' );
			if ( ! $existing ) {
				$existing = wp_insert_term( $name, 'category' );
			}
			if ( ! is_wp_error( $existing ) ) {
				$cat_ids[] = (int) $existing['term_id'];
			}
		}

		// _thumbnail_id (referência do XML).
		$thumb_xml_id = '';
		foreach ( $wp->postmeta as $pm ) {
			if ( (string) $pm->meta_key === '_thumbnail_id' ) {
				$thumb_xml_id = (string) $pm->meta_value;
			}
		}

		$post_id = wp_insert_post( array(
			'post_title'    => $title,
			'post_name'     => $slug,
			'post_content'  => $content,
			'post_excerpt'  => $excerpt,
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_date'     => $date ?: current_time( 'mysql' ),
			'post_category' => $cat_ids,
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$result['errors'][] = 'Falha ao criar: ' . $title;
			continue;
		}

		// Imagem destacada: resolve a URL pelo id do XML e importa o arquivo local.
		if ( $thumb_xml_id && isset( $attach_urls[ $thumb_xml_id ] ) ) {
			$basename   = basename( $attach_urls[ $thumb_xml_id ] );
			$local_file = ARTEMIS_DEMO_DIR . '/images/' . $basename;
			if ( file_exists( $local_file ) ) {
				$att_id = artemis_demo_sideload_image( $local_file, $post_id, $title );
				if ( $att_id ) {
					set_post_thumbnail( $post_id, $att_id );
				}
			}
		}

		$result['created']++;
	}

	return $result;
}

/**
 * Copia uma imagem local pra biblioteca de mídia e devolve o attachment ID.
 *
 * @param string $file    Caminho absoluto do arquivo no plugin.
 * @param int    $post_id Post pai.
 * @param string $title   Título pra alt/legenda.
 * @return int|false
 */
function artemis_demo_sideload_image( $file, $post_id, $title = '' ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload   = wp_upload_dir();
	$filename = wp_unique_filename( $upload['path'], basename( $file ) );
	$dest     = trailingslashit( $upload['path'] ) . $filename;

	if ( ! copy( $file, $dest ) ) {
		return false;
	}

	$filetype   = wp_check_filetype( $filename, null );
	$attachment = array(
		'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/webp',
		'post_title'     => $title ? sanitize_text_field( $title ) : sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$att_id = wp_insert_attachment( $attachment, $dest, $post_id );
	if ( is_wp_error( $att_id ) || ! $att_id ) {
		return false;
	}

	$meta = wp_generate_attachment_metadata( $att_id, $dest );
	wp_update_attachment_metadata( $att_id, $meta );

	if ( $title ) {
		update_post_meta( $att_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
	}

	return $att_id;
}

/**
 * Semeia o conteúdo de exemplo uma única vez (flag artemis_demo_seeded).
 * Chamado na ativação do plugin. Nunca duplica: pula slugs já existentes.
 */
function artemis_demo_maybe_seed() {
	if ( get_option( 'artemis_demo_seeded' ) ) {
		return;
	}
	$result = artemis_demo_import();
	update_option( 'artemis_demo_seeded', 1 );
	if ( ! empty( $result['created'] ) ) {
		flush_rewrite_rules();
	}
}

/**
 * Submenu "Conteúdo de exemplo" sob Artemis Blog.
 */
function artemis_demo_tools_page() {
	add_submenu_page(
		'artemis-blog',
		__( 'Conteúdo de exemplo', 'artemis-blog' ),
		__( 'Conteúdo de exemplo', 'artemis-blog' ),
		'manage_options',
		'artemis-demo-content',
		'artemis_demo_tools_render'
	);
}
// Prioridade 20: registra o submenu DEPOIS do menu-pai "Artemis Blog" (prioridade 10),
// senão vira o 1º subitem e quebra o link do menu-pai (404 em /wp-admin/artemis-demo-content).
add_action( 'admin_menu', 'artemis_demo_tools_page', 20 );

/**
 * Render da página + handler do botão.
 */
function artemis_demo_tools_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	if ( isset( $_POST['artemis_demo_run'] ) && check_admin_referer( 'artemis_demo_import' ) ) {
		$res = artemis_demo_import();
		flush_rewrite_rules();
		$notice = sprintf(
			/* translators: 1: criados, 2: pulados */
			__( '%1$d post(s) criado(s), %2$d já existiam (pulados).', 'artemis-blog' ),
			$res['created'],
			$res['skipped']
		);
		if ( ! empty( $res['errors'] ) ) {
			$notice .= ' ' . __( 'Erros:', 'artemis-blog' ) . ' ' . implode( '; ', $res['errors'] );
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Conteúdo de exemplo Artemis', 'artemis-blog' ); ?></h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Importa os posts de exemplo (com imagens) embutidos no plugin. Posts com o mesmo slug não são duplicados. Útil para testar o layout antes de publicar o conteúdo real.', 'artemis-blog' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'artemis_demo_import' ); ?>
			<p>
				<button type="submit" name="artemis_demo_run" class="button button-primary">
					<?php esc_html_e( 'Importar conteúdo de exemplo agora', 'artemis-blog' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}
