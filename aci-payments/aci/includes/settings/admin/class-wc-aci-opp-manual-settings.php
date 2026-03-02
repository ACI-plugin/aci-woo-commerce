<?php
/**
 * File for WC_ACI_OPP_Manual_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_ACI_OPP_Manual_Settings
 * Handles Manual Entry tab for OPP parameters
 */
class WC_ACI_OPP_Manual_Settings extends WC_Ignite_Settings_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_opp_manual_setting();
		parent::__construct();
	}

	/**
	 * Set ID and tab title.
	 */
	public function set_opp_manual_setting() {
		$this->id        = 'aci_opp_manual';
		$this->tab_title = __( 'OPP Parameter Settings: Manual Entry', 'woocommerce' );
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
			array( $this, 'woo_aci_admin_opp_manual_scripts' )
		);
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-opp-manual-settings.php';
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function woo_aci_admin_opp_manual_scripts() {
		// Only enqueue on WooCommerce settings page
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue CSS
		wp_enqueue_style( 'woo_aci_admin_opp_parameters', WC_ACI_ASSETS . 'css/admin/admin-opp-parameters.css', array(), WC_ACI_VERSION );

		// Enqueue JS with WooCommerce Backbone Modal dependency
		wp_enqueue_script( 'woo_aci_admin_opp_parameters', WC_ACI_ASSETS . 'js/admin/admin-opp-parameters' . $suffix . '.js', array( 'jquery', 'wc-backbone-modal' ), WC_ACI_VERSION, false );

		wp_localize_script(
			'woo_aci_admin_opp_parameters',
			'wooAciOPPParameters',
			array(
				'confirm_delete'     => __( 'Are you sure you want to remove this parameter?', 'woocommerce' ),
				'manual_field_key'   => $this->get_field_key( 'opp_parameters_manual' ),
				'dropdown_field_key' => 'woocommerce_aci_opp_dropdown_opp_parameters_dropdown',
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
	 * Generate HTML for manual entry OPP parameters field
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string HTML output
	 */
	public function generate_aci_opp_parameters_manual_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key, array() );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp" id="<?php echo esc_attr( $field_key ); ?>_container">
				<div class="aci-opp-manual-parameters-wrapper">
					<div class="aci-opp-manual-parameters-list">
						<?php
						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $index => $param ) {
								$this->render_manual_parameter_row( $field_key, $index, $param );
							}
						} else {
							// Always show at least one empty row by default.
							$this->render_manual_parameter_row( $field_key, 0, array() );
						}
						?>
					</div>
					<div class="aci-add-opp-manual-parameter">
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
	 * Render a single manual parameter row
	 *
	 * @param string $field_key Field key.
	 * @param int    $index Row index.
	 * @param array  $param Parameter data.
	 */
	private function render_manual_parameter_row( $field_key, $index, $param = array() ) {
		$key             = isset( $param['key'] ) ? $param['key'] : '';
		$value           = isset( $param['value'] ) ? $param['value'] : '';
		$use_random      = isset( $param['use_random'] ) ? $param['use_random'] : false;
		$random_type     = isset( $param['random_type'] ) ? $param['random_type'] : 'alphanumeric';
		$random_length   = isset( $param['random_length'] ) ? $param['random_length'] : '';
		$value_disabled  = $use_random ? 'disabled' : '';
		$random_disabled = ! $use_random ? 'disabled' : '';
		?>
		<div class="aci-opp-manual-parameter-row" data-index="<?php echo esc_attr( $index ); ?>">
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
						<label><?php esc_html_e( 'OPP Parameter Value', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<input type="text"
								name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][value]"
								value="<?php echo esc_attr( $value ); ?>"
								class="aci-opp-param-value"
								style="width: 100%;"
								<?php echo esc_attr( $value_disabled ); ?> />
							<div style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
								<label style="margin: 0;">
									<input type="checkbox"
										name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][use_random]"
										value="1"
										class="aci-opp-use-random"
										<?php checked( $use_random, true ); ?> />
									<?php esc_html_e( 'Use Random Value', 'woocommerce' ); ?>
								</label>
								<select name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][random_type]"
									class="aci-opp-random-type-select"
									style="width: auto; min-width: 200px;"
									<?php echo esc_attr( $random_disabled ); ?>>
									<option value="numeric" <?php selected( $random_type, 'numeric' ); ?>><?php esc_html_e( 'Random Number', 'woocommerce' ); ?></option>
									<option value="alphabetic" <?php selected( $random_type, 'alphabetic' ); ?>><?php esc_html_e( 'Random Character', 'woocommerce' ); ?></option>
									<option value="alphanumeric" <?php selected( $random_type, 'alphanumeric' ); ?>><?php esc_html_e( 'Random Alphanumeric Character', 'woocommerce' ); ?></option>
								</select>
								<input type="number"
									name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][random_length]"
									value="<?php echo esc_attr( $random_length ); ?>"
									class="aci-opp-random-length-input"
									style="width: 100px;"
									min="1"
									max="150"
									placeholder="<?php esc_attr_e( 'Length', 'woocommerce' ); ?>"
									<?php echo esc_attr( $random_disabled ); ?> />
							</div>
						</fieldset>
					</td>
				</tr>
			</table>
			<div class="aci-remove-opp-manual-parameter dashicons dashicons-trash" data-index="<?php echo esc_attr( $index ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Validate manual OPP parameters field
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Posted value.
	 * @return array Validated value
	 */
	public function validate_aci_opp_parameters_manual_field( $key, $value ) {
		$validated = array();

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $param ) {
				// Skip if both key and value are empty.
				if ( empty( $param['key'] ) && empty( $param['value'] ) && empty( $param['use_random'] ) ) {
					continue;
				}

				// Validate that key is not empty.
				if ( empty( $param['key'] ) ) {
					WC_Admin_Settings::add_error( __( 'OPP Parameter Key cannot be empty.', 'woocommerce' ) );
					continue;
				}

				// Validate that value is not empty if not using random.
				if ( empty( $param['use_random'] ) && empty( $param['value'] ) ) {
					WC_Admin_Settings::add_error( __( 'OPP Parameter Value cannot be empty when not using random values.', 'woocommerce' ) );
					continue;
				}

				// Validate random length if using random.
				if ( ! empty( $param['use_random'] ) ) {
					$length = intval( $param['random_length'] );
					if ( $length < 1 || $length > 150 ) {
						WC_Admin_Settings::add_error( __( 'Random value length must be between 1 and 150.', 'woocommerce' ) );
						continue;
					}
				}

				$validated[] = array(
					'key'           => sanitize_text_field( $param['key'] ),
					'value'         => sanitize_text_field( $param['value'] ),
					'use_random'    => ! empty( $param['use_random'] ),
					'random_type'   => isset( $param['random_type'] ) ? sanitize_text_field( $param['random_type'] ) : 'alphanumeric',
					'random_length' => isset( $param['random_length'] ) ? intval( $param['random_length'] ) : '',
				);
			}
		}

		return $validated;
	}

	/**
	 * Generate random value based on type and length
	 *
	 * @param string $type Random value type (numeric, alphabetic, alphanumeric).
	 * @param int    $length Length of random value.
	 * @return string
	 */
	public function generate_random_value( $type, $length ) {
		$length = max( 1, min( 150, intval( $length ) ) );

		switch ( $type ) {
			case 'numeric':
				$characters = '0123456789';
				break;
			case 'alphabetic':
				$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alphanumeric':
			default:
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
		}

		$random_string = '';
		$max           = strlen( $characters ) - 1;
		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ wp_rand( 0, $max ) ];
		}

		return $random_string;
	}
}
