<?php

/**
 * Add the new tab to product data
 * @see     https://github.com/woocommerce/woocommerce/blob/e1a82a412773c932e76b855a97bd5ce9dedf9c44/includes/admin/meta-boxes/class-wc-meta-box-product-data.php
 * @see     https://docs.woocommerce.com/document/editing-product-data-tabs/
 * @param   $tabs
 * @since   1.0.0
 */
// The code for displaying WooCommerce Product custom tab Custom Fields
add_filter( 'woocommerce_product_data_tabs', 'create_cidg_tab_in_product_account' );
function create_cidg_tab_in_product_account( $tabs )
{
    $tabs[PLUGIN_PREFIX . '_strikeforce_account'] = array(
        'label'         => __( 'Strikeforce Account', 'tpwcp' ), // The name of your panel
        'target'        => PLUGIN_PREFIX . '_strikeforce_account', // An anchor link must be unique
        'class'         => array( 'strikeforce_tab', 'show_if_simple', 'show_if_variable' ), // Hide/show
        'priority'      => 99, // Where your panel will appear; 70 is last item
    );
    return $tabs;
}


// The code for displaying WooCommerce Product Custom Fields
add_action( 'woocommerce_product_data_panels', 'strikeforce_account_product_custom_fields' );
function strikeforce_account_product_custom_fields()
{
    global $woocommerce, $post;
    echo '<div id="' . PLUGIN_PREFIX . '_strikeforce_account" class="panel woocommerce_options_panel">';
    echo '<h2>Cyber ID Guard Product Account</h2>';
    echo '<p>Leave fields blank if no account information is available for this product.</p>';

    // Custom Product Text Field
    $fields = array (
        array ('guardedIdUri', 'Enter Account API URL', 'GuardedID API URI', 'text'),
        array ('mobileTrustUri', 'Enter Account API URL (optional)', 'API URL', 'text'),
        array ('sellerID', 'Enter Product Seller ID', 'Seller ID', 'text'),
        array ('userID', 'Enter Product User ID', 'User ID', 'text'),
        array ('userPassword', 'Enter Account Password', 'Password', 'password'),
        array ('packSize', 'Enter Product Pack size', 'Pack Size', 'number'),
        array ('productId', 'Enter Product ID', 'Product ID', 'number'),
        array ('licenseType', 'Enter License Type', 'License Type', 'text'),
        array ('gracePeriod', 'Enter License Type', 'Grace Period', 'number'),

    );


    $input_checkbox = get_post_meta( $post->ID, 'is_strikeforce', true );
    if( empty( $input_checkbox ) ) {
        $input_checkbox = '';
    }
        woocommerce_wp_checkbox(array(
            'id'            => 'is_strikeforce',
            'label'         => __('Strikeforce', 'woocommerce' ),
            'description'   => __( 'This is a Strikefore product.', 'woocommerce' ),
            'value'         => $input_checkbox,
        ));



    foreach ($fields as $field)
    {
        woocommerce_wp_text_input( array(
                'id'            => $field[0],
                'placeholder'   => $field[1],
                'label'         => __($field[2], 'woocommerce'),
                'desc_tip'      => 'true',
                'type'          => $field[3],
        ));
    }
    echo '</div>';

}


// Following code Saves WooCommerce Product Custom Fields
function custom_account_text_fields_save( $post_id )
{
    $options = [
        'is_strikeforce',
        'guardedIdUri',
        'mobileTrustUri',
        'sellerID',
        'userID',
        'userPassword',
        'packSize',
        'productId',
        'licenseType',
        'gracePeriod',
    ];

    foreach ($options as $option)
    {
        $text_field_value = $_POST[$option];

        if (!empty($option) && isset($option))
        {
            $product = wc_get_product( $post_id );

            $product->update_meta_data($option, $values[] = wp_filter_nohtml_kses( $text_field_value ));
        }
        $product->save();
    }
}
add_action( 'woocommerce_process_product_meta', 'custom_account_text_fields_save' );




/*
 * Clean the submitted options
 */
