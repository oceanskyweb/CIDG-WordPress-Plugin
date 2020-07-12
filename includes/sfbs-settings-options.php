<?php
// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

/*
 * Clean the submitted options
 */
function clean_user_input($input){
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
        'productId' => array (
            'title' => 'Product ID',
            'description' => 'The Product ID.'
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

    for($i = 0; $i<2; $i++) {
        $name = ($i == 0) ? STRIKEFORCE : BLOCKSAFE;
        foreach ($options as $key => $value) {
            $input[$name][$key] = wp_filter_nohtml_kses($input[$name][$key]);
        }
    }

    return $input;
}

/*
 * Add menu item under Wordpress Settings
 */

add_action('admin_menu', function(){
    add_submenu_page(
        'options-general.php',
        __('Strikeforce & Blocksafe', PLUGIN_PREFIX),
        __('Strikeforce & Blocksafe', PLUGIN_PREFIX),
        'administrator',
        PLUGIN_PREFIX . '_settings_page',
        function(){
            ?>
            <div class="wrap">
                <?php //screen_icon(); ?>
                <form method="post" action="options.php">
                    <?php
                        settings_fields(PLUGIN_PREFIX . '_settings');
                        $data = get_option(PLUGIN_PREFIX);
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
                            'productId' => array (
                                'title' => 'Product ID',
                                'description' => 'The Product ID.'
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
                        for($i = 0; $i<2; $i++){
                            $name = ($i == 0) ? STRIKEFORCE : BLOCKSAFE; ?>
                            <h2><?php _e(ucfirst($name) . ' Settings', PLUGIN_PREFIX); ?></h2>
                            <p><?php _e('Please enter the '. ucfirst($name) . ' Account information below.', PLUGIN_PREFIX); ?></p>
                            <table class="form-table">
                                <tbody>
                                    <?php foreach($options as $key => $value){ ?>
                                        <tr valign="top">
                                            <th scope="row"><?php _e($value['title'], PLUGIN_PREFIX); ?></th>
                                            <td>
                                                <?php
                                                if (isset($data[$name][$key]))
                                                    $data[$name][$key] = wp_filter_nohtml_kses($data[$name][$key]);
                                                ?>
                                                <input
                                                        type="<?php echo isset($value['type']) ? $value['type'] : 'text'; ?>"
                                                        id="<?php echo $name . '_' . $key; ?>"
                                                        name="<?php echo PLUGIN_PREFIX; ?>[<?php echo $name; ?>][<?php echo $key; ?>]"
                                                        width="30"
                                                        value="<?php if(isset($data[$name][$key])) echo $data[$name][$key]; ?>"
                                                />
                                                <br />
                                                <label class="description" for="<?php echo PLUGIN_PREFIX; ?>[<?php echo $name; ?>][<?php echo $key; ?>]">
                                                    <?php _e($value['description'], PLUGIN_PREFIX); ?>
                                                </label>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <?php
                        }
                    ?>
                    <input type="submit" class="button-primary"
                           value="<?php _e('Save Options', PLUGIN_PREFIX); ?>" />
                    <input type="hidden" name="<?php echo PLUGIN_PREFIX; ?>-settings-submit" value="Y" />
                </form>
            </div>
            <?php
        }
    );
});

add_action('admin_init', function() {
    register_setting( PLUGIN_PREFIX . '_settings', PLUGIN_PREFIX, 'clean_user_input');
});


