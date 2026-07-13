<?php
/**
 * Plugin settings: analytics on/off and options page.
 *
 * @package Artemis_Convert\Inc\Admin
 */

namespace Artemis_Convert\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

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
	public function __construct( $version = '' ) {
		$this->version = $version ?: ARTEMIS_CONVERT_VERSION;
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		register_setting(
			'artemis_convert_settings',
			'artemis_convert_analytics_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					return $value === '1' ? '1' : '0';
				},
			)
		);
		add_settings_section(
			'artemis_convert_section',
			__( 'Métricas', 'artemis-convert' ),
			array( $this, 'section_callback' ),
			'artemis-convert-settings'
		);
		add_settings_field(
			'artemis_convert_analytics_enabled',
			__( 'Ativar métricas (views e cliques)', 'artemis-convert' ),
			array( $this, 'field_analytics_callback' ),
			'artemis-convert-settings',
			'artemis_convert_section'
		);
	}

	/**
	 * Section description.
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure se as visualizações e cliques dos CTAs devem ser registradas.', 'artemis-convert' ) . '</p>';
		echo '<p>' . esc_html__( 'Os dados ficam apenas no seu site; nenhuma informação é enviada a servidores externos.', 'artemis-convert' ) . '</p>';
	}

	/**
	 * Analytics checkbox field.
	 */
	public function field_analytics_callback() {
		$value = get_option( 'artemis_convert_analytics_enabled', '1' );
		?>
		<input type="hidden" name="artemis_convert_analytics_enabled" value="0" />
		<label>
			<input type="checkbox" name="artemis_convert_analytics_enabled" value="1" <?php checked( $value, '1' ); ?> />
			<?php esc_html_e( 'Registrar visualizações e cliques', 'artemis-convert' ); ?>
		</label>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'artemis_convert_settings' );
				do_settings_sections( 'artemis-convert-settings' );
				submit_button( __( 'Salvar', 'artemis-convert' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Check if analytics is enabled (helper for other classes).
	 *
	 * @return bool
	 */
	public static function analytics_enabled() {
		return get_option( 'artemis_convert_analytics_enabled', '1' ) === '1';
	}
}
