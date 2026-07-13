<?php
/**
 * Fired during plugin activation.
 *
 * @package Artemis_Convert\Inc\Core
 */

namespace Artemis_Convert\Inc\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 */
class Activator {

	/**
	 * Activate plugin: flush rewrite rules and set default option.
	 */
	public static function activate() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( dirname( __FILE__, 3 ) . '/artemis-convert.php' ) );
			wp_die( esc_html__( 'Artemis Convert requer PHP 7.4 ou superior.', 'artemis-convert' ) );
		}
		flush_rewrite_rules();
		if ( get_option( 'artemis_convert_analytics_enabled' ) === false ) {
			add_option( 'artemis_convert_analytics_enabled', '1' );
		}
	}
}
