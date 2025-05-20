<?php
/**
 * FC Buttons
 *
 * @package aci
 */

?>
<div class="fc-miniart ">
<?php if ( $gpay_enabled ) { ?>
	<div class="gpay-div">
		<form action="<?php echo $shopper_result_url; ?>" class="paymentWidgets" data-brands="GOOGLEPAY"></form>
	</div>
<?php } ?>
<?php if ( $applepay_enabled ) { ?>
	<div class="applepay-div">
		<form action="<?php echo $shopper_result_url; ?>" class="paymentWidgets" data-brands="APPLEPAY"></form>
	</div>
<?php } ?>
</div>