function sanitize_account_user_input($input)
{

    $options = array(
        'guardedIdUri' => array (
            'title' => 'GuardedID API URI',
            'description' => 'The GuaurdedID API URI.'
        ),
        'mobileTrustUri' => array (
            'title' => 'MobileTrust API URI.',
            'description' => 'MobileTrust API URI.'
        ),
        'sellerID' => array (
            'title' => 'Seller ID',
            'description' => 'The Seller ID.'
        ),
        'userID' => array (
            'title' => 'User ID',
            'description' => 'The User ID.'
        ),
        'userPassword' => array (
            'type' => 'password',
            'title' => 'Password',
            'description' => 'The Password'
        ),
        'productID' => array (
            'title' => 'Product ID',
            'description' => 'The Product ID.'
        ),
        'packSize' => array (
            'title' => 'Pack Size',
            'description' => 'The Pack'
        ),
        'licenseType' => array (
            'title' => 'License Type',
            'description' => 'License Type'
        ),
        'gracePeriod' => array (
            'title' => 'Grace Period',
            'description' => 'The allowed grace period for subscription renewall failure.'
        ),
        'varmailToken' => array (
            'title' => 'Varmail Token ',
            'description' => "The <a href='https://varmail.me' taget='_blank'>varmail.me</a> token used only for development testing of email messages."
        )
    );

    for($i = 0; $i<2; $i++) 
    {
        $name = ($i == 0) ? STRIKEFORCE : BLOCKSAFE;
    
        foreach ($options as $key => $value) {
    
            $input[$name][$key] = wp_filter_nohtml_kses($input[$name][$key]);
    
        }
    }

    return $input;
}

add_action('admin_init', function() {
    register_setting( 'options', PLUGIN_PREFIX . '_strikeforce_account', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_account_user_input')
    );
});








/**
 * 
 * This functionality that will fix discrependcies between local and strikeforce licenses statuses
 * 
 * If the current product is a subscription, it will check whether the status in StrikeForce's 
 * database matches the status of the local database. If it doese not, it will fix it so both databases match.
 * 
 */
