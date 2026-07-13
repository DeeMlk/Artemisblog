<?php
/**
 * Box do autor — avatar + nome + bio + botão "Todas as publicações".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$author_id   = get_the_author_meta( 'ID' );
$author_name = get_the_author();
$author_bio  = get_the_author_meta( 'description', $author_id );
$author_url  = get_author_posts_url( $author_id );
?>
<aside class="artemis-author-box">
	<div class="author-box-avatar"><?php echo get_avatar( $author_id, 88 ); ?></div>
	<h3 class="author-box-name"><?php echo esc_html( $author_name ); ?></h3>
	<?php if ( $author_bio ) : ?>
		<p class="author-box-bio"><?php echo esc_html( $author_bio ); ?></p>
	<?php endif; ?>
	<a class="artemis-btn artemis-btn-ghost author-box-cta" href="<?php echo esc_url( $author_url ); ?>">
		<?php esc_html_e( 'Todas as publicações', 'artemis-blog' ); ?>
	</a>
</aside>
