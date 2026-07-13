<?php
/**
 * Mostrada quando uma listagem não tem posts.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="no-results">
	<h2 class="no-results-title"><?php esc_html_e( 'Nada por aqui ainda.', 'artemis-blog' ); ?></h2>
	<p class="no-results-text">
		<?php if ( is_search() ) : ?>
			<?php esc_html_e( 'Sua busca não retornou resultados. Tente outro termo.', 'artemis-blog' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Nenhum conteúdo encontrado nesta seção.', 'artemis-blog' ); ?>
		<?php endif; ?>
	</p>
	<div class="no-results-search">
		<?php artemis_get_search_form(); ?>
	</div>
</div>
