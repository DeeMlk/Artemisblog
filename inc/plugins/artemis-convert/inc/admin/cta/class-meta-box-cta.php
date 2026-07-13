<?php
/**
 * Meta box for CTA: image, link, UTMs, position, excluded URLs.
 *
 * @package Artemis_Convert\Inc\Admin\CTA
 */

namespace Artemis_Convert\Inc\Admin\CTA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Box_CTA
 */
class Meta_Box_CTA {

	/**
	 * Meta keys.
	 */
	const META_IMAGE         = '_artemis_convert_cta_image';
	const META_IMAGE_MOBILE  = '_artemis_convert_cta_image_mobile';
	const META_TYPE          = '_artemis_convert_cta_type';
	const META_SHORTCODE     = '_artemis_convert_cta_shortcode';
	const META_SHORTCODE_DESKTOP = '_artemis_convert_cta_shortcode_desktop';
	const META_SHORTCODE_MOBILE  = '_artemis_convert_cta_shortcode_mobile';
	const META_LINK          = '_artemis_convert_cta_link';
	const META_UTM_SOURCE    = '_artemis_convert_cta_utm_source';
	const META_UTM_MEDIUM    = '_artemis_convert_cta_utm_medium';
	const META_UTM_CAMPAIGN  = '_artemis_convert_cta_utm_campaign';
	const META_POSITION      = '_artemis_convert_cta_position';
	const META_EXCLUDED_URLS = '_artemis_convert_cta_excluded_urls';

