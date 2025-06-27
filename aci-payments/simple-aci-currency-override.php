<?php
/**
 * Simple ACI Currency Override
 * 
 * A lightweight solution to ensure ACI payment gateway uses the raffle plugin's selected currency
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Simple_ACI_Currency_Override {
    
    private static $instance = null;
    private $raffle_currency_service = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Only initialize if both plugins are active
        if (!class_exists('WC_ACI') || !class_exists('Raffle_Currency_Service')) {
            return;
        }
        
        $this->raffle_currency_service = Raffle_Currency_Service::get_instance();
        $this->init_simple_hooks();
    }
    
    /**
     * Initialize simple, targeted hooks
     */
    private function init_simple_hooks() {
        // Hook specifically into get_woocommerce_currency function during ACI AJAX
        add_action('wp_ajax_woo_aci_ajax_request', [$this, 'override_currency_for_aci'], 1);
        add_action('wp_ajax_nopriv_woo_aci_ajax_request', [$this, 'override_currency_for_aci'], 1);
        
        // Clean up after ACI request
        add_action('wp_ajax_woo_aci_ajax_request', [$this, 'restore_currency_after_aci'], 999);
        add_action('wp_ajax_nopriv_woo_aci_ajax_request', [$this, 'restore_currency_after_aci'], 999);
    }
    
    /**
     * Override currency functions during ACI processing
     */
    public function override_currency_for_aci() {
        if (!$this->raffle_currency_service) {
            return;
        }
        
        $user_currency = $this->raffle_currency_service->get_user_currency();
        $base_currency = $this->raffle_currency_service->get_base_currency();
        
        // Only override if user has selected a different currency
        if (!$user_currency || $user_currency === $base_currency) {
            return;
        }
        
        // Override the currency function with a simple filter
        add_filter('woocommerce_currency', function($currency) use ($user_currency) {
            return $user_currency;
        }, 999);
        
        // Override currency symbol
        add_filter('woocommerce_currency_symbol', function($symbol, $currency) use ($user_currency) {
            if ($currency !== $user_currency) {
                $currency_obj = $this->raffle_currency_service ? $this->raffle_currency_service->get_currency($user_currency) : null;
                return $currency_obj ? $currency_obj->symbol : $symbol;
            }
            return $symbol;
        }, 999, 2);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Simple ACI Currency Override: Setting currency to {$user_currency}");
        }
    }
    
    /**
     * Restore original currency after ACI processing
     */
    public function restore_currency_after_aci() {
        // Remove our currency overrides
        remove_all_filters('woocommerce_currency', 999);
        remove_all_filters('woocommerce_currency_symbol', 999);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Simple ACI Currency Override: Restored original currency");
        }
    }
}

// Initialize simple override
add_action('plugins_loaded', function() {
    Simple_ACI_Currency_Override::get_instance();
}, 25);
