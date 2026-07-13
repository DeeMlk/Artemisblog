<?php
/**
 * Seção de CTA (home, embaixo dos destaques).
 * Não renderiza nada se nenhuma URL global tiver sido configurada.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! artemis_cta_url() ) {
	return;
}
$title    = artemis_get_option( 'artemis_cta_section_title', __( 'Pronto pra dar o próximo passo?', 'artemis-blog' ) );
$subtitle = artemis_get_option( 'artemis_cta_section_subtitle', __( 'Fale com a gente e descubra como podemos te ajudar.', 'artemis-blog' ) );
$image_id = (int) artemis_get_option( 'artemis_cta_section_image', 0 );
?>
<section class="artemis-cta-section">
	<div class="artemis-container">
		<div class="cta-section-inner<?php echo $image_id ? ' has-image' : ''; ?>">
			<?php if ( $image_id ) : ?>
				<div class="cta-section-image">
					<?php echo wp_get_attachment_image( $image_id, 'artemis-card', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
				</div>
			<?php endif; ?>
			<div class="cta-section-text">
				<h2 class="cta-section-title"><?php echo esc_html( $title ); ?></h2>
				<p class="cta-section-subtitle"><?php echo esc_html( $subtitle ); ?></p>
			</div>
			<div class="cta-section-action">
				<?php artemis_cta_button( array( 'class' => 'artemis-btn artemis-btn-primary artemis-btn-lg' ) ); ?>
			</div>
		</div>
	</div>
</section>
