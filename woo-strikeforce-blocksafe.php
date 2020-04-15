<?php
/*
    Plugin Name: Woo StrikeForce & BlockSafe API
    Plugin URI: http:/tecgent.com/plugins/woo-strikeforce-blocksafe-api
    Description: This plugin integrates Woo Subscriptions with the StrikeForce & BlockSafe API.
    Version: 2.0.2
    Author: tecgent
    Author URI: https://tecgent.com/
    License: Proprietary
*/

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

/*
 * Check if WooCommerce is active.
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    define('PLUGIN_PREFIX', 'sfbs');
    define('STRIKEFORCE', 'strikeforce');
    define('BLOCKSAFE', 'blocksafe');
    require_once('includes/sfbs-woo-admin-pannel.php');
    require_once('includes/sfbs-debug.php');
    require_once('includes/sfbs-guarded-api.php');
    require_once('includes/sfbs-settings-options.php');
    require_once('includes/sfbs-wc-action-hooks.php');
    require_once('includes/sfbs-license-management.php');
    require_once('includes/sfbs-actions.php');
    //require_once('includes/sfbs-shortcodes.php');
    require_once('includes/sfbs-base.php');
}