add_action('woocommerce_admin_order_data_after_order_details', function( $subscription )
{
    $time = time();

    $orderType = $subscription->get_type(); //shop_subscription

    if ( $orderType == "shop_subscription") 
    {
        foreach ($subscription->get_items() as $item_id => $item) 
        {

            $orderNumber = "{$subscription->get_id()}-{$item['product_id']}-{$time}";
            
            $product = wc_get_product($item['product_id']);
    
            $product_id = $product->get_id();

            $is_Strikeforce = get_post_meta( $product_id, 'is_strikeforce', true );

            $prod_acct_fields = [
                'sellerID',
                'userID',
                'userPassword',
                'productId',
                'licenseType',
                'guardedIdUri',
                'mobileTrustUri',
            ];
            
            if($is_Strikeforce == 'yes')
            {
                for($i = 0; $i<count($prod_acct_fields); $i++)
                {
                    if(!empty($prod_acct_fields[$i]))
                    {
                        $account_info[$prod_acct_fields[$i]] = get_post_meta( $product_id, $prod_acct_fields[$i], true );
                    }
                }
    
                $guardedIdApi = get_guardedID_manager($account_info);

                $xmlObject = $guardedIdApi->getLicenseInfo($item['License']);
    
                $licenseArray = convertXml2Array($xmlObject);

                if(array_key_exists('GIDLicenseInfo', $licenseArray))
                {
                    $sfLicenseStatus = $licenseArray['GIDLicenseInfo']['GIDLicense']['LicenseStatus'];

                    echo '<p class="form-field form-field-wide">';
                    
                    echo '<label for="order_status">Strikeforce License status:</label>';
                    
                    echo '<input type="text" id="order_status" name="license_status" value="' . $sfLicenseStatus . '" disabled />';
                    
                    echo '</p>';

                } elseif(array_key_exists('ErrorDescription', $licenseArray)) {

                    $sfLicenseStatus = $licenseArray['ErrorDescription'];

                    echo '<p class="form-field form-field-wide">';
                    
                    echo '<label for="order_status">Strikeforce License status:</label>';
                    
                    echo '<input type="text" id="order_status" name="license_status" value="' . $sfLicenseStatus . '" disabled />';
                    
                    echo '</p>';

                    $invalidLicenseKey = "Strikeforce API reports: " . $sfLicenseStatus . ".";

                    $cbidLicenseStatus = $subscription->get_status();
                }

                //     switch($cbidLicenseStatus)
                //     {
                //         case 'active':

                //             $returnedValue = $subscription->set_status('on-hold');

                //             $subscription->save();

                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }

                //             if (!empty($subscription->get_id() && $sfLicenseStatus != 'Issued')) 
                //             {   
                //                 for($i = 0; $i<3; $i++) {

                //                     $enableResponse = (array) $guardedIdApi->enableLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Enable license if active';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 }
                //             }
                            
                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'active'))
                //             {
                //                 $returnedValue = $subscription->set_status('on-hold');

                //                 $subscription->save();
                //             }
                            
                //         break;
                //         case 'on-hold':
                            
                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }
                //             elseif (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspend')) 
                //             {
                            
                //                 for($i = 0; $i<3; $i++) 
                //                 {
                                
                //                     $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Suspend license if on-hold';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 } 
                //             }

                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'on-hold'))
                //             {
                //                 $returnedValue = $subscription->set_status('on-hold');

                //                 $subscription->save();
                //             }
            
                //         break;
                //         case 'wc-cancelled':

                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }
                        
                //             if (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspend')) 
                //             {
                            
                //                 for($i = 0; $i<3; $i++) {
                                
                //                     $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Suspend license if cancelled';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 }
                //             }

                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'cancelled'))
                //             {
                //                 $returnedValue = $subscription->set_status('cancelled');

                //                 $subscription->save();
                //             }
            
                //         break;
                //         case 'expired':
                            
                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }

                //             if (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspend')) 
                //             {   
                //                 for($i = 0; $i<3; $i++) 
                //                 {
                                
                //                     $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Suspend license if expired';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 }
                //             }

                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'expired'))
                //             {
                //                 $returnedValue = $subscription->set_status('expired');

                //                 $subscription->save();
                //             }
            
                //         break;
                //         case 'pending-cancel':
                            
                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }

                //             if (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspend')) 
                //             {
                //                 for($i = 0; $i<3; $i++) {

                //                     $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Suspend license if expired';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 }
                //             }

                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'pending-cancel'))
                //             {
                //                 $returnedValue = $subscription->set_status('pending-cancel');

                //                 $subscription->save();
                //             }
            
                //         break;
                //         case 'pending':
                            
                //             if (!empty($subscription->get_id() && $sfLicenseStatus == 'Invalid license key')) {

                //                 $subscription->add_order_note($invalidLicenseKey, 0, false);
                                
                //                 break;
                //             }

                //             if (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspend')) 
                //             {
                //                 for($i = 0; $i<3; $i++) {

                //                     $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                //                     sleep(1);

                //                     if ($enableResponse["ErrorDescription"] == "Success")
                //                     {
                //                         $subscription->add_order_note('Discrepancy fixed!', 0, false);

                //                     } elseif($i === 3) {

                //                         $subscription->add_order_note('Discrepancy Alert! License discrepancy could not fixed. Contact the administrator', 0, false);

                //                         $to = 'support@cyberidguard.com';
                //                         $subject = 'The subject - Suspend license if expired';
                //                         $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
                //                         $headers = array('Content-Type: text/html; charset=UTF-8');
                                        
                //                         wp_mail( $to, $subject, $body, $headers );
                //                     }
                //                 }
                //             }

                //             if (!empty($subscription->get_id() && $subscription->get_status() != 'pending'))
                //             {
                //                 $returnedValue = $subscription->set_status('pending');

                //                 $subscription->save();
                //             }
            
                //         break;
                //     }
                // }

                //cyberIdGuard_subscription_discrepency_fix();
            
            }else{

                echo '<p class="form-field form-field-wide">';
                    
                echo '<label for="order_status">Strikeforce License status:</label>';
                
                echo '<input type="text" id="order_status" name="license_status" value="Not a Strikeforce product." disabled />';
                
                echo '</p>';
                
            }
        }
    }
});




