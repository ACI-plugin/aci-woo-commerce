<?php
/**
 * File for WC_Ignite_Settings_API abstraction
 *
 * @package ignite
 */

defined( 'ABSPATH' ) || exit();

/**
 * Abstract class for WC_Ignite_Settings_API
 *
 * @package Ignite/Abstract
 */
abstract class WC_Ignite_Settings_API extends WC_Settings_API {

	use WC_Ignite_Settings_Trait;

	/**
	 * WC_Ignite_Settings_API constructor
	 */
	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		$this->hooks();
	}

	/**
	 * Hook to localize the setting
	 */
	public function hooks() {
		add_action( 'wc_ignite_localize_' . $this->id . '_settings', array( $this, 'localize_settings' ) );
	}

	/**
	 * Callback to the localize hooks
	 */
	public function localize_settings() {
		return $this->settings;
	}

	/**
	 * Generate HTML for custom field type: ignite_custom_fields.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field data.
	 *
	 * @return string
	 */
	public function generate_ignite_custom_fields_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key, array() );

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		// Ensure at least one default field.
		if ( empty( $value ) ) {
			$value = array(
				0 => array(
					'enabled' => 'no',
					'name'    => '',
					'data'    => '',
				),
			);
		}

		ob_start();
		?>
	</table>
	<h3 class="wc-settings-sub-title" id="<?php echo esc_attr( $field_key ); ?>_title"><?php echo esc_html( $data['title'] ); ?></h3>
	<table class="form-table" role="presentation">
	<style>
		#<?php echo esc_attr( $field_key ); ?>_container {
			width: 550px;
			position: relative;
		}
		@media screen and (max-width: 782px) {
			#<?php echo esc_attr( $field_key ); ?>_container {
				width: 100%;
			}
		}
		#ignite-custom-fields-wrapper {
			position: relative;
		}
		.ignite-custom-field {
			position: relative;
			border: 1px solid #000000;
			padding: 25px;
			margin-bottom: 20px;
		}
		.ignite-custom-field .form-table {
			margin: 0;
			background: transparent;
		}
		.ignite-custom-field .form-table th {
			width: 150px;
			padding: 8px 15px 8px 0;
			vertical-align: middle;
			font-weight: 600;
		}
		.ignite-custom-field .form-table td {
			padding: 8px 0;
			vertical-align: middle;
		}
		.ignite-custom-field .form-table tr {
			border: none;
		}
		.ignite-custom-field input[type="text"],
		.ignite-custom-field select {
			width: 100%;
		}
		#add_custom_field {
			cursor: pointer;
			position: absolute;
			right: -25px;
			top: 0;
		}
		.remove_custom_field {
			cursor: pointer;
			position: absolute;
			right: 4px;
			top: 4px;
		}
	</style>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $field_key ); ?>">
				<?php echo esc_html( $data['title'] ); ?>
			</label>
		</th>
		<td class="forminp" id="<?php echo esc_attr( $field_key ); ?>_container">
			<div id="ignite-custom-fields-wrapper">
				<?php if ( ! empty( $value ) ) : ?>
					<?php
					foreach ( $value as $index => $row ) :
						$enabled = isset( $row['enabled'] ) ? $row['enabled'] : 'no';
						$name    = isset( $row['name'] ) ? $row['name'] : '';
						$data_v  = isset( $row['data'] ) ? $row['data'] : '';
						?>
						<div class="ignite-custom-field" id="ignite-custom-field-index-<?php echo esc_attr( $index ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
							<table class="form-table">
								<tr valign="top">
									<th scope="row" class="titledesc" style="width: 20%;">
										<label><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></span></legend>
											<select class="select ignite-custom-field-enabled" style="width: 100%;" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][enabled]">
												<option value="no" <?php selected( $enabled, 'no' ); ?>><?php esc_html_e( 'No', 'woocommerce' ); ?></option>
												<option value="yes" <?php selected( $enabled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woocommerce' ); ?></option>
											</select>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label><?php esc_html_e( 'Name', 'woocommerce' ); ?></label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php esc_html_e( 'Name', 'woocommerce' ); ?></span></legend>
											<input type="text" class="input-text regular-input ignite-custom-field-name required-field" value="<?php echo esc_attr( $name ); ?>" style="width: 100%;" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][name]" maxlength="100"/>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label><?php esc_html_e( 'Data', 'woocommerce' ); ?></label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php esc_html_e( 'Data', 'woocommerce' ); ?></span></legend>
											<input type="text" class="input-text regular-input ignite-custom-field-data required-field" value="<?php echo esc_attr( $data_v ); ?>" style="width: 100%;" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $index ); ?>][data]" maxlength="100"/>
										</fieldset>
									</td>
								</tr>
							</table>
							<div class="remove_custom_field dashicons dashicons-trash" data-index="<?php echo esc_attr( $index ); ?>"></div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				<div id="add_custom_field">
					<span class="dashicons dashicons-plus"></span>
				</div>
			</div>

			<!-- WooCommerce Backbone Modal Template -->
			<script type="text/template" id="tmpl-wc-custom-field-modal">
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
									<p><?php esc_html_e( 'Are you sure you want to remove the field?', 'woocommerce' ); ?></p>
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
	 * Validate and sanitize custom fields data.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Field value.
	 *
	 * @return array
	 */
	public function validate_ignite_custom_fields_field( $key, $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $value as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			// Skip empty fields (both name and data are empty).
			$name = isset( $field['name'] ) ? trim( $field['name'] ) : '';
			$data = isset( $field['data'] ) ? trim( $field['data'] ) : '';

			if ( empty( $name ) && empty( $data ) ) {
				continue;
			}

			$sanitized[ $index ] = array(
				'enabled' => isset( $field['enabled'] ) && 'yes' === $field['enabled'] ? 'yes' : 'no',
				'name'    => sanitize_text_field( $name ),
				'data'    => sanitize_text_field( $data ),
			);
		}

		// Ensure at least one default field exists after save.
		if ( empty( $sanitized ) ) {
			$sanitized = array(
				0 => array(
					'enabled' => 'no',
					'name'    => '',
					'data'    => '',
				),
			);
		}

		return $sanitized;
	}
}
