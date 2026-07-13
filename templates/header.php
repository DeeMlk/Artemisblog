<?php
/**
 * Header do blog em modo CANVAS.
 *
 * O plugin renderiza o documento HTML inteiro, ignorando o header/footer do tema
 * anfitrião (e a "barra de título" que o Elementor injeta). Assim o blog fica
 * isolado do builder do site.
 *
 * O <body> recebe a classe .artemis-blog para que TODO o CSS escopado
 * (.artemis-blog ...) valha também para o header e o rodapé próprios.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'artemis-blog artemis-canvas' ); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#site-content"><?php esc_html_e( 'Pular para o conteúdo', 'artemis-blog' ); ?></a>

<header id="site-header" class="site-header">
	<div class="artemis-container header-inner">

		<div class="site-branding">
			<?php
			$artemis_logo_id = (int) artemis_get_option( 'artemis_logo', 0 );
			if ( $artemis_logo_id ) {
				printf(
					'<a href="%1$s" class="custom-logo-link" rel="home">%2$s</a>',
					esc_url( home_url( '/' ) ),
					wp_get_attachment_image( $artemis_logo_id, 'full', false, array(
						'class' => 'custom-logo',
						'alt'   => get_bloginfo( 'name' ),
					) )
				);
			} elseif ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				printf(
					'<a class="site-title" href="%1$s" rel="home">%2$s</a>',
					esc_url( home_url( '/' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
			}
			if ( artemis_get_option( 'artemis_tagline_show', 1 ) ) {
				$desc = get_bloginfo( 'description', 'display' );
				if ( $desc ) {
					echo '<p class="site-description">' . esc_html( $desc ) . '</p>';
				}
			}
			?>
		</div>

		<nav id="site-navigation" class="site-nav" aria-label="<?php esc_attr_e( 'Menu principal', 'artemis-blog' ); ?>">
			<?php artemis_header_menu(); ?>
			<div class="nav-cta-wrap">
				<?php artemis_cta_button( array( 'class' => 'artemis-btn artemis-btn-primary nav-cta' ) ); ?>
			</div>
		</nav>

		<div class="header-actions">
			<form role="search" method="get" class="header-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<button type="button" class="search-toggle" aria-label="<?php esc_attr_e( 'Buscar', 'artemis-blog' ); ?>" aria-expanded="false">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</button>
				<input type="search" class="header-search-input" name="s" placeholder="<?php esc_attr_e( 'Buscar no blog…', 'artemis-blog' ); ?>" aria-label="<?php esc_attr_e( 'Termo de busca', 'artemis-blog' ); ?>" />
				<button type="submit" class="header-search-submit" aria-label="<?php esc_attr_e( 'Enviar busca', 'artemis-blog' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</button>
				<button type="button" class="search-close" aria-label="<?php esc_attr_e( 'Fechar busca', 'artemis-blog' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
			</form>
			<?php artemis_cta_button( array( 'class' => 'artemis-btn artemis-btn-primary header-cta' ) ); ?>
		</div>

		<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Abrir menu', 'artemis-blog' ); ?>">
			<span></span><span></span><span></span>
		</button>
	</div>
</header>

<main id="site-content" class="site-content" role="main">
