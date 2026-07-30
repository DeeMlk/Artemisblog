<?php
/**
 * Painel de ajustes do plugin ("Artemis Blog" no menu do admin).
 *
 * Recria, via Settings API, o que o Customizer do tema oferecia. Tudo é salvo
 * no único option array `artemis_settings`. O seletor de "Página do blog" grava
 * na opção nativa page_for_posts (é ela que dispara is_home() → blog-home.php).
 *
 * O painel expõe toda a identidade do blog — as cores de
 * cabeçalho e rodapé também, já que o blog usa header e rodapé próprios (modo canvas).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Item de menu.
 */
function artemis_admin_menu() {
	add_menu_page(
		__( 'Artemis Blog', 'artemis-blog' ),
		__( 'Artemis Blog', 'artemis-blog' ),
		'manage_options',
		'artemis-blog',
		'artemis_settings_page_render',
		'dashicons-welcome-write-blog',
		59
	);

	// Re-registra a própria página de ajustes como 1º subitem (mesmo slug do menu).
	// add_menu_page() sozinho NÃO cria um subitem próprio; sem isto, o WordPress aponta
	// o menu-pai para o 1º submenu que aparecer (ex.: "Conteúdo de exemplo"), gerando
	// link quebrado (/wp-admin/artemis-demo-content → 404).
	add_submenu_page(
		'artemis-blog',
		__( 'Ajustes', 'artemis-blog' ),
		__( 'Ajustes', 'artemis-blog' ),
		'manage_options',
		'artemis-blog',
		'artemis_settings_page_render'
	);
}
add_action( 'admin_menu', 'artemis_admin_menu' );

/**
 * Registro da opção + sanitização.
 */
function artemis_register_settings() {
	register_setting( 'artemis_settings_group', 'artemis_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'artemis_sanitize_settings',
		'default'           => artemis_default_settings(),
	) );
}
add_action( 'admin_init', 'artemis_register_settings' );

/**
 * Botão "Aplicar tema dark": joga a paleta da predefinição por cima das cores
 * atuais e volta pro painel com aviso.
 *
 * Só cores e layout — nenhum texto, link ou mídia é tocado (ver o porquê no
 * docblock de artemis_config_preset()).
 */
