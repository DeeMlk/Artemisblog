<?php
/**
 * CTA sticky na sidebar — card dark, imagem opcional no topo.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! artemis_cta_url() ) {
	return;
}
$title    = artemis_get_option( 'artemis_cta_sidebar_title', __( 'Gostou do conteúdo?', 'artemis-blog' ) );
$text     = artemis_get_option( 'artemis_cta_sidebar_text', __( 'Entre em contato e veja como podemos ajudar o seu negócio.', 'artemis-blog' ) );
$image_id = (int) artemis_get_option( 'artemis_cta_sidebar_image', 0 );
?>
<aside class="artemis-cta-sidebar">
	<?php if ( $image_id ) : ?>
		<div class="cta-sidebar-image">
			<?php echo wp_get_attachment_image( $image_id, 'artemis-card', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
		</div>
	<?php endif; ?>
	<div class="cta-sidebar-body">
		<h3 class="cta-sidebar-title"><?php echo esc_html( $title ); ?></h3>
		<p class="cta-sidebar-text"><?php echo esc_html( $text ); ?></p>
		<?php artemis_cta_button( array( 'class' => 'artemis-btn artemis-btn-primary artemis-btn-block single-cta' ) ); ?>
	</div>
</aside>
