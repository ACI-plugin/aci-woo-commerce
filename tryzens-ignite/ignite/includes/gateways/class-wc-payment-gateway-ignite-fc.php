<?php
/**
 * File for Ignite Fast Checkout implementation
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

/**
 * Class for Ignite Gateway Fast Checkout
 */
class WC_Payment_Gateway_Ignite_FC extends WC_Payment_Gateway_Ignite {
	use WC_Fc_Settings_Trait;
	use DraftOrderTrait;
	use WC_Fc_Initialize_Trait;
	use WC_Ignite_Fc_Trait;

	/**
	 * Logger instance for logging activities.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Context for the logger.
	 *
	 * @var Context
	 */
	private $context;

	/**
	 * WC_Payment_Gateway_Ignite_FC constructor
	 */
	public function __construct() {
		$this->id           = 'woo_ignite_fc';
		$this->method_title = __( 'Ignite Fast Checkout Settings', 'woocommerce' );
		$this->supports     = array(
			'refunds',
		);
		$this->logger       = wc_get_ignite_logger();
		$this->context      = array( 'source' => 'Ignite-FC-logger' );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_fc_payment_scripts' ) );
		parent::__construct();
		$this->title = __( 'Fast Checkout', 'woocommerce' );
	}


	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_ignite_fc_payment_scripts() {
		if ( ! is_checkout() && ! is_admin() && $this->is_available() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_checkout', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-fc-checkout' . $suffix . '.js', array(), WC_IGNITE_VERSION, false );
			global $wp;
			$fc_params = $this->get_fc_params();
			wp_localize_script(
				$this->id . '_checkout',
				$this->id . '_obj',
				$fc_params
			);
			wp_set_script_translations( $this->id . '_checkout', 'woocommerce' );
		}
	}



	/**
	 * Initialise Gateway Settings form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/ignite-fc-settings.php';
		}
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
	}

	/**
	 * Method to check apm
	 */
	public function is_fastcheckout() {
		return true;
	}
}
