<?php
/**
 * CTA display: inject into the_content and shortcode.
 *
 * @package Artemis_Convert\Inc\Frontend
 */

namespace Artemis_Convert\Inc\Frontend;

use Artemis_Convert\Inc\Admin\CTA\CPT_CTA;
use Artemis_Convert\Inc\Admin\CTA\Meta_Box_CTA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA
 */
class CTA {

	/**
	 * Filter post content and insert CTAs.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( ! is_singular( 'post' ) ) {
			return $content;
		}
		global $post;
		if ( ! $post || ! $post->ID ) {
			return $content;
		}
		// Não injetar CTAs quando o conteúdo é de um item de listagem (ex.: JetEngine Listing Grid).
		// Nesse caso o $post global é o item do loop, mas get_queried_object_id() é a página/post da URL.
		if ( (int) $post->ID !== (int) get_queried_object_id() ) {
			return $content;
		}
		return $this->insert_ctas( $content, $post );
	}

	/**
	 * Insert CTAs into content by category/tag and position.
	 *
	 * @param string   $content Content.
	 * @param \WP_Post $post    Current post.
	 * @return string
	 */
	public function insert_ctas( $content, $post ) {
		$post_cats = wp_get_post_categories( $post->ID );
		$post_tags = wp_get_post_tags( $post->ID );
		$tag_ids   = array_map( function ( $t ) {
			return $t->term_id;
		}, $post_tags ? $post_tags : array() );
		$permalink = get_permalink( $post );

		$args = array(
			'post_type'      => CPT_CTA::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $post_cats,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_ids,
					'operator' => 'IN',
				),
			),
		);
		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return $content;
		}

		$top_html    = '';
		$middle_html  = '';
		$bottom_html  = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			$cta_id = get_the_ID();
			$meta   = $this->get_cta_meta( $cta_id );
			if ( ! $this->is_cta_renderable( $meta ) ) {
				continue;
			}
			if ( $this->is_excluded( $meta['excluded_urls'], $permalink ) ) {
				continue;
			}
			// CTA must share at least one category or tag with the post.
			$cta_cats = wp_get_post_categories( $cta_id );
			$cta_tags = wp_get_post_tags( $cta_id );
			$cta_tag_ids = array_map( function ( $t ) {
				return $t->term_id;
			}, $cta_tags ? $cta_tags : array() );
			$match_cat = empty( $cta_cats ) || ! empty( array_intersect( $cta_cats, $post_cats ) );
			$match_tag = empty( $cta_tag_ids ) || ! empty( array_intersect( $cta_tag_ids, $tag_ids ) );
			if ( ! $match_cat && ! $match_tag ) {
				continue;
			}
			$html = $this->render_cta_html( $cta_id, $meta );
			// Multi-posição: o mesmo CTA pode entrar em mais de um ponto do post.
			if ( in_array( 'top', $meta['position'], true ) ) {
				$top_html .= $html;
			}
			if ( in_array( 'middle', $meta['position'], true ) ) {
				$middle_html .= $html;
			}
			if ( in_array( 'bottom', $meta['position'], true ) ) {
				$bottom_html .= $html;
			}
		}
		wp_reset_postdata();

		if ( $middle_html ) {
			$content = $this->inject_cta_in_middle( $content, $middle_html );
		}
		$out = '';
		if ( $top_html ) {
			$out .= '<div class="artemis-convert-cta-wrap artemis-convert-cta-top">' . $top_html . '</div>';
		}
		$out .= $content;
		if ( $bottom_html ) {
			$out .= '<div class="artemis-convert-cta-wrap artemis-convert-cta-bottom">' . $bottom_html . '</div>';
		}
		return $out;
	}

	/**
	 * Insert CTA block in the middle of the content (after the half-way point, at a paragraph break).
	 *
	 * @param string $content     Post content.
	 * @param string $middle_html HTML of CTA(s) to insert.
	 * @return string
	 */
	private function inject_cta_in_middle( $content, $middle_html ) {
		$len = strlen( $content );
		if ( $len < 100 ) {
			return $content . '<div class="artemis-convert-cta-wrap artemis-convert-cta-middle">' . $middle_html . '</div>';
		}
		$half       = (int) ( $len / 2 );
		$first_half = substr( $content, 0, $half );
		$pos        = strrpos( $first_half, '</p>' );
		if ( $pos !== false ) {
			$insert_after = $pos + 4;
		} else {
			$insert_after = $half;
		}
		$wrap = '<div class="artemis-convert-cta-wrap artemis-convert-cta-middle">' . $middle_html . '</div>';
		return substr_replace( $content, $wrap, $insert_after, 0 );
	}

	/**
	 * Shortcode [artemis-cta id="123"] or [artemis-cta slug="meu-cta"].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'   => 0,
			'slug' => '',
		), $atts, 'artemis-cta' );

		$cta_id = 0;
		if ( ! empty( $atts['id'] ) ) {
			$cta_id = (int) $atts['id'];
		} elseif ( ! empty( $atts['slug'] ) ) {
			$post = get_page_by_path( $atts['slug'], OBJECT, CPT_CTA::POST_TYPE );
			if ( $post ) {
				$cta_id = $post->ID;
			}
		}
		if ( ! $cta_id ) {
			return '';
		}
		$meta = $this->get_cta_meta( $cta_id );
		if ( ! $this->is_cta_renderable( $meta ) ) {
			return '';
		}
		return '<div class="artemis-convert-cta-wrap artemis-convert-cta-shortcode">' . $this->render_cta_html( $cta_id, $meta ) . '</div>';
	}

	/**
	 * Get CTA meta for a post.
	 *
	 * @param int $cta_id CTA post ID.
	 * @return array
	 */
	public function get_cta_meta( $cta_id ) {
		$link     = get_post_meta( $cta_id, Meta_Box_CTA::META_LINK, true );
		$image    = get_post_meta( $cta_id, Meta_Box_CTA::META_IMAGE, true );
		$image_m  = get_post_meta( $cta_id, Meta_Box_CTA::META_IMAGE_MOBILE, true );
		$type     = get_post_meta( $cta_id, Meta_Box_CTA::META_TYPE, true );
		$shortcode = get_post_meta( $cta_id, Meta_Box_CTA::META_SHORTCODE, true );
		$shortcode_desktop = metadata_exists( 'post', $cta_id, Meta_Box_CTA::META_SHORTCODE_DESKTOP ) ? (bool) get_post_meta( $cta_id, Meta_Box_CTA::META_SHORTCODE_DESKTOP, true ) : true;
		$shortcode_mobile  = metadata_exists( 'post', $cta_id, Meta_Box_CTA::META_SHORTCODE_MOBILE ) ? (bool) get_post_meta( $cta_id, Meta_Box_CTA::META_SHORTCODE_MOBILE, true ) : true;
		$utm_s    = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_SOURCE, true );
		$utm_m    = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_MEDIUM, true );
		$utm_c    = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_CAMPAIGN, true );
		$position = get_post_meta( $cta_id, Meta_Box_CTA::META_POSITION, true );
		$excluded = get_post_meta( $cta_id, Meta_Box_CTA::META_EXCLUDED_URLS, true );
		// Posição é multi-seleção (array). Retrocompat: string antiga ('top'/'both'/etc).
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
		$excluded = is_string( $excluded ) ? array_filter( array_map( 'trim', explode( "\n", $excluded ) ) ) : (array) $excluded;
		return array(
			'type'           => $type,
			'image_id'       => (int) $image,
			'image_mobile_id' => (int) $image_m,
			'shortcode'      => $shortcode,
			'shortcode_desktop' => $shortcode_desktop,
			'shortcode_mobile' => $shortcode_mobile,
			'link'           => $link ?: '#',
			'utm_source'     => $utm_s,
			'utm_medium'     => $utm_m,
			'utm_campaign'   => $utm_c,
			'position'       => $positions,
			'excluded_urls'  => $excluded,
		);
	}

	/**
	 * Check whether a CTA has enough content to be rendered.
	 *
	 * @param array $meta CTA meta.
	 * @return bool
	 */
	private function is_cta_renderable( $meta ) {
		if ( isset( $meta['type'] ) && $meta['type'] === 'shortcode' ) {
			$desktop = ! empty( $meta['shortcode_desktop'] );
			$mobile  = ! empty( $meta['shortcode_mobile'] );
			return ! empty( $meta['shortcode'] ) && ( $desktop || $mobile );
		}
		return ! empty( $meta['image_id'] );
	}

	/**
	 * Check if current URL is in excluded list.
	 *
	 * @param array  $excluded_urls List of URLs or path patterns.
	 * @param string $permalink    Current permalink.
	 * @return bool
	 */
	public function is_excluded( $excluded_urls, $permalink ) {
		if ( empty( $excluded_urls ) ) {
			return false;
		}
		foreach ( $excluded_urls as $url ) {
			if ( strpos( $permalink, $url ) !== false || $url === $permalink ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Build shortcode CTA HTML.
	 *
	 * @param int   $cta_id CTA post ID.
	 * @param array $meta   CTA meta.
	 * @return string
	 */
	private function render_shortcode_cta_html( $cta_id, $meta ) {
		$shortcode = isset( $meta['shortcode'] ) ? trim( $meta['shortcode'] ) : '';
		if ( $shortcode === '' ) {
			return '';
		}

		$output = do_shortcode( shortcode_unautop( $shortcode ) );
		if ( trim( $output ) === '' ) {
			return '';
		}

		$data_attrs = '';
		if ( \Artemis_Convert\Inc\Admin\Settings::analytics_enabled() ) {
			$data_attrs = ' data-cta-id="' . esc_attr( (string) $cta_id ) . '"';
		}
		$classes = array(
			'artemis-convert-cta-shortcode-content',
			'artemis-convert-cta-track',
		);
		if ( empty( $meta['shortcode_desktop'] ) ) {
			$classes[] = 'artemis-convert-hide-desktop';
		}
		if ( empty( $meta['shortcode_mobile'] ) ) {
			$classes[] = 'artemis-convert-hide-mobile';
		}

		return sprintf(
			'<div class="%1$s"%2$s>%3$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$data_attrs,
			$output
		);
	}

	/**
	 * Build CTA HTML (link with optional redirect URL for click tracking).
	 *
	 * @param int   $cta_id CTA post ID.
	 * @param array $meta   CTA meta.
	 * @return string
	 */
	public function render_cta_html( $cta_id, $meta ) {
		if ( isset( $meta['type'] ) && $meta['type'] === 'shortcode' ) {
			return $this->render_shortcode_cta_html( $cta_id, $meta );
		}

		$image_id  = $meta['image_id'];
		$src       = wp_get_attachment_image_url( $image_id, 'full' );
		if ( ! $src ) {
			return '';
		}
		$image_mobile_id = isset( $meta['image_mobile_id'] ) ? (int) $meta['image_mobile_id'] : 0;
		$src_mobile      = $image_mobile_id ? wp_get_attachment_image_url( $image_mobile_id, 'full' ) : '';
		$has_mobile_img  = ! empty( $src_mobile );

		$destination = $meta['link'];
		$utm         = array();
		if ( ! empty( $meta['utm_source'] ) ) {
			$utm['utm_source'] = $meta['utm_source'];
		}
		if ( ! empty( $meta['utm_medium'] ) ) {
			$utm['utm_medium'] = $meta['utm_medium'];
		}
		if ( ! empty( $meta['utm_campaign'] ) ) {
			$utm['utm_campaign'] = $meta['utm_campaign'];
		}
		$analytics_on = \Artemis_Convert\Inc\Admin\Settings::analytics_enabled();
		if ( $analytics_on ) {
			$redirect_url = home_url( '/' ) . '?artemis_convert_click=1&cta_id=' . (int) $cta_id;
			if ( ! empty( $utm ) ) {
				$redirect_url = add_query_arg( $utm, $redirect_url );
			}
			$href = $destination;
			if ( ! empty( $utm ) ) {
				$href = add_query_arg( $utm, $href );
			}
			$data_attrs = ' data-cta-id="' . esc_attr( (string) $cta_id ) . '" data-destination="' . esc_attr( $destination ) . '" data-redirect-url="' . esc_attr( $redirect_url ) . '"';
		} else {
			$href = $destination;
			if ( ! empty( $utm ) ) {
				$href = add_query_arg( $utm, $href );
			}
			$data_attrs = '';
		}
		$img_alt = get_the_title( $cta_id );

		if ( $has_mobile_img ) {
			$img_desktop = sprintf(
				'<img src="%1$s" alt="%2$s" class="artemis-convert-cta-img artemis-convert-cta-img-desktop" loading="lazy" />',
				esc_url( $src ),
				esc_attr( $img_alt )
			);
			$img_mobile  = sprintf(
				'<img src="%1$s" alt="%2$s" class="artemis-convert-cta-img artemis-convert-cta-img-mobile" loading="lazy" />',
				esc_url( $src_mobile ),
				esc_attr( $img_alt )
			);
			return sprintf(
				'<a href="%1$s" class="artemis-convert-cta-link artemis-convert-cta-track" %2$s>%3$s%4$s</a>',
				esc_url( $href ),
				$data_attrs,
				$img_desktop,
				$img_mobile
			);
		}

		return sprintf(
			'<a href="%1$s" class="artemis-convert-cta-link artemis-convert-cta-track" %2$s><img src="%3$s" alt="%4$s" class="artemis-convert-cta-img" loading="lazy" /></a>',
			esc_url( $href ),
			$data_attrs,
			esc_url( $src ),
			esc_attr( $img_alt )
		);
	}
}
