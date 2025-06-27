<?php
/**
 * ACI Raffle Currency Integration Test
 * 
 * This file provides test functions to verify that the currency integration
 * between ACI payment gateway and raffle plugin is working correctly.
 * 
 * Usage: Add ?test_aci_currency=1 to any page URL when logged in as admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACI_Raffle_Currency_Test {
    
    public function __construct() {
        // Only run tests for admin users with the test parameter
        if (is_admin() && current_user_can('manage_options') && isset($_GET['test_aci_currency'])) {
            add_action('admin_notices', [$this, 'run_currency_tests']);
        }
    }
    
    /**
     * Run currency integration tests
     */
    public function run_currency_tests() {
        echo '<div class="notice notice-info">';
        echo '<h3>ACI Raffle Currency Integration Test Results</h3>';
        
        // Test 1: Check if both plugins are active
        $aci_active = class_exists('WC_ACI');
        $raffle_active = class_exists('Raffle_Currency_Service');
        
        echo '<p><strong>Plugin Status:</strong></p>';
        echo '<ul>';
        echo '<li>ACI Plugin: ' . ($aci_active ? '✓ Active' : '✗ Not Active') . '</li>';
        echo '<li>Raffle Plugin: ' . ($raffle_active ? '✓ Active' : '✗ Not Active') . '</li>';
        echo '</ul>';
        
        if (!$aci_active || !$raffle_active) {
            echo '<p style="color: red;">Both plugins must be active for the integration to work.</p>';
            echo '</div>';
            return;
        }
        
        // Test 2: Check currency service
        $currency_service = Raffle_Currency_Service::get_instance();
        $user_currency = $currency_service ? $currency_service->get_user_currency() : null;
        $base_currency = $currency_service ? $currency_service->get_base_currency() : null;
        $wc_currency = get_woocommerce_currency();
        
        echo '<p><strong>Currency Status:</strong></p>';
        echo '<ul>';
        echo '<li>User Selected Currency: ' . ($user_currency ?: 'Not set') . '</li>';
        echo '<li>Base Currency: ' . ($base_currency ?: 'Not set') . '</li>';
        echo '<li>WooCommerce Currency: ' . ($wc_currency ?: 'Not set') . '</li>';
        echo '</ul>';
        
        // Test 3: Check integration class
        $integration_active = class_exists('Simple_ACI_Currency_Override');
        echo '<p><strong>Integration Status:</strong></p>';
        echo '<ul>';
        echo '<li>Integration Class: ' . ($integration_active ? '✓ Loaded (Simple Override)' : '✗ Not Loaded') . '</li>';
        echo '</ul>';
        
        // Test 4: Simulate currency filtering
        if ($integration_active && $user_currency && $user_currency !== $base_currency) {
            // Temporarily simulate ACI context
            $integration = Simple_ACI_Currency_Override::get_instance();
            $integration->override_currency_for_aci();
            
            $filtered_currency = apply_filters('woocommerce_currency', $base_currency);
            $filtered_symbol = apply_filters('woocommerce_currency_symbol', get_woocommerce_currency_symbol($base_currency), $base_currency);
            
            echo '<p><strong>Filter Test Results:</strong></p>';
            echo '<ul>';
            echo '<li>Original Currency: ' . $base_currency . '</li>';
            echo '<li>Filtered Currency: ' . $filtered_currency . '</li>';
            echo '<li>Filter Working: ' . ($filtered_currency === $user_currency ? '✓ Yes' : '✗ No') . '</li>';
            echo '<li>Filtered Symbol: ' . $filtered_symbol . '</li>';
            echo '</ul>';
            
            $integration->restore_currency_after_aci();
        }
        
        // Test 5: Check for common issues
        echo '<p><strong>Potential Issues:</strong></p>';
        echo '<ul>';
        
        if ($user_currency === $base_currency) {
            echo '<li>⚠️ User currency matches base currency - no conversion needed</li>';
        }
        
        if (!$currency_service) {
            echo '<li>✗ Raffle currency service not available</li>';
        }
        
        if (!has_filter('woocommerce_currency')) {
            echo '<li>⚠️ No woocommerce_currency filters detected</li>';
        } else {
            echo '<li>✓ WooCommerce currency filters are active</li>';
        }
        
        echo '</ul>';
        
        echo '<p><strong>Recommendations:</strong></p>';
        echo '<ul>';
        echo '<li>Test by changing currency in the raffle plugin</li>';
        echo '<li>Check checkout page with different currency selected</li>';
        echo '<li>Monitor payment gateway requests to ensure correct currency is passed</li>';
        echo '<li>Check order details after successful payment</li>';
        echo '</ul>';
        
        echo '</div>';
    }
}

// Initialize test class
new ACI_Raffle_Currency_Test();
