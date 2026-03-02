<?php
/**
 * Output the input field and buttons
 *
 * @package ignite
 */

$order_id   = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore
$order_info = wc_get_order( $order_id );
if ( ! is_a( $order_info, 'WC_Order' ) ) {
	return;
}
$order_status   = $order_info->get_status();
$payment_status = $order_info->is_paid();
$subtotal       = $order_info->get_total();

?>
<div class="inside">
	<div>
		<p style="font-weight: bold;"><?php echo esc_html__( 'Capture', 'woocommerce' ); ?></p>
		<p>
			<label for="woo_ignite_capture_amount" ><?php echo esc_html__( 'Amount:', 'woocommerce' ); ?></label>
		</p>
		<div style="display: flex; align-items: center;">
			<input type="number" step="0.01" min="0" id="woo_ignite_capture_amount" name="woo_ignite_capture_amount" value="" <?php echo ( ( 'on-hold' === $order_status ) && ! $payment_status ) ? '' : 'readonly'; ?> style="width:75%;margin-right:5px;">
			<input type="button" class="button" name="woo_ignite_capture" id="woo_ignite_capture" style="width:25%;padding:0;" value="<?php esc_html_e( 'Capture', 'woocommerce' ); ?>" <?php echo ( ( 'on-hold' === $order_status ) && ! $payment_status ) ? '' : 'disabled'; ?>>
		</div>
		<p></p>
	</div>
	<div style="margin: 5px 0px;">
		<p style="font-weight: bold;"> <?php echo esc_html__( 'Void', 'woocommerce' ); ?></p>
		<p>
			<label for="woo_ignite_cancel_amount"><?php echo esc_html__( 'Amount:', 'woocommerce' ); ?></label>
		</p>
		<div style="display: flex; align-items: center;"> 
			<input type="number" step="0.01" min="0" id="woo_ignite_cancel_amount" name="woo_ignite_cancel_amount" value="<?php echo esc_attr( $subtotal ); ?>" readonly style="width:75%;margin-right:5px;">
			<input type="button" class="button" name="woo_ignite_void" id="woo_ignite_void" value="<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>" <?php echo ( ( 'on-hold' === $order_status ) && ! $payment_status ) ? '' : 'disabled'; ?> style="width:25%;padding:0;">
		</div>
		<p></p>
		<p class="show_success msgs" style="color: green"></p>
		<p class="show_error msgs" style="color: red"></p>
	</div>
</div>

