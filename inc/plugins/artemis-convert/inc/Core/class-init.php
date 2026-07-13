<?php
/**
 * Main plugin initializer. Loads dependencies and registers admin/frontend hooks.
 *
 * @package Artemis_Convert\Inc\Core
 */

namespace Artemis_Convert\Inc\Core;

use Artemis_Convert\Inc\Admin\Admin;
use Artemis_Convert\Inc\Admin\CTA\CPT_CTA;
use Artemis_Convert\Inc\Admin\CTA\Meta_Box_CTA;
use Artemis_Convert\Inc\Admin\Settings;
use Artemis_Convert\Inc\Frontend\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 */
class Init {

	/**
	 * Singleton instance.
	 *
	 * @var Init|null
	 */
	protected static $instance = null;

	/**
	 * Loader instance.
	 *
	 * @var Loader
	 */
	protected $loader;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Initialize plugin (static entry point).
	 *
	 * @return Init
	 */
	public static function init() {
		if ( self::$instance === null ) {
			self::$instance = new self();
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version = ARTEMIS_CONVERT_VERSION;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load Loader only.
	 */
	private function load_dependencies() {
		$this->loader = new Loader();
	}

	/**
	 * Set locale and text domain.
	 */
	private function set_locale() {
		$this->loader->add_action(
			'plugins_loaded',
			$this,
			'load_plugin_textdomain'
		);
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			ARTEMIS_CONVERT_TEXT_DOMAIN,
			false,
			dirname( ARTEMIS_CONVERT_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register admin hooks: menu, enqueue, CPT, meta box, settings.
	 */
	private function define_admin_hooks() {
		$admin = new Admin( $this->version );
		$this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		$cpt = new CPT_CTA();
		$this->loader->add_action( 'init', $cpt, 'register_post_type' );

		new Meta_Box_CTA();

		$settings = new Settings();
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
	}

	/**
	 * Register public hooks: frontend assets, CTA display, analytics.
	 */
	private function define_public_hooks() {
		$frontend = new Frontend( $this->version );
		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_scripts' );
	}

	/**
	 * Run the loader.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get loader.
	 *
	 * @return Loader
	 */
	public function get_loader() {
		return $this->loader;
	}
}
