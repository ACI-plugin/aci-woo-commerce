<?php
/**
 * ACI Payment Gateway - Raffle Plugin Currency Integration
 * 
 * This file ensures that the ACI payment gateway respects the currency
 * selected by the raffle plugin's multi-currency system.
 * 
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACI_Raffle_Currency_Integration {
    
    private static $instance = null;
    private $raffle_currency_service = null;
    private $is_aci_request = false;
    private $is_filtering_currency = false;
    private $is_filtering_symbol = false;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Only initialize if both plugins are active
        if (!$this->are_required_plugins_active()) {
            return;
        }
        
        $this->init_hooks();
    }
    
    /**
     * Check if both ACI and Raffle plugins are active
     */
    private function are_required_plugins_active() {
        return class_exists('WC_ACI') && class_exists('Raffle_Currency_Service');
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Hook into ACI AJAX request to set currency context
        add_action('wp_ajax_woo_aci_ajax_request', [$this, 'set_aci_currency_context'], 1);
        add_action('wp_ajax_nopriv_woo_aci_ajax_request', [$this, 'set_aci_currency_context'], 1);
        
        // Hook into currency functions when ACI is processing (high priority)
        add_filter('woocommerce_currency', [$this, 'filter_aci_currency'], 5, 1);
        add_filter('woocommerce_currency_symbol', [$this, 'filter_aci_currency_symbol'], 5, 2);
        
        // REMOVED: option_woocommerce_currency filter - was causing infinite loops
        
        // Hook into order currency setting during ACI processing
        add_filter('woocommerce_new_order_data', [$this, 'set_raffle_currency_on_new_order'], 10, 1);
        
        // Hook into checkout processing to ensure currency is set correctly
        add_action('woocommerce_checkout_process', [$this, 'ensure_raffle_currency_in_checkout'], 5);
        
        // Additional hooks for order creation
        add_action('woocommerce_new_order', [$this, 'set_order_currency_after_creation'], 10, 1);
        add_action('woocommerce_checkout_create_order', [$this, 'set_order_currency_during_creation'], 5, 1);
        
        // Clean up after ACI request
        add_action('wp_ajax_woo_aci_ajax_request', [$this, 'cleanup_aci_currency_context'], 999);
        add_action('wp_ajax_nopriv_woo_aci_ajax_request', [$this, 'cleanup_aci_currency_context'], 999);
        
        // Debug hook (remove in production)
        add_action('wp_ajax_woo_aci_ajax_request', [$this, 'debug_currency_context'], 2);
        add_action('wp_ajax_nopriv_woo_aci_ajax_request', [$this, 'debug_currency_context'], 2);
    }
    
    /**
     * Set currency context when ACI is making requests
     */
    public function set_aci_currency_context() {
        $this->is_aci_request = true;
        
        // Initialize raffle currency service if not already done
        if (!$this->raffle_currency_service && class_exists('Raffle_Currency_Service')) {
            $this->raffle_currency_service = Raffle_Currency_Service::get_instance();
        }
    }
    
    /**
     * Filter WooCommerce currency for ACI requests
     */
    public function filter_aci_currency($currency) {
        // Prevent infinite recursion
        if ($this->is_filtering_currency) {
            return $currency;
        }
        
        // Only override during ACI requests or when specifically requested
        if (!$this->should_override_currency()) {
            return $currency;
        }
        
        $this->is_filtering_currency = true;
        
        $user_currency = $this->get_raffle_user_currency();
        
        $this->is_filtering_currency = false;
        
        return $user_currency ?: $currency;
    }
    
    /**
     * Filter WooCommerce currency symbol for ACI requests
     */
    public function filter_aci_currency_symbol($symbol, $currency) {
        // Prevent infinite recursion
        if ($this->is_filtering_symbol) {
            return $symbol;
        }
        
        // Only override during ACI requests or when specifically requested
        if (!$this->should_override_currency()) {
            return $symbol;
        }
        
        $this->is_filtering_symbol = true;
        
        $user_currency = $this->get_raffle_user_currency();
        if ($user_currency && $user_currency !== $currency) {
            $currency_obj = $this->raffle_currency_service ? $this->raffle_currency_service->get_currency($user_currency) : null;
            $symbol = $currency_obj ? $currency_obj->symbol : $symbol;
        }
        
        $this->is_filtering_symbol = false;
        
        return $symbol;
    }
    
    /**
     * Set raffle currency on new orders
     */
    public function set_raffle_currency_on_new_order($order_data) {
        if (!$this->should_override_currency()) {
            return $order_data;
        }
        
        $user_currency = $this->get_raffle_user_currency();
        if ($user_currency) {
            $order_data['currency'] = $user_currency;
        }
        
        return $order_data;
    }
    
    /**
     * Ensure raffle currency is used during checkout
     */
    public function ensure_raffle_currency_in_checkout() {
        if (!$this->should_override_currency()) {
            return;
        }
        
        $user_currency = $this->get_raffle_user_currency();
        if ($user_currency) {
            // Use a more targeted approach - only override temporarily during specific operations
            // REMOVED: Temporary option override as it was causing infinite loops
        }
    }
    
    /**
     * Clean up currency context after ACI request
     */
    public function cleanup_aci_currency_context() {
        $this->is_aci_request = false;
        $this->is_filtering_currency = false;
        $this->is_filtering_symbol = false;
        
        // REMOVED: remove_all_filters call as it was too aggressive
    }
    
    /**
     * Check if we should override currency
     */
    private function should_override_currency() {
        // Prevent any action if we're already filtering to avoid recursion
        if ($this->is_filtering_currency || $this->is_filtering_symbol) {
            return false;
        }
        
        // Override if this is an ACI request
        if ($this->is_aci_request) {
            return true;
        }
        
        // For checkout/payment context, be more conservative
        if ($this->is_checkout_or_payment_context()) {
            $user_currency = $this->get_raffle_user_currency();
            $base_currency = $this->get_raffle_base_currency();
            
            // Only override if currencies are different and we have valid values
            return $user_currency && $base_currency && $user_currency !== $base_currency;
        }
        
        return false;
    }
    
    /**
     * Check if we're in checkout or payment context
     */
    private function is_checkout_or_payment_context() {
        // Check for AJAX requests related to checkout/payment
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $ajax_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
            $wc_ajax = isset($_REQUEST['wc-ajax']) ? $_REQUEST['wc-ajax'] : '';
            
            $payment_ajax_actions = [
                'woo_aci_ajax_request',
                'update_order_review',
                'checkout',
                'woocommerce_checkout',
                'woocommerce_update_order_review'
            ];
            
            if (in_array($ajax_action, $payment_ajax_actions) || in_array($wc_ajax, $payment_ajax_actions)) {
                return true;
            }
        }
        
        // Simple check for checkout/cart pages
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }
        
        if (function_exists('is_cart') && is_cart()) {
            return true;
        }
        
        // Fallback URL check (less reliable but better than nothing)
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        return (strpos($uri, 'checkout') !== false || strpos($uri, 'cart') !== false);
    }
    
    /**
     * Get user's selected currency from raffle plugin
     */
    private function get_raffle_user_currency() {
        if (!$this->raffle_currency_service) {
            return null;
        }
        
        // Use direct method call to avoid triggering any currency filters
        try {
            return $this->raffle_currency_service->get_user_currency();
        } catch (Exception $e) {
            // Log error and return null to prevent further issues
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACI Currency Integration: Error getting user currency - ' . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Get base currency from raffle plugin
     */
    private function get_raffle_base_currency() {
        if (!$this->raffle_currency_service) {
            // Fallback to direct option access to avoid recursive calls
            return get_option('woocommerce_currency', 'USD');
        }
        
        try {
            return $this->raffle_currency_service->get_base_currency();
        } catch (Exception $e) {
            // Log error and return safe fallback
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACI Currency Integration: Error getting base currency - ' . $e->getMessage());
            }
            return get_option('woocommerce_currency', 'USD');
        }
    }
    
    /**
     * Set order currency after order creation
     */
    public function set_order_currency_after_creation($order_id) {
        if (!$this->should_override_currency()) {
            return;
        }
        
        $user_currency = $this->get_raffle_user_currency();
        if ($user_currency) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->set_currency($user_currency);
                $order->save();
            }
        }
    }
    
    /**
     * Set order currency during order creation
     */
    public function set_order_currency_during_creation($order) {
        if (!$this->should_override_currency()) {
            return;
        }
        
        $user_currency = $this->get_raffle_user_currency();
        if ($user_currency) {
            $order->set_currency($user_currency);
        }
    }
    
    /**
     * Debug currency context (remove in production)
     */
    public function debug_currency_context() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $user_currency = $this->get_raffle_user_currency();
            $base_currency = $this->get_raffle_base_currency();
            $wc_currency = get_woocommerce_currency();
            
            error_log("ACI Currency Integration Debug:");
            error_log("User Currency: " . ($user_currency ?: 'null'));
            error_log("Base Currency: " . ($base_currency ?: 'null'));
            error_log("WC Currency: " . ($wc_currency ?: 'null'));
            error_log("Should Override: " . ($this->should_override_currency() ? 'yes' : 'no'));
        }
    }
}

// Initialize the integration
add_action('plugins_loaded', function() {
    ACI_Raffle_Currency_Integration::get_instance();
}, 25); // Load after both plugins are initialized
