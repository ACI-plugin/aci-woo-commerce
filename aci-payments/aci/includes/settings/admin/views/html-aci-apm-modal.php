<?php
/**
 * Admin ACI APM modal
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit;
?>

<script type="text/template" id="tmpl-wc-apm-modal">
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
						<p><?php esc_html_e( 'Are you sure you want to remove the payment method?', 'woocommerce' ); ?></p>
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
