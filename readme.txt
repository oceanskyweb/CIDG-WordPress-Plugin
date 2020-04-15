=== WooCommerce StrikeForce Integration ===
Contributors: binarynoir,jarvisuser90
Tags: cyberguard,strikeforce,guardedid,mobiletrust
Requires at least: 4.3.1
Tested up to: 4.3.1
Stable tag: 1.1.0
License: Proprietary

Testing Push Git.

This plugin provides integration for StrikeForce API into Woo Commerce Subscription products.

== Description ==

This plugin provides integration for StrikeForce API into Woo Commerce Subscription products.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-strikeforce` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->StrikeForce Account screen to configure the plugin

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Usage ==
To get the proper pack size for machine licensing you must add a custom attribute to your product(s):

    name: packSize
    value: 10

> Note that the value of 10 is an example. The value should match the pack size of the product.

You can add custom attributes by navigating to the products administration page as follows:

     products->product [edit]->Attributes->Add (Custom product attribute)


You can retrieve a license for a given product order item in a site template as follows:

    <?php echo wc_get_order_item_meta($item_id, 'License'); ?>

    or

    <?php do_action('action_item_license_key', $item_id); ?>

You can retrieve all licenses for an order in a site template as follows:

    <?php echo strikeforce_order_license_keys($order_id); ?>

You can retrieve a license for a given subscription_key in a site template as follows:

    <?php echo strikeforce_subscription_license_key($subscription_key); ?>

You can retrieve the full license information for a given subscription_key in a site template as follows:

    <?php echo strikeforce_subscription_license_info($subscription_key); ?>

You can deactivate a machine for a given order (and additional data) as follows:

    <?php strikeforce_deactivate_machine_function($orderNumber, $licenseKey, $reasonForChange, $machineID); ?>

== Email Templates ==
Email templates for grace period cancellation are located in the `/wp-content/plugins/woocommerce-strikeforce/email_templates/`
directory. The available templates are as follows:

* first-notice-suspenstion.html: This is the initial warning notice that the user has x amount of days to make successful payment or the license will expire.
* followup-notice-suspension.html: This is the template for all remaining warning emails for the remainder of the grace period.
* final-notice-suspension.html: This is the last warning notice prior to suspension. The user will have 12 hours to adjust payment before complete license suspension.
* suspension-notice.html: This is the notice that the license has been suspended because the grace period has expired.

The templates have a series of available shortcodes you can use to customize the experience. They are as follows:

* [subject]: This is the text that is entered into the subject line of the email.
* [first_name]: The first name used at time of purchase.
* [last_name]: the last name used at time of purchase.
* [subscription_id]: The ID of the expiring subscription.
* [order_id]: The order ID associated with the expiring subscription.
* [warning_number]: The warning number count which auto increments.
* [grace_period_number]: The length of the grace period.
* [remaining_grace_period_number]: The days remaining of the grace period.
* [subscription_url]: The full URL to the expiring subscription.
* [order_url]: The full URL to the associated order of the expiring subscription.
* [my_account_url]: The full URL to the users "My Account" page.
* [site_url]: The full URL of the website.
* [shop_url]: The full URL of the websites store.

All of the shortcodes can be used in any of the email templates, however it is good to keep in mind
that not all would make sense for each phase of the grace period.

== Machine Page Templates ==
The machine page templates allow you to display the provisioned machine information to any web page using a subscription_key.
The templates can be found in the `/wp-content/plugins/woocommerce-strikeforce/page_templates/` directory.

To insert the machine information into a webpage add the following to your website template:

    <?php do_action('action_list_machines', $subscription); ?>

The templates have a series of available shortcodes you can use to customize the experience. They are as follows:

* [subscription_key]: The ID/Key of the expiring subscription.
* [order_id]: The order ID associated with the expiring subscription.
* [license_key]:The license id/number.
* [activation_url]:The MobileTrust activation URL.
* [license_count]: The number of machines a license is provisioned for.
* [machines_activated_count]: The number of machines provisioned.
* [machine_id]: The ID of the provisioned machine.
* [activation_date]: The date the machine was activated/provisioned.

All of the shortcodes can be used in any of the machine page templates so you can build the section according to your needs.


== Changelog ==

= 1.0 =
* Initial 1.0 release.

== Upgrade Notice ==

= 1.0 =
* This is the first stable release. All users should upgrade.
