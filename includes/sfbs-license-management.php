<?php

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

/*********************************
 ** suspension & cron functions **
 *********************************/

/*
 * Call this function to begin/continue the grace period process
 * for license suspension.
 *
 * @param $subscription_key (string) The subscription key for WooCommerce.
 * @param $count (int) The number of times an email has been sent.
 */
add_action('license_suspension_procedure', function($subscription_key, $count, $opt){
    /**
     * http://codex.wordpress.org/Template:Cron_Tags
     * http://codex.wordpress.org/Function_Reference/wp_schedule_single_event
     * https://tommcfarlin.com/wordpress-cron-jobs/
     */

    $gracePeriod = (isset($opt['gracePeriod']) ? $opt['gracePeriod'] : 0);

    /* https://docs.woothemes.com/document/subscriptions/develop/functions/management-functions/#section-1
     * https://docs.woothemes.com/document/subscriptions/develop/data-structure/
     * get the subscription object and related information
     * $subscription = WC_Subscriptions_Manager::wcs_get_subscription($subscription_key);
     */
    $subscription = wcs_get_subscription($subscription_key);

    /* check if subscription is still inactive (otherwise exit, we are done)
     * Can be 'pending', 'on-hold', 'active', 'expired' or 'cancelled'
     * $subscription->has_status(array('pending-cancel', 'cancelled'))
     * !$subscription->has_status('active')
     */
    //TODO:REMOVE
    write_log("Subscription Status: " . $subscription->get_status());

	wp_mail('tecgent@gmail.com', 'Mail Log', 'Status: ' . $subscription->get_status() . ', Opt: ' . print_r($opt, true) );

    foreach ($subscription->get_items() as $item_id => $item) {
        //if ($subscription->get_status() == 'expired' || $subscription->get_status() == 'on-hold' || $subscription->get_status() == 'pending') {
        //if ($subscription->get_status() != 'active') {
	    if( !wcs_user_has_subscription( $subscription->get_user_id(), $item->get_product_id(), 'active') ){

            // if there is no grace period or the grace period has expired then send a cancellation notice
            if (($gracePeriod == 0) || ($count > $gracePeriod)) {

                // get the email template
                $emailTemplate = file_get_contents(plugin_dir_path(__FILE__) . '../email_templates/suspension-notice.html');

                // suspend the license(s)
                $emailSubject = 'All licenses have been deactivated for this subscription.';
                suspend_subscription_licenses($subscription, $emailSubject);

                // add a note to the subscription
                $subscription->add_order_note($emailSubject, 1, false);
                write_log("notice of cancelation");
            }
            // if this is the last message send final notice
            elseif ($count == $gracePeriod) {

                // get the email template
                $emailTemplate = file_get_contents(plugin_dir_path(__FILE__) . '../email_templates/final-notice-suspension.html');

                // send final warning email message
                $emailSubject = 'Final notice of a problem with your Cyber ID Guard subscription.';

                // create admin note on subscription
                $subscription->add_order_note("Final deactivation email warning sent.", 0, false);

                // schedule cancellation for evening
                //wp_schedule_single_event(strtotime("+12 hours"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));
                wp_schedule_single_event(strtotime("+3 minutes"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));

                write_log("final cancelation warning");
            }
            // if this is the initial message, send the initial warning email
            elseif ($count == 0) {

                // get the email template
                $emailTemplate = file_get_contents(plugin_dir_path(__FILE__) . '../email_templates/first-notice-suspension.html');

                // create initial email message
                $emailSubject = 'Notice of a problem with your Cyber ID Guard subscription.';

                // create admin note on subscription
                $subscription->add_order_note("Initial deactivation email warning sent.", 0, false);

                // schedule next email in 24 hours
                //wp_schedule_single_event(strtotime("+1 day"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));
                wp_schedule_single_event(strtotime("+3 minutes"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));
                write_log("first cancelation warning");
            }
            // send followup email warning.
            else {

                // get the email template
                $emailTemplate = file_get_contents(plugin_dir_path(__FILE__) . '../email_templates/followup-notice-suspension.html');

                // create standard email warning message
                $emailSubject = 'Reminder notice of a problem with your Cyber ID Guard subscription.';

                // create admin note on subscription
                $subscription->add_order_note("Deactivation email warning #{$count} sent.", 0, false);

                // schedule next email in 24 hours
                //wp_schedule_single_event(strtotime("+1 day"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));
                wp_schedule_single_event(strtotime("+3 minutes"), 'license_suspension_procedure', array($subscription_key, ($count + 1), $opt));
                write_log("cancelation warning #{$count}");

            }

            // replace shotcodes in email template
            $siteUrl = get_site_url();
            $emailMessage = str_ireplace('[subject]', $emailSubject, $emailTemplate);
            $emailMessage = str_ireplace('[first_name]', $subscription->get_billing_first_name(), $emailMessage);
            $emailMessage = str_ireplace('[last_name]', $subscription->get_billing_last_name(), $emailMessage);
            $emailMessage = str_ireplace('[subscription_id]', $subscription_key, $emailMessage);
            $emailMessage = str_ireplace('[order_id]', $subscription->get_parent()->get_id(), $emailMessage);
            $emailMessage = str_ireplace('[warning_number]', $count, $emailMessage);
            $emailMessage = str_ireplace('[grace_period_number]', $gracePeriod, $emailMessage);
            $emailMessage = str_ireplace('[remaining_grace_period_number]', ($gracePeriod - $count), $emailMessage);

            $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
            if ($myaccount_page_id) {
                $myAccountUrl = get_permalink($myaccount_page_id);
                $emailMessage = str_ireplace('[my_account_url]', $myAccountUrl, $emailMessage);
                $emailMessage = str_ireplace('[subscription_url]', "{$myAccountUrl}view-subscription/{$subscription_key}/", $emailMessage);
                $emailMessage = str_ireplace('[order_url]', "{$myAccountUrl}view-order/{$subscription->get_parent()->get_id()}/", $emailMessage);
            } else {
                $emailMessage = str_ireplace('[my_account_url]', $siteUrl, $emailMessage);
                $emailMessage = str_ireplace('[subscription_url]', $siteUrl, $emailMessage);
                $emailMessage = str_ireplace('[order_url]', $siteUrl, $emailMessage);
            }

            $shopUrl = get_permalink(wc_get_page_id('shop'));
            if ($shopUrl) {
                $emailMessage = str_ireplace('[shop_url]', $shopUrl, $emailMessage);
            } else {
                $emailMessage = str_ireplace('[site_url]', $siteUrl, $emailMessage);
            }
            $emailMessage = str_ireplace('[site_url]', $siteUrl, $emailMessage);


            /* https://developer.wordpress.org/reference/functions/wp_mail/
             * https://developer.wordpress.org/reference/hooks/wp_mail_content_type/
             * send the generated email message
             */
            add_filter('wp_mail_content_type', 'set_html_content_type');
            //write_log("TO: {$subscription->get_billing_email()}");
            //write_log("SUBJECT: {$emailSubject}");
            //write_log("MESSAGE: {$emailMessage}");
            post_email($emailSubject, $emailMessage, $opt['varmailToken']);
		    // wp_mail(
            //     'tecgent@gmail.com',
			//     'Product Mail Log',
			//     print_r( array($subscription->get_billing_email(), $emailSubject, $emailMessage, $opt['varmailToken']), true )
		    // );

            $header = array(
		        'Content-Type: text/html; charset=UTF-8',
		        'From: CyberIdGuard <support@cyberidguard.com>',
		        'Reply-To: CyberIdGuard <support@cyberidguard.com>',
		        'Bcc: Creative Services <support@creativeservices.io>',
		        'Bcc: Tecgent <admin@tecgent.com>'
	        );
            wp_mail($subscription->get_billing_email(), $emailSubject, $emailMessage, $header);
            remove_filter('wp_mail_content_type', 'set_html_content_type');
        }
    }
}, 10, 3);