function artemis_handle_preset() {
	if ( ! isset( $_POST['artemis_apply_preset'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'artemis_apply_preset' ) ) {
		return;
	}
	$current = wp_parse_args( get_option( 'artemis_settings', array() ), artemis_default_settings() );
	update_option( 'artemis_settings', array_merge( $current, artemis_config_preset() ) );
	wp_safe_redirect( add_query_arg( array( 'page' => 'artemis-blog', 'artemis_preset' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'artemis_handle_preset' );

/**
 * Sanitiza todo o formulário e aplica o efeito colateral da página do blog.
 *
 * @param array $input
 * @return array
 */
function artemis_sanitize_settings( $input ) {
	$d   = artemis_default_settings();
	$in  = is_array( $input ) ? $input : array();
	$out = array();

	// Cores (hex).
	$color_keys = array(
		'artemis_color_primary', 'artemis_color_primary_hover', 'artemis_color_text',
		'artemis_color_body', 'artemis_color_muted', 'artemis_color_bg', 'artemis_color_bg_alt',
		'artemis_color_border', 'artemis_color_header_bg', 'artemis_color_header_text',
		'artemis_color_footer_bg', 'artemis_color_footer_text', 'artemis_color_cta_text',
		'artemis_color_cta_header', 'artemis_color_cta_single_bg', 'artemis_color_cta_single_text',
	);
	foreach ( $color_keys as $k ) {
		$val      = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
		$out[ $k ] = $val ? $val : $d[ $k ];
	}

	// Texto simples.
	foreach ( array( 'artemis_font_heading', 'artemis_font_body', 'artemis_cta_label', 'artemis_cta_section_title', 'artemis_cta_section_subtitle', 'artemis_cta_sidebar_title', 'artemis_footer_credit' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d[ $k ];
	}

	// Pesos de fonte: só dígitos e ';'.
	foreach ( array( 'artemis_font_weights_heading', 'artemis_font_weights_body' ) as $k ) {
		$raw       = isset( $in[ $k ] ) ? preg_replace( '/[^0-9;]/', '', $in[ $k ] ) : '';
		$out[ $k ] = $raw !== '' ? $raw : $d[ $k ];
	}

	// Textarea (aceita HTML básico).
	$out['artemis_cta_sidebar_text'] = isset( $in['artemis_cta_sidebar_text'] ) ? sanitize_textarea_field( $in['artemis_cta_sidebar_text'] ) : $d['artemis_cta_sidebar_text'];
	$out['artemis_footer_text']      = isset( $in['artemis_footer_text'] ) ? wp_kses_post( $in['artemis_footer_text'] ) : $d['artemis_footer_text'];

	// URLs.
	foreach ( array( 'artemis_cta_url', 'artemis_footer_credit_url', 'artemis_whatsapp_url' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? esc_url_raw( $in[ $k ] ) : $d[ $k ];
	}

	// Inteiros / IDs.
	$out['artemis_cta_sidebar_image'] = isset( $in['artemis_cta_sidebar_image'] ) ? absint( $in['artemis_cta_sidebar_image'] ) : 0;
	$out['artemis_cta_section_image'] = isset( $in['artemis_cta_section_image'] ) ? absint( $in['artemis_cta_section_image'] ) : 0;
	$out['artemis_logo']              = isset( $in['artemis_logo'] ) ? absint( $in['artemis_logo'] ) : 0;
	$out['artemis_footer_logo']       = isset( $in['artemis_footer_logo'] ) ? absint( $in['artemis_footer_logo'] ) : 0;
	$out['artemis_featured_count']    = isset( $in['artemis_featured_count'] ) ? min( 6, max( 1, absint( $in['artemis_featured_count'] ) ) ) : $d['artemis_featured_count'];
	$out['artemis_logo_height']        = isset( $in['artemis_logo_height'] ) ? absint( $in['artemis_logo_height'] ) : $d['artemis_logo_height'];
	$out['artemis_logo_height_footer'] = isset( $in['artemis_logo_height_footer'] ) ? absint( $in['artemis_logo_height_footer'] ) : $d['artemis_logo_height_footer'];

	// Checkboxes (ausente = 0).
	foreach ( array( 'artemis_cta_new_tab', 'artemis_show_breadcrumbs', 'artemis_footer_show_back_top', 'artemis_tagline_show' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}

	// Efeito colateral: página do blog → page_for_posts nativo.
	if ( isset( $in['artemis_blog_page_id'] ) ) {
		update_option( 'page_for_posts', absint( $in['artemis_blog_page_id'] ) );
	}

	return $out;
}

/**
 * Assets do painel (color picker + media), só na tela do plugin.
 *
 * @param string $hook
 */
function artemis_admin_assets( $hook ) {
	if ( $hook !== 'toplevel_page_artemis-blog' ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_media();

	$js = <<<'JS'
jQuery(function ($) {
	$('.artemis-color').wpColorPicker();

	$('.artemis-media-pick').on('click', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.artemis-media');
		var frame = wp.media({ title: 'Selecionar imagem', multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			wrap.find('.artemis-media-id').val(att.id);
			var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
			wrap.find('.artemis-media-preview').html('<img src="' + url + '" style="max-width:120px;height:auto;border-radius:6px;" />');
			wrap.find('.artemis-media-remove').show();
		});
		frame.open();
	});

	$('.artemis-media-remove').on('click', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.artemis-media');
		wrap.find('.artemis-media-id').val('');
		wrap.find('.artemis-media-preview').empty();
		$(this).hide();
	});
});
JS;
	wp_add_inline_script( 'wp-color-picker', $js );
}
add_action( 'admin_enqueue_scripts', 'artemis_admin_assets' );

/* ---------------------------------------------------------------------------
 * Helpers de render de campo.
 * ------------------------------------------------------------------------- */

function artemis_field_color( $o, $key, $label ) {
	$val = isset( $o[ $key ] ) ? $o[ $key ] : '';
	printf(
		'<tr><th scope="row">%s</th><td><input type="text" class="artemis-color" name="artemis_settings[%s]" value="%s" data-default-color="%s" /></td></tr>',
		esc_html( $label ),
		esc_attr( $key ),
		esc_attr( $val ),
		esc_attr( $val )
	);
}

function artemis_field_text( $o, $key, $label, $desc = '', $placeholder = '' ) {
	$val = isset( $o[ $key ] ) ? $o[ $key ] : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	printf(
		'<input type="text" class="regular-text" name="artemis_settings[%s]" value="%s" placeholder="%s" />',
		esc_attr( $key ),
		esc_attr( $val ),
		esc_attr( $placeholder )
	);
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
	echo '</td></tr>';
}

function artemis_field_url( $o, $key, $label, $desc = '' ) {
	$val = isset( $o[ $key ] ) ? $o[ $key ] : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	printf( '<input type="url" class="regular-text" name="artemis_settings[%s]" value="%s" placeholder="https://" />', esc_attr( $key ), esc_attr( $val ) );
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
	echo '</td></tr>';
}

function artemis_field_textarea( $o, $key, $label, $desc = '' ) {
	$val = isset( $o[ $key ] ) ? $o[ $key ] : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	printf( '<textarea class="large-text" rows="3" name="artemis_settings[%s]">%s</textarea>', esc_attr( $key ), esc_textarea( $val ) );
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
	echo '</td></tr>';
}

function artemis_field_number( $o, $key, $label, $min, $max, $desc = '' ) {
	$val = isset( $o[ $key ] ) ? $o[ $key ] : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	printf(
		'<input type="number" name="artemis_settings[%s]" value="%s" min="%d" max="%d" class="small-text" />',
		esc_attr( $key ),
		esc_attr( $val ),
		(int) $min,
		(int) $max
	);
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
	echo '</td></tr>';
}

function artemis_field_checkbox( $o, $key, $label, $desc = '' ) {
	$checked = ! empty( $o[ $key ] );
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><label>';
	printf(
		'<input type="checkbox" name="artemis_settings[%s]" value="1" %s /> %s',
		esc_attr( $key ),
		checked( $checked, true, false ),
		esc_html( $desc )
	);
	echo '</label></td></tr>';
}

function artemis_field_media( $o, $key, $label, $desc = '' ) {
	$id  = isset( $o[ $key ] ) ? (int) $o[ $key ] : 0;
	$img = $id ? wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'max-width:120px;height:auto;border-radius:6px;' ) ) : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	echo '<div class="artemis-media">';
	printf( '<input type="hidden" class="artemis-media-id" name="artemis_settings[%s]" value="%d" />', esc_attr( $key ), $id );
	echo '<div class="artemis-media-preview">' . $img . '</div>';
	echo '<p><button type="button" class="button artemis-media-pick">' . esc_html__( 'Selecionar imagem', 'artemis-blog' ) . '</button> ';
	printf(
		'<button type="button" class="button-link artemis-media-remove" style="%s">%s</button>',
		$id ? '' : 'display:none;',
		esc_html__( 'Remover', 'artemis-blog' )
	);
	echo '</p>';
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
	echo '</div></td></tr>';
}

/**
 * Render da página.
 */
function artemis_settings_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o            = wp_parse_args( get_option( 'artemis_settings', array() ), artemis_default_settings() );
	$blog_page_id = artemis_blog_page_id();
	$show_on_front = get_option( 'show_on_front' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Artemis Blog', 'artemis-blog' ); ?></h1>
		<p class="description"><?php esc_html_e( 'O blog é renderizado em modo canvas, com cabeçalho e rodapé próprios (isolados do tema do site). Aqui você edita toda a identidade: cores de cabeçalho, miolo e rodapé, fontes, logos, CTA global e conteúdo do rodapé.', 'artemis-blog' ); ?></p>

		<?php if ( isset( $_GET['artemis_preset'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pronto! Paleta dark aplicada. Ajuste o que quiser abaixo e clique em Salvar.', 'artemis-blog' ); ?></p></div>
		<?php endif; ?>

		<?php
		if ( function_exists( 'artemis_style_import_notice' ) ) {
			artemis_style_import_notice();
		}
		?>

		<form method="post" style="margin:16px 0;padding:14px 18px;border:1px solid #c3c4c7;border-left:4px solid #2271b1;background:#fff;">
			<?php wp_nonce_field( 'artemis_apply_preset' ); ?>
			<p style="margin:0 0 10px;"><strong><?php esc_html_e( 'Tema dark pronto', 'artemis-blog' ); ?></strong> — <?php esc_html_e( 'aplica de uma vez uma paleta dark coerente. Você ajusta depois. Não mexe em textos, links, logos/imagens nem na página do blog.', 'artemis-blog' ); ?></p>
			<button type="submit" name="artemis_apply_preset" value="1" class="button button-primary">⚡ <?php esc_html_e( 'Aplicar tema dark', 'artemis-blog' ); ?></button>
		</form>

		<?php
		if ( function_exists( 'artemis_style_import_form' ) ) {
			artemis_style_import_form();
		}
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'artemis_settings_group' ); ?>

			<h2 class="title"><?php esc_html_e( 'Página do blog', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Página que exibe o blog', 'artemis-blog' ); ?></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'artemis_settings[artemis_blog_page_id]',
							'selected'          => $blog_page_id,
							'show_option_none'  => __( '— selecione uma página —', 'artemis-blog' ),
							'option_none_value' => 0,
						) );
						?>
						<p class="description"><?php esc_html_e( 'Crie uma página vazia (ex.: "Blog") e selecione-a aqui. Ela passa a listar os posts com o layout Artemis. Equivale a definir a "Página de posts" em Configurações → Leitura.', 'artemis-blog' ); ?></p>
						<?php if ( 'page' !== $show_on_front ) : ?>
							<p class="description" style="color:#b32d2e;">
								<?php esc_html_e( 'Atenção: a página inicial do site está configurada para "Seus posts mais recentes". Para a página do blog funcionar como esperado, defina uma página estática como inicial em Configurações → Leitura.', 'artemis-blog' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Logo e cabeçalho', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_media( $o, 'artemis_logo', __( 'Logo do blog (header)', 'artemis-blog' ), __( 'Aparece no cabeçalho do blog. Vazio = nome do site. Como o blog usa cabeçalho próprio, configure o logo aqui (ele não puxa o logo do tema do site).', 'artemis-blog' ) );
				artemis_field_number( $o, 'artemis_logo_height', __( 'Altura do logo no header (px)', 'artemis-blog' ), 16, 120 );
				artemis_field_media( $o, 'artemis_footer_logo', __( 'Logo do rodapé', 'artemis-blog' ), __( 'Aparece no rodapé. Como o rodapé costuma ser escuro, use uma versão clara/branca do logo.', 'artemis-blog' ) );
				artemis_field_number( $o, 'artemis_logo_height_footer', __( 'Altura do logo no rodapé (px)', 'artemis-blog' ), 16, 160 );
				artemis_field_checkbox( $o, 'artemis_tagline_show', __( 'Tagline', 'artemis-blog' ), __( 'Mostrar a descrição do site ao lado do logo', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Cores da marca', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_color( $o, 'artemis_color_primary', __( 'Primária (botões, links, destaques)', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_primary_hover', __( 'Primária (hover)', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_text', __( 'Títulos (H1, H2, H3)', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_body', __( 'Texto do corpo', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_muted', __( 'Texto secundário', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_bg', __( 'Fundo dos cards', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_bg_alt', __( 'Fundo da área de conteúdo', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_border', __( 'Bordas', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Cores do cabeçalho', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_color( $o, 'artemis_color_header_bg', __( 'Fundo do cabeçalho', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_header_text', __( 'Texto do cabeçalho (título, menu)', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_cta_header', __( 'Fundo do botão CTA do cabeçalho', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Cores do rodapé', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_color( $o, 'artemis_color_footer_bg', __( 'Fundo do rodapé (também é o fundo dos heros escuros)', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_footer_text', __( 'Texto do rodapé', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Cores do CTA', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_color( $o, 'artemis_color_cta_text', __( 'Texto dos botões CTA', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_cta_single_bg', __( 'Fundo do CTA no post', 'artemis-blog' ) );
				artemis_field_color( $o, 'artemis_color_cta_single_text', __( 'Texto do CTA no post', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Tipografia', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_text( $o, 'artemis_font_heading', __( 'Fonte dos títulos', 'artemis-blog' ), __( 'Nome exato no Google Fonts (ex.: Poppins). Em branco = Montserrat.', 'artemis-blog' ), 'Montserrat' );
				artemis_field_text( $o, 'artemis_font_body', __( 'Fonte do corpo', 'artemis-blog' ), __( 'Ex.: Inter, Nunito, Open Sans. Em branco = Montserrat.', 'artemis-blog' ), 'Montserrat' );
				artemis_field_text( $o, 'artemis_font_weights_heading', __( 'Pesos dos títulos', 'artemis-blog' ), __( 'Separados por ; (ex.: 600;700)', 'artemis-blog' ), '600;700' );
				artemis_field_text( $o, 'artemis_font_weights_body', __( 'Pesos do corpo', 'artemis-blog' ), __( 'Separados por ; (ex.: 400;500;600)', 'artemis-blog' ), '400;500;600' );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'CTA global', 'artemis-blog' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Esse link alimenta todos os botões de chamada do blog (home, post).', 'artemis-blog' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_url( $o, 'artemis_cta_url', __( 'URL de destino', 'artemis-blog' ), __( 'Ex.: https://seusite.com.br/contato', 'artemis-blog' ) );
				artemis_field_text( $o, 'artemis_cta_label', __( 'Texto do botão', 'artemis-blog' ) );
				artemis_field_checkbox( $o, 'artemis_cta_new_tab', __( 'Abrir em nova aba', 'artemis-blog' ), __( 'Abre o link do CTA numa nova aba', 'artemis-blog' ) );
				artemis_field_text( $o, 'artemis_cta_section_title', __( 'Título da seção CTA (home)', 'artemis-blog' ) );
				artemis_field_text( $o, 'artemis_cta_section_subtitle', __( 'Subtítulo da seção CTA (home)', 'artemis-blog' ) );
					artemis_field_media( $o, 'artemis_cta_section_image', __( 'Imagem da seção CTA (home)', 'artemis-blog' ), __( 'Opcional. Aparece ao lado do texto na faixa de CTA da home. ~440×300px.', 'artemis-blog' ) );
				artemis_field_text( $o, 'artemis_cta_sidebar_title', __( 'Título do CTA no post', 'artemis-blog' ) );
				artemis_field_textarea( $o, 'artemis_cta_sidebar_text', __( 'Texto do CTA no post', 'artemis-blog' ) );
				artemis_field_media( $o, 'artemis_cta_sidebar_image', __( 'Imagem do CTA na sidebar', 'artemis-blog' ), __( 'Opcional. Aparece no topo do card. ~720×480px.', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Layout', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_number( $o, 'artemis_featured_count', __( 'Posts em destaque na home', 'artemis-blog' ), 1, 6, __( '1 = só hero; 5 = hero + 4 cards laterais (recomendado).', 'artemis-blog' ) );
				artemis_field_checkbox( $o, 'artemis_show_breadcrumbs', __( 'Breadcrumbs', 'artemis-blog' ), __( 'Mostrar breadcrumbs nas páginas internas', 'artemis-blog' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Rodapé', 'artemis-blog' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				artemis_field_url( $o, 'artemis_whatsapp_url', __( 'WhatsApp (URL completa)', 'artemis-blog' ), __( 'Ex.: https://wa.me/5511999999999 — se preencher, aparece o botão verde flutuante e no rodapé.', 'artemis-blog' ) );
				artemis_field_textarea( $o, 'artemis_footer_text', __( 'Texto do rodapé (esquerda)', 'artemis-blog' ), __( 'Aceita HTML básico. {year} = ano atual, {site} = nome do site. Vazio = © {year} {site}.', 'artemis-blog' ) );
				artemis_field_text( $o, 'artemis_footer_credit', __( 'Créditos (direita)', 'artemis-blog' ) );
				artemis_field_url( $o, 'artemis_footer_credit_url', __( 'URL dos créditos (opcional)', 'artemis-blog' ) );
				artemis_field_checkbox( $o, 'artemis_footer_show_back_top', __( 'Voltar ao topo', 'artemis-blog' ), __( 'Mostrar o botão "voltar ao topo" no rodapé', 'artemis-blog' ) );
				?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
