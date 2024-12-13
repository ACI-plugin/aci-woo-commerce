<?php
/**
 * File for Aci APM implementation
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit();
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
/**
 * Class for Aci Gateway APM
 */
class WC_Payment_Gateway_Aci_APM extends WC_Payment_Gateway_Ignite {
	use WC_Aci_Settings_Trait;
	use DraftOrderTrait;
	use WC_Aci_Initialize_Trait;

	/**
	 * Stores aci apm settings
	 *
	 * @var $aci_apm_previous_data
	 */
	protected $aci_apm_previous_data;

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
	 * WC_Payment_Gateway_Aci_APM constructor
	 */
	public function __construct() {
		$this->id           = 'woo_aci_apm';
		$this->method_title = __( 'Alternative Payment Settings', 'woocommerce' );

		add_action( 'admin_enqueue_scripts', array( $this, 'woo_admin_aci_apm_scripts' ) );
		add_filter( 'woocommerce_generate_' . $this->id . '_apm_settings_html', array( $this, 'wc_aci_apm_settings_html' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'woo_aci_apm_payment_scripts' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_aci_apm_api_request' ) );
		$this->supports = array(
			'refunds',
		);
		$this->logger   = wc_get_logger();
		$this->context  = array( 'source' => 'Aci-return-url-logger' );
		parent::__construct();
		$this->title = __( 'APM', 'woocommerce' );
	}

	/**
	 * Callback method for woocommerce_api_{id} action
	 *
	 * @throws Exception If the payment process fails.
	 */
	public function handle_aci_apm_api_request() {
		global $wp;
		$redirect_url = wc_get_checkout_url();
		$is_admin     = wc_clean( wp_unslash( $_GET['is_admin'] ?? '' ) ); // phpcs:ignore
		$order        = $this->get_draft_order();
		if ( empty( $order ) ) {
			$order = $this->wc_aci_get_order_from_transaction( explode( '/', trim( $_GET['resourcePath'] ) )[3] ); // phpcs:ignore
		}
		if ( '1' === $is_admin ) {
			$redirect_url = $order->get_checkout_payment_url( false );
		}
		try {
			$checkout_id    = $order->get_meta( 'checkout_id' );
			$aci_payment_id = $order->get_meta( 'aci_payment_id' );
			if ( ! is_a( $order, 'WC_Order' ) || ! $checkout_id ) {
				throw new Exception( 'Order ID or checkout id not found' );
			}
			if ( isset( $wp->query_vars['wc-api'] ) && $wp->query_vars['wc-api'] === $this->id ) {
				$resource_path  = isset( $_GET['resourcePath'] ) ? sanitize_text_field( wp_unslash( $_GET['resourcePath'] ) ) : ''; // phpcs:ignore
				$params        = array(
					'resource_path' => $resource_path . '?entityId=' . $this->get_aci_entity_id(),
				);
				$psp_response  = json_decode( $this->gateway->transaction->get( $params ), true );
				$response_code = '';
				$success       = false;
				if ( isset( $psp_response['result'] ) ) {
					$result_code   = $psp_response['result']['code'];
					$response_code = $this->validate_response( $result_code );
				}
				$this->logger->info( 'Transaction service response for the order #' . $order->get_id() . ' : ' . wc_print_r( $psp_response, true ), $this->context );
				if ( 'SUCCESS' === $response_code ) {
					$payment_brand = str_replace( 'woo_aci_', '', $aci_payment_id );
					if ( 'PA' === $psp_response['paymentType'] ) {
						$order->set_transaction_id( $psp_response['id'] );
						$success = $order->update_status( 'on-hold' );
						$order->set_payment_method( $this->get_apm_gateway_id() );
						// Translators: %s is the payment brand.
						$order->add_order_note( sprintf( __( 'Payment Authorized using %s', 'woocommerce' ), $payment_brand ), false, true );
						$order->save();
					} else {
						// Translators: %s is the payment brand.
						$order->add_order_note( sprintf( __( 'Payment Captured using %s', 'woocommerce' ), $payment_brand ), false, true );
						$order->set_payment_method( 'woo_aci_apm' );
						$success = $order->payment_complete( $psp_response['id'] );
					}
					$this->subscription_service_call( $order, $psp_response, $this->gateway );
				} elseif ( 'PENDING' === $response_code ) {
					$payment_brand = str_replace( 'woo_aci_', '', $aci_payment_id );
					$order->set_transaction_id( $psp_response['id'] );
					$order->set_payment_method( $this->get_apm_gateway_id() );
					$success = $order->update_status( 'pending' );
					// Translators: %s is the payment brand.
					$order->add_order_note( sprintf( __( 'Payment Pending - %s', 'woocommerce' ), $payment_brand ), false, true );
					$order->save();
					/**
					 * Fired after setting pending status
					 *
					 * @since 1.0.1
					 */
					do_action( 'wc_aci_after_setting_pending_status', $order, $psp_response );
				} else {
					$order->update_status( 'failed' );
					$order->save();
				}
				if ( $success ) {
					WC()->cart->empty_cart();
					$this->set_draft_order_id( 0 );
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				} else {
					throw new Exception( 'Transaction service failed' );
				}
			} else {
				throw new Exception( 'Invalid call' );
			}
		} catch ( Throwable $e ) {
			$this->logger->info( 'Exception : ' . wc_print_r( $e, true ), $this->context );
			wc_add_notice( __( 'We are currently unable to process your payment. Please try again', 'woocommerce' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Callback method for wp_enqueue_scripts action
	 */
	public function woo_aci_apm_payment_scripts() {
		if ( is_checkout() && $this->is_available() ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'woo_aci_apm_checkout', WC_ACI_ASSETS . 'js/frontend/woo-aci-apm-checkout' . $suffix . '.js', array( 'woo_aci_common' ), WC_ACI_VERSION, false );
			global $wp;
			$order_id           = '0';
			$shopper_result_url = WC()->api_request_url( $this->id );
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$order_id           = absint( $wp->query_vars['order-pay'] );
				$shopper_result_url = add_query_arg( array( 'is_admin' => 1 ), $shopper_result_url );
			}
			wp_localize_script(
				'woo_aci_apm_checkout',
				'woo_aci_apm_obj',
				array(
					'ajax_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                   => wp_create_nonce( 'woo_aci_ajax_request' ),
					'id'                      => $this->id,
					'payment_key'             => $this->get_all_apms_payments(),
					'payment_id'              => '',
					'action'                  => 'woo_aci_ajax_request',
					'admin_checkout_order_id' => $order_id,
					'shopper_result_url'      => $shopper_result_url,
					'end_point'               => $this->get_api_url(),
				)
			);
			wp_set_script_translations( 'woo_aci_apm_checkout', 'woocommerce' );
		}
	}

	/**
	 * Method to set gateway id
	 *
	 * @param  string $id gateway id.
	 */
	public function set_aci_apm_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Method to set gateway title
	 *
	 * @param  string $title gateway title.
	 */
	public function set_aci_apm_title( $title ) {
		$this->title = $title;
	}

	/**
	 * Method to get all apms
	 */
	public function get_all_apms() {
		return $this->get_option( 'settings' );
	}

	/**
	 * Method to get all apms
	 */
	public function get_all_apms_payments() {
		$apm     = array();
		$all_apm = $this->get_all_apms();
		foreach ( $all_apm as $apm_payment ) {
			$apm[ 'woo_aci_' . $apm_payment['payment_key'] ] = $apm_payment['payment_key'];
		}
		return $apm;
	}

	/**
	 * Method to get apm gateway id
	 */
	public function get_apm_gateway_id() {
		return 'woo_aci_apm';
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
		$all_apm = $this->get_all_apms();
		foreach ( $all_apm as $apm_payment ) {
			if ( 'woo_aci_' . $apm_payment['payment_key'] === $id ) {
				$logo = $apm_payment['logo_link'];
				if ( $logo ) {
					$icon = '<img src="' . WC_HTTPS::force_https_url( $logo ) . '" alt="' . esc_attr( $apm_payment['title'] ) . '" width="40" height="24" />';
				}
				break;
			}
		}
		return $icon;
	}

	/**
	 * Method to check apm
	 */
	public function is_apm() {
		return true;
	}

	/**
	 * Renders payment form on checkout page
	 */
	public function payment_fields() {
	}

	/**
	 * Callback method for woocommerce_update_options_payment_gateways_{id} action
	 */
	public function process_admin_options() {
		$this->aci_apm_previous_data = $this->get_option( 'settings' );
		parent::process_admin_options();
	}

	/**
	 * Callback method for woocommerce_settings_api_sanitized_fields_{id} filter
	 *
	 * @param array $settings options settings.
	 *
	 * @return array
	 */
	public function wc_ignite_sanitize_form_fields( $settings ) {
		$settings_id       = $this->get_field_key( 'settings' );
		$aci_apm_form_data = $settings['settings'];
		if ( ! empty( $aci_apm_form_data ) ) {
			foreach ( $aci_apm_form_data as $index => $data ) {
				[$aci_apm_form_data[ $index ]['logo_path'], $aci_apm_form_data[ $index ]['logo_link']] = $this->logo_upload( $index, $settings_id );
			}
			$settings['settings'] = $aci_apm_form_data;
			unset( $_FILES[ $settings_id ] );
		}
		$aci_apm_previous_data = $this->aci_apm_previous_data;
		if ( ! empty( $aci_apm_previous_data ) ) {
			foreach ( $aci_apm_previous_data as $index => $data ) {
				if ( ! isset( $aci_apm_form_data[ $index ] ) ) {
					if ( ! empty( $aci_apm_previous_data[ $index ]['logo_path'] ) ) {
						wp_delete_file( $aci_apm_previous_data[ $index ]['logo_path'] );
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * Validate Aci APM fields
	 *
	 * @param  string $key field key.
	 * @param  array  $value posted Value.
	 *
	 * @return string|array
	 */
	public function validate_settings_field( $key, $value ) {
		if ( ! empty( $value ) ) {
			foreach ( $value as $index => $data ) {
				$value[ $index ] = $this->validate_multiselect_field( $key, $value[ $index ] );
			}
		}
		return $value;
	}

	/**
	 * Uploads logo to server
	 *
	 * @param int    $index array index.
	 * @param string $settings_id settings id.
	 *
	 * @return array
	 */
	public function logo_upload( $index, $settings_id ) {
		$aci_apm_previous_data    = $this->aci_apm_previous_data;
		$payment_method_logo_path = $aci_apm_previous_data[ $index ]['logo_path'] ?? '';
		$payment_method_logo_link = $aci_apm_previous_data[ $index ]['logo_link'] ?? '';
		$nonce_value = wc_get_var( $_REQUEST['_wpnonce'] ); // @codingStandardsIgnoreLine.
		if ( wp_verify_nonce( $nonce_value, 'woocommerce-settings' ) ) {
			$logo_key        = $settings_id;
			$logo_key_exists = ! empty( $_FILES[ $logo_key ] ?? '' ) ? wc_clean( $_FILES[ $logo_key ] ) : null;
			if ( null !== $logo_key_exists ) {
				$file_name = ! empty( $_FILES[ $logo_key ]['name'][ $index ] ?? '' ) ? wc_clean( $_FILES[ $logo_key ]['name'][ $index ] ) : null;
				if ( null !== $file_name ) {
					$tmp_name = ! empty( $_FILES[ $logo_key ]['tmp_name'][ $index ] ?? '' ) ? wc_clean( $_FILES[ $logo_key ]['tmp_name'][ $index ] ) : null;
					if ( null !== $tmp_name ) {
						require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
						require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
						$filesystem = new WP_Filesystem_Direct( true );
						$content    = $filesystem->get_contents( $tmp_name );
						if ( ! empty( $payment_method_logo_path ) ) {
							wp_delete_file( $payment_method_logo_path );
						}
						$size = ! empty( $_FILES[ $logo_key ]['size'][ $index ] ?? '' ) ? wc_clean( $_FILES[ $logo_key ]['size'][ $index ] ) : null;
						if ( $this->icon_type_validation( $tmp_name ) && false !== $content && $size <= 100000 ) {
							$status = wp_upload_bits( $file_name, null, $content );
							if ( $status['error'] ) {
								WC_Admin_Settings::add_error( $status['error'] );
								$payment_method_logo_path = '';
								$payment_method_logo_link = '';
							} else {
								$payment_method_logo_path = $status['file'];
								$payment_method_logo_link = $status['url'];
							}
						} else {
							WC_Admin_Settings::add_error( esc_html__( 'Invalid file size/type', 'woocommerce' ) );
							$payment_method_logo_path = '';
							$payment_method_logo_link = '';
						}
					}
				}
			}
		}
		return array( $payment_method_logo_path, WC_HTTPS::force_https_url( $payment_method_logo_link ) );
	}

	/**
	 * Validates icon type
	 *
	 * @param string $path icon path.
	 *
	 * @return bool
	 */
	public function icon_type_validation( $path ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$displayable_image_types = array( IMAGETYPE_JPEG, IMAGETYPE_PNG );
		$info                    = wp_getimagesize( $path );
		if ( empty( $info ) ) {
			$result = false;
		} elseif ( ! in_array( $info[2], $displayable_image_types, true ) ) {
			$result = false;
		} else {
			$result = true;
		}
		return $result;
	}

	/**
	 * Callback method for woocommerce_generate_{id}_apm_settings_html filter
	 *
	 * @param string $field_html The markup of the field being generated (initiated as an empty string).
	 * @param string $key The key of the field.
	 * @param array  $data The attributes of the field as an associative array.
	 * @param object $current_object The current WC_Settings_API object.
	 *
	 * @return string
	 */
	public function wc_aci_apm_settings_html( $field_html, $key, $data, $current_object ) {
		if ( $current_object instanceof WC_Payment_Gateway_Aci_APM ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title' => '',
				'css'   => '',
			);
			$data      = wp_parse_args( $data, $defaults );
			ob_start();
			require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/html-aci-apm-settings.php';
			return ob_get_clean();
		}
		return '';
	}

	/**
	 * Callback method for admin_enqueue_scripts action
	 */
	public function woo_admin_aci_apm_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$tab       = wc_clean( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section   = wc_clean( wp_unslash( $_GET['section'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'woocommerce_page_wc-settings' === $screen_id && 'checkout' === $tab && $section === $this->id ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->id . '_admin_config', WC_ACI_ASSETS . 'js/admin/admin-aci-apm' . $suffix . '.js', array( 'jquery', 'wc-backbone-modal', 'wp-i18n' ), WC_ACI_VERSION, false );
			require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/html-aci-apm-modal.php';
			wp_localize_script(
				$this->id . '_admin_config',
				$this->id . '_admin_config_obj',
				array(
					'existing_data' => $this->get_option( 'settings' ),
					'settings_id'   => $this->get_field_key( 'settings' ),
				)
			);
			wp_set_script_translations( $this->id . '_admin_config', 'woocommerce' );
		}
	}


	/**
	 * Initialise Gateway Settings form fields.
	 */
	public function init_form_fields() {
		if ( is_admin() ) {
			$this->form_fields = require WC_ACI_PLUGIN_FILE_PATH . 'aci/includes/settings/admin/views/aci-apm-settings.php';
		}
	}
}
