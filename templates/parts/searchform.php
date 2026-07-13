<?php
/**
 * Formulário de busca do blog. A busca vai para home_url com ?s= (roteia is_search).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="artemis-search-field"><?php esc_html_e( 'Buscar:', 'artemis-blog' ); ?></label>
	<input type="search" id="artemis-search-field" class="search-field" placeholder="<?php esc_attr_e( 'Buscar no blog…', 'artemis-blog' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	<button type="submit" class="search-submit" aria-label="<?php esc_attr_e( 'Buscar', 'artemis-blog' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
	</button>
</form>
