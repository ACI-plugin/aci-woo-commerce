# Memory Issue Fix - Simple ACI Currency Override

## Problem
The original currency integration was causing a memory exhaustion error due to infinite recursion in currency filtering. This happened because:

1. Our filter on `woocommerce_currency` was called
2. Inside the filter, we called functions that triggered `get_woocommerce_currency()` again
3. This created an infinite loop, exhausting memory

## Solution
Replaced the complex integration with a simple, targeted approach:

### New File: `simple-aci-currency-override.php`
- **Lightweight**: Only hooks into ACI AJAX requests
- **Targeted**: Only applies currency override during ACI payment processing
- **Safe**: Uses high-priority filters (999) that are added and removed cleanly
- **No Recursion**: Doesn't call functions that would trigger the same filters

### How It Works
1. **Detection**: Hooks into `wp_ajax_woo_aci_ajax_request` action
2. **Override**: Adds high-priority filter to `woocommerce_currency` 
3. **Processing**: ACI gateway gets the raffle plugin's selected currency
4. **Cleanup**: Removes filters after ACI processing completes

### Key Improvements
- ✅ No infinite recursion
- ✅ No memory issues
- ✅ Simple and reliable
- ✅ Only affects ACI payment processing
- ✅ Clean hook management

## Files Changed
- `aci-payments.php` - Now loads `simple-aci-currency-override.php` instead of complex integration
- `simple-aci-currency-override.php` - New simple integration
- `aci-currency-test.php` - Updated to test simple integration

## Testing
1. The memory issue should be resolved
2. Currency should still be properly passed to ACI payment gateway
3. Test with `?test_aci_currency=1` parameter to verify integration is working

## Rollback
If issues persist, the original complex integration file (`aci-raffle-currency-integration.php`) is still available but commented out. You can switch back by editing `aci-payments.php`.
