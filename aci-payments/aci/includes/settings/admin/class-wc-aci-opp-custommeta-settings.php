<?php
/**
 * File for WC_ACI_OPP_CustomMeta_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_ACI_OPP_CustomMeta_Settings
 * Handles Custom Field Mapping tab for OPP parameters
 */
class WC_ACI_OPP_CustomMeta_Settings extends WC_Ignite_Settings_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_opp_custommeta_setting();
		parent::__construct();
	}

	/**
	 * Set ID and tab title.
	 */
	public function set_opp_custommeta_setting() {
		$this->id        = 'aci_opp_custommeta';
		$this->tab_title = __( 'OPP Parameter Settings: Custom Entry', 'woocommerce' );
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		parent::hooks();

		// Save.
		add_action(
			'woocommerce_update_options_checkout_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		// Register tab.
		add_filter(
			'wc_ignite_settings_nav_tabs',
			array( $this, 'admin_nav_tab' )
		);

		// Render settings page.
		add_action(
			'woocommerce_ignite_settings_checkout_' . $this->id,
			array( $this, 'admin_options' )
		);

		// Admin JS/CSS.
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'woo_aci_admin_opp_custommeta_scripts' )
		);
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-opp-custommeta-settings.php';
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function woo_aci_admin_opp_custommeta_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'woo_aci_admin_opp_parameters', WC_ACI_ASSETS . 'css/admin/admin-opp-parameters.css', array(), WC_ACI_VERSION );

		wp_enqueue_script( 'woo_aci_admin_opp_parameters', WC_ACI_ASSETS . 'js/admin/admin-opp-parameters' . $suffix . '.js', array( 'jquery', 'wc-backbone-modal' ), WC_ACI_VERSION, false );

		wp_localize_script(
			'woo_aci_admin_opp_parameters',
			'wooAciOPPParameters',
			array(
				'confirm_delete'       => __( 'Are you sure you want to remove this parameter?', 'woocommerce' ),
				'woocommerce_fields'   => WC_ACI_OPP_Dropdown_Settings::get_woocommerce_fields_list(),
				'manual_field_key'     => 'woocommerce_aci_opp_manual_opp_parameters_manual',
				'dropdown_field_key'   => 'woocommerce_aci_opp_dropdown_opp_parameters_dropdown',
				'custommeta_field_key' => $this->get_field_key( 'opp_parameters_custommeta' ),
			)
		);
	}

	/**
	 * Generate Title HTML with description.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_title_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title' => '',
			'desc'  => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		</table>
		<h3><?php echo wp_kses_post( $data['title'] ); ?></h3>
		<?php if ( ! empty( $data['desc'] ) ) : ?>
			<p><?php echo wp_kses_post( $data['desc'] ); ?></p>
		<?php endif; ?>
		<table class="form-table">
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate HTML for custom meta OPP parameters field.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string HTML output
	 */
	public function generate_aci_opp_parameters_custommeta_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key, array() );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp" id="<?php echo esc_attr( $field_key ); ?>_container">
				<div class="aci-opp-custommeta-parameters-wrapper">
					<div class="aci-opp-custommeta-parameters-list">
						<?php
						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $index => $param ) {
								$this->render_custommeta_parameter_row( $field_key, $index, $param );
							}
						} else {
							$this->render_custommeta_parameter_row( $field_key, 0, array() );
						}
						?>
					</div>
					<div class="aci-add-opp-custommeta-parameter">
						<span class="dashicons dashicons-plus"></span>
					</div>
				</div>

				<!-- WooCommerce Backbone Modal Template for Delete Confirmation -->
				<script type="text/template" id="tmpl-wc-aci-opp-delete-modal">
					<div class="wc-backbone-modal">
						<div class="wc-backbone-modal-content">
							<section class="wc-backbone-modal-main" role="main">
								<header class="wc-backbone-modal-header">
									<h1><?php esc_html_e( 'Delete', 'woocommerce' ); ?></h1>
									<button class="modal-close modal-close-link dashicons dashicons-no-alt">
										<span class="screen-reader-text">Close modal panel</span>
									</button>
								</header>
								<article>
									<div class="wc-ppcp-modal-content">
										<p><?php esc_html_e( 'Are you sure you want to delete this OPP Parameter?', 'woocommerce' ); ?></p>
									</div>
								</article>
								<footer>
									<div class="inner">
										<button id="btn-ok" class="button"><?php esc_html_e( 'OK', 'woocommerce' ); ?></button>
										<button class="modal-close button"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
									</div>
								</footer>
							</section>
						</div>
					</div>
					<div class="wc-backbone-modal-backdrop modal-close"></div>
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single custom meta parameter row.
	 *
	 * @param string $field_key Field key.
	 * @param int    $index Row index.
	 * @param array  $param Parameter data.
	 */
	private function render_custommeta_parameter_row( $field_key, $index, $param = array() ) {
		$key      = isset( $param['key'] ) ? $param['key'] : '';
		$meta_key = isset( $param['meta_key'] ) ? $param['meta_key'] : '';
		?>
		<div class="aci-opp-custommeta-parameter-row" data-index="<?php echo esc_attr( $index ); ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'OPP Parameter Key', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<input type="text"
								name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][key]"
								value="<?php echo esc_attr( $key ); ?>"
								class="aci-opp-param-key"
								style="width: 100%;" />
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'WooCommerce Meta Key', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<input type="text"
								name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][meta_key]"
								value="<?php echo esc_attr( $meta_key ); ?>"
								class="aci-opp-meta-key"
								style="width: 100%;" />
						</fieldset>
					</td>
				</tr>
			</table>
			<div class="aci-remove-opp-custommeta-parameter dashicons dashicons-trash" data-index="<?php echo esc_attr( $index ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Validate custom meta OPP parameters field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Posted value.
	 * @return array Validated value
	 */
	public function validate_aci_opp_parameters_custommeta_field( $key, $value ) {
		$validated = array();

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $param ) {
				if ( empty( $param['key'] ) && empty( $param['meta_key'] ) ) {
					continue;
				}

				if ( empty( $param['key'] ) ) {
					WC_Admin_Settings::add_error( __( 'OPP Parameter Key cannot be empty.', 'woocommerce' ) );
					continue;
				}

				if ( empty( $param['meta_key'] ) ) {
					WC_Admin_Settings::add_error( __( 'WooCommerce Meta Key cannot be empty.', 'woocommerce' ) );
					continue;
				}

				$validated[] = array(
					'key'      => sanitize_text_field( $param['key'] ),
					'meta_key' => sanitize_text_field( $param['meta_key'] ),
				);
			}
		}

		return $validated;
	}
}
