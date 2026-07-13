<?php
/**
 * Custom Post Type: Artemis CTA. List columns: views, clicks, CTR.
 *
 * @package Artemis_Convert\Inc\Admin\CTA
 */

namespace Artemis_Convert\Inc\Admin\CTA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CPT_CTA
 */
class CPT_CTA {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'artemis_cta';

	/**
	 * Register the CPT and list columns.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'CTAs', 'post type general name', 'artemis-convert' ),
			'singular_name'      => _x( 'CTA', 'post type singular name', 'artemis-convert' ),
			'menu_name'          => _x( 'CTAs', 'admin menu', 'artemis-convert' ),
			'all_items'          => __( 'Todos os CTAs', 'artemis-convert' ),
			'add_new'            => __( 'Adicionar novo', 'artemis-convert' ),
			'add_new_item'       => __( 'Adicionar novo CTA', 'artemis-convert' ),
			'edit_item'          => __( 'Editar CTA', 'artemis-convert' ),
			'new_item'           => __( 'Novo CTA', 'artemis-convert' ),
			'view_item'          => __( 'Ver CTA', 'artemis-convert' ),
			'search_items'       => __( 'Buscar CTAs', 'artemis-convert' ),
			'not_found'          => __( 'Nenhum CTA encontrado', 'artemis-convert' ),
			'not_found_in_trash' => __( 'Nenhum CTA na lixeira', 'artemis-convert' ),
		);
		$args   = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'artemis-convert',
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
		);
		register_post_type( self::POST_TYPE, $args );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'orderby_columns' ) );
		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_duplicate' ) );
	}

	/**
	 * Hide date and tags columns by default; user can show them via Screen options.
	 *
	 * @param array    $hidden  Default hidden columns.
	 * @param \WP_Screen $screen Current screen.
	 * @return array
	 */
	public function default_hidden_columns( $hidden, $screen ) {
		if ( $screen && $screen->id === 'edit-' . self::POST_TYPE ) {
			$hidden[] = 'date';
			$hidden[] = 'taxonomy-post_tag';
		}
		return $hidden;
	}

