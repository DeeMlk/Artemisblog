<?php
/**
 * Analytics: REST endpoints for view/click and click redirect handler.
 *
 * @package Artemis_Convert\Inc\Frontend
 */

namespace Artemis_Convert\Inc\Frontend;

use Artemis_Convert\Inc\Admin\CTA\CPT_CTA;
use Artemis_Convert\Inc\Admin\CTA\Meta_Box_CTA;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA_Analytics
 */
class CTA_Analytics {

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'artemis-convert/v1';

	/**
	 * Register REST routes for view and click (optional: click can be redirect-only).
	 */
	public function register_routes() {
		register_rest_route( self::REST_NAMESPACE, '/view', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_record_view' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'cta_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		) );
		register_rest_route( self::REST_NAMESPACE, '/click', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_record_click' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'cta_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		) );
	}

	/**
	 * Record view via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_record_view( WP_REST_Request $request ) {
		if ( ! \Artemis_Convert\Inc\Admin\Settings::analytics_enabled() ) {
			return new WP_REST_Response( array( 'ok' => false ), 200 );
		}
		$cta_id = $request->get_param( 'cta_id' );
		if ( ! $cta_id || get_post_type( $cta_id ) !== CPT_CTA::POST_TYPE ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}
		$this->increment_views( $cta_id );
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Record click via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_record_click( WP_REST_Request $request ) {
		if ( ! \Artemis_Convert\Inc\Admin\Settings::analytics_enabled() ) {
			return new WP_REST_Response( array( 'ok' => false ), 200 );
		}
		$cta_id = $request->get_param( 'cta_id' );
		if ( ! $cta_id || get_post_type( $cta_id ) !== CPT_CTA::POST_TYPE ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}
		$this->increment_clicks( $cta_id );
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Increment views meta.
	 *
	 * @param int $cta_id CTA post ID.
	 */
	public function increment_views( $cta_id ) {
		$current = (int) get_post_meta( $cta_id, '_artemis_convert_cta_views', true );
		update_post_meta( $cta_id, '_artemis_convert_cta_views', $current + 1 );
	}

	/**
	 * Increment clicks meta.
	 *
	 * @param int $cta_id CTA post ID.
	 */
	public function increment_clicks( $cta_id ) {
		$current = (int) get_post_meta( $cta_id, '_artemis_convert_cta_clicks', true );
		update_post_meta( $cta_id, '_artemis_convert_cta_clicks', $current + 1 );
	}

	/**
	 * Handle click redirect: ?artemis_convert_click=1&cta_id=123
	 */
	public function handle_click_redirect() {
		if ( empty( $_GET['artemis_convert_click'] ) || empty( $_GET['cta_id'] ) ) {
			return;
		}
		$cta_id = absint( $_GET['cta_id'] );
		if ( ! $cta_id || get_post_type( $cta_id ) !== CPT_CTA::POST_TYPE ) {
			return;
		}
		if ( \Artemis_Convert\Inc\Admin\Settings::analytics_enabled() ) {
			$this->increment_clicks( $cta_id );
		}
		$destination = get_post_meta( $cta_id, Meta_Box_CTA::META_LINK, true );
		if ( ! $destination ) {
			$destination = home_url( '/' );
		}
		$utm_s = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_SOURCE, true );
		$utm_m = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_MEDIUM, true );
		$utm_c = get_post_meta( $cta_id, Meta_Box_CTA::META_UTM_CAMPAIGN, true );
		if ( $utm_s || $utm_m || $utm_c ) {
			$destination = add_query_arg( array_filter( array(
				'utm_source'   => $utm_s,
				'utm_medium'   => $utm_m,
				'utm_campaign' => $utm_c,
			) ), $destination );
		}
		// wp_safe_redirect() só permite URLs do mesmo site; CTAs podem apontar para outros domínios.
		// A URL vem do meta do CTA (preenchido no admin), não do request.
		$destination = esc_url_raw( $destination );
		if ( $destination ) {
			wp_redirect( $destination, 302, 'Artemis Convert' );
			exit;
		}
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
}
