<?php
/********************
 ** base functions **
 ********************/


function process_order_item_for_subscription($subscription, $process_type){
    /**
     * https://docs.woothemes.com/document/subscriptions/develop/functions/management-functions/
     * https://docs.woothemes.com/document/subscriptions/develop/data-structure/
     * https://docs.woothemes.com/wc-apidocs/source-class-WC_Abstract_Order.html#2213-2251
     * https://docs.woothemes.com/wc-apidocs/class-WC_Abstract_Order.html#_add_order_note
     * wp-content/plugins/woocommerce-subscriptions/includes/class-wc-subscription.php
     * wp-content/plugins/woocommerce/includes/class-wc-order.php
     */

    if ($subscription && isset($subscription->order_type) && $subscription->order_type == 'shop_subscription') {
        $opt = get_option(PLUGIN_PREFIX);
        $order_id = $subscription->get_parent()->get_id();
        $subscription_id = $subscription->get_id();
        // get the $guardedApi management object

        // find the order item that matches the subscription to get the serial number
        // for renewal.
        //write_log($subscription);
        foreach ($subscription->get_items() as $item_id => $item) {
            $type = (get_post_meta($item->get_product_id(), PLUGIN_PREFIX, true)) ? get_post_meta($item->get_product_id(), PLUGIN_PREFIX, true) : 'none';
            write_log($type);
            write_log('has_subscripton? ' . ( (wcs_user_has_subscription( $subscription->get_user_id(), $item->get_product_id(), 'active'))? 'true' : 'false') );
            if($type != 'none' ){
                $guardedIdApi = get_guardedID_manager($opt[$type]);

                // calculate the proper quantity of machines for the subscription
                // note: this requires that the packSize attribute has been added to the product.
                $product = wc_get_product($item['product_id']);
                $packSize = $product->get_attribute('packSize');
                $quantity = ($packSize ? ($item['qty'] * $packSize) : $item['qty']);

                // build a unique order number per product License
                $time = time();
                $orderNumber = "{$subscription->get_id()}-{$item['product_id']}-{$time}";

                // generate the start of the subscription note
                $subscription_note = "<p><span class='product-info'>Product: </span> <span class='product-name'>{$item['name']}</span></p>";

                //TODO:REMOVE
                write_log('Process Type: ' . $process_type);

                //TODO:REMOVE
                write_log("Subscription Status: " . $subscription->get_status());

                switch ($process_type) {
                    case 'payment_processed':
                        break;
                    case 'hold':
                        /*if (isset($item['License'])) {
                            //TODO:REMOVE
                            write_log('License Exist.');
                            //do_action('license_suspension_procedure', $subscription_id, 0, $opt[$type]);
                            //$reasonForChange = 'Subscription put on hold. Grace period deactivation sequence activated.';
	                        do_action('license_suspension_procedure', $subscription_id, 0, $opt[$type]);
                            $reasonForChange = 'Subscription put on hold. Grace period deactivation sequence activated.';
                            $subscription->add_order_note($reasonForChange, 0, false);
                        }*/
                        break;
                    case 'activate':
                        if (isset($item['License'])) {
                            // TODO: we have to add code here to determine the last session_status
                            // of the subscription in order to populate the correct $reasonForChange
                            // if the status is already active skip this

                            //TODO:REMOVE
                            write_log('License Exist.');

                            $reasonForChange = 'License has been enabled.';
                            $enableResponse = $guardedIdApi->enableLicense($orderNumber, $item['License'], $reasonForChange);
                            //TODO:REMOVE
                            write_log("Enable Response = " . json_encode($enableResponse) );
                            $subscription->add_order_note($reasonForChange, 1, false);
                        } else {
                            // TODO: if the license is set then we need to activate the license
                            //       if it is on hold
                            // this is a new license so we need to register and get it from GuardedIdApi
                            if (!isset($item['License']) || !$item['License']) {
                                //TODO:REMOVE
                                write_log('Create License.');

                                try {
                                    $license = (string)$guardedIdApi
                                        ->sellLicense($orderNumber, $subscription, $quantity)
                                        ->GIDLicense
                                        ->LicenseKey;
                                } catch (Exception $e) {
                                    //TODO:REMOVE
                                    write_log('base.php:' . __LINE__ );
                                    write_log("Exception = {$e}");
                                }

                                // https://docs.woothemes.com/wc-apidocs/function-wc_add_order_item_meta.html
                                // https://docs.woothemes.com/wc-apidocs/function-wc_delete_order_item_meta.html

                                // add license information if it exists
                                if (isset($license) && $license) {
                                    wc_add_order_item_meta($item_id, 'License', $license);
                                    $subscription_note .= "<span class='" . PLUGIN_PREFIX . "-license'>License: </span> <span class='" . PLUGIN_PREFIX . "-license-number'>{$license}</span><br />";
                                } else {
                                    $subscription_note .= "<span class='" . PLUGIN_PREFIX . "-license'>Please contact CyberID Guard for license information.</span><br />";
                                    $admin_license_message = "<span class='" . PLUGIN_PREFIX . "-license'>There was a problem processing the license with StrikeForce.</span><br />";
                                    $subscription->add_order_note($admin_license_message, 0, false);
                                }
                            }

                            if (!isset($item['Activation']) || !$item['Activation']) {

                                //TODO:REMOVE
                                write_log('License Activation.');

                                try {
                                    if (isset($license)) {
                                        $activation_url = (string)$guardedIdApi
                                            ->UserRegistration($orderNumber, $subscription, $license)
                                            ->Activation
                                            ->Url;
                                    }
                                } catch (Exception $e) {
                                    //TODO:REMOVE
                                    write_log('base.php:' . __LINE__ . " Exception = {$e}");
                                }

                                // add the activation url data if it exists
                                if (isset($activation_url) && $activation_url) {
                                    wc_add_order_item_meta($item_id, 'Activation', $activation_url);
                                    $subscription_note .= "<span class='" . PLUGIN_PREFIX . "-activation'>Activate you license: </span> <span class='" . PLUGIN_PREFIX . "-activation-url'>{$activation_url}</span><br />";
                                } else {
                                    $subscription_note .= "<span class='" . PLUGIN_PREFIX . "-activation'>Please contact CyberID Guard for MobileTrust activation information.</span><br />";
                                    $admin_license_message = "<span class='" . PLUGIN_PREFIX . "-activation'>There was a problem processing the user registration with StrikeForce.</span><br />";
                                    $subscription->add_order_note($admin_license_message, 0, false);
                                }
                            }

                            // Note can also be added to the top level of the order as follows:
                            // $subscription->order->add_order_note($message, 1, false);
                            $subscription->add_order_note($subscription_note, 1, false);
                        }
                        break;
                    case 'cancel':
                        if (isset($item['License'])) {

                            //TODO:REMOVE
                            write_log('License Exist.');


                            $reasonForChange = "License {$item['License']} has been canceled.";
                            $suspendResponse = $guardedIdApi->suspendLicense($orderNumber, $item['License'], $reasonForChange);

                            //TODO:REMOVE
                            write_log('base.php:' . __LINE__ . " OrderNumber: {$orderNumber}, License: {$item['License']}, SuspendResponse: {$suspendResponse->ErrorDescription}");

                            $subscription->add_order_note($reasonForChange, 1, false);
                        }
                        break;
                    case 'expire':
                        if (isset($item['License'])) {
                            //TODO:REMOVE
                            write_log('License Exist.');
                            //do_action('license_suspension_procedure', $subscription_id, 0, $opt[$type]);
                            $reasonForChange = 'Subscription has expired.';
                            $subscription->add_order_note($reasonForChange, 0, false);
                        }
                        break;
                    case 'payment_failed':
                        if (isset($item['License'])) {

	                        do_action('license_suspension_procedure', $subscription_id, 0, $opt[$type]);
	                        $reasonForChange = 'Subscription payment failed. Grace period deactivation sequence activated.';
	                        $subscription->add_order_note($reasonForChange, 0, false);

                            //TODO:REMOVE
                            write_log('License Exist.');
                            //do_action('license_suspension_procedure', $subscription_id, 0, $opt[$type]);

                            $emailTemplate = file_get_contents(plugin_dir_path(__FILE__) . '../email_templates/payment-failed-notice.html');

                            //$reasonForChange = 'License subscription payment has failed. Grace period deactivation sequence activated.';
                            $reasonForChange = 'License subscription payment has failed.';

                            //TODO:REMOVE
                            //write_log('base.php:' . __LINE__ . "SuspendResponse = {$suspendResponse->ErrorDescription}");

                            $subscription->add_order_note($reasonForChange, 0, false);

                            // create standard email warning message
                            $emailSubject = $reasonForChange;


                            // replace shotcodes in email template
                            $siteUrl = get_site_url();
                            $emailMessage = str_ireplace('[subject]', $emailSubject, $emailTemplate);
                            $emailMessage = str_ireplace('[first_name]', $subscription->get_billing_first_name(), $emailMessage);
                            $emailMessage = str_ireplace('[last_name]', $subscription->get_billing_last_name(), $emailMessage);
                            $emailMessage = str_ireplace('[subscription_id]', $subscription_id, $emailMessage);
                            $emailMessage = str_ireplace('[order_id]', $subscription->get_parent()->get_id(), $emailMessage);

                            $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
                            if ($myaccount_page_id) {
                                $myAccountUrl = get_permalink($myaccount_page_id);
                                $emailMessage = str_ireplace('[my_account_url]', $myAccountUrl, $emailMessage);
                                $emailMessage = str_ireplace('[subscription_url]', "{$myAccountUrl}view-subscription/{$subscription_id}/", $emailMessage);
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
                            post_email($emailSubject, $emailMessage, $opt[$type]['varmailToken']);
                            $header = array(
                            	'Content-Type: text/html; charset=UTF-8',
	                            'From: CyberIdGuard <support@cyberidguard.com>',
	                            'Reply-To: CyberIdGuard <support@cyberidguard.com>',
	                            'Bcc: Tecgent <tecgent@gmail.com>',
	                            'Bcc: Tecgent <admin@tecgent.com>'
                            );
                            wp_mail($subscription->get_billing_email(), $emailSubject, $emailMessage, $header);
                            remove_filter('wp_mail_content_type', 'set_html_content_type');
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    } else {
        //TODO:REMOVE
        write_log('base.php:' . __LINE__ . " Inside process. No subscription object available.");
    }
}

function get_item_for_subscription($subscription_key){
    $subscription = wcs_get_subscription($subscription_key);
    //var_dump(subscription);

    foreach ($subscription->get_items() as $item_id => $item) {
        return $item;
    }
}

/*
 * Return the full license information for a given subscription.
 * TODO: I think the function not in use
 */
function get_license_info_for_subscription($subscription_key){
    // get the $guardedApi management object
    $guardedIdApi = get_guardedID_manager();

    $item = get_item_for_subscription($subscription_key);

    return $guardedIdApi->getLicenseInfo($item['License']);
}