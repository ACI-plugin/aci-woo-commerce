<?php
/**
 * File for WC_Payment_Gateway abstraction
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

require_once WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/class-wc-ignite-gateway.php';

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * Abstract class for WC_Payment_Gateway
 *
 * @package ignite
 */
abstract class WC_Payment_Gateway_Ignite extends WC_Payment_Gateway {
	use WC_Ignite_Settings_Trait;

	/**
	 * Stores WC_Ignite_Gateway class object
	 *
	 * @var WC_Ignite_Gateway
	 */
	public $gateway;

	/**
	 * Stores payment method logo path
	 *
	 * @var string
	 */
	protected $payment_method_logo_path;

	/**
	 * Stores payment method logo link
	 *
	 * @var string
	 */
	protected $payment_method_logo_link;

	/**
	 * WC_Payment_Gateway_Ignite constructor
	 */
	public function __construct() {
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option( 'payment_method_title' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'wc_ignite_sanitize_form_fields' ) );
		add_filter( 'wc_ignite_settings_nav_tabs', array( $this, 'wc_ignite_settings_nav_tab' ) );
		add_filter( 'woocommerce_generate_' . $this->id . '_image_html', array( $this, 'wc_ignite_ignite_image_html' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_ignite_payment_scripts' ) );
		add_filter( 'woocommerce_gateway_icon', array( $this, 'woo_ignite_icon' ), 10, 2 );
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		$this->gateway = WC_Ignite_Gateway::load( $this->get_ignite_setting() );
	}

	/**
	 * Generate Text Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @since  1.0.0
	 * @return string
	 */
	public function generate_ignitelogo_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data   = wp_parse_args( $data, $defaults );
		$images = maybe_unserialize( $this->get_option( $key ) );

		ob_start();
		?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo esc_html( $this->get_tooltip_html( $data ) ); // WPCS: XSS ok. ?></label>
					</th>
					<td class="forminp">
					<?php
					if ( is_array( $images ) ) {
						foreach ( $images as $key => $image ) {
							?>
						<fieldset style="position: relative; display: flex; align-items: center; margin-bottom: 10px;">
							<img style="width:100px;" src="<?php echo esc_url( WC_HTTPS::force_https_url( $image ) ); ?>" />
							<div style="display: flex; align-items: center; margin-left: 10px;">
								<input class="<?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field_key ); ?>_delete[]" style="margin-right: 5px;" value="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); // WPCS: XSS ok. ?> />
								<label>Delete</label>
							</div>
							<?php
							echo esc_html( $this->get_description_html( $data ) ); // WPCS: XSS ok.
							?>
						</fieldset>
							<?php
						}
					}
					?>
					</td>
				</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Callback method for woocommerce_gateway_icon
	 *
	 * @param string $icon icon.
	 * @param string $id payment method id.
	 *
	 * @return string
	 */
	public function woo_ignite_icon( $icon, $id ) {
		if ( $this->id === $id ) {
			if ( $this->get_option( 'payment_method_logo_link' ) && $this->get_option( 'payment_method_logo_link' ) !== '' ) {
				$logos = maybe_unserialize( $this->get_option( 'payment_method_logo_link' ) );
				if ( is_array( $logos ) ) {
					foreach ( $logos as $logo ) {
						$icon .= '<img src="' . WC_HTTPS::force_https_url( $logo ) . '" alt="' . esc_attr( $this->get_option( 'payment_method_title' ) ) . '" width="24" height="24" />';
					}
				}
			}
		}
		return $icon;
	}
	/**
	 * Callback method for wp_enqueue_scripts action - loads common js files
	 */
	public function woo_ignite_payment_scripts() {
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'woo_ignite_common', WC_IGNITE_ASSETS . 'js/frontend/woo-ignite-common' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), WC_IGNITE_VERSION, false );
		}
	}

	/**
	 * Clicking Place Order button from FO will call this function
	 *
	 * @param int $order_id order id.
	 */
	public function process_payment( $order_id ) {
		// Place Order.
	}

	/**
	 * Callback method for woocommerce_update_options_payment_gateways_{id} action
	 */
	public function process_admin_options() {
		$this->payment_method_logo_path = $this->get_option( 'payment_method_logo_path' );
		$this->payment_method_logo_link = $this->get_option( 'payment_method_logo_link' );
		parent::process_admin_options();
	}

	/**
	 * Check if the payment method is an alternative payment method (APM).
	 *
	 * This function determines if the current payment method is classified as
	 * an alternative payment method (APM). Specifically, it checks if the
	 * payment method ID matches either 'woo_ignite_gpay' (Google Pay) or
	 * 'woo_ignite_apay' (Apple Pay).
	 *
	 * @return bool True if the payment method is an APM, false otherwise.
	 */
	public function is_apm() {
		$check = false;
		if ( 'woo_ignite_gpay' === $this->id || 'woo_ignite_apay' === $this->id ) {
			$check = true;
		}
		return $check;
	}

	/**
	 * Callback method for woocommerce_settings_api_sanitized_fields_{id} filter
	 *
	 * @param array $settings options settings.
	 *
	 * @return array
	 */
	public function wc_ignite_sanitize_form_fields( $settings ) {
		$nonce_value = wc_get_var($_REQUEST['_wpnonce']); // @codingStandardsIgnoreLine.
		if ( wp_verify_nonce( $nonce_value, 'woocommerce-settings' ) ) {
			$payment_method_logo_path        = maybe_unserialize( $this->payment_method_logo_path );
			$payment_method_logo_link        = maybe_unserialize( $this->payment_method_logo_link );
			$logo_key                        = $this->get_field_key( 'payment_method_logo_path' );
			$logo_link_key                   = $this->get_field_key( 'payment_method_logo_link' );
			$payment_method_logo_link_delete = isset( $_POST[ $logo_link_key . '_delete' ] ) ? wc_clean( wp_unslash( $_POST[ $logo_link_key . '_delete' ] ) ) : array();
			$logo_key_exists                 = ! empty( $_FILES[ $logo_key ] ?? '' ) ? wc_clean( $_FILES[ $logo_key ] ) : null;
			$is_apm                          = $this->is_apm();

			// Initialize the settings arrays if they are not already set.
			if ( ! is_array( $payment_method_logo_path ) ) {
				$payment_method_logo_path = array();
			}
			if ( ! is_array( $payment_method_logo_link ) ) {
				$payment_method_logo_link = array();
			}

			if ( $payment_method_logo_link_delete ) {
				foreach ( $payment_method_logo_link_delete as $delete_key ) {
					if ( isset( $payment_method_logo_path[ $delete_key ] ) ) {
						wp_delete_file( $payment_method_logo_path[ $delete_key ] );
						unset( $payment_method_logo_path[ $delete_key ] );
						unset( $payment_method_logo_link[ $delete_key ] );
					}
				}
			}

			if ( null !== $logo_key_exists ) {
				$file_name = ! empty( $_FILES[ $logo_key ]['name'] ?? '' ) ? wc_clean( $_FILES[ $logo_key ]['name'] ) : null;
				if ( null !== $file_name ) {
					$tmp_name = ! empty( $_FILES[ $logo_key ]['tmp_name'] ?? '' ) ? wc_clean( $_FILES[ $logo_key ]['tmp_name'] ) : null;
					if ( null !== $tmp_name ) {
						require_once ABSPATH . 'wp-admin/includes/image.php';
						require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
						require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
						$filesystem = new WP_Filesystem_Direct( true );
						$content    = $filesystem->get_contents( $tmp_name );
						$file_type  = wp_check_filetype( $file_name );

						if ( count( $payment_method_logo_path ) >= 20 ) {
							WC_Admin_Settings::add_error( esc_html__( 'You can upload a maximum of 20 logos.', 'woocommerce' ) );
						} elseif ( isset( $_FILES[ $logo_key ] ['size'] ) && $_FILES[ $logo_key ]['size'] > 100 * 1024 ) {
							WC_Admin_Settings::add_error( esc_html__( 'Invalid file size/type', 'woocommerce' ) );
						} elseif ( ! in_array( $file_type['ext'], array( 'jpg', 'jpeg', 'png' ), true ) ) {
							WC_Admin_Settings::add_error( esc_html__( 'Invalid file size/type', 'woocommerce' ) );
						} elseif ( ! file_is_displayable_image( $tmp_name ) || false === $content ) {
							WC_Admin_Settings::add_error( esc_html__( 'Invalid logo file type', 'woocommerce' ) );
						} else {
							$status = wp_upload_bits( $file_name, null, $content );
							if ( $status['error'] ) {
								WC_Admin_Settings::add_error( $status['error'] );
							} else {
								if ( $is_apm ) {
									if ( isset( $payment_method_logo_path[0] ) ) {
										wp_delete_file( $payment_method_logo_path[0] );
									}
									$payment_method_logo_path = array();
									$payment_method_logo_link = array();
								}
								$payment_method_logo_path[] = $status['file'];
								$payment_method_logo_link[] = $status['url'];
							}
						}
					}
				}
				unset( $_FILES[ $logo_key ] );
			}
			// Serialize the final arrays.
			$settings['payment_method_logo_path'] = $payment_method_logo_path ? maybe_serialize( $payment_method_logo_path ) : '';
			$settings['payment_method_logo_link'] = $payment_method_logo_link ? maybe_serialize( $payment_method_logo_link ) : '';
		}
		return $settings;
	}

	/**
	 * Callback method for wc_ignite_settings_nav_tabs filter
	 *
	 * @param array $tabs admin settings tabs.
	 *
	 * @return array
	 */
	public function wc_ignite_settings_nav_tab( $tabs ) {
		$tabs[ $this->id ] = $this->method_title;
		return $tabs;
	}

	/**
	 * Callback method for woocommerce_generate_{id}_image_html filter
	 *
	 * @see WC_Settings_API()->generate_text_html() for code reference
	 *
	 * @param string $field_html The markup of the field being generated (initiated as an empty string).
	 * @param string $key The key of the field.
	 * @param array  $data The attributes of the field as an associative array.
	 * @param object $current_object The current WC_Settings_API object.
	 *
	 * @return string
	 */
	public function wc_ignite_ignite_image_html( $field_html, $key, $data, $current_object ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);
		$data      = wp_parse_args( $data, $defaults );
		ob_start();
		include WC_IGNITE_PLUGIN_FILE_PATH . 'ignite/includes/settings/admin/views/html-image.php';
		return ob_get_clean();
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$logger = wc_get_ignite_logger();
		$order  = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( 'Invalid order ID.' );
		}

		$transaction_id = $order->get_transaction_id();

		if ( empty( $amount ) ) {
			$amount = $order->get_total();
		}

		try {
			if ( '' !== $transaction_id ) {
				$params = array(
					'transactionId' => $transaction_id,
				);
				if ( ! empty( $amount ) ) {
					$params['refundedAmount'] = floatval( $amount );
				}

				$result       = $this->gateway->refund->create( $params );
				$response_msg = $result;
				$psp_response = json_decode( $result, true );
				if ( isset( $psp_response['action'] ) ) {
					$order->add_order_note(
						sprintf(
							// Translators: %s is the refunded amount.
							__( 'Order refunded in Ignite. Amount: %s', 'woocommerce' ),
							wc_price(
								$amount,
								array(
									'currency' => $order->get_currency(),
								)
							)
						),
						0,
						true
					);
				} else {
					$order->add_order_note( $response_msg, 0, true );
					return new WP_Error( 'refund-error', $response_msg );
				}
				return $response_msg;
			}
		} catch ( Exception $e ) {
			$order->add_order_note( $e->getMessage(), 0, true );
			$error_logger = array(
				'error' => $e,
			);
			$logger->error( $error_logger, array( 'source' => 'ignite Error' ) );
			return new WP_Error( 'refund-error', $e->getMessage() );
		}
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );
		return $is_available;
	}

	/**
	 * Get the specific payment method settings
	 *
	 * @return array
	 */
	public function get_api_setting() {
		return get_option( 'woocommerce_' . $this->id . '_settings' );
	}

	/**
	 * Get the payment action of the specific payment method
	 *
	 * @return string
	 */
	public function get_payment_action() {
		return $this->get_api_setting()['payment_action_option'] ?? '';
	}

	/**
	 * Get the ignite common settings
	 *
	 * @return array
	 */
	public function get_ignite_setting() {
		return get_option( 'woocommerce_ignite_api_settings' );
	}

	/**
	 * Check whether the ignite is enabled or not
	 *
	 * @return bool
	 */
	public function is_ignite_enabled() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['enabled'] ) ? ( ( 'yes' === $settings['enabled'] ) ? true : false ) : false;
	}

	/**
	 * Get the api mode test/live
	 *
	 * @return string
	 */
	public function get_api_mode() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['mode'] ) ? $settings['mode'] : '';
	}

	/**
	 * Get api key
	 *
	 * @return string
	 */
	public function get_api_key() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_api_mode();
		return isset( $settings[ $mode . '_key' ] ) ? $settings[ $mode . '_key' ] : '';
	}

	/**
	 * Get api publishable key
	 *
	 * @return string
	 */
	public function get_api_publishable_key() {
		$settings = $this->get_ignite_setting();
		$mode     = $this->get_api_mode();
		return isset( $settings[ $mode . '_publishable_key' ] ) ? $settings[ $mode . '_publishable_key' ] : '';
	}

	/**
	 * Get save card option
	 *
	 * @return bool
	 */
	public function get_save_card_option() {
		$settings = $this->get_api_setting();
		if ( is_user_logged_in() ) {
			return ( isset( $settings['save_card'] ) && 'Y' === $settings['save_card'] ) ? true : false;
		}
		return false;
	}

	/**
	 * Get webhook url
	 *
	 * @return string
	 */
	public function get_webhook_url() {
		$settings = $this->get_ignite_setting();
		return isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';
	}

	/**
	 * Function to get the payment method list item to show in my account payment method
	 *
	 * @param array $item method list.
	 * @param objet $payment_token payment token data.
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( $this->id === $payment_token->get_gateway_id() ) {
			$item['method']['last4'] = $payment_token->get_card_masked_number();
			$item['method']['brand'] = ucfirst( $payment_token->get_brand() );
			if ( $payment_token->get_expires() ) {
				$item['expires'] = sprintf( '%s', $payment_token->get_expires() );
			} else {
				$item['expires'] = __( 'n/a', 'woocommerce' );
			}
		}

		return $item;
	}
	/**
	 * Method to check fast checkout
	 */
	public function is_fastcheckout() {
		return false;
	}
}