	/**
	 * Add Duplicate to row actions.
	 *
	 * @param array   $actions Row actions.
	 * @param \WP_Post $post   Post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( $post->post_type !== self::POST_TYPE || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'artemis_convert_duplicate_cta',
					'post'     => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			'artemis_convert_duplicate_cta_' . $post->ID
		);
		$actions['artemis_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicar', 'artemis-convert' ) . '</a>';
		return $actions;
	}

	/**
	 * Handle duplicate CTA: create copy and redirect to edit screen.
	 */
	public function handle_duplicate() {
		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'artemis_convert_duplicate_cta' ) {
			return;
		}
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}
		$post_id = (int) $_GET['post'];
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'artemis_convert_duplicate_cta_' . $post_id ) ) {
			wp_die( esc_html__( 'Link inválido ou expirado.', 'artemis-convert' ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Sem permissão para duplicar este CTA.', 'artemis-convert' ) );
		}
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			wp_die( esc_html__( 'CTA não encontrado.', 'artemis-convert' ) );
		}
		$new_id = $this->duplicate_cta( $post_id );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			wp_die( esc_html__( 'Não foi possível duplicar o CTA.', 'artemis-convert' ) );
		}
		wp_safe_redirect( add_query_arg( array( 'post' => $new_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
		exit;
	}

	/**
	 * Create a copy of a CTA post (meta and taxonomies); new post has "(Cópia)" in title, no views/clicks.
	 *
	 * @param int $post_id Original CTA post ID.
	 * @return int|false New post ID or false on failure.
	 */
	private function duplicate_cta( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		$new_title = $post->post_title . ' (' . __( 'Cópia', 'artemis-convert' ) . ')';
		$new_post = array(
			'post_title'   => $new_title,
			'post_status'  => $post->post_status,
			'post_type'    => self::POST_TYPE,
			'post_author'  => get_current_user_id(),
		);
		$new_id = wp_insert_post( $new_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return false;
		}
		$meta_keys = array(
			Meta_Box_CTA::META_TYPE,
			Meta_Box_CTA::META_SHORTCODE,
			Meta_Box_CTA::META_SHORTCODE_DESKTOP,
			Meta_Box_CTA::META_SHORTCODE_MOBILE,
			Meta_Box_CTA::META_IMAGE,
			Meta_Box_CTA::META_IMAGE_MOBILE,
			Meta_Box_CTA::META_LINK,
			Meta_Box_CTA::META_UTM_SOURCE,
			Meta_Box_CTA::META_UTM_MEDIUM,
			Meta_Box_CTA::META_UTM_CAMPAIGN,
			Meta_Box_CTA::META_POSITION,
			Meta_Box_CTA::META_EXCLUDED_URLS,
		);
		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( $value !== '' ) {
				update_post_meta( $new_id, $key, $value );
			}
		}
		$taxonomies = array( 'category', 'post_tag' );
		foreach ( $taxonomies as $tax ) {
			$terms = wp_get_object_terms( $post_id, $tax );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				wp_set_object_terms( $new_id, wp_list_pluck( $terms, 'term_id' ), $tax );
			}
		}
		return $new_id;
	}

	/**
	 * Set list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function set_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( $key === 'title' ) {
				$new['artemis_type']          = __( 'Tipo', 'artemis-convert' );
				$new['artemis_image_desktop'] = __( 'Imagem (desktop)', 'artemis-convert' );
				$new['artemis_image_mobile']  = __( 'Imagem (mobile)', 'artemis-convert' );
				$new['artemis_position']      = __( 'Posição', 'artemis-convert' );
				$new['artemis_views']         = __( 'Visualizações', 'artemis-convert' );
				$new['artemis_clicks']        = __( 'Cliques', 'artemis-convert' );
				$new['artemis_ctr']           = __( 'CTR', 'artemis-convert' );
			}
		}
		return $new;
	}

	/**
	 * Output column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function column_content( $column, $post_id ) {
		$views    = (int) get_post_meta( $post_id, '_artemis_convert_cta_views', true );
		$clicks   = (int) get_post_meta( $post_id, '_artemis_convert_cta_clicks', true );
		$ctr      = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;
		$position = get_post_meta( $post_id, Meta_Box_CTA::META_POSITION, true );
		$type     = get_post_meta( $post_id, Meta_Box_CTA::META_TYPE, true );
		if ( ! in_array( $type, array( 'image', 'shortcode' ), true ) ) {
			$type = 'image';
		}
		$position_labels = array(
			'top'    => __( 'Topo', 'artemis-convert' ),
			'middle' => __( 'Meio', 'artemis-convert' ),
			'bottom' => __( 'Final', 'artemis-convert' ),
			'both'   => __( 'Final', 'artemis-convert' ),
		);

		switch ( $column ) {
			case 'artemis_type':
				echo esc_html( $type === 'shortcode' ? __( 'Shortcode', 'artemis-convert' ) : __( 'Imagem', 'artemis-convert' ) );
				break;
			case 'artemis_image_desktop':
				$image_id = (int) get_post_meta( $post_id, Meta_Box_CTA::META_IMAGE, true );
				if ( $image_id ) {
					$src = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					if ( $src ) {
						echo '<img src="' . esc_url( $src ) . '" alt="" class="artemis-cta-list-thumb" loading="lazy" />';
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;
			case 'artemis_image_mobile':
				$image_id = (int) get_post_meta( $post_id, Meta_Box_CTA::META_IMAGE_MOBILE, true );
				if ( $image_id ) {
					$src = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					if ( $src ) {
						echo '<img src="' . esc_url( $src ) . '" alt="" class="artemis-cta-list-thumb" loading="lazy" />';
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;
			case 'artemis_position':
				// Multi-posição: meta pode ser array; string antiga continua suportada.
				$pos_list = is_array( $position ) ? $position : array( $position ?: 'bottom' );
				$labels   = array();
				foreach ( $pos_list as $p ) {
					$labels[] = isset( $position_labels[ $p ] ) ? $position_labels[ $p ] : __( 'Final', 'artemis-convert' );
				}
				echo esc_html( implode( ', ', array_unique( $labels ) ) );
				break;
			case 'artemis_views':
				echo esc_html( number_format_i18n( $views ) );
				break;
			case 'artemis_clicks':
				echo esc_html( number_format_i18n( $clicks ) );
				break;
			case 'artemis_ctr':
				echo esc_html( $ctr . '%' );
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['artemis_views']  = 'artemis_views';
		$columns['artemis_clicks'] = 'artemis_clicks';
		return $columns;
	}

	/**
	 * Order by meta for views/clicks.
	 *
	 * @param \WP_Query $query Query.
	 */
	public function orderby_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::POST_TYPE ) {
			return;
		}
		$orderby = $query->get( 'orderby' );
		if ( $orderby === 'artemis_views' ) {
			$query->set( 'meta_key', '_artemis_convert_cta_views' );
			$query->set( 'orderby', 'meta_value_num' );
		}
		if ( $orderby === 'artemis_clicks' ) {
			$query->set( 'meta_key', '_artemis_convert_cta_clicks' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
