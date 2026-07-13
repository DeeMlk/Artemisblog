<?php
/**
 * Botões de compartilhamento (Facebook, LinkedIn, WhatsApp, Email).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url       = rawurlencode( get_permalink() );
$title     = rawurlencode( get_the_title() );
$mail_body = rawurlencode( get_the_title() . "\n\n" . get_permalink() );

$share = array(
	'facebook' => array(
		'label' => __( 'Compartilhar no Facebook', 'artemis-blog' ),
		'href'  => "https://www.facebook.com/sharer/sharer.php?u={$url}",
		'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.563V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>',
	),
	'linkedin' => array(
		'label' => __( 'Compartilhar no LinkedIn', 'artemis-blog' ),
		'href'  => "https://www.linkedin.com/sharing/share-offsite/?url={$url}",
		'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
	),
	'whatsapp' => array(
		'label' => __( 'Compartilhar no WhatsApp', 'artemis-blog' ),
		'href'  => "https://wa.me/?text={$title}%20{$url}",
		'svg'   => '<svg width="18" height="18" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>',
	),
	'email'    => array(
		'label' => __( 'Compartilhar por Email', 'artemis-blog' ),
		'href'  => "mailto:?subject={$title}&body={$mail_body}",
		'svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
	),
);
?>
<section class="artemis-social-share">
	<h3 class="share-label"><?php esc_html_e( 'Compartilhe este conteúdo', 'artemis-blog' ); ?></h3>
	<div class="share-buttons">
		<?php
		foreach ( $share as $key => $cfg ) :
			$target = ( $key === 'email' ) ? '_self' : '_blank';
		?>
			<a class="share-btn share-btn--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $cfg['href'] ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $cfg['label'] ); ?>">
				<?php echo $cfg['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG estático. ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