	/**
	 * Constructor: add meta box and save.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . CPT_CTA::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'artemis_convert_cta_settings',
			__( 'Configurações do CTA', 'artemis-convert' ),
			array( $this, 'render_meta_box' ),
			CPT_CTA::POST_TYPE,
			'normal'
		);
	}

	/**
	 * Render meta box HTML.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'artemis_convert_cta_meta', 'artemis_convert_cta_meta_nonce' );

		$image_id       = get_post_meta( $post->ID, self::META_IMAGE, true );
		$image_mobile_id = get_post_meta( $post->ID, self::META_IMAGE_MOBILE, true );
		$type           = get_post_meta( $post->ID, self::META_TYPE, true );
		$shortcode      = get_post_meta( $post->ID, self::META_SHORTCODE, true );
		$shortcode_desktop = metadata_exists( 'post', $post->ID, self::META_SHORTCODE_DESKTOP ) ? (bool) get_post_meta( $post->ID, self::META_SHORTCODE_DESKTOP, true ) : true;
		$shortcode_mobile  = metadata_exists( 'post', $post->ID, self::META_SHORTCODE_MOBILE ) ? (bool) get_post_meta( $post->ID, self::META_SHORTCODE_MOBILE, true ) : true;
		$link           = get_post_meta( $post->ID, self::META_LINK, true );
		$utm_source  = get_post_meta( $post->ID, self::META_UTM_SOURCE, true );
		$utm_medium  = get_post_meta( $post->ID, self::META_UTM_MEDIUM, true );
		$utm_campaign = get_post_meta( $post->ID, self::META_UTM_CAMPAIGN, true );
		$position    = get_post_meta( $post->ID, self::META_POSITION, true );
		$excluded    = get_post_meta( $post->ID, self::META_EXCLUDED_URLS, true );

		// Posição agora é multi-seleção. Retrocompat: string antiga vira array.
		if ( is_array( $position ) ) {
			$positions = $position;
		} elseif ( $position === 'both' ) {
			$positions = array( 'top', 'bottom' );
		} elseif ( $position ) {
			$positions = array( $position );
		} else {
			$positions = array( 'bottom' );
		}
		$positions = array_values( array_intersect( $positions, array( 'top', 'middle', 'bottom' ) ) );
		if ( empty( $positions ) ) {
			$positions = array( 'bottom' );
		}
		if ( ! in_array( $type, array( 'image', 'shortcode' ), true ) ) {
			$type = 'image';
		}

		$image_url        = '';
		$image_mobile_url = '';
		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( (int) $image_id, 'medium' );
		}
		if ( $image_mobile_id ) {
			$image_mobile_url = wp_get_attachment_image_url( (int) $image_mobile_id, 'medium' );
		}
		$utm_has_value = ! empty( $utm_source ) || ! empty( $utm_medium ) || ! empty( $utm_campaign );
		?>
		<div id="artemis_convert_cta_settings_inner">
			<div class="artemis-cta-section">
				<h4 class="artemis-cta-section-title"><?php esc_html_e( 'Tipo do CTA', 'artemis-convert' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label><?php esc_html_e( 'Conteudo', 'artemis-convert' ); ?></label></th>
						<td>
							<div class="artemis-cta-type-options" role="group" aria-label="<?php esc_attr_e( 'Tipo do CTA', 'artemis-convert' ); ?>">
								<label class="artemis-cta-type-option <?php echo $type === 'image' ? 'is-selected' : ''; ?>">
									<input type="radio" name="artemis_convert_cta_type" value="image" <?php checked( $type, 'image' ); ?> />
									<span class="artemis-cta-type-label"><?php esc_html_e( 'Imagem', 'artemis-convert' ); ?></span>
									<span class="artemis-cta-type-desc"><?php esc_html_e( 'Banner com link', 'artemis-convert' ); ?></span>
								</label>
								<label class="artemis-cta-type-option <?php echo $type === 'shortcode' ? 'is-selected' : ''; ?>">
									<input type="radio" name="artemis_convert_cta_type" value="shortcode" <?php checked( $type, 'shortcode' ); ?> />
									<span class="artemis-cta-type-label"><?php esc_html_e( 'Shortcode', 'artemis-convert' ); ?></span>
									<span class="artemis-cta-type-desc"><?php esc_html_e( 'Conteudo gerado por shortcode', 'artemis-convert' ); ?></span>
								</label>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Seção Banner -->
			<div class="artemis-cta-section artemis-cta-image-fields" <?php echo $type === 'shortcode' ? 'style="display:none;"' : ''; ?>>
				<h4 class="artemis-cta-section-title"><?php esc_html_e( 'Banner', 'artemis-convert' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label for="artemis_cta_image"><?php esc_html_e( 'Imagem do banner', 'artemis-convert' ); ?></label></th>
						<td>
							<div class="artemis-cta-image-wrap">
								<input type="hidden" id="artemis_cta_image" name="artemis_convert_cta_image" value="<?php echo esc_attr( $image_id ); ?>" />
								<div class="artemis-cta-image-preview">
									<?php if ( $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:300px;height:auto;" />
									<?php endif; ?>
								</div>
								<p>
									<button type="button" class="button button-primary artemis-cta-upload-image"><?php esc_html_e( 'Selecionar imagem', 'artemis-convert' ); ?></button>
									<button type="button" class="button artemis-cta-remove-image" <?php echo ! $image_id ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remover', 'artemis-convert' ); ?></button>
								</p>
								<p class="description"><?php esc_html_e( 'Recomendado: 1200×400 px', 'artemis-convert' ); ?></p>
							</div>
						</td>
					</tr>
					<tr>
						<th><label for="artemis_cta_image_mobile"><?php esc_html_e( 'Imagem para mobile (opcional)', 'artemis-convert' ); ?></label></th>
						<td>
							<div class="artemis-cta-image-mobile-wrap">
								<input type="hidden" id="artemis_cta_image_mobile" name="artemis_convert_cta_image_mobile" value="<?php echo esc_attr( $image_mobile_id ); ?>" />
								<div class="artemis-cta-image-mobile-preview">
									<?php if ( $image_mobile_url ) : ?>
										<img src="<?php echo esc_url( $image_mobile_url ); ?>" alt="" style="max-width:300px;height:auto;" />
									<?php endif; ?>
								</div>
								<p>
									<button type="button" class="button artemis-cta-upload-image-mobile"><?php esc_html_e( 'Selecionar imagem', 'artemis-convert' ); ?></button>
									<button type="button" class="button artemis-cta-remove-image-mobile" <?php echo ! $image_mobile_id ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remover', 'artemis-convert' ); ?></button>
								</p>
								<p class="description"><?php esc_html_e( 'Se preenchida, esta imagem será exibida em telas pequenas. Recomendado: 600×400 px.', 'artemis-convert' ); ?></p>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Seção Shortcode -->
			<div class="artemis-cta-section artemis-cta-shortcode-fields" <?php echo $type !== 'shortcode' ? 'style="display:none;"' : ''; ?>>
				<h4 class="artemis-cta-section-title"><?php esc_html_e( 'Shortcode', 'artemis-convert' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label for="artemis_cta_shortcode"><?php esc_html_e( 'Shortcode', 'artemis-convert' ); ?></label></th>
						<td>
							<textarea id="artemis_cta_shortcode" name="artemis_convert_cta_shortcode" rows="4" class="large-text code" placeholder="<?php echo esc_attr( '[seu-shortcode id="123"]' ); ?>"><?php echo esc_textarea( $shortcode ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Cole o shortcode que deve ser exibido na posicao escolhida.', 'artemis-convert' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Exibir em', 'artemis-convert' ); ?></label></th>
						<td>
							<div class="artemis-cta-device-options">
								<label>
									<input type="hidden" name="artemis_convert_cta_shortcode_desktop" value="0" />
									<input type="checkbox" name="artemis_convert_cta_shortcode_desktop" value="1" <?php checked( $shortcode_desktop ); ?> />
									<?php esc_html_e( 'Ativar no desktop', 'artemis-convert' ); ?>
								</label>
								<label>
									<input type="hidden" name="artemis_convert_cta_shortcode_mobile" value="0" />
									<input type="checkbox" name="artemis_convert_cta_shortcode_mobile" value="1" <?php checked( $shortcode_mobile ); ?> />
									<?php esc_html_e( 'Ativar no mobile', 'artemis-convert' ); ?>
								</label>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Seção Destino -->
			<div class="artemis-cta-section artemis-cta-image-fields" <?php echo $type === 'shortcode' ? 'style="display:none;"' : ''; ?>>
				<h4 class="artemis-cta-section-title"><?php esc_html_e( 'Destino do link', 'artemis-convert' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label for="artemis_cta_link"><?php esc_html_e( 'URL do link', 'artemis-convert' ); ?></label></th>
						<td>
							<input type="url" id="artemis_cta_link" name="artemis_convert_cta_link" value="<?php echo esc_url( $link ); ?>" class="large-text" placeholder="https://exemplo.com/sua-pagina" />
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<div class="artemis-cta-utm-toggle <?php echo $utm_has_value ? 'is-open' : ''; ?>">
								<button type="button" class="artemis-cta-utm-toggle-btn" aria-expanded="<?php echo $utm_has_value ? 'true' : 'false'; ?>">
									<span class="artemis-cta-utm-toggle-icon" aria-hidden="true"></span>
									<?php esc_html_e( 'Rastreamento (UTM) – opcional', 'artemis-convert' ); ?>
								</button>
								<div class="artemis-cta-utm-toggle-content" <?php echo ! $utm_has_value ? 'style="display:none;"' : ''; ?>>
									<p>
										<label><?php esc_html_e( 'Source', 'artemis-convert' ); ?>
											<input type="text" name="artemis_convert_cta_utm_source" value="<?php echo esc_attr( $utm_source ); ?>" class="regular-text" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Medium', 'artemis-convert' ); ?>
											<input type="text" name="artemis_convert_cta_utm_medium" value="<?php echo esc_attr( $utm_medium ); ?>" class="regular-text" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Campaign', 'artemis-convert' ); ?>
											<input type="text" name="artemis_convert_cta_utm_campaign" value="<?php echo esc_attr( $utm_campaign ); ?>" class="regular-text" />
										</label>
									</p>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Seção Exibição -->
			<div class="artemis-cta-section">
				<h4 class="artemis-cta-section-title"><?php esc_html_e( 'Exibição', 'artemis-convert' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label><?php esc_html_e( 'Posição no post', 'artemis-convert' ); ?></label></th>
						<td>
							<div class="artemis-cta-position-options" role="group" aria-label="<?php esc_attr_e( 'Posição no post', 'artemis-convert' ); ?>">
								<label class="artemis-cta-position-option <?php echo in_array( 'top', $positions, true ) ? 'is-selected' : ''; ?>">
									<input type="checkbox" name="artemis_convert_cta_position[]" value="top" <?php checked( in_array( 'top', $positions, true ) ); ?> />
									<span class="artemis-cta-position-label"><?php esc_html_e( 'Topo', 'artemis-convert' ); ?></span>
									<span class="artemis-cta-position-desc"><?php esc_html_e( 'No início do texto', 'artemis-convert' ); ?></span>
								</label>
								<label class="artemis-cta-position-option <?php echo in_array( 'middle', $positions, true ) ? 'is-selected' : ''; ?>">
									<input type="checkbox" name="artemis_convert_cta_position[]" value="middle" <?php checked( in_array( 'middle', $positions, true ) ); ?> />
									<span class="artemis-cta-position-label"><?php esc_html_e( 'Meio', 'artemis-convert' ); ?></span>
									<span class="artemis-cta-position-desc"><?php esc_html_e( 'No meio do texto', 'artemis-convert' ); ?></span>
								</label>
								<label class="artemis-cta-position-option <?php echo in_array( 'bottom', $positions, true ) ? 'is-selected' : ''; ?>">
									<input type="checkbox" name="artemis_convert_cta_position[]" value="bottom" <?php checked( in_array( 'bottom', $positions, true ) ); ?> />
									<span class="artemis-cta-position-label"><?php esc_html_e( 'Final', 'artemis-convert' ); ?></span>
									<span class="artemis-cta-position-desc"><?php esc_html_e( 'No final do texto', 'artemis-convert' ); ?></span>
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'Selecione uma ou mais posições — o CTA será exibido em todas as marcadas.', 'artemis-convert' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="artemis_cta_excluded_urls"><?php esc_html_e( 'URLs excluídas', 'artemis-convert' ); ?></label></th>
						<td>
							<textarea id="artemis_cta_excluded_urls" name="artemis_convert_cta_excluded_urls" rows="4" class="large-text" placeholder="<?php echo esc_attr( "https://seusite.com.br/pagina-especifica\nhttps://seusite.com.br/outra-pagina" ); ?>"><?php echo esc_textarea( is_array( $excluded ) ? implode( "\n", $excluded ) : $excluded ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Uma URL por linha. O CTA não será exibido nessas páginas.', 'artemis-convert' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['artemis_convert_cta_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['artemis_convert_cta_meta_nonce'] ) ), 'artemis_convert_cta_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Posição: multi-seleção (checkboxes). Se nada marcado, cai em 'bottom'.
		$raw_positions = isset( $_POST['artemis_convert_cta_position'] ) ? (array) wp_unslash( $_POST['artemis_convert_cta_position'] ) : array();
		$positions     = array_values( array_intersect( array_map( 'sanitize_key', $raw_positions ), array( 'top', 'middle', 'bottom' ) ) );
		if ( empty( $positions ) ) {
			$positions = array( 'bottom' );
		}
		update_post_meta( $post_id, self::META_POSITION, $positions );

		$fields = array(
			'artemis_convert_cta_image'      => 'intval',
			'artemis_convert_cta_image_mobile' => 'intval',
			'artemis_convert_cta_type'       => 'sanitize_key',
			'artemis_convert_cta_shortcode'  => 'sanitize_textarea_field',
			'artemis_convert_cta_shortcode_desktop' => 'intval',
			'artemis_convert_cta_shortcode_mobile' => 'intval',
			'artemis_convert_cta_link'       => 'esc_url_raw',
			'artemis_convert_cta_utm_source' => 'sanitize_text_field',
			'artemis_convert_cta_utm_medium' => 'sanitize_text_field',
			'artemis_convert_cta_utm_campaign' => 'sanitize_text_field',
			'artemis_convert_cta_excluded_urls' => array( $this, 'sanitize_excluded_urls' ),
		);
		$meta_map = array(
			'artemis_convert_cta_image'      => self::META_IMAGE,
			'artemis_convert_cta_image_mobile' => self::META_IMAGE_MOBILE,
			'artemis_convert_cta_type'       => self::META_TYPE,
			'artemis_convert_cta_shortcode'  => self::META_SHORTCODE,
			'artemis_convert_cta_shortcode_desktop' => self::META_SHORTCODE_DESKTOP,
			'artemis_convert_cta_shortcode_mobile' => self::META_SHORTCODE_MOBILE,
			'artemis_convert_cta_link'       => self::META_LINK,
			'artemis_convert_cta_utm_source' => self::META_UTM_SOURCE,
			'artemis_convert_cta_utm_medium' => self::META_UTM_MEDIUM,
			'artemis_convert_cta_utm_campaign' => self::META_UTM_CAMPAIGN,
			'artemis_convert_cta_excluded_urls' => self::META_EXCLUDED_URLS,
		);

		foreach ( $fields as $post_key => $sanitize ) {
			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}
			$value = is_callable( $sanitize )
				? call_user_func( $sanitize, wp_unslash( $_POST[ $post_key ] ) )
				: ( $sanitize === 'intval' ? (int) $_POST[ $post_key ] : $sanitize( wp_unslash( $_POST[ $post_key ] ) ) );
			if ( $post_key === 'artemis_convert_cta_type' && ! in_array( $value, array( 'image', 'shortcode' ), true ) ) {
				$value = 'image';
			}
			if ( in_array( $post_key, array( 'artemis_convert_cta_shortcode_desktop', 'artemis_convert_cta_shortcode_mobile' ), true ) ) {
				$value = $value ? 1 : 0;
			}
			update_post_meta( $post_id, $meta_map[ $post_key ], $value );
		}
	}

	/**
	 * Sanitize excluded URLs (one per line).
	 *
	 * @param string $input Raw input.
	 * @return string
	 */
	public function sanitize_excluded_urls( $input ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", $input ) ) );
		$lines = array_map( 'esc_url_raw', $lines );
		return implode( "\n", $lines );
	}
}
