<?php
/**
 * Admin: menu and enqueue scripts.
 *
 * @package Artemis_Convert\Inc\Admin
 */

namespace Artemis_Convert\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 */
class Admin {

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
	}

	/**
	 * Register admin menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Artemis Convert', 'artemis-convert' ),
			__( 'Artemis Convert', 'artemis-convert' ),
			'edit_posts',
			'artemis-convert',
			null,
			'dashicons-megaphone',
			30
		);
		add_submenu_page(
			'artemis-convert',
			__( 'Configurações', 'artemis-convert' ),
			__( 'Configurações', 'artemis-convert' ),
			'manage_options',
			'artemis-convert-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page (redirect to Settings class output if needed).
	 */
	public function render_settings_page() {
		// Settings page content is rendered by Settings class.
		$settings = new Settings( $this->version );
		$settings->render_page();
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'artemis_cta' ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style(
			'artemis-convert-admin',
			ARTEMIS_CONVERT_PLUGIN_URL . 'assets/admin/css/admin.css',
			array(),
			$this->version
		);
		wp_enqueue_script(
			'artemis-convert-admin',
			ARTEMIS_CONVERT_PLUGIN_URL . 'assets/admin/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}
}
