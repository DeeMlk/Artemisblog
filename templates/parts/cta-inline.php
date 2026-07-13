<?php
/**
 * CTA inline ao final do conteúdo do single.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! artemis_cta_url() ) {
	return;
}
$title = artemis_get_option( 'artemis_cta_sidebar_title', __( 'Gostou do conteúdo?', 'artemis-blog' ) );
$text  = artemis_get_option( 'artemis_cta_sidebar_text', __( 'Entre em contato e veja como podemos ajudar o seu negócio.', 'artemis-blog' ) );
?>
<section class="artemis-cta-inline">
	<div class="cta-inline-text">
		<h3 class="cta-inline-title"><?php echo esc_html( $title ); ?></h3>
		<p class="cta-inline-desc"><?php echo esc_html( $text ); ?></p>
	</div>
	<div class="cta-inline-action">
		<?php artemis_cta_button( array( 'class' => 'artemis-btn artemis-btn-primary artemis-btn-lg' ) ); ?>
	</div>
</section>
