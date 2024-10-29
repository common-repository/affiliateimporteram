<?php

if (!class_exists('AIDN_SettingsPage')) {

    class AIDN_SettingsPage
    {
        public function render()
        {
            $activePage = 'settings';
            include AIDN_ROOT_PATH . '/layout/toolbar.php';

            do_action('aidn_before_settings_page');

            if (isset($_POST['setting_form'])) {
                $current_api_module = (isset($_POST['module']) && $_POST['module']) ? sanitize_text_field($_POST['module']) : '';

                if ($current_api_module === 'common') {
                    update_option('aidn_currency_conversion_factor', isset($_POST['aidn_currency_conversion_factor']) ? (float)$_POST['aidn_currency_conversion_factor'] : 1);
                    update_option('aidn_per_page', isset($_POST['aidn_per_page']) ? $_POST['aidn_per_page'] : 1);
                    update_option('aidn_default_type', isset($_POST['aidn_default_type']) ? (int)$_POST['aidn_default_type'] : 1);
                    update_option('aidn_import_attributes', isset($_POST['aidn_import_attributes']));

                    update_option('aidn_remove_link_from_desc', isset($_POST['aidn_remove_link_from_desc']));
                    update_option('aidn_remove_img_from_desc', isset($_POST['aidn_remove_img_from_desc']));

                    update_option('aidn_import_product_images_limit', isset($_POST['aidn_import_product_images_limit']) ? sanitize_text_field($_POST['aidn_import_product_images_limit']) : '');

                    update_option('aidn_min_product_quantity', isset($_POST['aidn_min_product_quantity']) ? (int)$_POST['aidn_min_product_quantity'] : 5);
                    update_option('aidn_max_product_quantity', isset($_POST['aidn_max_product_quantity']) ? (int)$_POST['aidn_max_product_quantity'] : 10);

                    update_option('aidn_use_proxy', isset($_POST['aidn_use_proxy']));
                    update_option('aidn_proxies_list', isset($_POST['aidn_proxies_list']) ? sanitize_text_field($_POST['aidn_proxies_list']) : '');

                    if (isset($_POST['aidn_default_status'])) {
                        update_option('aidn_default_status', (int)$_POST['aidn_default_status']);
                    }


                    do_action('aidn_save_common_settings', $_POST);
                } else {
                    $api_account = aidn_get_account($current_api_module);
                    if ($api_account) {
                        $api_account->save(filter_input_array(INPUT_POST));
                    }
                    $api = aidn_get_api($current_api_module);
                    if ($api) {
                        $api->saveSetting(filter_input_array(INPUT_POST));
                        do_action('aidn_save_module_settings', $api, filter_input_array(INPUT_POST));
                    }
                }
            } else if (isset($_POST['shedule_settings'])) {
                $postData = filter_input_array(INPUT_POST);
                if (array_key_exists('shedule_settings', $postData)) {
                    update_option('aidn_price_auto_update', isset($postData['aidn_price_auto_update']));
                }
                update_option('aidn_regular_price_auto_update', isset($postData['aidn_regular_price_auto_update']));

                if (isset($postData['aidn_not_available_product_status'])) {
                    update_option('aidn_not_available_product_status', sanitize_text_field($postData['aidn_not_available_product_status']));
                } else {
                    update_option('aidn_not_available_product_status', 'trash');
                }

                if (isset($postData['aidn_price_auto_update_period'])) {
                    update_option('aidn_price_auto_update_period', sanitize_text_field($postData['aidn_price_auto_update_period']));
                }

                if (isset($postData['aidn_update_per_schedule'])) {
                    update_option('aidn_update_per_schedule', (int)$postData['aidn_update_per_schedule']);
                } else {
                    update_option('aidn_update_per_schedule', 20);
                }

                $price_auto_update = get_option('aidn_price_auto_update', false);
                if ($price_auto_update) {
                    wp_schedule_event(
                        time(),
                        get_option('aidn_price_auto_update_period', 'daily'),
                        'aidn_update_price_event'
                    );
                } else {
                    wp_clear_scheduled_hook('aidn_update_price_event');
                }
                do_action('aidn_save_common_settings', $_POST);
            } elseif (isset($_POST['language_settings'])) {
                update_option('aidn_tr_amazon_language', sanitize_text_field($_POST['aidn_tr_amazon_language']));

                update_option('aidn_tr_amazon_bing_secret', sanitize_text_field($_POST['aidn_tr_amazon_bing_secret']));

                update_option('aidn_tr_amazon_bing_client_id', sanitize_text_field($_POST['aidn_tr_amazon_bing_client_id']));
            }


            include AIDN_ROOT_PATH . '/layout/settings.php';
        }
    }
}
