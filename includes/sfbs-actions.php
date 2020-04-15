<?php

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

/*********
 ** action functions
 *********/

/*
 * Return all the licenses for the order function.
 */
add_action('action_order_license_keys', function($order_id){
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item_id => $item) {
        echo $item['License'];
    }
});

/*
 * Display the licenses for an item.
 */
add_action('action_item_license_key', function($item_id){
    $license_info = wc_get_order_item_meta($item_id, 'License');
    return $license_info;
});

/*
 * Display the license for a subscription.
 */
add_action('action_subscription_license_key', function($subscription_key){
    $item = get_item_for_subscription($subscription_key);
    return $item['License'];
});

/*
 * Display the full license information for a given subscription.
 * TODO: I THINK ACTION NOT IN USE
 */
/*add_action('action_subscription_license_info', function($subscription_key){
    return get_license_info_for_subscription($subscription_key);
});*

/*
 * Deactivate a machine for a given subscription.
 * TODO: I THINK ACTION NOT IN USE
 */
/*add_action('action_deactivate_machine', function($orderNumber, $licenseKey, $reasonForChange, $machineID){
    return deactivateMachine($orderNumber, $licenseKey, $reasonForChange, $machineID);
});*/

/*
 * Display machines for subscription.
 */
add_action('action_list_machines', function($subscription){
    $html_template_result = "";

    // parse templates for use in all items
    foreach ($subscription->get_items() as $item_id => $item) {

        $type = (get_post_meta($item_id, PLUGIN_PREFIX, true)) ? get_post_meta($item_id, PLUGIN_PREFIX, true) : 'none';
        if($type != 'none' ){
            // get the $guardedApi management object
            $guardedIdApi = get_guardedID_manager(get_option(PLUGIN_PREFIX)[$type]);

            $license = $guardedIdApi->getLicenseInfo($item['License']);

            if (isset($license) && $license) {

                // get the machine information
                $machines = $license->GIDLicenseInfo->MachinesActivated;
                $license_count = $license->GIDLicenseInfo->GIDLicense->LicenseCount;
                $machines_activated_count = $license->GIDLicenseInfo->GIDLicense->MachinesActivated;

                // gets the template and renders it's html
                ob_start();
                //include (plugin_dir_path(__FILE__).'../page_templates/provisioned-machine-header.php');
                include('../page_templates/provisioned-machine-header.php');
                $header_html = ob_get_clean();

                // gets the template and renders it's html
                ob_start();
                //include (plugin_dir_path(__FILE__).'../page_templates/provisioned-machine-loop-per-active.php');
                include ('../page_templates/provisioned-machine-loop-per-active.php');
                $loop_html = ob_get_clean();

                $loop_html_result = "";

                if (!empty($machines)) {
                    // itterate through machine information
                    foreach ($machines->Machine as $value) {
                        $local_html = str_ireplace('[machine_id]', $value->MachineID, $loop_html);
                        $local_html = str_ireplace('[activation_date]', $value->ActivationDate, $local_html);

                        $loop_html_result .= $local_html;
                    }
                }

                // Add rows for unactivated machines.
                // for ($i=0; $i < $license_count - $machines_activated_count; $i++) {
                //     $local_html = str_ireplace('[machine_id]', "Unused", $loop_html);
                //     $local_html = str_ireplace('[activation_date]', "", $local_html);
                //
                //     $loop_html_result .= $local_html;
                // }

                // gets the template and renders it's html
                ob_start();
                //include (plugin_dir_path(__FILE__).'../page_templates/provisioned-machine-footer.php');
                include ('../page_templates/provisioned-machine-footer.php');
                $footer_html = ob_get_clean();

                $html_template = $header_html . $loop_html_result . $footer_html;

                // replace shortcodes in page template
                $html_template = str_ireplace('[subscription_key]', $subscription->key, $html_template);
                $html_template = str_ireplace('[order_id]', $subscription->get_parent()->get_id(), $html_template);
                $html_template = str_ireplace('[license_key]', $item['License'], $html_template);
                $html_template = str_ireplace('[activation_url]', $item['Activation'], $html_template);

                $html_template = str_ireplace('[license_count]', $license_count, $html_template);
                $html_template = str_ireplace('[machines_activated_count]', $machines_activated_count, $html_template);
            }

            if (isset($html_template)) {
                $html_template_result .= $html_template;
            }
        }
    }

    echo $html_template_result;
    //echo "<br /><br />html template =" . htmlentities($html_template_result) . "<br><br>";
}, 10, 1);

/*
 * Checks the PHP post of each page.
 */
add_action('init', function(){
    if (isset($_POST[PLUGIN_PREFIX . '_action']) && $_POST[PLUGIN_PREFIX . '_action'] == "Deactivate Machine") {
        $reasonForChange = "User requested deactivation";

        // get the $guardedApi management object
        $guardedIdApi = get_guardedID_manager();
        $guardedIdApi->deactivateMachine(
            wp_filter_nohtml_kses($_POST['order_id']),
            wp_filter_nohtml_kses($_POST['license_key']),
            $reasonForChange,
            wp_filter_nohtml_kses($_POST['machine_id'])
        );
    }
});