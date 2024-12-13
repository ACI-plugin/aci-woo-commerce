# Recurring Order (RO) Guide

## Overview

This guide provides instructions for extending this plugin for RO capability. Merchants can manage subscriptions using provided hooks and filters.

## Table of Contents

- [Configuration](#configuration)
- [Handling Payments](#handling-payments)
- [Setting Checkout Session](#setting-checkout-session)
- [Setting Order Meta Data](#setting-order-meta-data)
- [Webhooks](#webhooks)

## Configuration

1. **Admin Configuration:**
   - Create an admin configuration panel for RO.
   - Display configured RO values on the checkout page for customers to select.

## Handling Payments

1. **Payment Applicability:**
   - Determine which payment methods support Recurring Orders.
   - Ensure RO is only available for applicable payment methods.

## Setting Checkout Session

1. **Frequency Selection:**
   - After a customer selects a specific RO frequency, update the selected frequency in the session using the key `wc_aci_recurring_order`.
   - Re-render the payment fields to reflect the selected frequency.

## Setting Order Meta Data

1. **Pending Orders:**
   - Save the selected RO frequency to the order meta using the key `wc_aci_recurring_order` for pending orders. Plugin will use this data to perform subscription service calls for pending webhook notifications.

2. **Hooks:**
   - Use the `wc_aci_after_setting_pending_status` action hook to save the order meta key when the order status is set to pending.
   - To save subscription response data against an order, use the `wc_aci_after_subscription_service_call` action hook.


## Webhooks

1. **Webhook Management:**
   - Ensure that the system correctly handles webhooks for creating or refunding orders based on the `wc_aci_recurring_order_create` filter.
   - Implement logic to respond to the webhook and update or refunds orders accordingly.

2. **Hooks:**
   - Use the `wc_aci_recurring_order_create` filter to handle order creation or refunds based on the webhook data.

