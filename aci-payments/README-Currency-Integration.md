# ACI Payment Gateway - Raffle Plugin Currency Integration

## Overview
This integration ensures that the ACI payment gateway respects the currency selected by the raffle plugin's multi-currency system. When a user selects a different currency through the raffle plugin, all payment processing through the ACI gateway will use that selected currency.

## Files Added

### 1. `aci-raffle-currency-integration.php`
Main integration class that handles currency synchronization between the raffle plugin and ACI payment gateway.

**Key Features:**
- Detects when ACI payment gateway is processing payments
- Overrides currency functions during ACI requests
- Ensures order currency matches user's selected currency
- Provides comprehensive hook coverage for different payment scenarios

### 2. `aci-currency-test.php`
Testing utility to verify the integration is working correctly.

**Usage:**
- Only loads when `WP_DEBUG` is enabled
- Access by adding `?test_aci_currency=1` to any admin page URL
- Provides detailed status report of the integration

## How It Works

### Currency Detection Flow
1. **User Selection**: User selects currency through raffle plugin
2. **ACI Request Detection**: Integration detects when ACI is processing payments
3. **Currency Override**: Filters `woocommerce_currency` and related functions
4. **Order Creation**: Ensures new orders use the selected currency
5. **Payment Processing**: ACI gateway receives correct currency for payment

### Hook Priority
The integration uses high-priority hooks (priority 5) to ensure currency filters are applied before other plugins or the ACI gateway itself processes currency information.

### Supported Scenarios
- ✅ Cart/Checkout currency display
- ✅ AJAX payment processing
- ✅ Order creation
- ✅ Payment gateway initialization
- ✅ Currency symbol display

## Installation

The integration is automatically loaded when the ACI plugin is activated, provided both the ACI payment gateway and raffle plugin are present.

## Testing

### Automatic Test
1. Enable WordPress debugging: `define('WP_DEBUG', true);`
2. Go to any admin page and add `?test_aci_currency=1` to the URL
3. Review the test results displayed in the admin notice

### Manual Testing
1. Select a different currency in the raffle plugin
2. Add items to cart and proceed to checkout
3. Verify currency is displayed correctly throughout checkout
4. Complete a test payment and verify order currency

### Expected Behavior
- Cart totals should display in selected currency
- Checkout page should show selected currency
- Payment gateway should process in selected currency
- Order confirmation should show selected currency
- Order details in admin should show selected currency

## Debugging

### Enable Debug Logging
The integration includes debug logging when `WP_DEBUG` is enabled. Check your WordPress debug log for entries like:
```
ACI Currency Integration Debug:
User Currency: EUR
Base Currency: USD
WC Currency: EUR
Should Override: yes
```

### Common Issues

**Currency not changing in checkout:**
- Verify raffle plugin currency filters are active
- Check if `is_checkout_context()` returns true
- Ensure ACI AJAX requests are being detected

**Orders created with wrong currency:**
- Check order creation hooks are firing
- Verify `woocommerce_new_order_data` filter is working
- Ensure currency is set before payment processing

**Payment gateway still using base currency:**
- Verify `get_woocommerce_currency()` is being filtered
- Check hook priorities (should be 5 or lower)
- Ensure ACI request context is properly detected

## Maintenance

### Updating
The integration should be compatible with future versions of both plugins, but test thoroughly after updates to either the ACI payment gateway or raffle plugin.

### Monitoring
Monitor order currencies after deployment to ensure the integration continues working correctly with real transactions.

## Technical Details

### Hook Overview
- `wp_ajax_woo_aci_ajax_request` - Detects ACI payment processing
- `woocommerce_currency` - Overrides currency function
- `woocommerce_currency_symbol` - Overrides currency symbol
- `option_woocommerce_currency` - Overrides currency option directly
- `woocommerce_new_order_data` - Sets currency on new orders
- `woocommerce_checkout_create_order` - Sets currency during checkout

### Class Structure
```php
ACI_Raffle_Currency_Integration
├── set_aci_currency_context()     // Detect ACI requests
├── filter_aci_currency()          // Override currency
├── filter_aci_currency_symbol()   // Override symbol
├── set_raffle_currency_on_new_order() // Set order currency
└── cleanup_aci_currency_context() // Clean up after request
```

## Support
For issues with this integration, check:
1. WordPress debug log for error messages
2. Test utility results for configuration issues
3. Browser network tab for AJAX request details
4. Order metadata for currency information
