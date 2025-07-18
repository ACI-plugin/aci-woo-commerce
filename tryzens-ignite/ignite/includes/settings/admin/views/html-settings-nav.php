<?php
/**
 * Admin payment tab setting
 *
 * @package ignite
 */

/**
 * Applying the admin payment navigation tabs
 *
 * @since 1.0.0
 */
$admin_tabs = apply_filters( 'wc_ignite_settings_nav_tabs', array() );
$last       = count( $admin_tabs );
$idx        = 0;
$tab_active = false;

global $current_section;
?>

<div class="ignite-settings-nav">
	<?php
	foreach ( $admin_tabs as $tabid => $admin_tab ) :
		++$idx;
		?>
		<a class="nav-tab 
		<?php
		if ( $current_section === $tabid || ( ! $tab_active && $last === $idx ) ) {
			echo 'nav-tab-active';
			$tab_active = true;
		}
		?>
		" 
		href="<?php echo esc_html( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $tabid ) ); ?>"><?php echo esc_attr( $admin_tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
