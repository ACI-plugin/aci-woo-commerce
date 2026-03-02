<?php
/**
 * FC Buttons
 *
 * @package ignite
 */

?>
<div>
<?php if ( $gpay_enabled ) { ?>
	<div>
		<form action="<?php echo $shopper_result_url; ?>" class="" data-brands="GOOGLEPAY"></form>
	</div>
<?php } ?>
<?php if ( $applepay_enabled ) { ?>
	<div>
		<form action="<?php echo $shopper_result_url; ?>" class="" data-brands="APPLEPAY"></form>
	</div>
<?php } ?>
</div>
