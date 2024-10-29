<?php
if (!class_exists('AIDN_WooCommerce_OrderList')) {

    class AIDN_WooCommerce_OrderList
    {

        public function __construct()
        {
            if (is_admin()) {
                add_action('admin_enqueue_scripts', [$this, 'assets']);
                add_action('manage_shop_order_posts_custom_column', [$this, 'columnsData'], 100);
            }
        }

        public function assets()
        {

            $plugin_data = get_plugin_data(AIDN_FILE_FULLNAME);
            wp_enqueue_style('aidn-wc-ol-style', plugins_url('assets/css/wc_ol_style.css', AIDN_FILE_FULLNAME), array(), $plugin_data['Version']);
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('aidn-wc-ol-script', plugins_url('assets/js/wc_ol_script.js', AIDN_FILE_FULLNAME), array(), $plugin_data['Version']);
        }

        public function columnsData($column)
        {
            global $post;

            $actions = array();

            if ($column === 'order_title') {
                $actions = array_merge($actions, array(
                    'aidn_product_info' => sprintf('<a class="aidn-order-info" id="aidn-%1$d" href="/">%2$s</a>', $post->ID, 'AffiliateImporterAm Info')
                ));

            }

            $actions = apply_filters('aidn_wcol_row_actions', $actions, $column);

            if (count($actions) > 0) {
                echo implode($actions, ' | ');
            }

        }
    }
}
new AIDN_WooCommerce_OrderList();