/*
 * Set the filter for the email content type.
 */
function set_html_content_type(){
    return 'text/html';
}

/*
 * Adds a custom action to the subscription admin page dropdown.
 *
 * @param $actions (array) The subscription admin page dropdown actions.
 * @return (array) The subscription admin page dropdown actions.
 */
add_action('woocommerce_order_actions', function($actions) {
    global $theorder;

    // Make sure we're in a subscription typeof order
    if (wcs_is_subscription($theorder) && ! $theorder->has_status(wcs_get_subscription_ended_statuses())) {
        // determine if any items are missing their license key.
        foreach ($theorder->get_items() as $item_id => $item) {
            if (!isset($item['License'])) {
                $missingLicense = true;
                break;
            }
        }

        if (isset($missingLicense)) {
            // New action
            $actions['wcs_generate_license_keys'] = esc_html__('Generate license keys and URLs', 'woocommerce-subscriptions');
        }
    }

    return $actions;
});

/*
 * Generates StrikeForce license keys for any item that doesn't already have one.
 *
 * @param $subscription (WooCommerceObject) The subscription object for WooCommerce.
 */
add_action('woocommerce_order_action_wcs_generate_license_keys', function($subscription){
    process_order_item_for_subscription($subscription, 'payment_processed', false, true);
});

/*
 * Call this function to suspend all licenses for a subscription.
 *
 * @param $subscription (WooCommerceObject) The subscription object for WooCommerce.
 * @param $reasonForChange (string) The reason for canceling the licenses.
 */
function suspend_subscription_licenses($subscription, $reasonForChange){
    // build a unique order number per product License
    // get the $guardedApi management object
    //$guardedIdApi = get_guardedID_manager($item['product_id']);

    foreach ($subscription->get_items() as $item_id => $item) {
        $time = time();
        $orderNumber = "{$subscription->get_id()}-{$item['product_id']}-{$time}";
        $opt = get_option(PLUGIN_PREFIX);
        $type = (get_post_meta($item->get_product_id(), PLUGIN_PREFIX, true)) ? get_post_meta($item->get_product_id(), PLUGIN_PREFIX, true) : 'none';

        if($type != 'none'){
            $guardedIdApi = get_guardedID_manager($opt[$type]);
            $guardedIdApi->suspendLicense($orderNumber, $item['License'], $reasonForChange);
            $subscription->add_order_note($reasonForChange, 1, false);
        }
    }
}

/*
 * Call this function to get the GuardedIdApi object.
 *
 * @return (GuardedIdApi) The GuardedIdApi object.
 */
function get_guardedID_manager($args){
    // Instantiate the GuardedIdApi class.
    return new OneTwenteyFourLabs\GuardedIdApi(
        $args['guardedIdUri'],
        $args['mobileTrustUri'],
        $args['sellerID'],
        $args['userID'],
        $args['userPassword'],
        $args['productId'],
        $args['licenseType']
    );
}