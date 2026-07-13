<?php
/**
 * Frontend: enqueue assets and register CTA + analytics.
 *
 * @package Artemis_Convert\Inc\Frontend
 */

namespace Artemis_Convert\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Frontend
 */
class Frontend {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( $version ) {
		$this->version = $version;
		$this->register_cta();
		$this->register_analytics();
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'artemis-convert-frontend',
			ARTEMIS_CONVERT_PLUGIN_URL . 'assets/frontend/css/frontend.css',
			array(),
			$this->version
		);
		wp_enqueue_script(
			'artemis-convert-frontend',
			ARTEMIS_CONVERT_PLUGIN_URL . 'assets/frontend/js/frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_localize_script( 'artemis-convert-frontend', 'artemisConvert', array(
			'restUrl'   => rest_url( 'artemis-convert/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'analytics' => \Artemis_Convert\Inc\Admin\Settings::analytics_enabled(),
		) );
	}

	/**
	 * Register CTA display (the_content + shortcode).
	 */
	private function register_cta() {
		$cta = new CTA();
		add_filter( 'the_content', array( $cta, 'filter_content' ), 10, 1 );
		add_shortcode( 'artemis-cta', array( $cta, 'shortcode' ) );
	}

	/**
	 * Register analytics (REST and click redirect).
	 */
	private function register_analytics() {
		$analytics = new CTA_Analytics();
		add_action( 'rest_api_init', array( $analytics, 'register_routes' ) );
		add_action( 'template_redirect', array( $analytics, 'handle_click_redirect' ) );
	}
}