// create a scheduled event (if it does not exist already)
function cronstarter_activation() {
    if( !wp_next_scheduled( 'cidg_strikeforce_discrepency_cron_job15' ) ) {  
        wp_schedule_event( time(), 'everyfifteenminute', 'cidg_strikeforce_discrepency_cron_job15' );  
    }
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activation');


// unschedule event upon plugin deactivation
function cronstarter_deactivate() {
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled ('cidg_strikeforce_discrepency_cron_job15');
    // unschedule previous event if any
wp_unschedule_event ($timestamp, 'cidg_strikeforce_discrepency_cron_job15');
}
register_deactivation_hook (__FILE__, 'cronstarter_deactivate');


// function cyberIdGuard_subscription_discrepency_fix_test()
// {
//     $args = array(
//         'numberposts' => 10,
//         'post_type'   => 'shop_subscription',
//         'post_status' => array('active', 'on-hold')
//       );
       
//       return $subscriptions = get_posts( $args );
// }

// print_r(cyberIdGuard_subscription_discrepency_fix_test());

add_action('cidg_strikeforce_discrepency_cron_job15', 'cyberIdGuard_subscription_discrepency_fix');

function cyberIdGuard_subscription_discrepency_fix()
{
    $time = time();
    
    // Get All Active and On Hold Subscriptions
    $get_subscriptions_args = array( 'subscriptions_per_page' => -1, 'subscription_status' => array('active', 'on-hold') );
    
    $subscriptions = wcs_get_subscriptions( $get_subscriptions_args );
    
    foreach ($subscriptions as $item_id => $subscription) 
    {
        foreach($subscription->get_items() as $item) {

            $orderNumber = "{$subscription->get_id()}-{$item['product_id']}-{$time}";
            
            $product = wc_get_product($item['product_id']);

            $product_id = $product->get_id();

            $is_Strikeforce = get_post_meta( $product_id, 'is_strikeforce', true );

            $prod_acct_fields = [
                'sellerID',
                'userID',
                'userPassword',
                'productId',
                'licenseType',
                'guardedIdUri',
                'mobileTrustUri',
            ];
            
            if($is_Strikeforce == 'yes')
            {
                for($i = 0; $i<count($prod_acct_fields); $i++)
                {
                    if(!empty($prod_acct_fields[$i]))
                    {
                        $account_info[$prod_acct_fields[$i]] = get_post_meta( $product_id, $prod_acct_fields[$i], true );
                    }
                }

                $guardedIdApi = get_guardedID_manager($account_info);

                $xmlObject = $guardedIdApi->getLicenseInfo($item['License']);

                $licenseArray = convertXml2Array($xmlObject);

                if(array_key_exists('GIDLicenseInfo', $licenseArray))
                {
                    $sfLicenseStatus = $licenseArray['GIDLicenseInfo']['GIDLicense']['LicenseStatus'];

                    //$invalidLicenseKey = "Strikeforce API reports: " . $sfLicenseStatus . ".";

                    $cbidLicenseStatus = $subscription->get_status();

                    switch($cbidLicenseStatus)
                    {
                        case 'on-hold':
                            if (!empty($subscription->get_id() && $sfLicenseStatus != 'Suspended')) 
                            {   
                                $subscriptionDiscrepencyUpdate = 'Detected a license status discrepancy. License status updated.';

                                $enableResponse = (array) $guardedIdApi->SuspendLicense($orderNumber, $item['License'], $subscriptionDiscrepencyUpdate);
                                
                                // If you don't have the WC_Order object (from a dynamic $order_id)
                                $order = wc_get_order(  $item_id );

                                // The text for the note
                                $note = __($subscriptionDiscrepencyUpdate);

                                // Add the note
                                $subscription->add_order_note( $note );

                                //$returnedValue = $subscription->set_status('on-hold'); //UPDATE API to cbidLicenseStatus
                                //$subscription->save();
                            }
                            
                        break;
                        case 'active':
                            if (!empty($subscription->get_id() && $sfLicenseStatus != 'Issued')) 
                            {   
                                $subscriptionDiscrepencyUpdate = 'Detected a license status discrepancy. License status updated.';

                                $enableResponse = (array) $guardedIdApi->enableLicense($orderNumber, $item['License'], 'Discrepancy Alert. Attempting to fix discrepancy.');

                                // If you don't have the WC_Order object (from a dynamic $order_id)
                                $order = wc_get_order(  $item['id'] );

                                // The text for the note
                                $note = __($subscriptionDiscrepencyUpdate);

                                // Add the note
                                $order->add_order_note( $note );

                                //$returnedValue = $subscription->set_status('active');
                                //$subscription->save();
                            }
            
                        break;
                        default:
                            if (!empty($subscription->get_id())) 
                            {   
                                $subscriptionDiscrepencyUpdate = ('There was a problem with this license.');
                                
                                // If you don't have the WC_Order object (from a dynamic $order_id)
                                $order = wc_get_order(  $item['id'] );

                                // The text for the note
                                $note = __($subscriptionDiscrepencyUpdate);

                                // Add the note
                                $order->add_order_note( $note );

                            }
                            elseif ($sfLicenseStatus != 'Issued') 
                            {   
                                $subscriptionDiscrepencyUpdate = ('Detected an issue: License is not issued.');
                                
                                // If you don't have the WC_Order object (from a dynamic $order_id)
                                $order = wc_get_order(  $item['id'] );

                                // The text for the note
                                $note = __($subscriptionDiscrepencyUpdate);

                                // Add the note
                                $order->add_order_note( $note );
                            }
                            else
                            {   
                                $subscriptionDiscrepencyUpdate = ('Detected an issue: unknown');
                                
                                // If you don't have the WC_Order object (from a dynamic $order_id)
                                $order = wc_get_order(  $item['id'] );

                                // The text for the note
                                $note = __($subscriptionDiscrepencyUpdate);

                                // Add the note
                                $order->add_order_note( $note );
                            }
                    }
                }
            }
            else{

                echo '<p class="form-field form-field-wide">';
                    
                echo '<label for="order_status">Strikeforce License status:</label>';
                
                echo '<input type="text" id="order_status" name="license_status" value="Not a Strikeforce product." disabled />';
                
                echo '</p>';
                
            }
        }
        $to = 'victor@creativeservices.io';
        $subject = 'ran 87 times';
        $body = 'After 3 attempts of trying to update status to {cbidLicenseStatus}, {orderNumber} is having problems synching with the Strikeforce License Server. Contact the administrator for assistance ';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail( $to, $subject, $body, $headers );
    }
}




function update_order_status($order_id, $status){

    if ( ! $order_id || ! $status)
        return;
    
    $order = new WC_Order($order_id);
    $order->update_status($status); 

}













/**
 * 
 * Add new column named License that will hold the license number of the subscription
 * 
 */
add_filter( 'manage_edit-shop_subscription_columns', function ( $existing_columns )
{
    $new_columns = array();

    foreach($existing_columns as $column_name => $columne_info)
    {
        $new_columns[ $column_name ] = $columne_info;

        if ( 'order_title' === $column_name )
        {
            $new_columns['order_license'] = __( 'License', 'textdomain' );
        }
    }

	return $new_columns;
 }, 1000);




/**
 * 
 * Populate the new License column with the license number
 * 
 */
add_action('manage_shop_subscription_posts_custom_column', function ( $column ) {
    
    global $post;
    
    if ( 'order_license' === $column ) {
        
        $subscription = new WC_Subscription($post->ID);
        
        foreach ($subscription->get_items() as $item_id => $item) {
            
            $license = $item['License'];
        
        }
        echo $license;
    }
});




add_action( 'parse_query','shop_license_search_custom_fields');
function shop_license_search_custom_fields( $wp ) {
    global $pagenow, $wpdb;    
    if ( 'edit.php' !== $pagenow || empty( $_GET['s'] ) || 'shop_subscription' !== $wp->query_vars['post_type'] ) {	
        /* Checking for the subscriptions edit page, if it isn’t then do nothing and exit from this function*/
        return;
    }
    
    $search = $_GET['s']; //getting the search phrases from the URL
    
    $post_status = array( $_GET['post_status'] );   
    
    $query = "  SELECT DISTINCT posts.ID AS product_id
                FROM {$wpdb->prefix}woocommerce_order_items as order_items
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
                ON order_items.order_item_id = order_item_meta.order_item_id
                LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
                LEFT JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
                WHERE posts.post_type = 'shop_subscription'
                AND posts.post_status IN ( '" . implode( "','", $post_status ) . "' )
                AND order_items.order_item_type = 'line_item'
                AND order_item_meta.meta_key = 'License'
                AND order_item_meta.meta_value LIKE '%" .$search. "%'
    ";
	/* custom query */
    
    $search_results = $wpdb->get_results( $query );
    
    $post_ids = wp_parse_id_list( array_merge( wp_list_pluck( $search_results, 'product_id' )) );  
    
    /* Executing the query and returning the result in the following code*/  
    if ( ! empty( $post_ids ) ) {
        unset( $wp->query_vars['s'] );
        $wp->query_vars['License'] = true;
        $wp->query_vars['post__in'] = $post_ids;
    }
}