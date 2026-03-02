<?php
/**
 * File for WC_ACI_OPP_Dropdown_Settings class
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();

/**
 * Class for WC_ACI_OPP_Dropdown_Settings
 * Handles Dropdown Entry tab for OPP parameters with WooCommerce field mapping
 */
class WC_ACI_OPP_Dropdown_Settings extends WC_Ignite_Settings_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_opp_dropdown_setting();
		parent::__construct();
	}

	/**
	 * Set ID and tab title.
	 */
	public function set_opp_dropdown_setting() {
		$this->id        = 'aci_opp_dropdown';
		$this->tab_title = __( 'OPP Parameter Settings: Drop Down Entry', 'woocommerce' );
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
			array( $this, 'woo_aci_admin_opp_dropdown_scripts' )
		);
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-opp-dropdown-settings.php';
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function woo_aci_admin_opp_dropdown_scripts() {
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
				'woocommerce_fields' => $this->get_woocommerce_fields_list(),
				'dropdown_field_key' => $this->get_field_key( 'opp_parameters_dropdown' ),
				'manual_field_key'   => 'woocommerce_aci_opp_manual_opp_parameters_manual',
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
	 * Get list of WooCommerce fields available for mapping
	 *
	 * @return array List of WooCommerce fields grouped by category
	 */
	public function get_woocommerce_fields_list() {
		return array(
			'Cart Fields (Frontend Checkout Only)' => array(
				'Cart.tax_included'        => __( 'Cart - Check if prices include tax', 'woocommerce' ),
				'Cart.total'               => __( 'Cart - Get cart total amount', 'woocommerce' ),
				'Cart.total_tax'           => __( 'Cart - Get total tax', 'woocommerce' ),
				'Cart.subtotal'            => __( 'Cart - Get cart subtotal', 'woocommerce' ),
				'Cart.subtotal_tax'        => __( 'Cart - Get subtotal tax', 'woocommerce' ),
				'Cart.discount_total'      => __( 'Cart - Get discount total', 'woocommerce' ),
				'Cart.discount_tax'        => __( 'Cart - Get discount tax', 'woocommerce' ),
				'Cart.shipping_total'      => __( 'Cart - Get shipping total', 'woocommerce' ),
				'Cart.shipping_tax'        => __( 'Cart - Get shipping tax', 'woocommerce' ),
				'Cart.fee_total'           => __( 'Cart - Get total fee amount', 'woocommerce' ),
				'Cart.fee_tax'             => __( 'Cart - Get total fee tax', 'woocommerce' ),
				'Cart.cart_contents_total' => __( 'Cart - Get cart contents total', 'woocommerce' ),
				'Cart.cart_contents_tax'   => __( 'Cart - Get cart contents tax', 'woocommerce' ),
			),
			'Order Fields'                         => array(
				'Order.total_tax'      => __( 'Order - Total Tax', 'woocommerce' ),
				'Order.discount_total' => __( 'Order - Discount Total', 'woocommerce' ),
				'Order.discount_tax'   => __( 'Order - Discount Tax', 'woocommerce' ),
				'Order.shipping_total' => __( 'Order - Shipping Total', 'woocommerce' ),
				'Order.shipping_tax'   => __( 'Order - Shipping Tax', 'woocommerce' ),
				'Order.cart_tax'       => __( 'Order - Cart Tax', 'woocommerce' ),
			),
			'Checkout Fields'                      => array(
				'Checkout.tax_total'        => __( 'Checkout - Tax Total', 'woocommerce' ),
				'Checkout.customer_message' => __( 'Checkout - Customer Message', 'woocommerce' ),
			),
			'Billing Address'                      => array(
				'Billing.company' => __( 'Billing - Company', 'woocommerce' ),
			),
			'Shipping Address'                     => array(
				'Shipping.company' => __( 'Shipping - Company', 'woocommerce' ),
			),
			'Customer Fields'                      => array(
				'Customer.username'      => __( 'Customer - Username', 'woocommerce' ),
				'Customer.display_name'  => __( 'Customer - Display Name', 'woocommerce' ),
				'Customer.role'          => __( 'Customer - Role', 'woocommerce' ),
				'Customer.date_created'  => __( 'Customer - Date Created', 'woocommerce' ),
				'Customer.date_modified' => __( 'Customer - Date Modified', 'woocommerce' ),
			),
		);
	}

	/**
	 * Generate HTML for dropdown entry OPP parameters field
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string HTML output
	 */
	public function generate_aci_opp_parameters_dropdown_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key, array() );
		$wc_fields = $this->get_woocommerce_fields_list();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp" id="<?php echo esc_attr( $field_key ); ?>_container">
				<div class="aci-opp-dropdown-parameters-wrapper">
					<div class="aci-opp-dropdown-parameters-list">
						<?php
						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $index => $param ) {
								$this->render_dropdown_parameter_row( $field_key, $index, $param, $wc_fields );
							}
						} else {
							// Always show at least one empty row by default.
							$this->render_dropdown_parameter_row( $field_key, 0, array(), $wc_fields );
						}
						?>
					</div>
					<div class="aci-add-opp-dropdown-parameter">
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
	 * Render a single dropdown parameter row
	 *
	 * @param string $field_key Field key.
	 * @param int    $index Row index.
	 * @param array  $param Parameter data.
	 * @param array  $wc_fields WooCommerce fields list.
	 */
	private function render_dropdown_parameter_row( $field_key, $index, $param = array(), $wc_fields = array() ) {
		$key           = isset( $param['key'] ) ? $param['key'] : '';
		$wc_field_path = isset( $param['wc_field'] ) ? $param['wc_field'] : '';
		?>
		<div class="aci-opp-dropdown-parameter-row" data-index="<?php echo esc_attr( $index ); ?>">
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
							<p class="description">
								<a href="#" class="aci-opp-view-mappings">
									<?php esc_html_e( 'Click here to view the list of WooCommerce fields mapped to OPP parameters Value.', 'woocommerce' ); ?>
								</a>
							</p>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label><?php esc_html_e( 'OPP Parameter Value', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<select name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][wc_field]"
								class="aci-opp-wc-field-select"
								style="width: 100%;">
								<option value=""><?php esc_html_e( 'Select a WooCommerce field', 'woocommerce' ); ?></option>
								<?php foreach ( $wc_fields as $group_label => $fields ) : ?>
									<optgroup label="<?php echo esc_attr( $group_label ); ?>">
										<?php foreach ( $fields as $field_path => $field_label ) : ?>
											<option value="<?php echo esc_attr( $field_path ); ?>" <?php selected( $wc_field_path, $field_path ); ?>>
												<?php echo esc_html( $field_label ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</fieldset>
					</td>
				</tr>
			</table>
			<div class="aci-remove-opp-dropdown-parameter dashicons dashicons-trash" data-index="<?php echo esc_attr( $index ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Validate dropdown OPP parameters field
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Posted value.
	 * @return array Validated value
	 */
	public function validate_aci_opp_parameters_dropdown_field( $key, $value ) {
		$validated = array();

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $param ) {
				// Skip if both key and wc_field are empty.
				if ( empty( $param['key'] ) && empty( $param['wc_field'] ) ) {
					continue;
				}

				// Validate that key is not empty.
				if ( empty( $param['key'] ) ) {
					WC_Admin_Settings::add_error( __( 'OPP Parameter Key cannot be empty.', 'woocommerce' ) );
					continue;
				}

				// Validate that wc_field is not empty.
				if ( empty( $param['wc_field'] ) ) {
					WC_Admin_Settings::add_error( __( 'OPP Parameter Value (WooCommerce field) must be selected.', 'woocommerce' ) );
					continue;
				}

				$validated[] = array(
					'key'      => sanitize_text_field( $param['key'] ),
					'wc_field' => sanitize_text_field( $param['wc_field'] ),
				);
			}
		}

		return $validated;
	}
}
