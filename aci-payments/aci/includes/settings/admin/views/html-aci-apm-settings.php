<?php
/**
 * Admin ACI APM settings
 *
 * @package aci
 */

defined( 'ABSPATH' ) || exit;
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
			<div id="<?php echo esc_attr( $field_key ); ?>">
				<div style="<?php echo esc_attr( $data['css'] ); ?>" id="add_apm">
					<span class="dashicons dashicons-plus"></span>
				</div>
			</div>
			<style>
				#<?php echo esc_attr( $field_key ); ?> {
					width: 550px;
					position: relative;
				}
				@media screen and (max-width: 782px) {
					#<?php echo esc_attr( $field_key ); ?> {
						width: 100%;
					}
				}
			</style>
		<fieldset>
	</td>
</tr>