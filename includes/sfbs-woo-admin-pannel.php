<?php

add_action( 'woocommerce_product_write_panel_tabs', function() {
    echo '<li class="woo_api_tab hide_if_grouped"><a href="#' . PLUGIN_PREFIX . '_product_data">StrikeForce & BlockSafe Test</a></li>';
}, 99 );

add_action( 'woocommerce_product_data_panels', function() {
    global $post;
    ?>
    <div id="<?php echo PLUGIN_PREFIX; ?>_product_data" class="panel woocommerce_options_panel woocommerce_options_pane_<?php echo PLUGIN_PREFIX; ?>">
        <?php
        $settings = array(
            PLUGIN_PREFIX => (get_post_meta($post->ID, PLUGIN_PREFIX, true)) ? get_post_meta($post->ID, PLUGIN_PREFIX, true) : 'none',
        );
        echo '<div id="' . PLUGIN_PREFIX . '_product-subpanel" class="woo_api_product-subpanel">';
        echo '<h2>Srikeforce / BlockSafe / None</h2>';
        woocommerce_wp_radio( array(
            'id'            => PLUGIN_PREFIX,
            'value'         => $settings[PLUGIN_PREFIX],
            'class'         => 'radio ' . PLUGIN_PREFIX,
            'label'         => '',
            'options'       => array(
                STRIKEFORCE => 'Strike Force',
                BLOCKSAFE   => 'Block Safe',
                'none'      => 'None'
            )
        ) );
        echo '</div>';
        ?>
    </div>
    <style>
        .wc-radios li{
            height: 10px;
        }
    </style>
    <?php
});

add_action('woocommerce_process_product_meta', function( $post_id ) {
    update_post_meta( $post_id, PLUGIN_PREFIX, $_POST[PLUGIN_PREFIX]);
}, 10, 2);