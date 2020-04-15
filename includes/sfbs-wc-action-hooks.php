<?php

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

/*********
 ** WooCommerce Development Documentation
 ** https://docs.woothemes.com/wc-apidocs/index.html
 ** http://woothemes.github.io/woocommerce-rest-api-docs/
 **
 ** WooCommerce Subscription Action Functions
 ** Note: for full action reference see:
 ** https://docs.woothemes.com/document/subscriptions/develop/version-2/
 ** https://docs.woothemes.com/document/subscriptions/develop/action-reference/
 ** https://wisdmlabs.com/blog/testing-woocommerce-subscriptions-with-hooks-and-wcs_debug/
 ** http://www.remicorson.com/add-order-notes-to-woocommerce-completed-order-email/
 *********/

/*
 Action: 'woocommerce_subscription_status_active'

 Parameters:
 $user_id Integer The ID of the user for whom the subscription was activated.
 $subscription_key String The key for the subscription that was just set as activated on the user’s account.

 Description: This action is triggered after the subscription specified with $subscription_key has been
 activated for the user specified with $user_id.
 */
add_action('woocommerce_subscription_status_active', function($subscription) {
    //TODO:REMOVE
    write_log("Active-hook. Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'activate');
});

/*
 Action: 'cancelled_subscription'

 Parameters:
 $user_id Integer The ID of the user for whom the subscription was cancelled.
 $subscription_key String The key for the subscription that was just cancelled on the user’s account.

 Description: Triggered when a subscription has been cancelled on a user’s account, which can be triggered by
 either an administrator, subscriber on their account page or the payment gateway.
 */
add_action('woocommerce_subscription_status_cancelled', function($subscription){
    //TODO:REMOVE
    write_log("Cancelled-hook. Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'cancel');
});

/*
 Action: 'subscription_expired'

 Parameters:
 $user_id Integer The ID of the user for whom the subscription expired.
 $subscription_key String The key for the subscription that just expired on the user’s account.

 Description: Triggered when a subscription reaches the end of its term, if a length was set on
 the subscription when it was purchased. This event may be triggered by either WooCommerce Subscriptions,
 which schedules a cron-job to expire each subscription, or by the payment gateway extension which can call
 the WC_Subscriptions_Manager::expire_subscription() function directly.
 */
add_action('woocommerce_subscription_status_expired', function($subscription){
    //TODO:REMOVE
    write_log("Expired-hook. Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'expire');
});

/*
 Action: 'subscription_put_on-hold'

 Parameters:
 $user_id Integer The ID of the user who owns the subscription.
 $subscription_key String The key for the subscription that has been put on hold.

 Description: Triggered when a subscription is put on-hold (suspended). A subscription is put on hold when:
 the store manager has manually suspended the subscription
 the customer has manually suspended the subscription from her My Account page
 a renewal payment is due (subscriptions are suspended temporarily for automatic renewal payments until the
 payment is processed successfully and indefinitely for manual renewal payments until the customer logs in
 to the store to pay for the renewal. For more information, see the subscription renewal guide).
 */
add_action('woocommerce_subscription_status_on-hold', function($subscription){
    //TODO:REMOVE
    write_log("On-hold-hook, Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'hold');
});

/*
 Action: 'woocommerce_subscription_payment_complete'

 Parameters:
 $user_id Integer The ID of the user who owns the subscription.
 $subscription_key String The key for the subscription that has received a payment.

 Description: Triggered when a subscription payment is made for the subscription specified with
 $subscription_key. If a gateway is working correctly with the Subscription’s API, this will fire for the
 first payment on a subscription and all successive payments for the subscription.
 */
add_action('woocommerce_subscription_payment_complete', function($subscription) {
    //TODO:REMOVE
    write_log("Complete-hook, Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'payment_processed');
});

/*
 Action: 'processed_subscription_payment_failure'

 Parameters:
 $user_id Integer The ID of the user who owns the subscription.
 $subscription_key String The key for the subscription to which the failed payment relates.

 Description: Triggered when a subscription payment is attempted for a subscription specified with $subscription_key
 but the payment failed.
 */
add_action('woocommerce_subscription_payment_failed', function($subscription){
    //TODO:REMOVE
    write_log("Failed-hook, Subscription id = {$subscription->get_id()}");
    process_order_item_for_subscription($subscription, 'payment_failed');
});